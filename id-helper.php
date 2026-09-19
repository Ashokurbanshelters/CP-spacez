<?php
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
?>