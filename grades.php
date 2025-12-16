<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اتصال به دیتابیس
$host = 'localhost';
$dbname = 'student';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(Exception $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// بررسی وجود ستون date_update
$check_column = $pdo->prepare("SHOW COLUMNS FROM studen LIKE 'date_update'");
$check_column->execute();
if (!$check_column->fetch()) {
    $pdo->exec("ALTER TABLE studen ADD COLUMN date_update DATETIME NULL");
}

// گرفتن id دانش‌آموز
if (!isset($_GET['id'])) die("کاربر مشخص نشده است.");
$user_id = (int)$_GET['id'];

// مشخصات دانش‌آموز
$stmt = $pdo->prepare("SELECT id, f_name, l_name FROM stude WHERE id=?");
$stmt->execute([$user_id]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$userData) die("دانش‌آموز یافت نشد.");

// متغیرها
$lessons = ['فارسی','ریاضی','قرآن','دینی','تاریخ','هنر','ورزش'];
$message = '';
$edit_mode = false;
$edit_data = null;

// گرفتن نمرات
$stmt_scores = $pdo->prepare("SELECT id, name_dars, score, date_time, date_update FROM studen WHERE user_id=? ORDER BY id DESC");
$stmt_scores->execute([$user_id]);
$scores = $stmt_scores->fetchAll(PDO::FETCH_ASSOC);

// حالت ویرایش
if (isset($_GET['edit']) && !empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt_edit = $pdo->prepare("SELECT id, name_dars, score FROM studen WHERE id=? AND user_id=?");
    $stmt_edit->execute([(int)$_GET['edit'], $user_id]);
    $edit_data = $stmt_edit->fetch(PDO::FETCH_ASSOC);
    if ($edit_data) $edit_mode = true;
}

// پردازش فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        // ویرایش
        if (isset($_POST['score']) && is_numeric($_POST['score'])) {
            $score = (int)$_POST['score'];
            $edit_id = (int)$_POST['edit_id'];
            
            if ($score < 0 || $score > 20) {
                $message = "نمره باید بین 0 تا 20 باشد.";
            } else {
                $current_datetime = date('Y-m-d H:i:s');
                $stmtUpdate = $pdo->prepare("UPDATE studen SET score=?, date_update=? WHERE id=? AND user_id=?");
                $stmtUpdate->execute([$score, $current_datetime, $edit_id, $user_id]);
                
                if ($stmtUpdate->rowCount() > 0) {
                    header("Location: ?id=" . $user_id . "&success=edited");
                    exit();
                } else {
                    $message = "خطا در ویرایش نمره!";
                }
            }
        }
    } else {
        // ثبت جدید
        if (isset($_POST['score'], $_POST['name_dars']) && is_numeric($_POST['score'])) {
            $score = (int)$_POST['score'];
            $name_dars = trim($_POST['name_dars']);
            
            if ($score < 0 || $score > 20) {
                $message = "نمره باید بین 0 تا 20 باشد.";
            } elseif (empty($name_dars)) {
                $message = "لطفاً نام درس را وارد کنید.";
            } else {
                $current_datetime = date('Y-m-d H:i:s');
                
                $stmtCheck = $pdo->prepare("SELECT id FROM studen WHERE user_id=? AND name_dars=?");
                $stmtCheck->execute([$user_id, $name_dars]);
                $exists = $stmtCheck->fetch();

                if ($exists) {
                    $stmtUpdate = $pdo->prepare("UPDATE studen SET score=?, date_update=? WHERE id=?");
                    $stmtUpdate->execute([$score, $current_datetime, $exists['id']]);
                    $message = "نمره برای درس {$name_dars} با موفقیت به‌روزرسانی شد ✅";
                } else {
                    $stmtInsert = $pdo->prepare("INSERT INTO studen (user_id, name_dars, score, date_time) VALUES (?,?,?,?)");
                    $stmtInsert->execute([$user_id, $name_dars, $score, $current_datetime]);
                    $message = "نمره برای درس {$name_dars} با موفقیت ثبت شد ✅";
                }
            }
        }
    }
}

