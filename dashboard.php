<?php
session_start();
require_once 'auth-config.php';

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php?error=session');
    exit;
}

// Load data
 $leadsFile = 'data/leads.json';
 $cpFile = 'data/channel_partners.json';

 $leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];
 $channelPartners = file_exists($cpFile) ? json_decode(file_get_contents($cpFile), true) : [];

// Handle filter submissions
 $filters = [
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'status' => $_GET['status'] ?? '',
    'project' => $_GET['project'] ?? '',
    'channel_partner' => $_GET['channel_partner'] ?? '',
    'assigned_to' => $_GET['assigned_to'] ?? '', // Added assigned_to filter
    'search' => $_GET['search'] ?? ''
];

// Apply filters to leads
 $filteredLeads = $leads;
if (!empty($filters['date_from'])) {
    $filteredLeads = array_filter($filteredLeads, function($lead) use ($filters) {
        return strtotime($lead['created_at']) >= strtotime($filters['date_from']);
    });
}
if (!empty($filters['date_to'])) {
    $filteredLeads = array_filter($filteredLeads, function($lead) use ($filters) {
        return strtotime($lead['created_at']) <= strtotime($filters['date_to'] . ' 23:59:59');
    });
}
if (!empty($filters['status'])) {
    $filteredLeads = array_filter($filteredLeads, function($lead) use ($filters) {
        return $lead['status'] === $filters['status'];
    });
}
if (!empty($filters['project'])) {
    $filteredLeads = array_filter($filteredLeads, function($lead) use ($filters) {
        return strcasecmp($lead['project'], $filters['project']) === 0;
    });
}
if (!empty($filters['channel_partner'])) {
    $filteredLeads = array_filter($filteredLeads, function($lead) use ($filters) {
        return $lead['channel_partner_id'] === $filters['channel_partner'];
    });
}
// New Assigned To Logic
if (!empty($filters['assigned_to'])) {
    $filteredLeads = array_filter($filteredLeads, function($lead) use ($filters) {
        if ($filters['assigned_to'] === 'not_assigned') {
             return !isset($lead['assigned_to']) || empty($lead['assigned_to']);
        }
        return isset($lead['assigned_to']) && $lead['assigned_to'] === $filters['assigned_to'];
    });
}
if (!empty($filters['search'])) {
    $searchTerm = strtolower($filters['search']);
    $filteredLeads = array_filter($filteredLeads, function($lead) use ($searchTerm) {
        return strpos(strtolower($lead['name']), $searchTerm) !== false || 
               strpos(strtolower($lead['phone']), $searchTerm) !== false || 
               strpos(strtolower($lead['email']), $searchTerm) !== false;
    });
}

// Apply filters to channel partners
 $cpFilters = [
    'search_cp' => $_GET['search_cp'] ?? '',
    'rera_status' => $_GET['rera_status'] ?? ''
];

 $filteredCPs = $channelPartners;
if (!empty($cpFilters['search_cp'])) {
    $searchTerm = strtolower($cpFilters['search_cp']);
    $filteredCPs = array_filter($filteredCPs, function($cp) use ($searchTerm) {
        return strpos(strtolower($cp['firm_name']), $searchTerm) !== false || 
               strpos(strtolower($cp['cp_name']), $searchTerm) !== false || 
               strpos(strtolower($cp['mobile']), $searchTerm) !== false || 
               strpos(strtolower($cp['email']), $searchTerm) !== false;
    });
}
if (!empty($cpFilters['rera_status'])) {
    $filteredCPs = array_filter($filteredCPs, function($cp) use ($cpFilters) {
        if ($cpFilters['rera_status'] === 'with_rera') {
            return !empty($cp['rera']);
        } else {
            return empty($cp['rera']);
        }
    });
}

// Calculate statistics
 $totalLeads = count($leads);
 $registeredLeads = count(array_filter($leads, function($lead) { return $lead['status'] === 'registered'; }));
 $convertedLeads = count(array_filter($leads, function($lead) { return $lead['status'] === 'converted'; }));
 $totalCPs = count($channelPartners);

// Get project-wise distribution
 $projectStats = [];
foreach ($leads as $lead) {
    $project = $lead['project'];
    if (!isset($projectStats[$project])) {
        $projectStats[$project] = 0;
    }
    $projectStats[$project]++;
}
arsort($projectStats);

// Get unique projects for filter dropdown (includes new projects)
$defaultProjects = ['Platinum Landmark', 'Platinum Green Fields', 'Suraksha Sunrise Park', 'Urban Tranquil', 'Sri Nandana Paradise', 'PANCHAJANYAA'];
$uniqueProjects = array_unique(array_merge($defaultProjects, array_column($leads, 'project')));
sort($uniqueProjects);

