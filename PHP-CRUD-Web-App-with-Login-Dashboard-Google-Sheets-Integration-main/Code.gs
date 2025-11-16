// Google Apps Script Code for Google Sheets Backend
// Deploy this as Web App with Execute as: Me and Access: Anyone

// Configuration
const USERS_SHEET = 'Users';
const EMPLOYEES_SHEET = 'Employees';
const USER_HEADERS = ['ID', 'Username', 'Password', 'Email', 'Full Name', 'Role', 'Status', 'Created At', 'Last Login'];
const EMPLOYEE_HEADERS = ['ID', 'Employee Code', 'First Name', 'Last Name', 'Email', 'Phone', 'Department', 'Position', 'Date of Birth', 'Date of Joining', 'Salary', 'Address', 'City', 'Country', 'Status', 'Created At', 'Updated At'];

// Main entry point for Web App
function doPost(e) {
  try {
    const params = JSON.parse(e.postData.contents);
    const action = params.action;
    
    switch(action) {
      case 'setup':
        return handleSetup();
      case 'login':
        return handleLogin(params);
      case 'register':
        return handleRegister(params);
      case 'createUser':
        return handleCreateUser(params);
      case 'getUser':
        return handleGetUser(params);
      case 'updateUser':
        return handleUpdateUser(params);
      case 'getAllUsers':
        return handleGetAllUsers();
      case 'deleteUser':
        return handleDeleteUser(params);
      // Employee operations
      case 'createEmployee':
        return handleCreateEmployee(params);
      case 'getEmployee':
        return handleGetEmployee(params);
      case 'updateEmployee':
        return handleUpdateEmployee(params);
      case 'deleteEmployee':
        return handleDeleteEmployee(params);
      case 'getAllEmployees':
        return handleGetAllEmployees();
      case 'syncFromCSV':
        return handleSyncFromCSV(params);
      default:
        return createResponse(false, 'Invalid action', null);
    }
  } catch (error) {
    return createResponse(false, error.toString(), null);
  }
}