// پیام موفقیت
if (isset($_GET['success']) && $_GET['success'] == 'edited') {
    $message = "نمره با موفقیت ویرایش شد ✅";
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت و ویرایش نمرات</title>
    <style>
        /* استایل اصلی */
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --radius: 12px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazirmatn', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        /* هدر */
        .header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-report {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-report:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        /* بقیه استایل‌ها... */
        .user-card {
            padding: 30px;
            background: white;
            border-bottom: 1px solid #eee;
        }
        
        .user-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: var(--radius);
            border-right: 5px solid var(--primary);
        }
        
        .user-info h3 {
            color: var(--secondary);
            font-size: 22px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-info p {
            color: var(--dark);
            font-size: 16px;
        }
        
        .form-section {
            padding: 30px;
        }
        
        .form-section h2 {
            color: var(--secondary);
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .grade-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: var(--radius);
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        input[type="text"],
        input[type="number"],
        input[list] {
            width: 100%;
            padding: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            transition: var(--transition);
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .lesson-display {
            background: white;
            padding: 14px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            flex: 1;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-submit {
            background: var(--gradient);
            color: white;
            padding: 16px 30px;
            border: none;
            border-radius: 8px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 14px 25px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: var(--transition);
            flex: 0.5;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-right: 5px solid #10b981;
        }
        
        .error {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-right: 5px solid #ef4444;
        }
        
        .scores-section {
            padding: 30px;
        }
        
        .scores-section h2 {
            color: var(--secondary);
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .scores-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .scores-table th {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 18px 20px;
            text-align: right;
            font-weight: 600;
            font-size: 15px;
        }
        
        .scores-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            background: white;
            transition: var(--transition);
        }
        
        .scores-table tr:hover td {
            background: #f8fafc;
        }
        
        .score-badge {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            min-width: 45px;
            text-align: center;
        }
        
        .datetime-cell {
            font-family: monospace;
            font-size: 14px;
            color: #6c757d;
        }
        
        .datetime-cell.updated {
            color: #059669;
        }
        
        .datetime-cell small {
            display: block;
            font-size: 12px;
            color: #9ca3af;
            margin-top: 3px;
        }
        
        .no-edit {
            color: #9ca3af;
            font-style: italic;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-block;
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .user-card, .form-section, .scores-section {
                padding: 20px;
            }
            
            .grade-form {
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-cancel {
                flex: 1;
            }
            
            .scores-table th,
            .scores-table td {
                padding: 12px 10px;
                font-size: 14px;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- هدر با دکمه گزارش -->
        <div class="header">
            <h1>📝 سیستم ثبت نمرات</h1>
            <div class="nav-buttons">
                <a href="time_report.php" class="btn btn-report">
                    📊 گزارش زمانی ویرایش‌ها
                </a>
            </div>
        </div>
        
        <div class="user-card">
            <div class="user-info">
                <h3>👨‍🎓 دانش‌آموز: <?php echo htmlspecialchars($userData['f_name'] . ' ' . $userData['l_name']); ?></h3>
                <p>کد دانش‌آموزی: <?php echo $user_id; ?></p>
            </div>
        </div>
        
        <div class="form-section">
            <h2><?php echo $edit_mode ? '✏️ ویرایش نمره' : '➕ ثبت نمره جدید'; ?></h2>
            
            <form method="post" class="grade-form">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <div class="form-group">
                        <label>درس:</label>
                        <div class="lesson-display">
                            <?php echo htmlspecialchars($edit_data['name_dars']); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="score">نمره جدید:</label>
                        <input type="number" id="score" name="score" min="0" max="20" 
                               value="<?php echo $edit_data['score']; ?>" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <span>💾 ذخیره تغییرات</span>
                        </button>
                        <a href="?id=<?php echo $user_id; ?>" class="btn-cancel">
                            ❌ لغو ویرایش
                        </a>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="name_dars">نام درس:</label>
                        <input list="lessons" id="name_dars" name="name_dars" 
                               placeholder="انتخاب یا تایپ درس" required>
                        <datalist id="lessons">
                            <?php foreach($lessons as $lesson): ?>
                                <option value="<?php echo $lesson; ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label for="score">نمره:</label>
                        <input type="number" id="score" name="score" min="0" max="20" 
                               placeholder="بین 0 تا 20" required>
                    </div>
                    <button type="submit" class="btn-submit">
                        <span>✅ ثبت نمره</span>
                    </button>
                <?php endif; ?>
            </form>
            
            <?php if($message): ?>
                <div class="message <?php echo strpos($message,'موفقیت')!==false || strpos($message,'ویرایش')!==false ? 'success':'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($scores)): ?>
            <div class="scores-section">
                <h2>📊 نمرات ثبت شده</h2>
                <div class="table-container">
                    <table class="scores-table">
                        <thead>
                            <tr>
                                <th>ردیف</th>
                                <th>نام درس</th>
                                <th>نمره</th>
                                <th>تاریخ ثبت</th>
                                <th>آخرین ویرایش</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($scores as $index => $score): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($score['name_dars']); ?></td>
                                <td><span class="score-badge"><?php echo $score['score']; ?></span></td>
                                <td>
                                    <div class="datetime-cell">
                                        <?php echo date('Y/m/d H:i', strtotime($score['date_time'])); ?>
                                        <small>اولین ثبت</small>
                                    </div>
                                </td>
                                <td>
                                    <?php if(!empty($score['date_update'])): ?>
                                        <div class="datetime-cell updated">
                                            <?php echo date('Y/m/d H:i', strtotime($score['date_update'])); ?>
                                            <small>آخرین ویرایش</small>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-edit">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?id=<?php echo $user_id; ?>&edit=<?php echo $score['id']; ?>" 
                                       class="btn-edit">
                                        ✏️ ویرایش
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div style="color:#6c757d; text-align:center; padding:40px; background:#f8f9fa; margin:30px; border-radius:var(--radius);">
                <div style="font-size: 60px; margin-bottom: 20px;">📝</div>
                <h3 style="color:#4361ee; margin-bottom:10px;">هنوز نمره‌ای ثبت نشده است</h3>
                <p>اولین نمره را برای این دانش‌آموز ثبت کنید.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
