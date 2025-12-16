<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['user_name']);
    $password = trim($_POST['pas']);

    $conn = new mysqli("localhost", "root", "", "save");
    if ($conn->connect_error) {
        die("❌ خطا در اتصال به دیتابیس: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT * FROM save_db2 WHERE user_name = ? AND pas = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $_SESSION['username'] = $username;
        header("Location: welcome.php");
        exit();
    } else {
        $message = "<div class='msg error'>❌ نام کاربری یا رمز عبور اشتباه است</div>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<title>ورود کاربران</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #5b92b0, #2f6f85);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .container {
        background-color: #fff;
        padding: 40px 50px;
        border-radius: 15px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
        width: 350px;
    }
    h2 { color: #2f6f85; margin-bottom: 25px; text-align: center; }
    label { display: block; text-align: right; margin: 10px 0 5px; }
    input[type="text"], input[type="password"] {
        width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #bcd2da;
    }
    input[type="submit"] {
        width: 100%; padding: 10px; border: none; border-radius: 8px;
        background: linear-gradient(135deg, #2f6f85, #3fa0c6); color: white; cursor: pointer;
    }
    input[type="submit"]:hover { background: linear-gradient(135deg, #3fa0c6, #2f6f85); }
    .msg { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 14px; }
    .msg.error { background-color: #ffeaea; color: #a60000; border: 1px solid #f1b5b5; }
    .footer {
        margin-top: 20px;
        font-size: 13px;
        color: #5f7b83;
        text-align: center;
    }
    .footer a {
        color: #2f6f85;
        text-decoration: none;
        font-weight: bold;
    }
    .footer a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
    <h2>ورود به حساب کاربری</h2>
    <?php echo $message; ?>
    <form method="POST" action="">
        <label>نام کاربری</label>
        <input type="text" name="user_name" required>
        <label>رمز عبور</label>
        <input type="password" name="pas" required>
        <input type="submit" value="ورود">
    </form>

    <!-- لینک ثبت نام -->
    <div class="footer">
        حساب ندارید؟ <a href="form.php">ثبت نام کنید</a>
    </div>
</div>
</body>
</html>
