<?php
$file = 'd:/laragon/www/smkn5-toko/assets/js/penjualan.js';
$content = file_get_contents($file);
$old = "    // Muat data saat halaman pertama kali dibuka\n    loadPenjualan();\n}";
$new = "    // Muat data saat halaman pertama kali dibuka\n    loadPenjualan(1, false);\n    setupInfiniteScroll();\n}";

// Juga coba versi CRLF
$old_crlf = "    // Muat data saat halaman pertama kali dibuka\r\n    loadPenjualan();\r\n}";

if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents($file, $content);
    echo "Fixed with LF";
} elseif (strpos($content, $old_crlf) !== false) {
    $content = str_replace($old_crlf, $new, $content);
    file_put_contents($file, $content);
    echo "Fixed with CRLF";
} else {
    // Brute force replace just the function call near the end
    $content = preg_replace('/loadPenjualan\(\);(?:\s+)\}$+/', "loadPenjualan(1, false);\n    setupInfiniteScroll();\n}", $content);
    file_put_contents($file, $content);
    echo "Fixed with Regex fallback";
}
