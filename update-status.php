<?php
session_start();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['lead_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$leadId = $input['lead_id'];
$newStatus = $input['status'];

// Load leads
$leadsFile = 'data/leads.json';
$leads = [];

if (file_exists($leadsFile)) {
    $leads = json_decode(file_get_contents($leadsFile), true) ?: [];
}

// Update lead status
$updated = false;
foreach ($leads as &$lead) {
    if ($lead['id'] === $leadId) {
        $lead['status'] = $newStatus;
        $lead['updated_at'] = date('Y-m-d H:i:s');
        $lead['updated_by'] = $_SESSION['username'];
        $updated = true;
        break;
    }
}

if ($updated) {
    file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Lead not found']);
}
?>