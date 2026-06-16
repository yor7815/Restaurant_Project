<?php
session_start();
require_once 'db_config.php';

$error_msg = "";
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
    $phone_number = trim($_POST['phone_number']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($gender) || empty($phone_number) || empty($password)) {
        $error_msg = "請填寫所有必要欄位（姓名、性別、電話、密碼）。";
    } else {
        // Check if phone number already exists
        $stmt = $conn->prepare("SELECT Customer_ID FROM Customers WHERE phone_number = ?");
        $stmt->bind_param("s", $phone_number);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $error_msg = "此電話號碼已被註冊。";
            $stmt->close();
        } else {
            $stmt->close();
            // Generate next Customer_ID (e.g. C004)
            $id_res = $conn->query("SELECT Customer_ID FROM Customers WHERE Customer_ID LIKE 'C%' ORDER BY Customer_ID DESC LIMIT 1");
            $next_id = "C001";
            if ($id_res && $id_res->num_rows > 0) {
                $last_row = $id_res->fetch_assoc();
                $last_id = $last_row['Customer_ID'];
                $num = intval(substr($last_id, 1));
                $next_id = 'C' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
            }

            // Insert new customer
            $member_level = "一般顧客";
            $stmt = $conn->prepare("INSERT INTO Customers (Customer_ID, member_level, name, gender, age, phone_number, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $next_id, $member_level, $name, $gender, $age, $phone_number, $password);
            
            if ($stmt->execute()) {
                $success_msg = "註冊成功！即將轉向登入頁面...";
            } else {
                $error_msg = "註冊失敗，系統發生錯誤：" . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>紅番茄餐廳 - 顧客註冊</title>
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
            min-height: 100vh;
            position: relative;
            box-sizing: border-box;
            padding: 40px 20px;
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

        /* Register Card */
        .register-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 100%;
            max-width: 460px;
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

        input, select {
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

        input:focus, select:focus {
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

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .btn-back:hover {
            color: var(--text-light);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #a7f3d0;
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
    <?php if ($success_msg !== ""): ?>
        <script>
            setTimeout(function() {
                window.location.href = 'login.php';
            }, 2000);
        </script>
    <?php endif; ?>
</head>
<body>

<a href="login.php" class="brand">紅番茄</a>

<div class="glow"></div>

<div class="register-card">
    <h2>顧客帳戶註冊</h2>
    <div class="subtitle">歡迎加入紅番茄，註冊後即可填寫滿意度回饋表單</div>

    <?php if ($error_msg !== ""): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <?php if ($success_msg !== ""): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="name">您的姓名 *</label>
            <input type="text" id="name" name="name" placeholder="請輸入姓名" required>
        </div>

        <div class="form-group">
            <label for="gender">性別 *</label>
            <select id="gender" name="gender" required>
                <option value="" disabled selected>請選擇性別</option>
                <option value="M">男 (M)</option>
                <option value="F">女 (F)</option>
                <option value="O">其他 (O)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="age">年齡</label>
            <input type="number" id="age" name="age" placeholder="請輸入年齡 (選填)" min="0" max="150">
        </div>

        <div class="form-group">
            <label for="phone_number">電話號碼 (登入帳號) *</label>
            <input type="text" id="phone_number" name="phone_number" placeholder="請輸入手機或市話" required>
        </div>

        <div class="form-group">
            <label for="password">登入密碼 *</label>
            <input type="password" id="password" name="password" placeholder="請輸入登入密碼" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-submit">確認註冊</button>
        <a href="login.php" class="btn-back">已有帳號？返回登入</a>
    </form>
</div>

</body>
</html>
