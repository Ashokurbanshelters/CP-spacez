<?php
session_start();
require_once 'auth-config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['lead_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

 $leadId = $_POST['lead_id'];
 $leadsFile = 'data/leads.json';
 $inactiveLeadsFile = 'data/inactive-leads.json';

// Load both files
 $leads = file_exists($leadsFile) ? json_decode(file_get_contents($leadsFile), true) : [];
 $inactiveLeads = file_exists($inactiveLeadsFile) ? json_decode(file_get_contents($inactiveLeadsFile), true) : [];

 $leadToReactivate = null;
 $updatedInactiveLeads = [];

// Find the lead in the inactive list
foreach ($inactiveLeads as $lead) {
    if ($lead['id'] === $leadId) {
        $leadToReactivate = $lead;
    } else {
        $updatedInactiveLeads[] = $lead;
    }
}

if ($leadToReactivate) {
    // Prepare the lead for reactivation
    $leadToReactivate['status'] = 'registered'; // Reset status
    $leadToReactivate['reactivated_at'] = date('Y-m-d H:i:s');
    $leadToReactivate['reactivated_by'] = $_SESSION['username'];
    unset($leadToReactivate['expired_at']); // Remove expired flag

    // Add it back to the active leads
    $leads[] = $leadToReactivate;

    // Save both files
    file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT));
    file_put_contents($inactiveLeadsFile, json_encode($updatedInactiveLeads, JSON_PRETTY_PRINT));

    // Redirect back with a success message
    header('Location: manage-inactive-leads.php?message=reactivated');
    exit;
} else {
    // Lead not found
    header('Location: manage-inactive-leads.php?error=not_found');
    exit;
}
?>