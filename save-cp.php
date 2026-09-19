<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Load existing channel partners to generate ID
 $cpFile = 'data/channel_partners.json';
 $channelPartners = [];

if (file_exists($cpFile)) {
    $channelPartners = json_decode(file_get_contents($cpFile), true) ?: [];
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

// Get JSON input
 $input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!$input || !isset($input['firm_name']) || !isset($input['cp_name']) || 
    !isset($input['mobile']) || !isset($input['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Create new channel partner
 $newCP = [
    'id' => generateChannelPartnerId($channelPartners),
    'firm_name' => $input['firm_name'],
    'cp_name' => $input['cp_name'],
    'mobile' => '+91' . $input['mobile'],
    'email' => $input['email'],
    'rera' => $input['rera'] ?? '',
    'created_at' => date('Y-m-d H:i:s')
];

// Add new CP
 $channelPartners[] = $newCP;

// Save to file
if (file_put_contents($cpFile, json_encode($channelPartners, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'cp' => $newCP
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save channel partner']);
}
?>