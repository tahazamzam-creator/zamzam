<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اتصال به دیتابیس
$conn = new mysqli("localhost", "root", "", "student");
if ($conn->connect_error) {
    die("❌ خطا در اتصال به دیتابیس: " . $conn->connect_error);
}

// گرفتن کاربران
$sql = "SELECT id, f_name, l_name, fa_name, username FROM stude ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لیست کاربران</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600&display=swap');

body {
    background: linear-gradient(135deg, #5b92b0, #2f6f85);
    font-family: 'Vazirmatn', Tahoma, sans-serif;
    margin: 0;
    padding: 50px;
    color: #fff;
    text-align: center;
}

h2 {
    margin-bottom: 25px;
}

.table-container {
    background: #222;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    width: 80%;
    max-width: 900px;
    margin: auto;
    overflow-x: auto;
    animation: fadeIn 0.7s ease;
}

@keyframes fadeIn {
  from {opacity: 0; transform: translateY(20px);}
  to {opacity: 1; transform: translateY(0);}
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
}

th {
    background-color: #4a7cff;
    color: #fff;
    padding: 12px;
    text-align: center;
}

td {
    border-bottom: 1px solid #555;
    padding: 10px;
    text-align: center;
}

tr:nth-child(even) { background-color: #333; }
tr:hover { background-color: #4a7cff33; transition:0.2s; }

a { color:#fff; text-decoration:none; font-weight:700; }
a:hover { text-decoration:underline; }

.no-data { color: #ccc; padding: 20px; font-weight: 600; }
</style>
</head>
<body>

<h2>📋 لیست کاربران ثبت‌شده</h2>

<div class="table-container">
<?php
if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr>
            <th>ردیف</th>
            <th>نام</th>
            <th>نام خانوادگی</th>
            <th>نام پدر</th>
            <th>نام کاربری</th>
          </tr>";

    $i = 1;
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $i++ . "</td>";
        echo "<td>" . htmlspecialchars($row['f_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['l_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['fa_name']) . "</td>";
        // لینک به صفحه نمره گذاری با id
        echo '<td><a href="grades.php?id=' . $row['id'] . '">' . htmlspecialchars($row['username']) . '</a></td>';
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='no-data'>هیچ کاربری ثبت نشده است ❌</div>";
}
$conn->close();
?>
</div>

</body>
</html>
