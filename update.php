<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
if (empty($id)) {
    header('Location: index.php');
    exit;
}

// 1. Fetch overall feedback form details
$stmt = $conn->prepare("SELECT f.*, c.name AS customer_name, cr.table_number, cr.date AS record_date 
                        FROM Feedback_forms f
                        JOIN Customers c ON f.Customer_ID = c.Customer_ID
                        JOIN Consumption_records cr ON f.Record_ID = cr.Record_ID
                        WHERE f.Feedback_ID = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("找不到該筆回饋表單。");
}
$feedback = $res->fetch_assoc();
$stmt->close();

// 2. Fetch Dish rating details if exists (Detail_ID = 1)
$dish_feedback = null;
$stmt = $conn->prepare("SELECT fd.*, rd.Dish_ID, d.name AS dish_name 
                        FROM Feedback_details fd
                        JOIN Rate_dish rd ON fd.Feedback_ID = rd.Feedback_ID AND fd.Detail_ID = rd.Detail_ID
                        JOIN Dishes d ON rd.Dish_ID = d.Dish_ID
                        WHERE fd.Feedback_ID = ? AND fd.Detail_ID = 1");
$stmt->bind_param("s", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $dish_feedback = $res->fetch_assoc();
}
$stmt->close();

// 3. Fetch Staff rating details if exists (Detail_ID = 2)
$staff_feedback = null;
$stmt = $conn->prepare("SELECT fd.*, rs.Staff_ID, s.name AS staff_name, s.position AS staff_position 
                        FROM Feedback_details fd
                        JOIN Rate_staff rs ON fd.Feedback_ID = rs.Feedback_ID AND fd.Detail_ID = rs.Detail_ID
                        JOIN Staffs s ON rs.Staff_ID = s.Staff_ID
                        WHERE fd.Feedback_ID = ? AND fd.Detail_ID = 2");
$stmt->bind_param("s", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $staff_feedback = $res->fetch_assoc();
}
$stmt->close();

// 4. Fetch dropdown items for dish and staff changes
$dishes = [];
$res = $conn->query("SELECT Dish_ID, name FROM Dishes");
while ($row = $res->fetch_assoc()) $dishes[] = $row;

$staffs = [];
$res = $conn->query("SELECT Staff_ID, name, position FROM Staffs");
while ($row = $res->fetch_assoc()) $staffs[] = $row;
?>
<!DOCTYPE html>
<html>
<head>
    <title>修改顧客回饋資料</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
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

        input[readonly] {
            background-color: #f3f4f6;
            cursor: not-allowed;
            color: #6b7280;
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
        <h1>✏️ 修改顧客回饋資料</h1>

        <form action="doupdate.php" method="POST">
            <!-- Part 1: Overall Feedback -->
            <div class="section-title">整體消費評價 (不可修改編號與消費對象)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="feedback_id">表單編號</label>
                    <input type="text" id="feedback_id" name="feedback_id" value="<?php echo htmlspecialchars($feedback['Feedback_ID']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="rating">整體滿意度</label>
                    <select id="rating" name="rating" required>
                        <option value="5" <?php if($feedback['rating'] == 5) echo 'selected'; ?>>★★★★★ (5分)</option>
                        <option value="4" <?php if($feedback['rating'] == 4) echo 'selected'; ?>>★★★★☆ (4分)</option>
                        <option value="3" <?php if($feedback['rating'] == 3) echo 'selected'; ?>>★★★☆☆ (3分)</option>
                        <option value="2" <?php if($feedback['rating'] == 2) echo 'selected'; ?>>★★☆☆☆ (2分)</option>
                        <option value="1" <?php if($feedback['rating'] == 1) echo 'selected'; ?>>★☆☆☆☆ (1分)</option>
                    </select>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>顧客姓名</label>
                    <input type="text" value="<?php echo htmlspecialchars($feedback['customer_name'] . " (" . $feedback['Customer_ID'] . ")"); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>消費紀錄</label>
                    <input type="text" value="<?php echo htmlspecialchars("桌號: " . $feedback['table_number'] . " | 日期: " . $feedback['record_date']); ?>" readonly>
                </div>
            </div>

            <div class="form-group">
                <label for="opinion">整體意見</label>
                <textarea id="opinion" name="opinion" required><?php echo htmlspecialchars($feedback['opinion']); ?></textarea>
            </div>

            <!-- Part 2: Detailed Dish Rating -->
            <div class="section-title">餐點滿意度評價 (Detail_ID: 1)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="dish_id">評價餐點</label>
                    <select id="dish_id" name="dish_id">
                        <option value="">-- 無 (不評價/刪除評價) --</option>
                        <?php foreach($dishes as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['Dish_ID']); ?>" 
                                <?php if($dish_feedback && $dish_feedback['Dish_ID'] == $d['Dish_ID']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($d['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dish_rating">餐點滿意度</label>
                    <select id="dish_rating" name="dish_rating">
                        <option value="0">-- 評分 --</option>
                        <option value="5" <?php if($dish_feedback && $dish_feedback['rating'] == 5) echo 'selected'; ?>>★★★★★ (5分)</option>
                        <option value="4" <?php if($dish_feedback && $dish_feedback['rating'] == 4) echo 'selected'; ?>>★★★★☆ (4分)</option>
                        <option value="3" <?php if($dish_feedback && $dish_feedback['rating'] == 3) echo 'selected'; ?>>★★★☆☆ (3分)</option>
                        <option value="2" <?php if($dish_feedback && $dish_feedback['rating'] == 2) echo 'selected'; ?>>★★☆☆☆ (2分)</option>
                        <option value="1" <?php if($dish_feedback && $dish_feedback['rating'] == 1) echo 'selected'; ?>>★☆☆☆☆ (1分)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="dish_opinion">餐點意見細節</label>
                <textarea id="dish_opinion" name="dish_opinion" placeholder="口味、熟度或份量上的細部意見..."><?php echo $dish_feedback ? htmlspecialchars($dish_feedback['opinion']) : ''; ?></textarea>
            </div>

            <!-- Part 3: Detailed Staff Rating -->
            <div class="section-title">服務人員滿意度評價 (Detail_ID: 2)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="staff_id">評價服務同仁</label>
                    <select id="staff_id" name="staff_id">
                        <option value="">-- 無 (不評價/刪除評價) --</option>
                        <?php foreach($staffs as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['Staff_ID']); ?>" 
                                <?php if($staff_feedback && $staff_feedback['Staff_ID'] == $s['Staff_ID']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($s['name'] . " (" . $s['position'] . ")"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="staff_rating">服務滿意度</label>
                    <select id="staff_rating" name="staff_rating">
                        <option value="0">-- 評分 --</option>
                        <option value="5" <?php if($staff_feedback && $staff_feedback['rating'] == 5) echo 'selected'; ?>>★★★★★ (5分)</option>
                        <option value="4" <?php if($staff_feedback && $staff_feedback['rating'] == 4) echo 'selected'; ?>>★★★★☆ (4分)</option>
                        <option value="3" <?php if($staff_feedback && $staff_feedback['rating'] == 3) echo 'selected'; ?>>★★★☆☆ (3分)</option>
                        <option value="2" <?php if($staff_feedback && $staff_feedback['rating'] == 2) echo 'selected'; ?>>★★☆☆☆ (2分)</option>
                        <option value="1" <?php if($staff_feedback && $staff_feedback['rating'] == 1) echo 'selected'; ?>>★☆☆☆☆ (1分)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="staff_opinion">服務意見細節</label>
                <textarea id="staff_opinion" name="staff_opinion" placeholder="服務態度、效率等細部意見..."><?php echo $staff_feedback ? htmlspecialchars($staff_feedback['opinion']) : ''; ?></textarea>
            </div>

            <div class="btn-row">
                <a href="index.php" class="btn btn-cancel">返回主頁</a>
                <button type="submit" class="btn btn-submit">更新回饋</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>