<?php
require_once 'db_config.php';

$message = "";
$error = false;

// Handle form submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset($_POST['feedback_id']) && 
        isset($_POST['customer_id']) && 
        isset($_POST['record_id']) && 
        isset($_POST['rating'])
    ) {
        $feedback_id = $_POST['feedback_id'];
        $customer_id = $_POST['customer_id'];
        $record_id = $_POST['record_id'];
        $rating = intval($_POST['rating']);
        $opinion = $_POST['opinion'];
        
        $date = date('Y-m-d');
        $time = date('H:i:s');

        // Optional Dish feedback
        $dish_id = isset($_POST['dish_id']) ? $_POST['dish_id'] : '';
        $dish_rating = isset($_POST['dish_rating']) ? intval($_POST['dish_rating']) : 0;
        $dish_opinion = isset($_POST['dish_opinion']) ? $_POST['dish_opinion'] : '';

        // Optional Staff feedback
        $staff_id = isset($_POST['staff_id']) ? $_POST['staff_id'] : '';
        $staff_rating = isset($_POST['staff_rating']) ? intval($_POST['staff_rating']) : 0;
        $staff_opinion = isset($_POST['staff_opinion']) ? $_POST['staff_opinion'] : '';

        // START TRANSACTION
        $conn->begin_transaction();

        try {
            // 1. Insert into Feedback_forms
            $stmt = $conn->prepare("INSERT INTO Feedback_forms (Feedback_ID, date, time, opinion, rating, Customer_ID, Record_ID) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $feedback_id, $date, $time, $opinion, $rating, $customer_id, $record_id);
            if (!$stmt->execute()) {
                throw new Exception("新增主要回饋表單失敗: " . $stmt->error);
            }
            $stmt->close();

            // 2. Insert Dish feedback detail (Detail_ID = 1) if provided
            if (!empty($dish_id) && $dish_rating > 0) {
                $detail_id = 1;
                $stmt = $conn->prepare("INSERT INTO Feedback_details (Feedback_ID, Detail_ID, opinion, rating) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siis", $feedback_id, $detail_id, $dish_opinion, $dish_rating);
                if (!$stmt->execute()) {
                    throw new Exception("新增餐點評價細節失敗: " . $stmt->error);
                }
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO Rate_dish (Feedback_ID, Detail_ID, Dish_ID) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $feedback_id, $detail_id, $dish_id);
                if (!$stmt->execute()) {
                    throw new Exception("建立餐點評分關係失敗: " . $stmt->error);
                }
                $stmt->close();
            }

            // 3. Insert Staff feedback detail (Detail_ID = 2) if provided
            if (!empty($staff_id) && $staff_rating > 0) {
                $detail_id = 2;
                $stmt = $conn->prepare("INSERT INTO Feedback_details (Feedback_ID, Detail_ID, opinion, rating) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siis", $feedback_id, $detail_id, $staff_opinion, $staff_rating);
                if (!$stmt->execute()) {
                    throw new Exception("新增員工評價細節失敗: " . $stmt->error);
                }
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO Rate_staff (Feedback_ID, Detail_ID, Staff_ID) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $feedback_id, $detail_id, $staff_id);
                if (!$stmt->execute()) {
                    throw new Exception("建立員工評分關係失敗: " . $stmt->error);
                }
                $stmt->close();
            }

            // Commit Transaction
            $conn->commit();
            header('Location: index.php');
            exit;

        } catch (Exception $e) {
            // Rollback on any failure
            $conn->rollback();
            $message = "交易失敗，已恢復變更。錯誤訊息: " . $e->getMessage();
            $error = true;
        }
    } else {
        $message = "資料不完整，請填寫必填欄位。";
        $error = true;
    }
}

// Fetch helper data for dropdown lists (GET)
$customers = [];
$res = $conn->query("SELECT Customer_ID, name FROM Customers");
if ($res) {
    while($row = $res->fetch_assoc()) $customers[] = $row;
}

$records = [];
$res = $conn->query("SELECT Record_ID, date, table_number FROM Consumption_records");
if ($res) {
    while($row = $res->fetch_assoc()) $records[] = $row;
}

$dishes = [];
$res = $conn->query("SELECT Dish_ID, name FROM Dishes");
if ($res) {
    while($row = $res->fetch_assoc()) $dishes[] = $row;
}

$staffs = [];
$res = $conn->query("SELECT Staff_ID, name, position FROM Staffs");
if ($res) {
    while($row = $res->fetch_assoc()) $staffs[] = $row;
}

