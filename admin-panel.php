<?php
session_start();
require_once 'auth-config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=session');
    exit;
}

// Load data
$leadsFile = 'data/leads.json';
$cpFile = 'data/channel_partners.json';
$usersFile = 'data/users.json';

$leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];
$channelPartners = file_exists($cpFile) ? json_decode(file_get_contents($cpFile), true) : [];
$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

// ✅ SORT LEADS BY DATE (Newest first)
usort($leads, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Function to generate lead ID
function generateLeadId($leads) {
    $datePrefix = 'L' . date('Ymd');
    $counter = 1;
    
    // Find existing leads with the same date prefix
    $existingIds = array_filter($leads, function($lead) use ($datePrefix) {
        return strpos($lead['id'], $datePrefix) === 0;
    });
    
    if (!empty($existingIds)) {
        // Extract the counter part from existing IDs
        $counters = [];
        foreach ($existingIds as $lead) {
            $counterPart = substr($lead['id'], 9); // Remove LYYYYMMDD prefix
            if (is_numeric($counterPart)) {
                $counters[] = intval($counterPart);
            }
        }
        
        if (!empty($counters)) {
            $counter = max($counters) + 1;
        }
    }
    
    return $datePrefix . str_pad($counter, 2, '0', STR_PAD_LEFT);
}

// Function to generate channel partner ID
function generateChannelPartnerId($channelPartners) {
    $datePrefix = 'CP' . date('Ymd');
    $counter = 1;
    
    // Find existing partners with the same date prefix
    $existingIds = array_filter($channelPartners, function($cp) use ($datePrefix) {
        return strpos($cp['id'], $datePrefix) === 0;
    });
    
    if (!empty($existingIds)) {
        // Extract the counter part from existing IDs
        $counters = [];
        foreach ($existingIds as $cp) {
            $counterPart = substr($cp['id'], 10); // Remove CPYYYYMMDD prefix
            if (is_numeric($counterPart)) {
                $counters[] = intval($counterPart);
            }
        }
        
        if (!empty($counters)) {
            $counter = max($counters) + 1;
        }
    }
    
    return $datePrefix . str_pad($counter, 2, '0', STR_PAD_LEFT);
}

// Handle form submissions
$message = '';
$error = '';

// Handle Lead Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_lead':
                $newLead = [
                    'id' => generateLeadId($leads),
                    'name' => $_POST['name'],
                    'phone' => '+91' . $_POST['phone'],
                    'email' => $_POST['email'],
                    'spouse_phone' => !empty($_POST['spouse_phone']) ? '+91' . $_POST['spouse_phone'] : '',
                    'project' => $_POST['project'],
                    'channel_partner_id' => $_POST['channel_partner_id'],
                    'remarks' => $_POST['remarks'],
                    'status' => $_POST['status'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Check for duplicate phone
                $duplicate = false;
                foreach ($leads as $lead) {
                    if ($lead['phone'] === $newLead['phone']) {
                        $duplicate = true;
                        break;
                    }
                }
                
                if ($duplicate) {
                    $error = 'A lead with this phone number already exists!';
                } else {
                    $leads[] = $newLead;
                    // Re-sort after adding
                    usort($leads, function($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });
                    file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
                    $message = 'Lead added successfully!';
                }
                break;
                
            case 'edit_lead':
                $leadId = $_POST['lead_id'];
                foreach ($leads as &$lead) {
                    if ($lead['id'] === $leadId) {
                        $lead['name'] = $_POST['name'];
                        $lead['phone'] = '+91' . $_POST['phone'];
                        $lead['email'] = $_POST['email'];
                        $lead['spouse_phone'] = !empty($_POST['spouse_phone']) ? '+91' . $_POST['spouse_phone'] : '';
                        $lead['project'] = $_POST['project'];
                        $lead['channel_partner_id'] = $_POST['channel_partner_id'];
                        $lead['remarks'] = $_POST['remarks'];
                        $lead['status'] = $_POST['status'];
                        if (isset($_POST['assigned_to'])) {
                            $lead['assigned_to'] = $_POST['assigned_to'];
                        }
                        $lead['updated_at'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                // Re-sort after editing
                usort($leads, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
                $message = 'Lead updated successfully!';
                break;
                
            case 'delete_lead':
                $leadId = $_POST['lead_id'];
                $leads = array_filter($leads, function($lead) use ($leadId) {
                    return $lead['id'] !== $leadId;
                });
                $leads = array_values($leads);
                file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
                $message = 'Lead deleted successfully!';
                break;
                
            case 'add_cp':
                $newCP = [
                    'id' => generateChannelPartnerId($channelPartners),
                    'firm_name' => $_POST['firm_name'],
                    'cp_name' => $_POST['cp_name'],
                    'mobile' => '+91' . $_POST['mobile'],
                    'email' => $_POST['email'],
                    'rera' => $_POST['rera'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Check for duplicate mobile
                $duplicate = false;
                foreach ($channelPartners as $cp) {
                    if ($cp['mobile'] === $newCP['mobile']) {
                        $duplicate = true;
                        break;
                    }
                }
                
                if ($duplicate) {
                    $error = 'A channel partner with this mobile number already exists!';
                } else {
                    $channelPartners[] = $newCP;
                    file_put_contents($cpFile, json_encode($channelPartners, JSON_PRETTY_PRINT));
                    $message = 'Channel Partner added successfully!';
                }
                break;
                
            case 'edit_cp':
                $cpId = $_POST['cp_id'];
                foreach ($channelPartners as &$cp) {
                    if ($cp['id'] === $cpId) {
                        $cp['firm_name'] = $_POST['firm_name'];
                        $cp['cp_name'] = $_POST['cp_name'];
                        $cp['mobile'] = '+91' . $_POST['mobile'];
                        $cp['email'] = $_POST['email'];
                        $cp['rera'] = $_POST['rera'];
                        $cp['updated_at'] = date('Y-m-d H:i:s');
                        break;
                    }
                }
                file_put_contents($cpFile, json_encode($channelPartners, JSON_PRETTY_PRINT));
                $message = 'Channel Partner updated successfully!';
                break;
                
            case 'delete_cp':
                $cpId = $_POST['cp_id'];
                // Check if CP has associated leads
                $hasLeads = false;
                foreach ($leads as $lead) {
                    if ($lead['channel_partner_id'] === $cpId) {
                        $hasLeads = true;
                        break;
                    }
                }
                
                if ($hasLeads) {
                    $error = 'Cannot delete channel partner. Associated leads found!';
                } else {
                    $channelPartners = array_filter($channelPartners, function($cp) use ($cpId) {
                        return $cp['id'] !== $cpId;
                    });
                    file_put_contents($cpFile, json_encode(array_values($channelPartners), JSON_PRETTY_PRINT));
                    $message = 'Channel Partner deleted successfully!';
                }
                break;
                
            // User Management Operations
            case 'add_user':
                $username = trim($_POST['username']);
                $password = $_POST['password'];
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                
                // Check if username already exists
                $usernameExists = false;
                foreach ($users as $user) {
                    if ($user['username'] === $username) {
                        $usernameExists = true;
                        break;
                    }
                }
                
                if ($usernameExists) {
                    $error = 'Username already exists!';
                } else {
                    $newUser = [
                        'id' => 'USR' . date('YmdHis') . rand(100, 999),
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_BCRYPT),
                        'name' => $name,
                        'email' => $email,
                        'role' => $role,
                        'created_at' => date('Y-m-d H:i:s'),
                        'last_login' => null
                    ];
                    
                    $users[] = $newUser;
                    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                    $message = 'User added successfully!';
                }
                break;
                
            case 'edit_user':
                $userId = $_POST['user_id'];
                foreach ($users as &$user) {
                    if ($user['id'] === $userId) {
                        $user['name'] = $_POST['name'];
                        $user['email'] = $_POST['email'];
                        $user['role'] = $_POST['role'];
                        
                        // Only update password if a new one is provided
                        if (!empty($_POST['password'])) {
                            $user['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
                        }
                        
                        break;
                    }
                }
                file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                $message = 'User updated successfully!';
                break;
                
            case 'delete_user':
                $userId = $_POST['user_id'];
                
                // Prevent deletion of current user
                if ($userId === $_SESSION['user_id']) {
                    $error = 'You cannot delete your own account!';
                } else {
                    $users = array_filter($users, function($user) use ($userId) {
                        return $user['id'] !== $userId;
                    });
                    file_put_contents($usersFile, json_encode(array_values($users), JSON_PRETTY_PRINT));
                    $message = 'User deleted successfully!';
                }
                break;
        }
    }
}

// Get unique projects for dropdown (includes new projects)
$defaultProjects = ['Platinum Landmark', 'Platinum Green Fields', 'Suraksha Sunrise Park', 'Urban Tranquil'];
$uniqueProjects = array_unique(array_merge($defaultProjects, array_column($leads, 'project')));
sort($uniqueProjects);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Real Estate Lead Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-button {
            position: relative;
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #3b82f6;
        }
        .sort-btn {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .sort-btn:hover {
            background-color: #e5e7eb;
        }
        .sort-icon {
            display: inline-block;
            margin-left: 5px;
            font-size: 10px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">Admin Panel - Real Estate CRM</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</span>
                    <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        Dashboard
                    </a>
                    <a href="manage-users.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                        Manage Users
                    </a>
                    <a href="manage-inactive-leads.php" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition duration-200">
                        Inactive Leads
                    </a>
                    <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-600"><?php echo $message; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-600"><?php echo $error; ?></p>
        </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="switchTab('leads')" class="tab-button active py-4 px-6 text-sm font-medium text-blue-600 border-b-2 border-blue-500 focus:outline-none">
                        Manage Leads
                    </button>
                    <button onclick="switchTab('partners')" class="tab-button py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none">
                        Manage Channel Partners
                    </button>
                    <button onclick="switchTab('users')" class="tab-button py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none">
                        Manage Users
                    </button>
                </nav>
            </div>
        </div>

        <!-- Leads Tab Content -->
        <div id="leads-tab" class="tab-content active">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Leads Management</h3>
                    <div class="flex gap-3">
                        <button onclick="sortTableByDate()" class="bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 transition duration-200">
                            Sort by Date ↓
                        </button>
                        <button onclick="openLeadModal()" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200">
                            Add New Lead
                        </button>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex gap-4">
                        <input type="text" id="leadSearch" placeholder="Search by name, phone, or email..." 
                               class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                        <select id="leadStatusFilter" class="border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            <option value="">All Status</option>
                            <option value="registered">Registered</option>
                            <option value="converted">Converted</option>
                        </select>
                        <select id="leadProjectFilter" class="border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            <option value="">All Projects</option>
                            <?php foreach($uniqueProjects as $project): ?>
                            <option value="<?php echo htmlspecialchars($project); ?>"><?php echo htmlspecialchars($project); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="leadsTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sort-btn" onclick="sortByColumn(0)">
                                    Lead ID <span class="sort-icon">↕</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sort-btn" onclick="sortByColumn(1)">
                                    Name <span class="sort-icon">↕</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sort-btn" onclick="sortByColumn(3)">
                                    Project <span class="sort-icon">↕</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channel Partner</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sort-btn" onclick="sortByColumn(6)">
                                    Status <span class="sort-icon">↕</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer sort-btn" onclick="sortByColumn(7)">
                                    Date <span class="sort-icon">↕</span>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="leadsTableBody">
                            <?php 
                            $salesTeam = ['shivu', 'bharath', 'ravi'];
                            foreach($leads as $lead): 
                                $cpName = 'N/A';
                                foreach($channelPartners as $cp) {
                                    if($cp['id'] == $lead['channel_partner_id']) {
                                        $cpName = $cp['firm_name'] . ' - ' . $cp['cp_name'];
                                        break;
                                    }
                                }
                            ?>
                            <tr class="lead-row" data-status="<?php echo $lead['status']; ?>" data-project="<?php echo htmlspecialchars($lead['project']); ?>" data-search="<?php echo strtolower($lead['name'] . ' ' . $lead['phone'] . ' ' . $lead['email']); ?>" data-date="<?php echo $lead['created_at']; ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" data-sort="<?php echo $lead['id']; ?>">
                                    <?php echo htmlspecialchars($lead['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-sort="<?php echo strtolower($lead['name']); ?>">
                                    <?php echo htmlspecialchars($lead['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($lead['phone']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-sort="<?php echo strtolower($lead['project']); ?>">
                                    <?php echo htmlspecialchars($lead['project']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($cpName); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select onchange="updateAssignment('<?php echo $lead['id']; ?>', this.value)" class="text-sm border border-gray-300 rounded px-2 py-1">
                                        <option value="">Not Assigned</option>
                                        <?php foreach($salesTeam as $person): ?>
                                        <option value="<?php echo $person; ?>" <?php echo (isset($lead['assigned_to']) && $lead['assigned_to'] === $person) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($person); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap" data-sort="<?php echo $lead['status']; ?>">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $lead['status'] === 'converted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($lead['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" data-sort="<?php echo $lead['created_at']; ?>">
                                    <?php echo date('d M Y', strtotime($lead['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editLead('<?php echo $lead['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <button onclick="deleteLead('<?php echo $lead['id']; ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Channel Partners Tab Content -->
        <div id="partners-tab" class="tab-content">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Channel Partners Management</h3>
                    <button onclick="openCPModal()" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200">
                        Add New Channel Partner
                    </button>
                </div>

                <!-- Search -->
                <div class="p-6 border-b border-gray-200">
                    <input type="text" id="cpSearch" placeholder="Search by firm name, CP name, or mobile..." 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>

                <!-- Partners Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="cpTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CP ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Firm Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CP Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mobile</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RERA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($channelPartners as $cp): ?>
                            <tr class="cp-row" data-search="<?php echo strtolower($cp['firm_name'] . ' ' . $cp['cp_name'] . ' ' . $cp['mobile']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($cp['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($cp['firm_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($cp['cp_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($cp['mobile']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($cp['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo !empty($cp['rera']) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo !empty($cp['rera']) ? 'RERA Registered' : 'No RERA'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editCP('<?php echo $cp['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <button onclick="deleteCP('<?php echo $cp['id']; ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Users Tab Content -->
        <div id="users-tab" class="tab-content">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Users Management</h3>
                    <button onclick="openUserModal()" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200">
                        Add New User
                    </button>
                </div>

                <!-- Search -->
                <div class="p-6 border-b border-gray-200">
                    <input type="text" id="userSearch" placeholder="Search by username, name, or email..." 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>

                <!-- Users Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="usersTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($users as $user): ?>
                            <tr class="user-row" data-search="<?php echo strtolower($user['username'] . ' ' . $user['name'] . ' ' . $user['email']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        if($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                                        elseif($user['role'] === 'manager') echo 'bg-blue-100 text-blue-800';
                                        else echo 'bg-green-100 text-green-800';
                                        ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $user['last_login'] ? date('d M Y, h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editUser('<?php echo $user['id']; ?>')" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser('<?php echo $user['id']; ?>')" class="text-red-600 hover:text-red-900">Delete</button>
                                    <?php else: ?>
                                    <span class="text-gray-400">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Modal -->
    <div id="leadModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <h3 class="text-lg font-semibold text-gray-800 mb-4" id="leadModalTitle">Add New Lead</h3>
            
            <form id="leadForm" method="POST">
                <input type="hidden" name="action" id="leadAction" value="add_lead">
                <input type="hidden" name="lead_id" id="lead_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name *</label>
                        <input type="text" name="name" id="lead_name" required 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                +91
                            </span>
                            <input type="tel" name="phone" id="lead_phone" pattern="[0-9]{10}" maxlength="10" required 
                                   class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="lead_email" 
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Spouse Phone</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                +91
                            </span>
                            <input type="tel" name="spouse_phone" id="lead_spouse_phone" pattern="[0-9]{10}" maxlength="10" 
                                   class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Project *</label>
                        <select name="project" id="lead_project" required 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            <option value="">Select Project</option>
                            <?php foreach($uniqueProjects as $project): ?>
                            <option value="<?php echo htmlspecialchars($project); ?>"><?php echo htmlspecialchars($project); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Channel Partner *</label>
                        <select name="channel_partner_id" id="lead_channel_partner" required 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            <option value="">Select Partner</option>
                            <?php foreach($channelPartners as $cp): ?>
                            <option value="<?php echo $cp['id']; ?>"><?php echo htmlspecialchars($cp['firm_name'] . ' - ' . $cp['cp_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select name="status" id="lead_status" required 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            <option value="registered">Registered</option>
                            <option value="converted">Converted</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                        <select name="assigned_to" id="lead_assigned_to" 
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            <option value="">Not Assigned</option>
                            <?php 
                            $salesTeam = ['shivu', 'bharath', 'ravi'];
                            foreach($salesTeam as $person): 
                            ?>
                            <option value="<?php echo $person; ?>"><?php echo ucfirst($person); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <textarea name="remarks" id="lead_remarks" rows="3" 
                              class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none"></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Save Lead</button>
                    <button type="button" onclick="closeLeadModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Channel Partner Modal -->
    <div id="cpModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4" id="cpModalTitle">Add New Channel Partner</h3>
            
            <form id="cpForm" method="POST">
                <input type="hidden" name="action" id="cpAction" value="add_cp">
                <input type="hidden" name="cp_id" id="cp_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Firm Name *</label>
                    <input type="text" name="firm_name" id="cp_firm_name" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CP Name *</label>
                    <input type="text" name="cp_name" id="cp_name" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile *</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            +91
                        </span>
                        <input type="tel" name="mobile" id="cp_mobile" pattern="[0-9]{10}" maxlength="10" required 
                               class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" id="cp_email" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">RERA Number</label>
                    <input type="text" name="rera" id="cp_rera" 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Save Partner</button>
                    <button type="button" onclick="closeCPModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4" id="userModalTitle">Add New User</h3>
            
            <form id="userForm" method="POST">
                <input type="hidden" name="action" id="userAction" value="add_user">
                <input type="hidden" name="user_id" id="user_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                    <input type="text" name="username" id="user_username" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password <?php echo isset($_POST['action']) && $_POST['action'] === 'edit_user' ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" name="password" id="user_password" <?php echo !isset($_POST['action']) || $_POST['action'] !== 'edit_user' ? 'required' : ''; ?> 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="name" id="user_name" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="email" id="user_email" required 
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role" id="user_role" required 
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="sales">Sales</option>
                    </select>
                </div>
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Save User</button>
                    <button type="button" onclick="closeUserModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Confirm Delete</h3>
            <p class="text-gray-600 mb-6" id="deleteMessage">Are you sure you want to delete this item?</p>
            
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" id="deleteAction">
                <input type="hidden" name="lead_id" id="delete_lead_id">
                <input type="hidden" name="cp_id" id="delete_cp_id">
                <input type="hidden" name="user_id" id="delete_user_id">
                
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">Delete</button>
                    <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sorting variables
        let currentSortColumn = 7;
        let currentSortDirection = 'desc';
        
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-500');
                button.classList.add('text-gray-500');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-500');
            event.target.classList.remove('text-gray-500');
        }

        // Sort table by date function
        function sortTableByDate() {
            const tbody = document.getElementById('leadsTableBody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const dateA = new Date(a.getAttribute('data-date'));
                const dateB = new Date(b.getAttribute('data-date'));
                return dateB - dateA; // Newest first
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Sort by column function
        function sortByColumn(columnIndex) {
            const tbody = document.getElementById('leadsTableBody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Toggle direction if same column
            if (currentSortColumn === columnIndex) {
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortColumn = columnIndex;
                currentSortDirection = 'asc';
            }
            
            rows.sort((a, b) => {
                let aVal, bVal;
                const cells = a.querySelectorAll('td');
                const cellA = cells[columnIndex];
                const cellB = b.querySelectorAll('td')[columnIndex];
                
                // Get sort values from data-sort attribute or text content
                if (columnIndex === 0) { // Lead ID
                    aVal = cellA.getAttribute('data-sort') || cellA.textContent;
                    bVal = cellB.getAttribute('data-sort') || cellB.textContent;
                } else if (columnIndex === 1) { // Name
                    aVal = cellA.getAttribute('data-sort') || cellA.textContent.toLowerCase();
                    bVal = cellB.getAttribute('data-sort') || cellB.textContent.toLowerCase();
                } else if (columnIndex === 3) { // Project
                    aVal = cellA.getAttribute('data-sort') || cellA.textContent.toLowerCase();
                    bVal = cellB.getAttribute('data-sort') || cellB.textContent.toLowerCase();
                } else if (columnIndex === 6) { // Status
                    aVal = cellA.getAttribute('data-sort') || cellA.textContent;
                    bVal = cellB.getAttribute('data-sort') || cellB.textContent;
                } else if (columnIndex === 7) { // Date
                    aVal = new Date(cellA.getAttribute('data-sort') || cellA.textContent);
                    bVal = new Date(cellB.getAttribute('data-sort') || cellB.textContent);
                } else {
                    aVal = cellA.textContent;
                    bVal = cellB.textContent;
                }
                
                if (currentSortDirection === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
            
            // Update sort icons
            document.querySelectorAll('.sort-icon').forEach(icon => icon.textContent = '↕');
            const headers = document.querySelectorAll('#leadsTable thead th');
            const currentHeader = headers[columnIndex];
            const icon = currentHeader.querySelector('.sort-icon');
            if (icon) {
                icon.textContent = currentSortDirection === 'asc' ? '↑' : '↓';
            }
        }

        // Lead Modal Functions
        function openLeadModal() {
            document.getElementById('leadModalTitle').textContent = 'Add New Lead';
            document.getElementById('leadAction').value = 'add_lead';
            document.getElementById('leadForm').reset();
            document.getElementById('leadModal').style.display = 'flex';
        }

        function closeLeadModal() {
            document.getElementById('leadModal').style.display = 'none';
        }

        function editLead(leadId) {
            const leads = <?php echo json_encode($leads); ?>;
            const lead = leads.find(l => l.id === leadId);
            
            if (lead) {
                document.getElementById('leadModalTitle').textContent = 'Edit Lead';
                document.getElementById('leadAction').value = 'edit_lead';
                document.getElementById('lead_id').value = lead.id;
                document.getElementById('lead_name').value = lead.name;
                document.getElementById('lead_phone').value = lead.phone.replace('+91', '');
                document.getElementById('lead_email').value = lead.email || '';
                document.getElementById('lead_spouse_phone').value = lead.spouse_phone ? lead.spouse_phone.replace('+91', '') : '';
                document.getElementById('lead_project').value = lead.project;
                document.getElementById('lead_channel_partner').value = lead.channel_partner_id;
                document.getElementById('lead_status').value = lead.status;
                document.getElementById('lead_remarks').value = lead.remarks || '';
                document.getElementById('lead_assigned_to').value = lead.assigned_to || '';
                document.getElementById('leadModal').style.display = 'flex';
            }
        }

        function deleteLead(leadId) {
            document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this lead?';
            document.getElementById('deleteAction').value = 'delete_lead';
            document.getElementById('delete_lead_id').value = leadId;
            document.getElementById('delete_cp_id').value = '';
            document.getElementById('delete_user_id').value = '';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        // Update the delete form submission handler
        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = formData.get('action');
            
            fetch('admin-panel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the lead.');
            });
        });

        // Channel Partner Modal Functions
        function openCPModal() {
            document.getElementById('cpModalTitle').textContent = 'Add New Channel Partner';
            document.getElementById('cpAction').value = 'add_cp';
            document.getElementById('cpForm').reset();
            document.getElementById('cpModal').style.display = 'flex';
        }

        function closeCPModal() {
            document.getElementById('cpModal').style.display = 'none';
        }

        function editCP(cpId) {
            const cps = <?php echo json_encode($channelPartners); ?>;
            const cp = cps.find(c => c.id === cpId);
            
            if (cp) {
                document.getElementById('cpModalTitle').textContent = 'Edit Channel Partner';
                document.getElementById('cpAction').value = 'edit_cp';
                document.getElementById('cp_id').value = cp.id;
                document.getElementById('cp_firm_name').value = cp.firm_name;
                document.getElementById('cp_name').value = cp.cp_name;
                document.getElementById('cp_mobile').value = cp.mobile.replace('+91', '');
                document.getElementById('cp_email').value = cp.email;
                document.getElementById('cp_rera').value = cp.rera || '';
                document.getElementById('cpModal').style.display = 'flex';
            }
        }

        function deleteCP(cpId) {
            document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this channel partner?';
            document.getElementById('deleteAction').value = 'delete_cp';
            document.getElementById('delete_cp_id').value = cpId;
            document.getElementById('delete_lead_id').value = '';
            document.getElementById('delete_user_id').value = '';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        // User Modal Functions
        function openUserModal() {
            document.getElementById('userModalTitle').textContent = 'Add New User';
            document.getElementById('userAction').value = 'add_user';
            document.getElementById('userForm').reset();
            document.getElementById('user_password').required = true;
            document.getElementById('userModal').style.display = 'flex';
        }

        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function editUser(userId) {
            const users = <?php echo json_encode($users); ?>;
            const user = users.find(u => u.id === userId);
            
            if (user) {
                document.getElementById('userModalTitle').textContent = 'Edit User';
                document.getElementById('userAction').value = 'edit_user';
                document.getElementById('user_id').value = user.id;
                document.getElementById('user_username').value = user.username;
                document.getElementById('user_password').value = '';
                document.getElementById('user_password').required = false;
                document.getElementById('user_name').value = user.name;
                document.getElementById('user_email').value = user.email;
                document.getElementById('user_role').value = user.role;
                document.getElementById('userModal').style.display = 'flex';
            }
        }

        function deleteUser(userId) {
            document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this user?';
            document.getElementById('deleteAction').value = 'delete_user';
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_lead_id').value = '';
            document.getElementById('delete_cp_id').value = '';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Search and Filter Functions
        document.getElementById('leadSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#leadsTable .lead-row');
            
            rows.forEach(row => {
                const searchable = row.getAttribute('data-search');
                row.style.display = searchable.includes(searchTerm) ? '' : 'none';
            });
        });

        document.getElementById('leadStatusFilter')?.addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('#leadsTable .lead-row');
            
            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                row.style.display = !status || rowStatus === status ? '' : 'none';
            });
        });
        
        document.getElementById('leadProjectFilter')?.addEventListener('change', function() {
            const project = this.value;
            const rows = document.querySelectorAll('#leadsTable .lead-row');
            
            rows.forEach(row => {
                const rowProject = row.getAttribute('data-project');
                row.style.display = !project || rowProject === project ? '' : 'none';
            });
        });

        document.getElementById('cpSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#cpTable .cp-row');
            
            rows.forEach(row => {
                const searchable = row.getAttribute('data-search');
                row.style.display = searchable.includes(searchTerm) ? '' : 'none';
            });
        });

        document.getElementById('userSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable .user-row');
            
            rows.forEach(row => {
                const searchable = row.getAttribute('data-search');
                row.style.display = searchable.includes(searchTerm) ? '' : 'none';
            });
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.id === 'leadModal') closeLeadModal();
            if (e.target.id === 'cpModal') closeCPModal();
            if (e.target.id === 'userModal') closeUserModal();
            if (e.target.id === 'deleteModal') closeDeleteModal();
        });
        
        function updateAssignment(leadId, assignedTo) {
            fetch('update-assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lead_id: leadId,
                    assigned_to: assignedTo
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'mb-6 p-4 bg-green-50 border border-green-200 rounded-lg';
                    messageDiv.innerHTML = `<p class="text-green-600">Lead assignment updated successfully!</p>`;
                    const container = document.querySelector('.container');
                    container.insertBefore(messageDiv, container.firstChild);
                    setTimeout(() => {
                        messageDiv.remove();
                    }, 3000);
                } else {
                    alert('Error updating assignment: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the assignment.');
            });
        }
    </script>
</body>
</html>