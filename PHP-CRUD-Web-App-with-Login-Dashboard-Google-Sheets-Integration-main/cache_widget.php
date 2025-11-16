<!-- Cache Status Widget -->
<div class="cache-widget" id="cacheWidget">
    <div class="cache-indicator">
        <i class="bi bi-speedometer2"></i>
        <span class="cache-status">
            <span class="status-dot" id="cacheStatusDot"></span>
            <span id="cacheStatusText">Checking...</span>
        </span>
    </div>
    <div class="cache-details" id="cacheDetails" style="display: none;">
        <div class="cache-stat">
            <span>Last Sync:</span>
            <span id="lastSync">-</span>
        </div>
        <div class="cache-stat">
            <span>Cache Age:</span>
            <span id="cacheAge">-</span>
        </div>
        <div class="cache-stat">
            <span>Records:</span>
            <span id="cacheRecords">-</span>
        </div>
        <button class="btn-sync" onclick="forceSync()">
            <i class="bi bi-arrow-clockwise"></i> Sync Now
        </button>
    </div>
</div>

<style>
.cache-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 1rem;
    min-width: 200px;
    z-index: 1000;
    transition: all 0.3s ease;
}

.cache-widget:hover {
    background: rgba(255, 255, 255, 0.08);
}

.cache-widget:hover .cache-details {
    display: block !important;
    margin-top: 1rem;
}

.cache-indicator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
}

.cache-indicator i {
    font-size: 1.5rem;
    color: #667eea;
}

.cache-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ffd93d;
    animation: pulse 2s infinite;
}

.status-dot.active {
    background: #2ed573;
}

.status-dot.syncing {
    background: #667eea;
    animation: pulse 1s infinite;
}

.status-dot.error {
    background: #f5365c;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.cache-details {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 0.75rem;
}

.cache-stat {
    display: flex;
    justify-content: space-between;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.cache-stat span:first-child {
    color: rgba(255, 255, 255, 0.6);
}

.btn-sync {
    width: 100%;
    padding: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.75rem;
}

.btn-sync:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-sync:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-sync i {
    margin-right: 0.5rem;
}

@media (max-width: 768px) {
    .cache-widget {
        bottom: 10px;
        right: 10px;
        min-width: 150px;
        padding: 0.75rem;
    }
    
    .cache-indicator i {
        font-size: 1.2rem;
    }
}
</style>

<script>
let syncInterval;
let isSyncing = false;

// Update cache status
function updateCacheStatus() {
    fetch('sync_ajax.php?action=status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                const statusDot = document.getElementById('cacheStatusDot');
                const statusText = document.getElementById('cacheStatusText');
                const lastSync = document.getElementById('lastSync');
                const cacheAge = document.getElementById('cacheAge');
                const cacheRecords = document.getElementById('cacheRecords');
                
                // Update status indicator
                if (stats.sync_status === 'syncing') {
                    statusDot.className = 'status-dot syncing';
                    statusText.textContent = 'Syncing...';
                } else if (stats.cache_age && stats.cache_age < 60) {
                    statusDot.className = 'status-dot active';
                    statusText.textContent = 'Cache Active';
                } else if (stats.cache_age && stats.cache_age < 120) {
                    statusDot.className = 'status-dot';
                    statusText.textContent = 'Cache OK';
                } else {
                    statusDot.className = 'status-dot error';
                    statusText.textContent = 'Cache Stale';
                }
                
                // Update details
                if (stats.last_sync) {
                    const syncTime = new Date(stats.last_sync);
                    lastSync.textContent = syncTime.toLocaleTimeString();
                }
                
                if (stats.cache_age) {
                    const seconds = stats.cache_age;
                    if (seconds < 60) {
                        cacheAge.textContent = seconds + 's ago';
                    } else {
                        cacheAge.textContent = Math.floor(seconds / 60) + 'm ago';
                    }
                }
                
                const totalRecords = (stats.users_count || 0) + 
                                   (stats.employees_count || 0);
                cacheRecords.textContent = totalRecords;
            }
        })
        .catch(error => {
            console.error('Cache status error:', error);
            document.getElementById('cacheStatusDot').className = 'status-dot error';
            document.getElementById('cacheStatusText').textContent = 'Error';
        });
}

// Force sync
function forceSync() {
    if (isSyncing) return;
    
    isSyncing = true;
    const btn = event.target.closest('.btn-sync');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Syncing...';
    
    fetch('sync_ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=sync'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const statusText = document.getElementById('cacheStatusText');
            statusText.textContent = 'Sync Complete!';
            
            // Update status immediately
            updateCacheStatus();
        }
    })
    .catch(error => {
        console.error('Sync error:', error);
    })
    .finally(() => {
        isSyncing = false;
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Sync Now';
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Initial status check
    updateCacheStatus();
    
    // Update status every 10 seconds
    syncInterval = setInterval(updateCacheStatus, 10000);
});

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (syncInterval) {
        clearInterval(syncInterval);
    }
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.spin {
    animation: spin 1s linear infinite;
    display: inline-block;
}
</style>