// Suggest next Feedback_ID
$next_id = "F001";
$res = $conn->query("SELECT Feedback_ID FROM Feedback_forms ORDER BY Feedback_ID DESC LIMIT 1");
if ($res && $res->num_rows > 0) {
    $last_id = $res->fetch_assoc()['Feedback_ID'];
    $num = intval(substr($last_id, 1)) + 1;
    $next_id = "F" . str_pad($num, 3, "0", STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>新增顧客回饋表單</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --error-color: #ef4444;
            --success-color: #10b981;
        }

        body {
            font-family: 'Noto Sans TC', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin-bottom: 24px;
            font-size: 2rem;
            color: var(--text-main);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.95rem;
            color: #374151;
        }

        input[type="text"], select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s;
            font-family: inherit;
        }

        input[type="text"]:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin: 30px 0 15px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--border-color);
            color: var(--primary-color);
        }

        .btn-row {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 24px;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .btn-cancel {
            background-color: #e5e7eb;
            color: #374151;
        }

        .btn-cancel:hover {
            background-color: #d1d5db;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h1>✍️ 新增顧客回饋表單</h1>
        
        <?php if ($message !== ""): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <!-- Part 1: Overall Feedback -->
            <div class="section-title">整體消費評價 (必填)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="feedback_id">表單編號</label>
                    <input type="text" id="feedback_id" name="feedback_id" value="<?php echo htmlspecialchars($next_id); ?>" required>
                </div>

                <div class="form-group">
                    <label for="rating">整體滿意度</label>
                    <select id="rating" name="rating" required>
                        <option value="5">★★★★★ (5分 - 非常滿意)</option>
                        <option value="4">★★★★☆ (4分 - 滿意)</option>
                        <option value="3">★★★☆☆ (3分 - 普通)</option>
                        <option value="2">★★☆☆☆ (2分 - 不滿意)</option>
                        <option value="1">★☆☆☆☆ (1分 - 非常不滿意)</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="customer_id">選擇顧客</label>
                    <select id="customer_id" name="customer_id" required>
                        <option value="">-- 請選擇顧客 --</option>
                        <?php foreach($customers as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['Customer_ID']); ?>">
                                <?php echo htmlspecialchars($c['name'] . " (" . $c['Customer_ID'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="record_id">選擇消費紀錄</label>
                    <select id="record_id" name="record_id" required>
                        <option value="">-- 請選擇消費紀錄 --</option>
                        <?php foreach($records as $r): ?>
                            <option value="<?php echo htmlspecialchars($r['Record_ID']); ?>">
                                <?php echo htmlspecialchars("桌號: " . $r['table_number'] . " | 日期: " . $r['date'] . " (" . $r['Record_ID'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="opinion">整體意見</label>
                <textarea id="opinion" name="opinion" placeholder="請輸入您對本次用餐的整體感覺或建議..."></textarea>
            </div>

            <!-- Part 2: Detailed Dish Rating -->
            <div class="section-title">餐點滿意度評價 (選填)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="dish_id">選擇餐點</label>
                    <select id="dish_id" name="dish_id">
                        <option value="">-- 無 --</option>
                        <?php foreach($dishes as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['Dish_ID']); ?>">
                                <?php echo htmlspecialchars($d['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dish_rating">餐點滿意度</label>
                    <select id="dish_rating" name="dish_rating">
                        <option value="0">-- 評分 --</option>
                        <option value="5">★★★★★ (5分)</option>
                        <option value="4">★★★★☆ (4分)</option>
                        <option value="3">★★★☆☆ (3分)</option>
                        <option value="2">★★☆☆☆ (2分)</option>
                        <option value="1">★☆☆☆☆ (1分)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="dish_opinion">餐點意見細節</label>
                <textarea id="dish_opinion" name="dish_opinion" placeholder="口味、熟度或份量上的細部意見..."></textarea>
            </div>

            <!-- Part 3: Detailed Staff Rating -->
            <div class="section-title">服務人員滿意度評價 (選填)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="staff_id">選擇外場服務員</label>
                    <select id="staff_id" name="staff_id">
                        <option value="">-- 無 --</option>
                        <?php foreach($staffs as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['Staff_ID']); ?>">
                                <?php echo htmlspecialchars($s['name'] . " (" . $s['position'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="staff_rating">服務滿意度</label>
                    <select id="staff_rating" name="staff_rating">
                        <option value="0">-- 評分 --</option>
                        <option value="5">★★★★★ (5分)</option>
                        <option value="4">★★★★☆ (4分)</option>
                        <option value="3">★★★☆☆ (3分)</option>
                        <option value="2">★★☆☆☆ (2分)</option>
                        <option value="1">★☆☆☆☆ (1分)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="staff_opinion">服務意見細節</label>
                <textarea id="staff_opinion" name="staff_opinion" placeholder="服務態度、帶位或上菜效率等意見..."></textarea>
            </div>

            <div class="btn-row">
                <a href="index.php" class="btn btn-cancel">返回主頁</a>
                <button type="submit" class="btn btn-submit">送出回饋</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