// Also handle GET requests
function doGet(e) {
  const action = e.parameter.action;
  
  if (action === 'test') {
    return ContentService.createTextOutput(JSON.stringify({
      success: true,
      message: 'Google Apps Script is working!',
      timestamp: new Date().toISOString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
  
  return ContentService.createTextOutput(JSON.stringify({
    success: false,
    message: 'Use POST method for data operations'
  })).setMimeType(ContentService.MimeType.JSON);
}

// Initialize spreadsheet with headers
function handleSetup() {
  try {
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    
    // Setup Users sheet
    let userSheet = ss.getSheetByName(USERS_SHEET);
    if (!userSheet) {
      userSheet = ss.insertSheet(USERS_SHEET);
    }
    userSheet.clear();
    userSheet.getRange(1, 1, 1, USER_HEADERS.length).setValues([USER_HEADERS]);
    
    // Format user headers
    const userHeaderRange = userSheet.getRange(1, 1, 1, USER_HEADERS.length);
    userHeaderRange.setBackground('#4285F4');
    userHeaderRange.setFontColor('#FFFFFF');
    userHeaderRange.setFontWeight('bold');
    
    // Set user column widths
    userSheet.setColumnWidth(1, 50);   // ID
    userSheet.setColumnWidth(2, 150);  // Username
    userSheet.setColumnWidth(3, 200);  // Password (hashed)
    userSheet.setColumnWidth(4, 200);  // Email
    userSheet.setColumnWidth(5, 200);  // Full Name
    userSheet.setColumnWidth(6, 100);  // Role
    userSheet.setColumnWidth(7, 100);  // Status
    userSheet.setColumnWidth(8, 150);  // Created At
    userSheet.setColumnWidth(9, 150);  // Last Login
    
    // Add default admin user
    const adminData = [
      '1',
      'admin',
      hashPassword('admin123'),
      'admin@example.com',
      'Administrator',
      'admin',
      'active',
      new Date().toISOString(),
      ''
    ];
    userSheet.getRange(2, 1, 1, adminData.length).setValues([adminData]);
    
    // Setup Employees sheet
    let empSheet = ss.getSheetByName(EMPLOYEES_SHEET);
    if (!empSheet) {
      empSheet = ss.insertSheet(EMPLOYEES_SHEET);
    }
    empSheet.clear();
    empSheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length).setValues([EMPLOYEE_HEADERS]);
    
    // Format employee headers
    const empHeaderRange = empSheet.getRange(1, 1, 1, EMPLOYEE_HEADERS.length);
    empHeaderRange.setBackground('#28a745');
    empHeaderRange.setFontColor('#FFFFFF');
    empHeaderRange.setFontWeight('bold');
    
    // Set employee column widths
    for (let i = 1; i <= EMPLOYEE_HEADERS.length; i++) {
      empSheet.setColumnWidth(i, 120);
    }
    
    return createResponse(true, 'Setup completed successfully', {
      userHeaders: USER_HEADERS,
      employeeHeaders: EMPLOYEE_HEADERS,
      defaultUser: 'admin',
      defaultPassword: 'admin123'
    });
  } catch (error) {
    return createResponse(false, 'Setup failed: ' + error.toString(), null);
  }
}

// Handle user login
function handleLogin(params) {
  try {
    const username = params.username;
    const password = params.password;
    
    if (!username || !password) {
      return createResponse(false, 'Username and password are required', null);
    }
    
    const sheet = getUserSheet();
    const data = sheet.getDataRange().getValues();
    
    for (let i = 1; i < data.length; i++) {
      if (data[i][1] === username) { // Username column
        const storedPassword = data[i][2]; // Password column
        const status = data[i][6]; // Status column
        
        if (status !== 'active') {
          return createResponse(false, 'Account is inactive', null);
        }
        
        if (verifyPassword(password, storedPassword)) {
          // Update last login
          sheet.getRange(i + 1, 9).setValue(new Date().toISOString());
          
          return createResponse(true, 'Login successful', {
            id: data[i][0],
            username: data[i][1],
            email: data[i][3],
            fullName: data[i][4],
            role: data[i][5]
          });
        } else {
          return createResponse(false, 'Invalid password', null);
        }
      }
    }
    
    return createResponse(false, 'User not found', null);
  } catch (error) {
    return createResponse(false, 'Login failed: ' + error.toString(), null);
  }
}

// Handle user registration
function handleRegister(params) {
  try {
    const username = params.username;
    const password = params.password;
    const email = params.email;
    const fullName = params.fullName || '';
    const role = params.role || 'user';
    
    if (!username || !password || !email) {
      return createResponse(false, 'Username, password, and email are required', null);
    }
    
    const sheet = getUserSheet();
    const data = sheet.getDataRange().getValues();
    
    // Check if username already exists
    for (let i = 1; i < data.length; i++) {
      if (data[i][1] === username) {
        return createResponse(false, 'Username already exists', null);
      }
      if (data[i][3] === email) {
        return createResponse(false, 'Email already registered', null);
      }
    }
    
    // Generate new ID
    const lastRow = sheet.getLastRow();
    const newId = lastRow > 1 ? String(parseInt(data[lastRow - 1][0]) + 1) : '1';
    
    // Add new user
    const newUser = [
      newId,
      username,
      hashPassword(password),
      email,
      fullName,
      role,
      'active',
      new Date().toISOString(),
      ''
    ];
    
    sheet.appendRow(newUser);
    
    return createResponse(true, 'Registration successful', {
      id: newUser[0],
      username: newUser[1],
      email: newUser[3]
    });
  } catch (error) {
    return createResponse(false, 'Registration failed: ' + error.toString(), null);
  }
}

// Create new user (for admin)
function handleCreateUser(params) {
  try {
    const username = params.username;
    const password = params.password;
    const email = params.email;
    const fullName = params.fullName || '';
    const role = params.role || 'user';
    const status = params.status || 'active';
    
    if (!username || !password || !email) {
      return createResponse(false, 'Username, password, and email are required', null);
    }
    
    const sheet = getUserSheet();
    const data = sheet.getDataRange().getValues();
    
    // Check if username already exists
    for (let i = 1; i < data.length; i++) {
      if (data[i][1] === username) {
        return createResponse(false, 'Username already exists', null);
      }
      if (data[i][3] === email) {
        return createResponse(false, 'Email already registered', null);
      }
    }
    
    // Generate new ID
    const lastRow = sheet.getLastRow();
    const newId = lastRow > 1 ? String(parseInt(data[lastRow - 1][0]) + 1) : '1';
    
    // Add new user
    const newUser = [
      newId,
      username,
      hashPassword(password),
      email,
      fullName,
      role,
      status,
      new Date().toISOString(),
      ''
    ];
    
    sheet.appendRow(newUser);
    
    return createResponse(true, 'User created successfully', {
      id: newUser[0],
      username: newUser[1],
      email: newUser[3],
      fullName: newUser[4],
      role: newUser[5],
      status: newUser[6]
    });
  } catch (error) {
    return createResponse(false, 'Failed to create user: ' + error.toString(), null);
  }
}

// Get user details
function handleGetUser(params) {
  try {
    const userId = params.userId;
    
    if (!userId) {
      return createResponse(false, 'User ID is required', null);
    }
    
    const sheet = getUserSheet();
    const data = sheet.getDataRange().getValues();
    
    for (let i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(userId)) {
        return createResponse(true, 'User found', {
          id: data[i][0],
          username: data[i][1],
          email: data[i][3],
          fullName: data[i][4],
          role: data[i][5],
          status: data[i][6],
          createdAt: data[i][7],
          lastLogin: data[i][8]
        });
      }
    }
    
    return createResponse(false, 'User not found', null);
  } catch (error) {
    return createResponse(false, 'Failed to get user: ' + error.toString(), null);
  }
}

// Update user details
function handleUpdateUser(params) {
  try {
    const userId = params.userId;
    const updates = params.updates;
    
    if (!userId || !updates) {
      return createResponse(false, 'User ID and updates are required', null);
    }
    
    const sheet = getUserSheet();
    const data = sheet.getDataRange().getValues();
    
    for (let i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(userId)) {
        // Update allowed fields
        if (updates.email) sheet.getRange(i + 1, 4).setValue(updates.email);
        if (updates.fullName) sheet.getRange(i + 1, 5).setValue(updates.fullName);
        if (updates.role) sheet.getRange(i + 1, 6).setValue(updates.role);
        if (updates.status) sheet.getRange(i + 1, 7).setValue(updates.status);
        if (updates.password) sheet.getRange(i + 1, 3).setValue(hashPassword(updates.password));
        
        return createResponse(true, 'User updated successfully', { userId: userId });
      }
    }
    
    return createResponse(false, 'User not found', null);
  } catch (error) {
    return createResponse(false, 'Update failed: ' + error.toString(), null);
  }
}

// Get all users
function handleGetAllUsers() {
  try {
    const sheet = getUserSheet();
    const data = sheet.getDataRange().getValues();
    const users = [];
    
    for (let i = 1; i < data.length; i++) {
      users.push({
        id: data[i][0],
        username: data[i][1],
        email: data[i][3],
        fullName: data[i][4],
        role: data[i][5],
        status: data[i][6],
        createdAt: data[i][7],
        lastLogin: data[i][8]
      });
    }
    
    return createResponse(true, 'Users retrieved successfully', users);
  } catch (error) {
    return createResponse(false, 'Failed to get users: ' + error.toString(), null);
  }
}

// Delete user
function handleDeleteUser(params) {
  try {
    const userId = params.userId;
    
    if (!userId) {
      return createResponse(false, 'User ID is required', null);
    }
    
    const sheet = getUserSheet();
    const data = sheet.getDataRange().getValues();
    
    for (let i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(userId)) {
        sheet.deleteRow(i + 1);
        return createResponse(true, 'User deleted successfully', { userId: userId });
      }
    }
    
    return createResponse(false, 'User not found', null);
  } catch (error) {
    return createResponse(false, 'Delete failed: ' + error.toString(), null);
  }
}

