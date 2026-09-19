<?php
// Set timezone to ensure consistent date calculations
date_default_timezone_set('Asia/Kolkata');

 $message = '';
 $error = '';
 $leadDetails = null;

// Handle form submission for lead assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_lead') {
    $leadId = $_POST['lead_id'];
    $assignedTo = $_POST['assigned_to'];
    
    // Load leads
    $leadsFile = 'data/leads.json';
    $leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];
    
    // Find and update the lead
    $updated = false;
    foreach ($leads as &$lead) {
        if ($lead['id'] === $leadId) {
            $lead['assigned_to'] = $assignedTo;
            $lead['assigned_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
        $message = "Lead assigned to {$assignedTo} successfully!";
    } else {
        $error = "Failed to assign lead. Please try again.";
    }
}

// Handle lead search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search_lead') {
    $phone = '+91' . preg_replace('/[^0-9]/', '', $_POST['phone']);
    
    // Load leads
    $leadsFile = 'data/leads.json';
    $leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];
    
    // Search for lead
    foreach ($leads as $lead) {
        if ($lead['phone'] === $phone) {
            $leadDetails = $lead;
            break;
        }
    }
    
    if (!$leadDetails) {
        $error = "No lead found with this phone number.";
    }
}

// Sales team members
 $salesTeam = ['shivu', 'bharath', 'ravi'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Lead Assignment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Sales Lead Assignment</h1>
                
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
                
                <!-- Search Form -->
                <form method="POST" class="mb-8">
                    <input type="hidden" name="action" value="search_lead">
                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Customer Phone Number
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                +91
                            </span>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                pattern="[0-9]{10}" 
                                maxlength="10"
                                required
                                placeholder="Enter 10-digit number"
                                class="flex-1 rounded-none rounded-r-md border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200 font-medium">
                        Search Lead
                    </button>
                </form>
                
                <!-- Lead Details and Assignment Form -->
                <?php if ($leadDetails): ?>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Lead Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <span class="text-sm text-gray-600">Lead ID:</span>
                            <p class="font-medium"><?php echo htmlspecialchars($leadDetails['id']); ?></p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Name:</span>
                            <p class="font-medium"><?php echo htmlspecialchars($leadDetails['name']); ?></p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Phone:</span>
                            <p class="font-medium"><?php echo htmlspecialchars($leadDetails['phone']); ?></p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Email:</span>
                            <p class="font-medium"><?php echo htmlspecialchars($leadDetails['email'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Project:</span>
                            <p class="font-medium"><?php echo htmlspecialchars($leadDetails['project']); ?></p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">Status:</span>
                            <p class="font-medium"><?php echo ucfirst($leadDetails['status']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <span class="text-sm text-gray-600">Assigned To:</span>
                        <p class="font-medium"><?php echo isset($leadDetails['assigned_to']) ? htmlspecialchars($leadDetails['assigned_to']) : 'Not assigned'; ?></p>
                    </div>
                </div>
                
                <?php if (!isset($leadDetails['assigned_to']) || empty($leadDetails['assigned_to'])): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_lead">
                    <input type="hidden" name="lead_id" value="<?php echo $leadDetails['id']; ?>">
                    
                    <div class="mb-4">
                        <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-2">
                            Assign To Sales Person
                        </label>
                        <select 
                            id="assigned_to" 
                            name="assigned_to" 
                            required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Select Sales Person</option>
                            <?php foreach($salesTeam as $person): ?>
                            <option value="<?php echo $person; ?>"><?php echo ucfirst($person); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition duration-200 font-medium">
                        Assign Lead
                    </button>
                </form>
                <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-700">This lead is already assigned to <strong><?php echo htmlspecialchars($leadDetails['assigned_to']); ?></strong>.</p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <div class="mt-6 text-center">
                    <a href="sales-assign.php" class="inline-block bg-gray-300 text-gray-700 py-2 px-6 rounded-md hover:bg-gray-400 transition duration-200 font-medium">
                        Search Another Lead
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>