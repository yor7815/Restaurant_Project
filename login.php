<?php
session_start();
require_once 'db_config.php';

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account = trim($_POST['account']);
    $password = trim($_POST['password']);

    if (empty($account) || empty($password)) {
        $error_msg = "請輸入帳號與密碼。";
    } else {
        // 1. Check Manager (admin / 114514)
        if ($account === 'admin' && $password === '114514') {
            $_SESSION['role'] = 'manager';
            $_SESSION['user_id'] = 'admin';
            $_SESSION['user_name'] = '系統經理';
            header('Location: index.php');
            exit;
        }

        // 2. Check Staff (Employee ID / 123)
        if ($password === '123') {
            $stmt = $conn->prepare("SELECT Staff_ID, name FROM Staffs WHERE Staff_ID = ?");
            $stmt->bind_param("s", $account);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $staff = $res->fetch_assoc();
                $_SESSION['role'] = 'staff';
                $_SESSION['user_id'] = $staff['Staff_ID'];
                $_SESSION['user_name'] = $staff['name'];
                header('Location: staff_dashboard.php');
                exit;
            }
            $stmt->close();
        }

        // 3. Check Customer (Phone Number & Password from database)
        $stmt = $conn->prepare("SELECT Customer_ID, name, password FROM Customers WHERE phone_number = ?");
        $stmt->bind_param("s", $account);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $customer = $res->fetch_assoc();
            if ($password === $customer['password']) {
                $_SESSION['role'] = 'customer';
                $_SESSION['user_id'] = $customer['Customer_ID'];
                $_SESSION['user_name'] = $customer['name'];
                $stmt->close();
                header('Location: customer_dashboard.php');
                exit;
            }
        }
        $stmt->close();

        // If nothing matches
        $error_msg = "帳號或密碼錯誤。";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>紅番茄餐廳 - 系統登入</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #ef4444;
            --hover-red: #dc2626;
            --bg-dark: #111827;
            --card-bg: #1f2937;
            --border-color: #374151;
            --text-light: #f3f4f6;
            --text-muted: #9ca3af;
        }

        body {
            font-family: 'Noto Sans TC', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* Branding at the top-left */
        .brand {
            position: absolute;
            top: 30px;
            left: 30px;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-red);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            letter-spacing: 0.05em;
        }

        .brand::before {
            content: "🍅";
            font-size: 2rem;
        }

        /* Login Card */
        .login-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            box-sizing: border-box;
            z-index: 10;
        }

        h2 {
            text-align: center;
            font-size: 1.7rem;
            margin-top: 0;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .subtitle {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        input {
            width: 100%;
            padding: 12px 16px;
            background-color: #111827;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            font-size: 1rem;
            outline: none;
            box-sizing: border-box;
            transition: all 0.2s;
        }

        input:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: var(--primary-red);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--hover-red);
        }

        .alert {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Ambient background decoration */
        .glow {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
            pointer-events: none;
        }
    </style>
</head>
<body>

<a href="#" class="brand">紅番茄</a>

<div class="glow"></div>

<div class="login-card">
    <h2>歡迎登入</h2>
    <div class="subtitle">請使用您的帳號與密碼進入系統</div>

    <?php if ($error_msg !== ""): ?>
        <div class="alert"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="account">帳號 (顧客電話 / 員工編號 / admin)</label>
            <input type="text" id="account" name="account" placeholder="請輸入帳號" required autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password">密碼</label>
            <input type="password" id="password" name="password" placeholder="請輸入密碼" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-submit">登入系統</button>
        <div style="margin-top: 15px; text-align: center;">
            <span style="color: var(--text-muted); font-size: 0.9rem;">顧客還沒有帳戶嗎？</span>
            <a href="register.php" style="color: var(--primary-red); font-size: 0.9rem; text-decoration: none; font-weight: 500; margin-left: 5px;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">立即註冊</a>
        </div>
    </form>
</div>

</body>
</html>