// Employee CRUD Operations

function handleCreateEmployee(params) {
  try {
    const sheet = getEmployeeSheet();
    const lastRow = sheet.getLastRow();
    const newId = lastRow > 1 ? String(parseInt(sheet.getRange(lastRow, 1).getValue()) + 1) : '1';
    
    const employeeData = [
      newId,
      params.employeeCode || 'EMP' + newId.padStart(4, '0'),
      params.firstName || '',
      params.lastName || '',
      params.email || '',
      params.phone || '',
      params.department || '',
      params.position || '',
      params.dateOfBirth || '',
      params.dateOfJoining || new Date().toISOString().split('T')[0],
      params.salary || '',
      params.address || '',
      params.city || '',
      params.country || '',
      params.status || 'active',
      new Date().toISOString(),
      new Date().toISOString()
    ];
    
    sheet.appendRow(employeeData);
    
    return createResponse(true, 'Employee created successfully', {
      id: newId,
      employeeCode: employeeData[1]
    });
  } catch (error) {
    return createResponse(false, 'Failed to create employee: ' + error.toString(), null);
  }
}

function handleGetEmployee(params) {
  try {
    const employeeId = params.employeeId;
    if (!employeeId) {
      return createResponse(false, 'Employee ID is required', null);
    }
    
    const sheet = getEmployeeSheet();
    const data = sheet.getDataRange().getValues();
    
    for (let i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(employeeId)) {
        return createResponse(true, 'Employee found', {
          id: data[i][0],
          employeeCode: data[i][1],
          firstName: data[i][2],
          lastName: data[i][3],
          email: data[i][4],
          phone: data[i][5],
          department: data[i][6],
          position: data[i][7],
          dateOfBirth: data[i][8],
          dateOfJoining: data[i][9],
          salary: data[i][10],
          address: data[i][11],
          city: data[i][12],
          country: data[i][13],
          status: data[i][14],
          createdAt: data[i][15],
          updatedAt: data[i][16]
        });
      }
    }
    
    return createResponse(false, 'Employee not found', null);
  } catch (error) {
    return createResponse(false, 'Failed to get employee: ' + error.toString(), null);
  }
}

