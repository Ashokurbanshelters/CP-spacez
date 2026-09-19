<?php
session_start();
require_once 'auth-config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php?error=session');
    exit;
}

// Load inactive leads
 $inactiveLeadsFile = 'data/inactive-leads.json';
 $inactiveLeads = file_exists($inactiveLeadsFile) ? json_decode(file_get_contents($inactiveLeadsFile), true) : [];

// Handle messages
 $message = '';
 $error = '';
if (isset($_GET['message']) && $_GET['message'] === 'reactivated') {
    $message = 'Lead reactivated successfully!';
}
if (isset($_GET['error']) && $_GET['error'] === 'not_found') {
    $error = 'Lead not found in inactive list.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inactive Leads - Real Estate CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">Manage Inactive Leads</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</span>
                    <a href="admin-panel.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200">
                        Admin Panel
                    </a>
                    <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        Dashboard
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

        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Inactive Leads (<?php echo count($inactiveLeads); ?>)</h3>
            </div>

            <!-- Search -->
            <div class="p-6 border-b border-gray-200">
                <input type="text" id="inactiveLeadSearch" placeholder="Search by name, phone, or email..." 
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none">
            </div>

            <!-- Inactive Leads Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="inactiveLeadsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered On</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expired On</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($inactiveLeads)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No inactive leads found.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($inactiveLeads as $lead): ?>
                            <tr class="inactive-lead-row" data-search="<?php echo strtolower($lead['name'] . ' ' . $lead['phone'] . ' ' . $lead['email']); ?>">
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
                                    <?php echo date('d M Y', strtotime($lead['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M Y', strtotime($lead['expired_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form action="reactivate-lead.php" method="POST" onsubmit="return confirm('Are you sure you want to reactivate this lead?');">
                                        <input type="hidden" name="lead_id" value="<?php echo $lead['id']; ?>">
                                        <button type="submit" class="bg-green-600 text-white py-1 px-3 rounded-md hover:bg-green-700 transition duration-200 text-sm">
                                            Reactivate
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('inactiveLeadSearch')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#inactiveLeadsTable .inactive-lead-row');
            
            rows.forEach(row => {
                const searchable = row.getAttribute('data-search');
                row.style.display = searchable.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>