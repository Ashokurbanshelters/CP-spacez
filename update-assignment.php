<?php
session_start();
require_once 'auth-config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get JSON input
 $input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['lead_id']) || !isset($input['assigned_to'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

 $leadId = $input['lead_id'];
 $assignedTo = $input['assigned_to'];

// Load leads
 $leadsFile = 'data/leads.json';
 $leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];

// Update lead assignment
 $updated = false;
foreach ($leads as &$lead) {
    if ($lead['id'] === $leadId) {
        if (empty($assignedTo)) {
            unset($lead['assigned_to']);
            unset($lead['assigned_at']);
        } else {
            $lead['assigned_to'] = $assignedTo;
            $lead['assigned_at'] = date('Y-m-d H:i:s');
        }
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