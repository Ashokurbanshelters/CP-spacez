<?php
header('Content-Type: application/json');

$cpFile = 'data/channel_partners.json';
$channelPartners = [];

if (file_exists($cpFile)) {
    $channelPartners = json_decode(file_get_contents($cpFile), true) ?: [];
}

echo json_encode($channelPartners);
?>