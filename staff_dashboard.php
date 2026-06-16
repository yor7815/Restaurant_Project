<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>紅番茄餐廳 - 服務員首頁</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #ef4444;
            --hover-red: #dc2626;
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Noto Sans TC', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
        }

        .brand {
            position: absolute;
            top: 30px;
            left: 30px;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-red);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand::before {
            content: "🍅";
        }

        .dashboard-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            padding: 50px 40px;
            text-align: center;
            width: 100%;
            max-width: 480px;
            box-sizing: border-box;
        }

        h1 {
            font-size: 1.8rem;
            margin-top: 0;
            margin-bottom: 8px;
        }

        .welcome-text {
            color: #6b7280;
            font-size: 1.05rem;
            margin-bottom: 35px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-primary {
            background-color: #f59e0b;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2);
            margin-bottom: 16px;
        }

        .btn-primary:hover {
            background-color: #d97706;
            transform: translateY(-1px);
        }

        .btn-logout {
            background-color: #f3f4f6;
            color: #4b5563;
            font-size: 0.95rem;
            padding: 10px;
        }

        .btn-logout:hover {
            background-color: #e5e7eb;
            color: #1f2937;
        }
    </style>
</head>
<body>

<div class="brand">紅番茄</div>

<div class="dashboard-card">
    <h1>服務員專區</h1>
    <div class="welcome-text">外場服務同仁 <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>，您好！</div>
    
    <a href="create_record.php" class="btn btn-primary">🍽️ 建立用餐紀錄</a>
    <a href="logout.php" class="btn btn-logout">登出系統</a>
</div>

</body>
</html>
