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

// تابع تبدیل میلادی به شمسی
function gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    if($gy > 1600) {
        $jy = 979;
        $gy -= 1600;
    } else {
        $jy = 0;
        $gy -= 621;
    }
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * ((int)($days / 12053));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return array($jy, $jm, $jd);
}

// تابع تبدیل تاریخ میلادی به شمسی با فرمت
function date_to_jalali($date, $format = 'Y/m/d H:i') {
    $date_time = new DateTime($date);
    $year = (int)$date_time->format('Y');
    $month = (int)$date_time->format('m');
    $day = (int)$date_time->format('d');
    $hour = $date_time->format('H');
    $minute = $date_time->format('i');
    
    list($jy, $jm, $jd) = gregorian_to_jalali($year, $month, $day);
    
    // نام ماه‌های شمسی
    $jalali_months = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
    ];
    
    $replacements = [
        'Y' => str_pad($jy, 4, '0', STR_PAD_LEFT),
        'm' => str_pad($jm, 2, '0', STR_PAD_LEFT),
        'd' => str_pad($jd, 2, '0', STR_PAD_LEFT),
        'H' => $hour,
        'i' => $minute,
        'M' => $jalali_months[$jm] ?? '',
        'F' => $jalali_months[$jm] ?? '',
    ];
    
    $result = $format;
    foreach ($replacements as $key => $value) {
        $result = str_replace($key, $value, $result);
    }
    
    return $result;
}

