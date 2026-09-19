<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Load existing leads to generate ID
 $leadsFile = 'data/leads.json';
 $leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];

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

// Collect form data
 $leadData = [
    'id' => generateLeadId($leads),
    'name' => $_POST['name'],
    'phone' => $_POST['phone'],
    'email' => $_POST['email'],
    'spouse_phone' => !empty($_POST['spouse_phone']) ? '+91' . $_POST['spouse_phone'] : '',
    'project' => $_POST['project'],
    'channel_partner_id' => $_POST['channel_partner'],
    'remarks' => $_POST['remarks'],
    'status' => 'registered',
    'created_at' => date('Y-m-d H:i:s')
];

// Add new lead
 $leads[] = $leadData;

// Save to file
file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));

// Get channel partner details
 $cpFile = 'data/channel_partners.json';
 $channelPartners = [];

if (file_exists($cpFile)) {
    $channelPartners = json_decode(file_get_contents($cpFile), true) ?: [];
}

 $cpName = 'Unknown';
foreach ($channelPartners as $cp) {
    if ($cp['id'] == $leadData['channel_partner_id']) {
        $cpName = $cp['firm_name'] . ' - ' . $cp['cp_name'];
        break;
    }
}

// Clear session
unset($_SESSION['customer_phone']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Saved Successfully</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Lead Saved Successfully!</h2>
                    <p class="text-gray-600">The lead has been registered in the system.</p>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Lead Details</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Lead ID:</span>
                            <span class="font-medium text-gray-800"><?php echo $leadData['id']; ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Customer Name:</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($leadData['name']); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Phone:</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($leadData['phone']); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Project:</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($leadData['project']); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Channel Partner:</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($cpName); ?></span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Date:</span>
                            <span class="font-medium text-gray-800"><?php echo date('d M Y, h:i A', strtotime($leadData['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-4">
                    <a href="index.php" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200 font-medium text-center">
                        Register New Lead
                    </a>
                    <button onclick="window.print()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-400 transition duration-200 font-medium">
                        Print Details
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>