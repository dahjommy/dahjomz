#!/bin/bash

# Setup Cron Job for Cache Sync
# This script sets up automatic sync every 30 seconds

echo "Setting up automatic cache sync..."

# Get the current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Create cron job that runs every minute (cron doesn't support seconds)
# We'll use a workaround to run twice per minute
CRON_JOB="* * * * * /usr/bin/php $SCRIPT_DIR/sync.php >/dev/null 2>&1"
CRON_JOB2="* * * * * sleep 30; /usr/bin/php $SCRIPT_DIR/sync.php >/dev/null 2>&1"

# Check if cron job already exists
(crontab -l 2>/dev/null | grep -q "$SCRIPT_DIR/sync.php") && {
    echo "Cron job already exists. Removing old entries..."
    (crontab -l 2>/dev/null | grep -v "$SCRIPT_DIR/sync.php") | crontab -
}

# Add new cron jobs
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
(crontab -l 2>/dev/null; echo "$CRON_JOB2") | crontab -

echo "Cron jobs added successfully!"
echo "Cache will sync every 30 seconds."

# Alternative: Run as daemon
echo ""
echo "Alternatively, you can run the sync as a daemon:"
echo "nohup php $SCRIPT_DIR/sync.php --daemon > sync.out 2>&1 &"
echo ""

# Create systemd service file (optional)
if [ -d "/etc/systemd/system" ]; then
    echo "Creating systemd service..."
    
    cat > /tmp/mri-scripts-sync.service <<EOF
[Unit]
Description=MRI Scripts Cache Sync Service
After=network.target

[Service]
Type=simple
User=$USER
WorkingDirectory=$SCRIPT_DIR
ExecStart=/usr/bin/php $SCRIPT_DIR/sync.php --daemon
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    echo "Systemd service file created at /tmp/mri-scripts-sync.service"
    echo "To install: sudo cp /tmp/mri-scripts-sync.service /etc/systemd/system/"
    echo "To enable: sudo systemctl enable mri-scripts-sync"
    echo "To start: sudo systemctl start mri-scripts-sync"
fi

echo ""
echo "Setup complete!"