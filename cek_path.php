<?php
echo "<h1>Status Vhost</h1>";
echo "<b>Domain yang diakses:</b> " . $_SERVER['HTTP_HOST'] . "<br>";
echo "<b>Document Root Aktif:</b> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "<b>Lokasi File Real:</b> " . __DIR__;
echo "<br><br><b>Diagnosa:</b> Jika 'Domain yang diakses' tertulis <b>localhost</b> atau <b>127.0.0.1</b>, berarti Cloudflare Tunnel belum mengirimkan Host Header yang benar.";
?>