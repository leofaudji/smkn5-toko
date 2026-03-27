<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

// Session check can be added here if needed, but changelog is usually public/all-user
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$changelog_path = PROJECT_ROOT . '/CHANGELOG.md';

if (!file_exists($changelog_path)) {
    echo json_encode([]);
    exit;
}

$content = file_get_contents($changelog_path);
$lines = explode("\n", $content);

$changelog = [];
$current_version = null;
$current_category = null;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    // Detect version: ## [1.2.0] - 2026-03-27
    if (preg_match('/^##\s+\[(.*?)\]\s+-\s+(.*)/', $line, $matches)) {
        if ($current_version) {
            $changelog[] = $current_version;
        }
        $current_version = [
            'version' => $matches[1],
            'date' => $matches[2],
            'categories' => []
        ];
        $current_category = null;
    } 
    // Detect category: ### ADD
    elseif (preg_match('/^###\s+(.*)/', $line, $matches)) {
        $category_name = strtoupper(trim($matches[1]));
        $current_category = $category_name;
        if (!isset($current_version['categories'][$current_category])) {
            $current_version['categories'][$current_category] = [];
        }
    }
    // Detect list item: - Added something
    elseif (preg_match('/^-\s+(.*)/', $line, $matches) && $current_version && $current_category) {
        $current_version['categories'][$current_category][] = $matches[1];
    }
}

if ($current_version) {
    $changelog[] = $current_version;
}

// Reformat to array of categories for easier rendering if needed, 
// but current structure is fine: {version, date, categories: {ADD: [], FIX: []}}

echo json_encode($changelog);
