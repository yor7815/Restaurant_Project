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

// 2. Fetch all Dish feedbacks for this Feedback_ID (Detail_ID < 100)
$dish_feedbacks = [];
$stmt = $conn->prepare("SELECT fd.*, rd.Dish_ID, d.name AS dish_name 
                        FROM Feedback_details fd
                        JOIN Rate_dish rd ON fd.Feedback_ID = rd.Feedback_ID AND fd.Detail_ID = rd.Detail_ID
                        JOIN Dishes d ON rd.Dish_ID = d.Dish_ID
                        WHERE fd.Feedback_ID = ? AND fd.Detail_ID < 100
                        ORDER BY fd.Detail_ID ASC");
$stmt->bind_param("s", $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $dish_feedbacks[] = $row;
}
$stmt->close();

// 3. Fetch Staff rating details if exists (Detail_ID = 100)
$staff_feedback = null;
$stmt = $conn->prepare("SELECT fd.*, rs.Staff_ID, s.name AS staff_name, s.position AS staff_position 
                        FROM Feedback_details fd
                        JOIN Rate_staff rs ON fd.Feedback_ID = rs.Feedback_ID AND fd.Detail_ID = rs.Detail_ID
                        JOIN Staffs s ON rs.Staff_ID = s.Staff_ID
                        WHERE fd.Feedback_ID = ? AND fd.Detail_ID = 100");
$stmt->bind_param("s", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $staff_feedback = $res->fetch_assoc();
}
$stmt->close();

// 4. Query dishes of this consumption record
$record_id = $feedback['Record_ID'];
$record_dishes = [];
$res = $conn->query("SELECT c.Dish_ID, d.name 
                     FROM Contain c 
                     JOIN Dishes d ON c.Dish_ID = d.Dish_ID
                     WHERE c.Record_ID = '$record_id'");
while ($row = $res->fetch_assoc()) {
    $record_dishes[] = $row;
}

// 5. Query staffs of this consumption record
$record_staffs = [];
$res = $conn->query("SELECT s.Staff_ID, st.name, st.position 
                     FROM Service s
                     JOIN Staffs st ON s.Staff_ID = st.Staff_ID
                     WHERE s.Record_ID = '$record_id'");
while ($row = $res->fetch_assoc()) {
    $record_staffs[] = $row;
}
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
            --text-muted: #6b7280;
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
            max-width: 750px;
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
            height: 80px;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 600;
            margin: 30px 0 15px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--border-color);
            color: var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-row {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        .btn-add-dish {
            background-color: #e0f2fe;
            color: #0369a1;
            font-size: 0.85rem;
            padding: 6px 12px;
        }

        .btn-add-dish:hover {
            background-color: #bae6fd;
        }

        .btn-delete-dish {
            background-color: #fee2e2;
            color: #b91c1c;
            font-size: 0.85rem;
            padding: 6px 12px;
            align-self: flex-end;
            margin-bottom: 20px;
        }

        .btn-delete-dish:hover {
            background-color: #fecaca;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .dish-feedback-item {
            background-color: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 15px;
        }

        .dish-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .dish-title {
            font-weight: 600;
            color: #4b5563;
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

            <!-- Part 2: Detailed Dish Ratings -->
            <div class="section-title">
                <span>餐點滿意度評價 (選填)</span>
                <button type="button" id="add-dish-btn" class="btn btn-add-dish">➕ 增加餐點評價</button>
            </div>
            
            <div id="dishes-container">
                <?php 
                $dish_count = 0;
                if (count($dish_feedbacks) > 0) {
                    foreach ($dish_feedbacks as $df) {
                        ?>
                        <div class="dish-feedback-item" id="dish-item-<?php echo $dish_count; ?>">
                            <div class="dish-header">
                                <span class="dish-title">餐點評價 #<?php echo $dish_count + 1; ?></span>
                                <button type="button" class="btn btn-delete-dish" onclick="removeDishField(<?php echo $dish_count; ?>)">🗑️ 移除</button>
                            </div>
                            <div class="grid-2">
                                <div class="form-group">
                                    <label>評價餐點</label>
                                    <select name="dishes[<?php echo $dish_count; ?>][dish_id]" required>
                                        <option value="">-- 請選擇餐點 --</option>
                                        <?php foreach ($record_dishes as $rd) { ?>
                                            <option value="<?php echo htmlspecialchars($rd['Dish_ID']); ?>" 
                                                <?php if($df['Dish_ID'] == $rd['Dish_ID']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($rd['name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>餐點滿意度</label>
                                    <select name="dishes[<?php echo $dish_count; ?>][rating]" required>
                                        <option value="5" <?php if($df['rating'] == 5) echo 'selected'; ?>>★★★★★ (5分)</option>
                                        <option value="4" <?php if($df['rating'] == 4) echo 'selected'; ?>>★★★★☆ (4分)</option>
                                        <option value="3" <?php if($df['rating'] == 3) echo 'selected'; ?>>★★★☆☆ (3分)</option>
                                        <option value="2" <?php if($df['rating'] == 2) echo 'selected'; ?>>★★☆☆☆ (2分)</option>
                                        <option value="1" <?php if($df['rating'] == 1) echo 'selected'; ?>>★☆☆☆☆ (1分)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>餐點意見細節</label>
                                <textarea name="dishes[<?php echo $dish_count; ?>][opinion]" placeholder="對此餐點口味、熟度或份量上的細部意見..."><?php echo htmlspecialchars($df['opinion']); ?></textarea>
                            </div>
                        </div>
                        <?php
                        $dish_count++;
                    }
                } else {
                    ?>
                    <p id="dish-placeholder-text" style="color: var(--text-muted); font-size: 0.9rem; text-align: center; margin: 20px 0;">
                        尚未新增餐點評價，點擊右上角按鈕新增
                    </p>
                    <?php
                }
                ?>
            </div>

            <!-- Part 3: Detailed Staff Rating -->
            <div class="section-title">服務人員滿意度評價 (選填)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="staff_id">評價服務同仁</label>
                    <select id="staff_id" name="staff_id" <?php if(count($record_staffs) == 0) echo 'disabled'; ?>>
                        <option value="">-- 無 --</option>
                        <?php foreach($record_staffs as $s): ?>
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

<script>
const recordDishes = <?php echo json_encode($record_dishes); ?> || [];
let dishCount = <?php echo $dish_count; ?>;

document.getElementById('add-dish-btn').addEventListener('click', function() {
    addDishField();
});

function addDishField() {
    const container = document.getElementById('dishes-container');
    const placeholder = document.getElementById('dish-placeholder-text');
    if (placeholder) {
        placeholder.remove();
    }

    const dishItem = document.createElement('div');
    dishItem.className = 'dish-feedback-item';
    dishItem.id = `dish-item-${dishCount}`;

    // Generate option elements for dishes
    let dishOptionsHtml = '<option value="">-- 請選擇餐點 --</option>';
    recordDishes.forEach(d => {
        dishOptionsHtml += `<option value="${d.Dish_ID}">${d.name}</option>`;
    });

    dishItem.innerHTML = `
        <div class="dish-header">
            <span class="dish-title">餐點評價 #${dishCount + 1}</span>
            <button type="button" class="btn btn-delete-dish" onclick="removeDishField(${dishCount})">🗑️ 移除</button>
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label>評價餐點</label>
                <select name="dishes[${dishCount}][dish_id]" required>
                    ${dishOptionsHtml}
                </select>
            </div>
            <div class="form-group">
                <label>餐點滿意度</label>
                <select name="dishes[${dishCount}][rating]" required>
                    <option value="5">★★★★★ (5分)</option>
                    <option value="4">★★★★☆ (4分)</option>
                    <option value="3">★★★☆☆ (3分)</option>
                    <option value="2">★★☆☆☆ (2分)</option>
                    <option value="1">★☆☆☆☆ (1分)</option>
                </select>
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>餐點意見細節</label>
            <textarea name="dishes[${dishCount}][opinion]" placeholder="對此餐點口味、熟度或份量上的細部意見..."></textarea>
        </div>
    `;

    container.appendChild(dishItem);
    dishCount++;
}

function removeDishField(id) {
    const item = document.getElementById(`dish-item-${id}`);
    if (item) {
        item.remove();
    }
    
    // Show placeholder text if all items removed
    const container = document.getElementById('dishes-container');
    if (container.children.length === 0) {
        container.innerHTML = '<p id="dish-placeholder-text" style="color: var(--text-muted); font-size: 0.9rem; text-align: center; margin: 20px 0;">尚未新增餐點評價，點擊右上角按鈕新增</p>';
    }
}
</script>

</body>
</html>