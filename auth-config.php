<?php
// Set timezone to ensure consistent date calculations
date_default_timezone_set('Asia/Kolkata');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

// Session timeout (30 minutes)
 $session_timeout = 1800;

// Check session timeout
if (isset($_SESSION['login_time'])) {
    if (time() - $_SESSION['login_time'] > $session_timeout) {
        session_destroy();
        header('Location: login.php?error=session');
        exit;
    }
    $_SESSION['login_time'] = time();
}

/**
 * Archives leads that are older than 60 days.
 * Moves them from leads.json to inactive-leads.json.
 */
function archiveExpiredLeads() {
    $leadsFile = 'data/leads.json';
    $inactiveLeadsFile = 'data/inactive-leads.json';
    $validityPeriodDays = 60;

    $leads = [];
    $inactiveLeads = [];

    // Load active leads
    if (file_exists($leadsFile)) {
        $leads = json_decode(file_get_contents($leadsFile), true) ?: [];
    }

    // Load existing inactive leads
    if (file_exists($inactiveLeadsFile)) {
        $inactiveLeads = json_decode(file_get_contents($inactiveLeadsFile), true) ?: [];
    }
    
    $activeLeads = [];
    $now = time();
    $archivedCount = 0;

    foreach ($leads as $lead) {
        $createdAt = strtotime($lead['created_at']);
        $expiryTime = $createdAt + ($validityPeriodDays * 24 * 60 * 60);

        if ($now > $expiryTime) {
            // Lead is expired, move to inactive
            $lead['status'] = 'expired';
            $lead['expired_at'] = date('Y-m-d H:i:s');
            $inactiveLeads[] = $lead;
            $archivedCount++;
        } else {
            // Lead is still active
            $activeLeads[] = $lead;
        }
    }

    // If any leads were archived, save the updated files
    if ($archivedCount > 0) {
        // Ensure data directory exists
        if (!is_dir('data')) {
            mkdir('data', 0755, true);
        }
        
        file_put_contents($leadsFile, json_encode($activeLeads, JSON_PRETTY_PRINT));
        file_put_contents($inactiveLeadsFile, json_encode($inactiveLeads, JSON_PRETTY_PRINT));
    }
}

// Run the archival check on every authenticated page load
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    archiveExpiredLeads();
}

// Initialize users file if it doesn't exist
 $usersFile = 'data/users.json';
if (!file_exists($usersFile)) {
    $defaultUsers = [
        [
            'id' => 'USR001',
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'name' => 'Administrator',
            'email' => 'admin@realestate.com',
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ],
        [
            'id' => 'USR002',
            'username' => 'manager',
            'password' => password_hash('manager123', PASSWORD_BCRYPT),
            'name' => 'Sales Manager',
            'email' => 'manager@realestate.com',
            'role' => 'manager',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ],
        [
            'id' => 'USR003',
            'username' => 'sales',
            'password' => password_hash('sales123', PASSWORD_BCRYPT),
            'name' => 'Sales Executive',
            'email' => 'sales@realestate.com',
            'role' => 'sales',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null
        ]
    ];
    
    if (!is_dir('data')) {
        mkdir('data', 0755, true);
    }
    
    file_put_contents($usersFile, json_encode($defaultUsers, JSON_PRETTY_PRINT));
}
?>