<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

 $phone = '+91' . preg_replace('/[^0-9]/', '', $_POST['phone']);

// Read leads data
 $leadsFile = 'data/leads.json';
 $leads = [];

if (file_exists($leadsFile)) {
    $leads = json_decode(file_get_contents($leadsFile), true) ?: [];
}

// Check if phone number exists
 $existingLead = null;
foreach ($leads as $lead) {
    if ($lead['phone'] === $phone) {
        $existingLead = $lead;
        break;
    }
}

if ($existingLead) {
    // Customer already exists
    $status = $existingLead['status'];
    $message = $status === 'converted' ? 'Lead already converted!' : 'Lead already registered!';
    
    // --- NEW: Calculate Lead Expiry ---
    $validityPeriodDays = 60;
    $createdAt = strtotime($existingLead['created_at']);
    $expiryTime = $createdAt + ($validityPeriodDays * 24 * 60 * 60);
    $timeRemaining = $expiryTime - time();
    
    $expiryMessage = '';
    if ($timeRemaining > 0) {
        $days = floor($timeRemaining / (60 * 60 * 24));
        $hours = floor(($timeRemaining % (60 * 60 * 24)) / (60 * 60));
        $expiryMessage = "Lead expires in <strong>{$days} days and {$hours} hours</strong>.";
    } else {
        $daysExpired = abs(floor($timeRemaining / (60 * 60 * 24)));
        $expiryMessage = "Lead expired <strong>{$daysExpired} days ago</strong>.";
    }
    // --- END NEW ---
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Customer Status</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-md mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-2"><?php echo $message; ?></h2>
                        <p class="text-gray-600 mb-2">Lead ID: <?php echo $existingLead['id']; ?></p>
                        
                        <!-- NEW: Expiry Message Display -->
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-blue-700 text-sm"><?php echo $expiryMessage; ?></p>
                        </div>
                        <!-- END NEW -->

                        <p class="text-sm text-gray-500 mb-6">Registered on: <?php echo date('d M Y, h:i A', strtotime($existingLead['created_at'])); ?></p>
                        <a href="index.php" class="inline-block bg-blue-600 text-white py-2 px-6 rounded-md hover:bg-blue-700 transition duration-200">
                            Check Another Customer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    // New customer - proceed to lead form
    $_SESSION['customer_phone'] = $phone;
    header('Location: lead-form.php');
    exit;
}
?>