function handleUpdateEmployee(params) {
  try {
    const employeeId = params.employeeId;
    const updates = params.updates;
    
    if (!employeeId || !updates) {
      return createResponse(false, 'Employee ID and updates are required', null);
    }
    
    const sheet = getEmployeeSheet();
    const data = sheet.getDataRange().getValues();
    
    for (let i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(employeeId)) {
        // Update fields
        if (updates.employeeCode) sheet.getRange(i + 1, 2).setValue(updates.employeeCode);
        if (updates.firstName) sheet.getRange(i + 1, 3).setValue(updates.firstName);
        if (updates.lastName) sheet.getRange(i + 1, 4).setValue(updates.lastName);
        if (updates.email) sheet.getRange(i + 1, 5).setValue(updates.email);
        if (updates.phone) sheet.getRange(i + 1, 6).setValue(updates.phone);
        if (updates.department) sheet.getRange(i + 1, 7).setValue(updates.department);
        if (updates.position) sheet.getRange(i + 1, 8).setValue(updates.position);
        if (updates.dateOfBirth) sheet.getRange(i + 1, 9).setValue(updates.dateOfBirth);
        if (updates.dateOfJoining) sheet.getRange(i + 1, 10).setValue(updates.dateOfJoining);
        if (updates.salary) sheet.getRange(i + 1, 11).setValue(updates.salary);
        if (updates.address) sheet.getRange(i + 1, 12).setValue(updates.address);
        if (updates.city) sheet.getRange(i + 1, 13).setValue(updates.city);
        if (updates.country) sheet.getRange(i + 1, 14).setValue(updates.country);
        if (updates.status) sheet.getRange(i + 1, 15).setValue(updates.status);
        sheet.getRange(i + 1, 17).setValue(new Date().toISOString());
        
        return createResponse(true, 'Employee updated successfully', { employeeId: employeeId });
      }
    }
    
    return createResponse(false, 'Employee not found', null);
  } catch (error) {
    return createResponse(false, 'Update failed: ' + error.toString(), null);
  }
}

function handleDeleteEmployee(params) {
  try {
    const employeeId = params.employeeId;
    
    if (!employeeId) {
      return createResponse(false, 'Employee ID is required', null);
    }
    
    const sheet = getEmployeeSheet();
    const data = sheet.getDataRange().getValues();
    
    for (let i = 1; i < data.length; i++) {
      if (String(data[i][0]) === String(employeeId)) {
        sheet.deleteRow(i + 1);
        return createResponse(true, 'Employee deleted successfully', { employeeId: employeeId });
      }
    }
    
    return createResponse(false, 'Employee not found', null);
  } catch (error) {
    return createResponse(false, 'Delete failed: ' + error.toString(), null);
  }
}

