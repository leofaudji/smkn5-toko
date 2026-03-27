<?php
$variations = [
    "Original" => "https://smkn5-ksp.crudworks.web.id/api/public/anggota?secret=1234567890abcdefgh",
    "With Quotes" => "https://smkn5-ksp.crudworks.web.id/api/public/anggota?secret=%221234567890abcdefgh%22",
    "As api_key" => "https://smkn5-ksp.crudworks.web.id/api/public/anggota?api_key=1234567890abcdefgh",
    "As token" => "https://smkn5-ksp.crudworks.web.id/api/public/anggota?token=1234567890abcdefgh"
];

foreach ($variations as $name => $url) {
    echo "--- Testing $name: $url ---\n";
    $options = [
        "http" => [
            "method" => "GET",
            "header" => ["User-Agent: PHP", "X-SPA-REQUEST: true"],
            "ignore_errors" => true
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    echo "Body: $response\n\n";
}
?>
