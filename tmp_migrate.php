<?php
$conn = mysqli_connect('localhost', 'root', '', 'database_keuangan');
if (!$conn) die("Connection failed: " . mysqli_connect_error());
$sql = "ALTER TABLE items ADD expired_date DATE NULL DEFAULT NULL AFTER stok";
if (mysqli_query($conn, $sql)) {
    echo "Success: Column 'expired_date' added.";
} else {
    echo "Error: " . mysqli_error($conn);
}
mysqli_close($conn);
