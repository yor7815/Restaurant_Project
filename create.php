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

        // Multiple Dishes feedback
        $dishes_posted = isset($_POST['dishes']) ? $_POST['dishes'] : [];

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

            // 2. Insert multiple Dish feedback details (Detail_ID = 1, 2, 3...)
            $detail_id = 1;
            foreach ($dishes_posted as $dish_data) {
                if (!isset($dish_data['dish_id']) || empty($dish_data['dish_id'])) {
                    continue;
                }
                $dish_id = $dish_data['dish_id'];
                $dish_rating = intval($dish_data['rating']);
                $dish_opinion = isset($dish_data['opinion']) ? $dish_data['opinion'] : '';

                if ($dish_rating > 0) {
                    $stmt = $conn->prepare("INSERT INTO Feedback_details (Feedback_ID, Detail_ID, opinion, rating) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("sisi", $feedback_id, $detail_id, $dish_opinion, $dish_rating);
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

                    $detail_id++;
                }
            }

            // 3. Insert Staff feedback detail (Detail_ID = 100 to avoid conflicts with dishes)
            if (!empty($staff_id) && $staff_rating > 0) {
                $staff_detail_id = 100;
                $stmt = $conn->prepare("INSERT INTO Feedback_details (Feedback_ID, Detail_ID, opinion, rating) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sisi", $feedback_id, $staff_detail_id, $staff_opinion, $staff_rating);
                if (!$stmt->execute()) {
                    throw new Exception("新增員工評價細節失敗: " . $stmt->error);
                }
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO Rate_staff (Feedback_ID, Detail_ID, Staff_ID) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $feedback_id, $staff_detail_id, $staff_id);
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

// Fetch lists for mappings
$customers = [];
$res = $conn->query("SELECT Customer_ID, name FROM Customers");
while($row = $res->fetch_assoc()) $customers[] = $row;

// Query Customer -> Records mapping
$customer_records = [];
$res = $conn->query("SELECT h.Customer_ID, r.Record_ID, r.date, r.table_number 
                     FROM Has h 
                     JOIN Consumption_records r ON h.Record_ID = r.Record_ID");
while ($row = $res->fetch_assoc()) {
    $customer_records[$row['Customer_ID']][] = [
        'id' => $row['Record_ID'],
        'date' => $row['date'],
        'table' => $row['table_number']
    ];
}

// Query Record -> Dishes mapping
$record_dishes = [];
$res = $conn->query("SELECT c.Record_ID, d.Dish_ID, d.name 
                     FROM Contain c 
                     JOIN Dishes d ON c.Dish_ID = d.Dish_ID");
while ($row = $res->fetch_assoc()) {
    $record_dishes[$row['Record_ID']][] = [
        'id' => $row['Dish_ID'],
        'name' => $row['name']
    ];
}

// Query Record -> Staffs mapping (who served that record)
$record_staffs = [];
$res = $conn->query("SELECT s.Record_ID, st.Staff_ID, st.name, st.position 
                     FROM Service s
                     JOIN Staffs st ON s.Staff_ID = st.Staff_ID");
while ($row = $res->fetch_assoc()) {
    $record_staffs[$row['Record_ID']][] = [
        'id' => $row['Staff_ID'],
        'name' => $row['name'],
        'position' => $row['position']
    ];
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
            position: relative;
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
                    <select id="record_id" name="record_id" required disabled>
                        <option value="">-- 請先選擇顧客 --</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="opinion">整體意見</label>
                <textarea id="opinion" name="opinion" placeholder="請輸入您對本次用餐的整體感覺或建議..."></textarea>
            </div>

            <!-- Part 2: Detailed Dish Ratings -->
            <div class="section-title">
                <span>餐點滿意度評價 (選填)</span>
                <button type="button" id="add-dish-btn" class="btn btn-add-dish" disabled>➕ 增加餐點評價</button>
            </div>
            
            <div id="dishes-container">
                <!-- Dynamic dish evaluation items will be appended here -->
                <p id="dish-placeholder-text" style="color: var(--text-muted); font-size: 0.9rem; text-align: center; margin: 20px 0;">
                    請先選擇消費紀錄以評價點購的餐點
                </p>
            </div>

            <!-- Part 3: Detailed Staff Rating -->
            <div class="section-title">服務人員滿意度評價 (選填)</div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label for="staff_id">選擇外場服務員</label>
                    <select id="staff_id" name="staff_id" disabled>
                        <option value="">-- 請先選擇消費紀錄 --</option>
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

<script>
// JSON mapping output from PHP
const customerRecordsMap = <?php echo json_encode($customer_records); ?> || {};
const recordDishesMap = <?php echo json_encode($record_dishes); ?> || {};
const recordStaffsMap = <?php echo json_encode($record_staffs); ?> || {};

let dishCount = 0;
let currentRecordDishes = [];

document.getElementById('customer_id').addEventListener('change', function() {
    const customerId = this.value;
    const recordSelect = document.getElementById('record_id');
    
    // Clear record select
    recordSelect.innerHTML = '<option value="">-- 請選擇消費紀錄 --</option>';
    recordSelect.disabled = true;
    
    // Reset dependency controls
    resetDishContainer();
    resetStaffSelect();

    if (customerId && customerRecordsMap[customerId]) {
        const records = customerRecordsMap[customerId];
        records.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = `桌號: ${r.table} | 日期: ${r.date} (${r.id})`;
            recordSelect.appendChild(opt);
        });
        recordSelect.disabled = false;
    } else {
        recordSelect.innerHTML = '<option value="">-- 請先選擇顧客 --</option>';
    }
});

document.getElementById('record_id').addEventListener('change', function() {
    const recordId = this.value;
    const addDishBtn = document.getElementById('add-dish-btn');
    
    resetDishContainer();
    resetStaffSelect();

    if (recordId) {
        // 1. Store and enable dishes evaluation
        currentRecordDishes = recordDishesMap[recordId] || [];
        if (currentRecordDishes.length > 0) {
            document.getElementById('dish-placeholder-text').textContent = "尚未新增餐點評價，點擊右上角按鈕新增";
            addDishBtn.disabled = false;
            // Add first dish input automatically for user convenience
            addDishField();
        } else {
            document.getElementById('dish-placeholder-text').textContent = "此消費紀錄中無餐點資料";
            addDishBtn.disabled = true;
        }

        // 2. Populate and enable staff select
        const staffSelect = document.getElementById('staff_id');
        staffSelect.innerHTML = '<option value="">-- 無 --</option>';
        const staffs = recordStaffsMap[recordId] || [];
        if (staffs.length > 0) {
            staffs.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = `${s.name} (${s.position})`;
                staffSelect.appendChild(opt);
            });
            staffSelect.disabled = false;
        } else {
            staffSelect.innerHTML = '<option value="">-- 此紀錄無分配服務人員 --</option>';
            staffSelect.disabled = true;
        }
    }
});

function resetDishContainer() {
    const container = document.getElementById('dishes-container');
    container.innerHTML = '<p id="dish-placeholder-text" style="color: var(--text-muted); font-size: 0.9rem; text-align: center; margin: 20px 0;">請先選擇消費紀錄以評價點購的餐點</p>';
    document.getElementById('add-dish-btn').disabled = true;
    dishCount = 0;
    currentRecordDishes = [];
}

function resetStaffSelect() {
    const staffSelect = document.getElementById('staff_id');
    staffSelect.innerHTML = '<option value="">-- 請先選擇消費紀錄 --</option>';
    staffSelect.disabled = true;
}

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
    currentRecordDishes.forEach(d => {
        dishOptionsHtml += `<option value="${d.id}">${d.name}</option>`;
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
