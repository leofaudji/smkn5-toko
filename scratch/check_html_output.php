<?php
require_once __DIR__ . '/../includes/bootstrap.php';
// Simulate a request to the page
ob_start();
$_SERVER['HTTP_X_SPA_REQUEST'] = 'true';
require_once __DIR__ . '/../pages/pelunasan_konsinyasi.php';
$html = ob_get_clean();

echo "HTML LENGTH: " . strlen($html) . "\n";
echo "HAS history-table-body: " . (strpos($html, 'payment-history-table-body') !== false ? 'YES' : 'NO') . "\n";
echo "HAS tab-content-history: " . (strpos($html, 'tab-content-history') !== false ? 'YES' : 'NO') . "\n";
echo "\n--- PREVIEW (Last 1000 chars) ---\n";
echo substr($html, -1000);
