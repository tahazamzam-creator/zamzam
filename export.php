<?php
include 'conn.php';

$conn = connect("localhost", "root", "", "save");

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data_export.csv');

$output = fopen("php://output", "w");

// عنوان ستون‌ها
fputcsv($output, ['first_name', 'last_name', 'father_name', 'national_code', 'user_name', 'password_hash']);

$result = mysqli_query($conn, "SELECT * FROM save_db2");

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