// Get unique assigned_to for filter dropdown
$uniqueAssignees = array_unique(array_column($leads, 'assigned_to'));
$uniqueAssignees = array_filter($uniqueAssignees); // Remove empty values
sort($uniqueAssignees);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Real Estate Lead Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">Real Estate CRM Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        New Lead
                    </a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin-panel.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                        admin-panel
                    </a>
                    <a href="manage-inactive-leads.php" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition duration-200">
                        Inactive Leads
                    </a>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200">
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm">Total Leads</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalLeads; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm">Converted</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $convertedLeads; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $registeredLeads; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-5">
                        <p class="text-gray-500 text-sm">Channel Partners</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $totalCPs; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Project Distribution Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Project-wise Distribution</h3>
                <div class="relative h-64">
                    <canvas id="projectChart"></canvas>
                </div>
            </div>

            <!-- Lead Status Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Lead Status Overview</h3>
                <div class="relative h-64">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="switchTab('leads')" class="tab-button active py-4 px-6 text-sm font-medium text-blue-600 border-b-2 border-blue-500 focus:outline-none">
                        Leads Management
                    </button>
                    <button onclick="switchTab('partners')" class="tab-button py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none">
                        Channel Partners
                    </button>
                </nav>
            </div>
        </div>

        <!-- Leads Tab Content -->
        <div id="leads-tab" class="tab-content active">
            <div class="bg-white rounded-lg shadow">
                <!-- Leads Filter Section -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Leads</h3>
                    <form method="GET" class="space-y-4">
                        <!-- Row 1 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                                    <option value="">All Status</option>
                                    <option value="registered" <?php echo $filters['status'] === 'registered' ? 'selected' : ''; ?>>Registered</option>
                                    <option value="converted" <?php echo $filters['status'] === 'converted' ? 'selected' : ''; ?>>Converted</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                                <select name="project" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                                    <option value="">All Projects</option>
                                    <?php foreach($uniqueProjects as $project): ?>
                                    <option value="<?php echo htmlspecialchars($project); ?>" <?php echo strcasecmp($filters['project'], $project) === 0 ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Row 2 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Channel Partner</label>
                                <select name="channel_partner" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                                    <option value="">All Partners</option>
                                    <?php foreach($channelPartners as $cp): ?>
                                    <option value="<?php echo $cp['id']; ?>" <?php echo $filters['channel_partner'] === $cp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cp['firm_name'] . ' - ' . $cp['cp_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- New Assigned To Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                                <select name="assigned_to" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                                    <option value="">All Staff</option>
                                    <option value="not_assigned" <?php echo $filters['assigned_to'] === 'not_assigned' ? 'selected' : ''; ?>>Not Assigned</option>
                                    <?php foreach($uniqueAssignees as $staff): ?>
                                    <option value="<?php echo htmlspecialchars($staff); ?>" <?php echo $filters['assigned_to'] === $staff ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(htmlspecialchars($staff)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search (Name/Phone/Email)</label>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                       placeholder="Search leads..." 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-200">
                                Apply Filters
                            </button>
                            <a href="dashboard.php" class="bg-gray-300 text-gray-700 py-2 px-6 rounded-md hover:bg-gray-400 transition duration-200">
                                Clear Filters
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Leads Table -->
                <div class="overflow-x-auto">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Leads (<?php echo count($filteredLeads); ?> of <?php echo $totalLeads; ?>)
                        </h3>
                        <button onclick="exportLeads()" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200">
                            Export to CSV
                        </button>
                    </div>
                    <!-- Updated leads table with assigned sales person column -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Channel Partner</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $recentLeads = array_slice(array_reverse($filteredLeads), 0, 50);
                            foreach($recentLeads as $lead): 
                                $cpName = 'N/A';
                                foreach($channelPartners as $cp) {
                                    if($cp['id'] == $lead['channel_partner_id']) {
                                        $cpName = $cp['cp_name'];
                                        break;
                                    }
                                }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($lead['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($lead['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($lead['phone']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($lead['project']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($cpName); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo isset($lead['assigned_to']) ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo isset($lead['assigned_to']) ? ucfirst($lead['assigned_to']) : 'Not Assigned'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $lead['status'] === 'converted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($lead['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M Y', strtotime($lead['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="updateStatus('<?php echo $lead['id']; ?>')" class="text-indigo-600 hover:text-indigo-900">Update</button>
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
                <!-- Partners Filter Section -->
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Filter Channel Partners</h3>
                    <form method="GET" class="space-y-4">
                        <input type="hidden" name="tab" value="partners">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search (Firm/Name/Mobile/Email)</label>
                                <input type="text" name="search_cp" value="<?php echo htmlspecialchars($cpFilters['search_cp']); ?>" 
                                       placeholder="Search partners..." 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">RERA Status</label>
                                <select name="rera_status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                                    <option value="">All Partners</option>
                                    <option value="with_rera" <?php echo $cpFilters['rera_status'] === 'with_rera' ? 'selected' : ''; ?>>With RERA</option>
                                    <option value="without_rera" <?php echo $cpFilters['rera_status'] === 'without_rera' ? 'selected' : ''; ?>>Without RERA</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-200">
                                Apply Filters
                            </button>
                            <a href="dashboard.php" class="bg-gray-300 text-gray-700 py-2 px-6 rounded-md hover:bg-gray-400 transition duration-200">
                                Clear Filters
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Partners Table -->
                <div class="overflow-x-auto">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Channel Partners (<?php echo count($filteredCPs); ?> of <?php echo $totalCPs; ?>)
                        </h3>
                        <button onclick="exportPartners()" class="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200">
                            Export to CSV
                        </button>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CP ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Firm Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CP Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mobile</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RERA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Leads</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conversion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($filteredCPs as $cp): 
                                $cpLeads = array_filter($leads, function($lead) use ($cp) {
                                    return $lead['channel_partner_id'] === $cp['id'];
                                });
                                $cpLeadCount = count($cpLeads);
                                $cpConvertedCount = count(array_filter($cpLeads, function($lead) {
                                    return $lead['status'] === 'converted';
                                }));
                                $conversionRate = $cpLeadCount > 0 ? round(($cpConvertedCount / $cpLeadCount) * 100, 1) : 0;
                            ?>
                            <tr>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $cpLeadCount; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $conversionRate; ?>%"></div>
                                        </div>
                                        <span class="text-xs"><?php echo $conversionRate; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Update Lead Status</h3>
            <form id="statusForm">
                <input type="hidden" id="lead_id_update">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Status</label>
                    <select id="new_status" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
                        <option value="registered">Registered</option>
                        <option value="converted">Converted</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">Update</button>
                    <button type="button" onclick="closeStatusModal()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-500');
                button.classList.add('text-gray-500');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-500');
            event.target.classList.remove('text-gray-500');
        }

        // Limit project data to top 10 projects
        const allProjectData = <?php echo json_encode($projectStats); ?>;
        const sortedProjects = Object.entries(allProjectData)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 10);
        
        const projectData = Object.fromEntries(sortedProjects);
        
        // Project Distribution Chart
        const projectCtx = document.getElementById('projectChart').getContext('2d');
        new Chart(projectCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(projectData),
                datasets: [{
                    label: 'Number of Leads',
                    data: Object.values(projectData),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        max: Object.values(projectData).length > 0 ? Math.max(...Object.values(projectData)) + 2 : 10
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Registered', 'Converted'],
                datasets: [{
                    data: [<?php echo $registeredLeads; ?>, <?php echo $convertedLeads; ?>],
                    backgroundColor: [
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderColor: [
                        'rgba(251, 191, 36, 1)',
                        'rgba(34, 197, 94, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Update Status Modal
        function updateStatus(leadId) {
            document.getElementById('lead_id_update').value = leadId;
            document.getElementById('statusModal').style.display = 'flex';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        document.getElementById('statusForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const leadId = document.getElementById('lead_id_update').value;
            const newStatus = document.getElementById('new_status').value;
            
            const response = await fetch('update-status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({lead_id: leadId, status: newStatus})
            });
            
            if (response.ok) {
                location.reload();
            }
        });

        // Export to CSV functions
        function exportLeads() {
            const leads = <?php echo json_encode(array_values($filteredLeads)); ?>;
            const cps = <?php echo json_encode($channelPartners); ?>;
            
            let csv = 'Lead ID,Customer Name,Phone,Email,Project,Channel Partner,Assigned To,Status,Created At\n';
            
            leads.forEach(lead => {
                const cp = cps.find(c => c.id === lead.channel_partner_id);
                const cpName = cp ? `${cp.firm_name} - ${cp.cp_name}` : 'N/A';
                const assignedTo = lead.assigned_to ? lead.assigned_to : 'Not Assigned';
                
                csv += `"${lead.id}","${lead.name}","${lead.phone}","${lead.email || ''}","${lead.project}","${cpName}","${assignedTo}","${lead.status}","${lead.created_at}"\n`;
            });
            
            downloadCSV(csv, 'leads_export.csv');
        }

        function exportPartners() {
            const partners = <?php echo json_encode(array_values($filteredCPs)); ?>;
            const leads = <?php echo json_encode($leads); ?>;
            
            let csv = 'CP ID,Firm Name,CP Name,Mobile,Email,RERA,Total Leads,Converted Leads,Conversion Rate\n';
            
            partners.forEach(cp => {
                const cpLeads = leads.filter(l => l.channel_partner_id === cp.id);
                const convertedLeads = cpLeads.filter(l => l.status === 'converted').length;
                const conversionRate = cpLeads.length > 0 ? ((convertedLeads / cpLeads.length) * 100).toFixed(1) : 0;
                
                csv += `"${cp.id}","${cp.firm_name}","${cp.cp_name}","${cp.mobile}","${cp.email}","${cp.rera || ''}","${cpLeads.length}","${convertedLeads}","${conversionRate}%"\n`;
            });
            
            downloadCSV(csv, 'partners_export.csv');
        }

        function downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Check for tab parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'partners') {
            switchTab('partners');
        }
    </script>
</body>
</html>