// تبدیل تاریخ شمسی به میلادی برای جستجو
function jalali_to_gregorian($jy, $jm, $jd) {
    if($jy > 979) {
        $gy = 1600;
        $jy -= 979;
    } else {
        $gy = 621;
    }
    $days = (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + 78 + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy += 400 * ((int)($days / 146097));
    $days %= 146097;
    if($days > 36524) {
        $gy += 100 * ((int)(--$days / 36524));
        $days %= 36524;
        if($days >= 365) $days++;
    }
    $gy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if($days > 365) {
        $gy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $sal_a = array(0, 31, (($gy % 4 == 0 and $gy % 100 != 0) or ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    for($gm = 0; $gm < 13 and $gd > $sal_a[$gm]; $gm++) $gd -= $sal_a[$gm];
    return array($gy, $gm, $gd);
}

// --- درست کردن تاریخ پیش‌فرض ---
// تاریخ امروز میلادی
$today_gregorian = date('Y-m-d');
// تاریخ 7 روز پیش میلادی
$seven_days_ago_gregorian = date('Y-m-d', strtotime('-7 days'));

// تبدیل به شمسی
$today_jalali = date_to_jalali($today_gregorian, 'Y-m-d');
$seven_days_ago = date_to_jalali($seven_days_ago_gregorian, 'Y-m-d');

// --- دیباگ: ببینیم چه تاریخ‌هایی تولید میشه ---
// echo "امروز میلادی: $today_gregorian<br>";
// echo "امروز شمسی: $today_jalali<br>";
// echo "7 روز قبل میلادی: $seven_days_ago_gregorian<br>";
// echo "7 روز قبل شمسی: $seven_days_ago<br>";

// گرفتن تاریخ از GET (با سیستم جدید تقویم شمسی)
if (isset($_GET['search'])) {
    // اگر فیلدهای میلادی ارسال شده‌اند (از تقویم جدید)
    if (isset($_GET['start_date_gregorian'])) {
        // تبدیل میلادی به شمسی برای نمایش
        list($gy, $gm, $gd) = explode('-', $_GET['start_date_gregorian']);
        list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);
        $start_date = sprintf('%04d-%02d-%02d', $jy, $jm, $jd);
    } else if (isset($_GET['start_date'])) {
        // حالت قدیمی
        $start_date = $_GET['start_date'];
    } else {
        $start_date = $seven_days_ago;
    }
    
    if (isset($_GET['end_date_gregorian'])) {
        // تبدیل میلادی به شمسی برای نمایش
        list($gy, $gm, $gd) = explode('-', $_GET['end_date_gregorian']);
        list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);
        $end_date = sprintf('%04d-%02d-%02d', $jy, $jm, $jd);
    } else if (isset($_GET['end_date'])) {
        // حالت قدیمی
        $end_date = $_GET['end_date'];
    } else {
        $end_date = $today_jalali;
    }
} else {
    // حالت پیش‌فرض
    $start_date = $seven_days_ago;
    $end_date = $today_jalali;
}

// --- تازه: چک کنیم که سال‌ها درست باشن ---
// اگر سال کمتر از 1300 بود، یعنی تبدیل درست نیست
list($start_year, $start_month, $start_day) = explode('-', $start_date);
list($end_year, $end_month, $end_day) = explode('-', $end_date);

// اگر سال‌ها کوچک‌تر از 1300 بودن، بیایم یه تاریخ شمسی درست تعریف کنیم
// مثلاً امروز شمسی رو بصورت دستی حساب کنیم
// تاریخ امروز: فرض کنیم 1403/10/15 باشه
// برای تست: تاریخ امروز شمسی رو دستی میزاریم

// اگر میخوای تاریخ دقیق امروز رو بگیری، میتونی از این تابع استفاده کنی:
function get_current_jalali_date() {
    // تاریخ امروز میلادی
    $today = date('Y-m-d');
    // تبدیل به شمسی
    return date_to_jalali($today, 'Y-m-d');
}

function get_jalali_date_7days_ago() {
    // تاریخ 7 روز قبل میلادی
    $seven_days_ago = date('Y-m-d', strtotime('-7 days'));
    // تبدیل به شمسی
    return date_to_jalali($seven_days_ago, 'Y-m-d');
}

// تاریخ‌های درست رو حساب کنیم
$correct_today_jalali = get_current_jalali_date();
$correct_7days_ago = get_jalali_date_7days_ago();

// اگر سال اولی کمتر از 1300 بود، از تاریخ‌های درست استفاده کنیم
if ($start_year < 1300) {
    $start_date = $correct_7days_ago;
}
if ($end_year < 1300) {
    $end_date = $correct_today_jalali;
}

// دیباگ: ببینیم چه تاریخ‌هایی داریم
// echo "start_date: $start_date<br>";
// echo "end_date: $end_date<br>";

$search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';

// گرفتن اطلاعات ویرایش‌ها در بازه زمانی
$results = [];
if (isset($_GET['search'])) {
    // تبدیل تاریخ شمسی به میلادی برای جستجو در دیتابیس
    list($start_year, $start_month, $start_day) = explode('-', $start_date);
    list($end_year, $end_month, $end_day) = explode('-', $end_date);
    
    $start_gregorian = jalali_to_gregorian($start_year, $start_month, $start_day);
    $end_gregorian = jalali_to_gregorian($end_year, $end_month, $end_day);
    
    $start_datetime = sprintf('%04d-%02d-%02d 00:00:00', $start_gregorian[0], $start_gregorian[1], $start_gregorian[2]);
    $end_datetime = sprintf('%04d-%02d-%02d 23:59:59', $end_gregorian[0], $end_gregorian[1], $end_gregorian[2]);
    
    if ($search_type == 'update' || $search_type == 'all') {
        // ویرایش‌ها
        $sql_update = "SELECT 
            s.id, s.user_id, s.name_dars, s.score, s.date_update,
            u.f_name, u.l_name
            FROM studen s
            JOIN stude u ON s.user_id = u.id
            WHERE s.date_update BETWEEN ? AND ?
            ORDER BY s.date_update DESC";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$start_datetime, $end_datetime]);
        $updates = $stmt_update->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($updates as $update) {
            $update['type'] = 'ویرایش';
            $update['date'] = $update['date_update'];
            $results[] = $update;
        }
    }
    
    if ($search_type == 'create' || $search_type == 'all') {
        // ثبت‌های جدید
        $sql_create = "SELECT 
            s.id, s.user_id, s.name_dars, s.score, s.date_time as date,
            u.f_name, u.l_name
            FROM studen s
            JOIN stude u ON s.user_id = u.id
            WHERE s.date_time BETWEEN ? AND ?
            ORDER BY s.date_time DESC";
        
        $stmt_create = $pdo->prepare($sql_create);
        $stmt_create->execute([$start_datetime, $end_datetime]);
        $creates = $stmt_create->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($creates as $create) {
            $create['type'] = 'ثبت جدید';
            $results[] = $create;
        }
    }
    
    // مرتب‌سازی بر اساس تاریخ
    usort($results, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// آمار کلی
$stats = [
    'total' => count($results),
    'updates' => 0,
    'creates' => 0
];

foreach ($results as $item) {
    if ($item['type'] == 'ویرایش') {
        $stats['updates']++;
    } else {
        $stats['creates']++;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 گزارش زمانی ویرایش‌ها</title>
    <!-- اضافه کردن Persian Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gradient: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --radius: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            padding: 30px;
            color: var(--dark);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            animation: fadeIn 0.8s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            background: linear-gradient(to right, #ffffff, #f8f9fa);
            border-left: 5px solid var(--primary);
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 32px;
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
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        
        .filter-card {
            background: white;
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .filter-card h2 {
            color: var(--secondary);
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: var(--transition);
            background: #f8f9fa;
            font-family: 'Vazirmatn', sans-serif;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            background: white;
        }
        
        /* استایل مخصوص input تاریخ */
        .date-input {
            cursor: pointer;
            background: white url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%234361ee" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>') no-repeat left 15px center;
            background-size: 20px;
            padding-left: 45px;
        }
        
        .radio-group {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 12px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .radio-label:hover {
            background: #e9ecef;
        }
        
        .radio-label.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .radio-label input {
            display: none;
        }
        
        .btn-search {
            grid-column: 1 / -1;
            background: var(--gradient);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            animation: slideUp 0.6s ease 0.2s backwards;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }
        
        .stat-icon.total { background: linear-gradient(135deg, #4361ee, #3a0ca3); }
        .stat-icon.update { background: linear-gradient(135deg, #4cc9f0, #4895ef); }
        .stat-icon.create { background: linear-gradient(135deg, #f72585, #b5179e); }
        
        .stat-info h3 {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 8px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .results-section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            animation: slideUp 0.6s ease 0.4s backwards;
        }
        
        .results-header {
            padding: 25px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-header h2 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .results-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 15px;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
            padding: 20px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .results-table th {
            background: #f8f9fa;
            padding: 18px 20px;
            text-align: right;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #e0e0e0;
            font-size: 15px;
        }
        
        .results-table td {
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            transition: var(--transition);
        }
        
        .results-table tr:hover td {
            background: #f8fafc;
        }
        
        .type-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .type-update {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .type-create {
            background: #dcfce7;
            color: #166534;
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
        
        .student-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .datetime-cell {
            font-family: 'Vazirmatn', monospace;
            font-size: 14px;
            color: var(--gray);
            direction: ltr;
            text-align: right;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 30px;
            color: var(--gray);
        }
        
        .no-results i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #e0e0e0;
        }
        
        .no-results h3 {
            font-size: 22px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            
            .header h1 {
                font-size: 26px;
            }
            
            .filter-card {
                padding: 25px;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 25px;
            }
            
            .results-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .table-container {
                padding: 10px;
            }
            
            .results-table th,
            .results-table td {
                padding: 12px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span>📊</span>
                گزارش زمانی ویرایش‌های نمرات
            </h1>
            <div class="nav-buttons">
                <a href="grades.php?id=1" class="btn btn-outline">
                    ← بازگشت به ثبت نمرات
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    🖨️ چاپ گزارش
                </button>
            </div>
        </div>
        
        <div class="filter-card">
            <h2><span>🔍</span> فیلتر جستجو</h2>
            <form method="GET" class="filter-form" id="searchForm">
                <!-- فیلدهای مخفی برای ذخیره تاریخ میلادی -->
                <input type="hidden" id="start_date_gregorian" name="start_date_gregorian" 
                       value="<?php 
                           list($y, $m, $d) = explode('-', $start_date);
                           list($gy, $gm, $gd) = jalali_to_gregorian($y, $m, $d);
                           echo sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                       ?>">
                <input type="hidden" id="end_date_gregorian" name="end_date_gregorian" 
                       value="<?php 
                           list($y, $m, $d) = explode('-', $end_date);
                           list($gy, $gm, $gd) = jalali_to_gregorian($y, $m, $d);
                           echo sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                       ?>">
                
                <div class="form-group">
                    <label for="start_date_display"><span>📅</span> تاریخ شروع (شمسی):</label>
                    <input type="text" id="start_date_display" 
                           value="<?php echo $start_date; ?>" 
                           class="form-control date-input" 
                           placeholder="برای انتخاب تاریخ کلیک کنید" 
                           readonly>
                    <small style="color: var(--gray);">روی کادر کلیک کنید تا تقویم باز شود</small>
                </div>
                
                <div class="form-group">
                    <label for="end_date_display"><span>📅</span> تاریخ پایان (شمسی):</label>
                    <input type="text" id="end_date_display" 
                           value="<?php echo $end_date; ?>" 
                           class="form-control date-input" 
                           placeholder="برای انتخاب تاریخ کلیک کنید" 
                           readonly>
                    <small style="color: var(--gray);">روی کادر کلیک کنید تا تقویم باز شود</small>
                </div>
                
                <div class="form-group">
                    <label><span>📋</span> نوع رویداد:</label>
                    <div class="radio-group">
                        <label class="radio-label <?php echo $search_type == 'all' ? 'active' : ''; ?>">
                            <input type="radio" name="search_type" value="all" 
                                   <?php echo $search_type == 'all' ? 'checked' : ''; ?>>
                            <span>📊 همه رویدادها</span>
                        </label>
                        <label class="radio-label <?php echo $search_type == 'update' ? 'active' : ''; ?>">
                            <input type="radio" name="search_type" value="update" 
                                   <?php echo $search_type == 'update' ? 'checked' : ''; ?>>
                            <span>✏️ فقط ویرایش‌ها</span>
                        </label>
                        <label class="radio-label <?php echo $search_type == 'create' ? 'active' : ''; ?>">
                            <input type="radio" name="search_type" value="create" 
                                   <?php echo $search_type == 'create' ? 'checked' : ''; ?>>
                            <span>➕ فقط ثبت‌های جدید</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" name="search" class="btn-search">
                    <span>🔎</span>
                    جستجو در بازه زمانی
                </button>
            </form>
        </div>
        
        <?php if (isset($_GET['search'])): ?>
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon total">
                    📈
                </div>
                <div class="stat-info">
                    <h3>تعداد کل رویدادها</h3>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon update">
                    ✏️
                </div>
                <div class="stat-info">
                    <h3>ویرایش‌ها</h3>
                    <div class="stat-number"><?php echo $stats['updates']; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon create">
                    ➕
                </div>
                <div class="stat-info">
                    <h3>ثبت‌های جدید</h3>
                    <div class="stat-number"><?php echo $stats['creates']; ?></div>
                </div>
            </div>
        </div>
        
        <div class="results-section">
            <div class="results-header">
                <h2><span>📋</span> نتایج جستجو</h2>
                <div class="results-count">
                    <?php echo count($results); ?> مورد یافت شد
                </div>
            </div>
            
            <?php if (!empty($results)): ?>
                <div class="table-container">
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>ردیف</th>
                                <th>نوع رویداد</th>
                                <th>دانش‌آموز</th>
                                <th>کد دانش‌آموزی</th>
                                <th>نام درس</th>
                                <th>نمره</th>
                                <th>تاریخ و زمان (شمسی)</th>
                                <th>جزئیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $row): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <span class="type-badge <?php echo $row['type'] == 'ویرایش' ? 'type-update' : 'type-create'; ?>">
                                        <?php echo $row['type']; ?>
                                    </span>
                                </td>
                                <td class="student-name">
                                    <?php echo htmlspecialchars($row['f_name'] . ' ' . $row['l_name']); ?>
                                </td>
                                <td><?php echo $row['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name_dars']); ?></td>
                                <td>
                                    <span class="score-badge">
                                        <?php echo $row['score']; ?>
                                    </span>
                                </td>
                                <td class="datetime-cell">
                                    <?php echo date_to_jalali($row['date'], 'Y/m/d H:i'); ?>
                                </td>
                                <td>
                                    <a href="grades.php?id=<?php echo $row['user_id']; ?>" 
                                       class="btn btn-outline" style="padding: 8px 15px; font-size: 14px;">
                                        مشاهده جزئیات
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <div>🔍</div>
                    <h3>هیچ موردی یافت نشد</h3>
                    <p>در بازه زمانی انتخاب شده هیچ رویدادی ثبت نشده است.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="no-results" style="background: white; border-radius: var(--radius); padding: 60px; text-align: center;">
            <div style="font-size: 80px; margin-bottom: 20px; color: #4361ee;">📊</div>
            <h3 style="color: #3a0ca3; margin-bottom: 15px;">گزارش زمانی ویرایش‌ها</h3>
            <p style="color: #6c757d; max-width: 600px; margin: 0 auto 30px; line-height: 1.6;">
                برای مشاهده گزارش ویرایش‌ها و ثبت‌های نمرات، لطفاً بازه زمانی مورد نظر را انتخاب کرده و دکمه جستجو را بزنید.
            </p>
            <p style="color: #f8961e; font-weight: 600;">
                ⏱️ گزارش‌دهی بر اساس تاریخ و ساعت شمسی انجام می‌شود
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- اضافه کردن اسکریپت‌های jQuery و Persian Datepicker -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    
    <script>
    // تابع تبدیل شمسی به میلادی (برای ارسال به سرور)
    function jalaliToGregorianForServer(jy, jm, jd) {
        if(jy > 979) {
            gy = 1600;
            jy -= 979;
        } else {
            gy = 621;
        }
        days = (365 * jy) + (parseInt(jy / 33) * 8) + parseInt(((jy % 33) + 3) / 4) + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        gy += 400 * parseInt(days / 146097);
        days %= 146097;
        if(days > 36524) {
            gy += 100 * parseInt(--days / 36524);
            days %= 36524;
            if(days >= 365) days++;
        }
        gy += 4 * parseInt(days / 1461);
        days %= 1461;
        if(days > 365) {
            gy += parseInt((days - 1) / 365);
            days = (days - 1) % 365;
        }
        gd = days + 1;
        sal_a = [0, 31, ((gy % 4 == 0 && gy % 100 != 0) || (gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for(gm = 0; gm < 13 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
        
        // فرمت کردن به YYYY-MM-DD
        return gy + '-' + ('0' + gm).slice(-2) + '-' + ('0' + gd).slice(-2);
    }

    $(document).ready(function() {
        // فعال‌سازی تقویم برای تاریخ شروع
        $("#start_date_display").persianDatepicker({
            format: 'YYYY-MM-DD',
            autoClose: true,
            initialValue: false,
            observer: true,
            calendar: {
                persian: {
                    locale: 'fa',
                    showHint: true
                }
            },
            onSelect: function (unixDate) {
                // تبدیل تاریخ انتخاب شده به رشته
                var selectedDate = $(this).val();
                $("#start_date_display").val(selectedDate);
                
                // تبدیل به میلادی و ذخیره در فیلد مخفی
                var parts = selectedDate.split('-');
                var gregorianDate = jalaliToGregorianForServer(
                    parseInt(parts[0]), 
                    parseInt(parts[1]), 
                    parseInt(parts[2])
                );
                $("#start_date_gregorian").val(gregorianDate);
            }
        });
        
        // فعال‌سازی تقویم برای تاریخ پایان
        $("#end_date_display").persianDatepicker({
            format: 'YYYY-MM-DD',
            autoClose: true,
            initialValue: false,
            observer: true,
            calendar: {
                persian: {
                    locale: 'fa',
                    showHint: true
                }
            },
            onSelect: function (unixDate) {
                // تبدیل تاریخ انتخاب شده به رشته
                var selectedDate = $(this).val();
                $("#end_date_display").val(selectedDate);
                
                // تبدیل به میلادی و ذخیره در فیلد مخفی
                var parts = selectedDate.split('-');
                var gregorianDate = jalaliToGregorianForServer(
                    parseInt(parts[0]), 
                    parseInt(parts[1]), 
                    parseInt(parts[2])
                );
                $("#end_date_gregorian").val(gregorianDate);
            }
        });
        
        // فعال‌سازی radio buttons
        $('.radio-label').click(function() {
            $('.radio-label').removeClass('active');
            $(this).addClass('active');
            $(this).find('input').prop('checked', true);
        });
        
        // اعتبارسنجی فرم
        $("#searchForm").submit(function(e) {
            var startDate = $("#start_date_display").val();
            var endDate = $("#end_date_display").val();
            
            if (!startDate || !endDate) {
                e.preventDefault();
                alert("⚠️ لطفاً هر دو تاریخ را انتخاب کنید.");
                return false;
            }
            
            // تبدیل تاریخ‌ها به عدد برای مقایسه
            var startNum = parseInt(startDate.replace(/-/g, ''));
            var endNum = parseInt(endDate.replace(/-/g, ''));
            
            if (startNum > endNum) {
                e.preventDefault();
                alert("⚠️ تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد!");
                return false;
            }
        });
        
        // نمایش تاریخ امروز
        var jalaliToday = "<?php echo $end_date; ?>";
        $("#end_date_display").attr('placeholder', 'امروز: ' + jalaliToday);
        
        // تنظیم محدودیت‌های تاریخ در تقویم
        // می‌توانیم سال را بین 1300 تا 1500 محدود کنیم
        var currentYear = parseInt(jalaliToday.split('-')[0]);
        var minYear = 1300;
        var maxYear = currentYear + 10; // 10 سال بعد
        
        console.log("سال جاری شمسی: " + currentYear);
        console.log("محدوده سال: " + minYear + " تا " + maxYear);
    });
    </script>
</body>
</html>
