<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../includes/bootstrap.php';

echo "<h2>Redis Debugger</h2>";

// 1. Cek Ekstensi
if (!class_exists('Redis')) {
    echo "<p style='color:red'>❌ ERROR: Ekstensi PHP Redis (php-redis) tidak terdeteksi di server ini.</p>";
    echo "<p>Solusi: Pastikan 'extension=redis' aktif di php.ini.</p>";
    die();
} else {
    echo "<p style='color:green'>✅ Ekstensi PHP Redis terdeteksi.</p>";
}

// 2. Cek Koneksi melalui RedisManager
$redisManager = RedisManager::getInstance();
if (!$redisManager->isAvailable()) {
    echo "<p style='color:red'>❌ ERROR: RedisManager gagal terhubung ke server Redis.</p>";
    echo "<h4>Konfigurasi saat ini:</h4>";
    echo "<ul>";
    echo "<li>HOST: " . (Config::get('REDIS_HOST') ?: '127.0.0.1') . "</li>";
    echo "<li>PORT: " . (Config::get('REDIS_PORT') ?: 6379) . "</li>";
    echo "</ul>";
    echo "<p>Periksa kembali file .env Anda.</p>";
} else {
    echo "<p style='color:green'>✅ RedisManager BERHASIL terhubung ke server Redis.</p>";
    
    // 3. Tes Write/Read
    $test_key = "test_debug_" . time();
    $test_val = ["time" => date('Y-m-d H:i:s'), "message" => "Hello Redis"];
    
    $redisManager->set($test_key, $test_val, 60);
    $retrieved = $redisManager->get($test_key);
    
    if ($retrieved && $retrieved['message'] === "Hello Redis") {
        echo "<p style='color:green'>✅ TES WRITE/READ BERHASIL!</p>";
        echo "<pre>";
        print_r($retrieved);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>❌ ERROR: Gagal membaca data yang baru saja disimpan.</p>";
    }
}
