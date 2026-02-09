<?php

/**
 * Mengirim notifikasi push ke semua anggota yang berlangganan.
 * @param string $title Judul notifikasi.
 * @param string $body Isi pesan notifikasi.
 * @param string $url URL yang akan dibuka saat notifikasi diklik.
 * @param string|null &$error_msg Variabel referensi untuk menyimpan pesan error jika gagal.
 * @return bool True jika berhasil, false jika gagal.
 */
function send_push_notification_to_all($title, $body, $url, &$error_msg = null): bool {
    $app_id = trim(get_setting('onesignal_app_id'));
    $api_key = trim(get_setting('onesignal_rest_api_key'));

    if (empty($app_id) || empty($api_key)) {
        $error_msg = "OneSignal App ID atau REST API Key belum diatur di settings.";
        log_push_notification($title, $body, $url, 'failed', $error_msg);
        return false;
    }

    // Helper untuk memastikan URL absolut (http://...)
    $ensure_absolute_url = function($path) {
        if (preg_match("~^(?:f|ht)tps?://~i", $path)) return $path;
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . "://" . $host . $path;
    };

    $fields = [
        'app_id' => $app_id,
        'included_segments' => ['Subscribed Users'], // Kirim ke semua yang subscribe
        'headings' => ['en' => $title],
        'contents' => ['en' => $body],
        'web_url' => $ensure_absolute_url(base_url($url)),
        'chrome_web_icon' => $ensure_absolute_url(base_url(get_setting('app_logo', 'assets/img/icon-192.png'))),
        'firefox_icon' => $ensure_absolute_url(base_url(get_setting('app_logo', 'assets/img/icon-192.png'))),
    ];
    
    $fields = json_encode($fields);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error_msg = 'Curl Error: ' . curl_error($ch);
        curl_close($ch);
        log_push_notification($title, $body, $url, 'failed', $error_msg);
        return false;
    }
    
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (isset($result['errors'])) {
        $error_msg = 'OneSignal Error: ' . (is_array($result['errors']) ? implode(', ', $result['errors']) : json_encode($result['errors']));
        log_push_notification($title, $body, $url, 'failed', $error_msg);
        return false;
    }
    
    $success = isset($result['id']) && !empty($result['id']);
    log_push_notification($title, $body, $url, $success ? 'success' : 'failed', $success ? null : 'Unknown error');
    
    return $success;
}

function log_push_notification($title, $body, $url, $status, $error_message = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $user_id = $_SESSION['user_id'] ?? null;
        $stmt = $db->prepare("INSERT INTO ksp_notification_logs (title, body, url, status, error_message, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $body, $url, $status, $error_message, $user_id);
        $stmt->execute();
    } catch (Exception $e) {
        // Silent fail for logging to avoid breaking the main flow
        error_log("Failed to log notification: " . $e->getMessage());
    }
}