function handleGetAllEmployees() {
  try {
    const sheet = getEmployeeSheet();
    const data = sheet.getDataRange().getValues();
    const employees = [];
    
    for (let i = 1; i < data.length; i++) {
      employees.push({
        id: data[i][0],
        employeeCode: data[i][1],
        firstName: data[i][2],
        lastName: data[i][3],
        email: data[i][4],
        phone: data[i][5],
        department: data[i][6],
        position: data[i][7],
        dateOfBirth: data[i][8],
        dateOfJoining: data[i][9],
        salary: data[i][10],
        address: data[i][11],
        city: data[i][12],
        country: data[i][13],
        status: data[i][14],
        createdAt: data[i][15],
        updatedAt: data[i][16]
      });
    }
    
    return createResponse(true, 'Employees retrieved successfully', employees);
  } catch (error) {
    return createResponse(false, 'Failed to get employees: ' + error.toString(), null);
  }
}

// Helper Functions

function getUserSheet() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = ss.getSheetByName(USERS_SHEET);
  
  if (!sheet) {
    throw new Error('Users sheet not found. Please run setup first.');
  }
  
  return sheet;
}

function getEmployeeSheet() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = ss.getSheetByName(EMPLOYEES_SHEET);
  
  if (!sheet) {
    throw new Error('Employees sheet not found. Please run setup first.');
  }
  
  return sheet;
}

function hashPassword(password) {
  // Simple hashing (in production, use proper hashing)
  const hash = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, password);
  return Utilities.base64Encode(hash);
}

function verifyPassword(password, hashedPassword) {
  return hashPassword(password) === hashedPassword;
}

function createResponse(success, message, data) {
  return ContentService.createTextOutput(JSON.stringify({
    success: success,
    message: message,
    data: data,
    timestamp: new Date().toISOString()
  })).setMimeType(ContentService.MimeType.JSON);
}

// CSV Sync Handler
function handleSyncFromCSV(params) {
  try {
    const users = params.users || [];
    const employees = params.employees || [];
    const timestamp = params.timestamp || new Date().toISOString();
    
    // Sync Users
    if (users.length > 0) {
      const userSheet = getUserSheet();
      // Clear existing data (keep headers)
      const lastRow = userSheet.getLastRow();
      if (lastRow > 1) {
        userSheet.deleteRows(2, lastRow - 1);
      }
      
      // Add new data
      const userData = users.map(user => [
        user.id,
        user.username,
        user.password,
        user.email || '',
        user.fullName || '',
        user.role || 'user',
        user.createdAt || timestamp,
        user.updatedAt || timestamp
      ]);
      
      if (userData.length > 0) {
        userSheet.getRange(2, 1, userData.length, 8).setValues(userData);
      }
    }
    
    // Sync Employees
    if (employees.length > 0) {
      const empSheet = getEmployeeSheet();
      // Clear existing data (keep headers)
      const lastRow = empSheet.getLastRow();
      if (lastRow > 1) {
        empSheet.deleteRows(2, lastRow - 1);
      }
      
      // Add new data
      const empData = employees.map(emp => [
        emp.id,
        emp.employeeCode || '',
        emp.firstName || '',
        emp.lastName || '',
        emp.email || '',
        emp.phone || '',
        emp.department || '',
        emp.position || '',
        emp.dateOfBirth || '',
        emp.dateOfJoining || '',
        emp.salary || '',
        emp.address || '',
        emp.city || '',
        emp.country || '',
        emp.status || 'active',
        emp.createdAt || timestamp,
        emp.updatedAt || timestamp
      ]);
      
      if (empData.length > 0) {
        empSheet.getRange(2, 1, empData.length, 17).setValues(empData);
      }
    }
    
    // Log sync
    const logSheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('SyncLog');
    if (!logSheet) {
      const newLogSheet = SpreadsheetApp.getActiveSpreadsheet().insertSheet('SyncLog');
      newLogSheet.getRange(1, 1, 1, 4).setValues([['Timestamp', 'Users', 'Employees', 'Status']]);
    }
    
    const log = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('SyncLog');
    log.appendRow([timestamp, users.length, employees.length, 'Success']);
    
    return createResponse(true, 'Data synced successfully', {
      users_synced: users.length,
      employees_synced: employees.length,
      timestamp: timestamp
    });
    
  } catch (error) {
    return createResponse(false, 'Sync failed: ' + error.toString(), null);
  }
}

