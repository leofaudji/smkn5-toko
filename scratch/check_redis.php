<?php
header('Content-Type: application/json');
echo json_encode([
    'redis_extension' => extension_loaded('redis'),
    'redis_class_exists' => class_exists('Redis')
]);
