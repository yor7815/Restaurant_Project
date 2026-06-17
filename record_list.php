<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = "";

$customers = [];
$res = $conn->query("SELECT Customer_ID, name FROM Customers ORDER BY Customer_ID");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $customers[] = $row;
    }
}

$dishes = [];
$res = $conn->query("SELECT Dish_ID, name, price FROM Dishes ORDER BY Dish_ID");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $dishes[] = $row;
    }
}

$staffs = [];
$res = $conn->query("SELECT Staff_ID, name, position FROM Staffs ORDER BY Staff_ID");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $staffs[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $record_id = isset($_POST['record_id']) ? trim($_POST['record_id']) : '';

    if ($action === 'delete' && $record_id !== '') {
        $stmt = $conn->prepare("DELETE FROM Consumption_records WHERE Record_ID = ?");
        $stmt->bind_param("s", $record_id);
        if ($stmt->execute()) {
            header('Location: record_list.php');
            exit;
        }
        $message = "刪除失敗：" . $stmt->error;
        $stmt->close();
    }

    if ($action === 'update') {
        $customer_id = isset($_POST['customer_id']) ? trim($_POST['customer_id']) : '';
        $date = isset($_POST['date']) ? trim($_POST['date']) : '';
        $time = isset($_POST['time']) ? trim($_POST['time']) : '';
        $table_number = isset($_POST['table_number']) ? trim($_POST['table_number']) : '';
        $number_of_customers = isset($_POST['number_of_customers']) ? intval($_POST['number_of_customers']) : 0;
        $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 1;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $staff_id = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : '';
        $dish_ids = isset($_POST['dish_id']) ? $_POST['dish_id'] : [];
        $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];

        $selected_dishes = [];
        foreach ($dish_ids as $index => $dish_id) {
            $qty = isset($quantities[$index]) ? intval($quantities[$index]) : 0;
            if ($dish_id !== '' && $qty > 0) {
                $selected_dishes[] = [
                    'dish_id' => $dish_id,
                    'quantity' => $qty
                ];
            }
        }

        if ($record_id === '' || $customer_id === '' || $date === '' || $time === '' || $table_number === '' || $number_of_customers <= 0 || $amount < 0 || $staff_id === '') {
            $message = "請填寫完整資料。";
        } elseif (count($selected_dishes) === 0) {
            $message = "請至少選擇一項餐點並填寫數量。";
        } else {
            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare("UPDATE Consumption_records SET date = ?, time = ?, table_number = ?, number_of_customers = ?, discount = ?, amount = ? WHERE Record_ID = ?");
                $stmt->bind_param("sssidds", $date, $time, $table_number, $number_of_customers, $discount, $amount, $record_id);
                if (!$stmt->execute()) {
                    throw new Exception("修改用餐紀錄失敗：" . $stmt->error);
                }
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM `Has` WHERE Record_ID = ?");
                $stmt->bind_param("s", $record_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO `Has` (Customer_ID, Record_ID) VALUES (?, ?)");
                $stmt->bind_param("ss", $customer_id, $record_id);
                if (!$stmt->execute()) {
                    throw new Exception("修改顧客關係失敗：" . $stmt->error);
                }
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM `Service` WHERE Record_ID = ?");
                $stmt->bind_param("s", $record_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO `Service` (Record_ID, Staff_ID, Customer_ID) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $record_id, $staff_id, $customer_id);
                if (!$stmt->execute()) {
                    throw new Exception("修改服務員關係失敗：" . $stmt->error);
                }
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM `Contain` WHERE Record_ID = ?");
                $stmt->bind_param("s", $record_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO `Contain` (Record_ID, Dish_ID, number) VALUES (?, ?, ?)");
                foreach ($selected_dishes as $dish) {
                    $dish_id = $dish['dish_id'];
                    $quantity = $dish['quantity'];
                    $stmt->bind_param("ssi", $record_id, $dish_id, $quantity);
                    if (!$stmt->execute()) {
                        throw new Exception("修改餐點明細失敗：" . $stmt->error);
                    }
                }
                $stmt->close();

                $conn->commit();
                header('Location: record_list.php');
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $message = $e->getMessage();
            }
        }
    }
}

$edit_id = isset($_GET['edit_id']) ? trim($_GET['edit_id']) : '';
$edit_record = null;
$edit_dishes = [];

if ($edit_id !== '') {
    $stmt = $conn->prepare("
        SELECT
            cr.Record_ID,
            cr.date,
            cr.time,
            cr.table_number,
            cr.number_of_customers,
            cr.discount,
            cr.amount,
            h.Customer_ID,
            sv.Staff_ID
        FROM Consumption_records cr
        LEFT JOIN `Has` h
          ON cr.Record_ID = h.Record_ID
        LEFT JOIN Service sv
          ON cr.Record_ID = sv.Record_ID
        WHERE cr.Record_ID = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $edit_id);
    $stmt->execute();
    $edit_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT Dish_ID, number FROM Contain WHERE Record_ID = ? ORDER BY Dish_ID");
    $stmt->bind_param("s", $edit_id);
    $stmt->execute();
    $dish_result = $stmt->get_result();
    while ($row = $dish_result->fetch_assoc()) {
        $edit_dishes[] = $row;
    }
    $stmt->close();
}

$sql = "
    SELECT
        cr.Record_ID,
        cr.date,
        cr.time,
        cr.table_number,
        cr.number_of_customers,
        cr.discount,
        cr.amount,
        c.name AS customer_name,
        s.name AS staff_name,
        GROUP_CONCAT(
            CONCAT(d.name, ' x ', co.number)
            ORDER BY d.Dish_ID
            SEPARATOR '、'
        ) AS dish_summary
    FROM Consumption_records cr
    LEFT JOIN `Has` h
      ON cr.Record_ID = h.Record_ID
    LEFT JOIN Customers c
      ON h.Customer_ID = c.Customer_ID
    LEFT JOIN Service sv
      ON cr.Record_ID = sv.Record_ID
    LEFT JOIN Staffs s
      ON sv.Staff_ID = s.Staff_ID
    LEFT JOIN Contain co
      ON cr.Record_ID = co.Record_ID
    LEFT JOIN Dishes d
      ON co.Dish_ID = d.Dish_ID
    GROUP BY
        cr.Record_ID,
        cr.date,
        cr.time,
        cr.table_number,
        cr.number_of_customers,
        cr.discount,
        cr.amount,
        c.name,
        s.name
    ORDER BY cr.date DESC, cr.time DESC, cr.Record_ID DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>紅番茄餐廳 - 用餐紀錄一覽</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-red: #ef4444;
            --hover-red: #dc2626;
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --accent-color: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans TC', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .brand-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary-red);
            padding-bottom: 15px;
        }

        .brand-title {
            margin: 0;
            color: var(--primary-red);
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        h2 {
            font-size: 1.5rem;
            margin: 0 0 20px;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .btn,
        button.btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-back {
            background-color: #4b5563;
            color: white;
        }

        .btn-back:hover {
            background-color: #374151;
            transform: translateY(-1px);
        }

        .btn-edit {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .btn-delete {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .btn-submit {
            background-color: var(--primary-red);
            color: white;
        }

        .btn-submit:hover {
            background-color: var(--hover-red);
        }

        .card,
        .edit-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .edit-card {
            padding: 24px;
            margin-bottom: 24px;
        }

        .alert {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px 24px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #374151;
        }

        input,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        input[readonly] {
            background-color: #f3f4f6;
            color: var(--text-muted);
        }

        .dish-row {
            display: grid;
            grid-template-columns: 1fr 90px;
            gap: 10px;
            margin-bottom: 10px;
        }

        .form-actions,
        .row-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .form-actions {
            justify-content: flex-end;
            margin-top: 14px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1080px;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            padding: 14px 16px;
            font-size: 0.86rem;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            font-size: 0.92rem;
            color: #4b5563;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f9fafb;
        }

        .badge {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .dish-list {
            max-width: 260px;
            line-height: 1.6;
        }

        .amount {
            color: #111827;
            font-weight: 700;
            white-space: nowrap;
        }

        .no-data {
            padding: 40px;
            text-align: center;
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        @media (max-width: 760px) {
            .brand-header,
            .action-bar,
            .form-actions {
                align-items: flex-start;
                flex-direction: column;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="brand-header">
        <h1 class="brand-title">🍅 紅番茄</h1>
        <a href="logout.php" style="background-color: #fee2e2; color: #b91c1c; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem;">登出系統</a>
    </div>

    <div class="action-bar">
        <div>
            <h2>用餐紀錄一覽</h2>
            <div style="color: var(--text-muted);">查看、修改或刪除目前系統中已建立的用餐紀錄。</div>
        </div>
        <a href="staff_dashboard.php" class="btn btn-back">返回服務員首頁</a>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert"><?php echo h($message); ?></div>
    <?php endif; ?>

    <?php if ($edit_record): ?>
        <div class="edit-card">
            <h2>修改用餐紀錄 <?php echo h($edit_record['Record_ID']); ?></h2>
            <form method="POST" action="record_list.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="record_id" value="<?php echo h($edit_record['Record_ID']); ?>">

                <div class="form-grid">
                    <div>
                        <div class="form-group">
                            <label for="record_id_display">Record ID</label>
                            <input type="text" id="record_id_display" value="<?php echo h($edit_record['Record_ID']); ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label for="customer_id">顧客</label>
                            <select id="customer_id" name="customer_id" required>
                                <option value="">-- 請選擇顧客 --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo h($customer['Customer_ID']); ?>" <?php echo $customer['Customer_ID'] === $edit_record['Customer_ID'] ? 'selected' : ''; ?>>
                                        <?php echo h($customer['Customer_ID'] . ' - ' . $customer['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date">日期</label>
                            <input type="date" id="date" name="date" value="<?php echo h($edit_record['date']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="time">時間</label>
                            <input type="time" id="time" name="time" value="<?php echo h(substr($edit_record['time'], 0, 5)); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="table_number">桌號</label>
                            <input type="text" id="table_number" name="table_number" value="<?php echo h($edit_record['table_number']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="number_of_customers">顧客人數</label>
                            <input type="number" id="number_of_customers" name="number_of_customers" min="1" value="<?php echo h($edit_record['number_of_customers']); ?>" required>
                        </div>
                    </div>

                    <div>
                        <div class="form-group">
                            <label for="staff_id">服務員</label>
                            <select id="staff_id" name="staff_id" required>
                                <option value="">-- 請選擇服務員 --</option>
                                <?php foreach ($staffs as $staff): ?>
                                    <option value="<?php echo h($staff['Staff_ID']); ?>" <?php echo $staff['Staff_ID'] === $edit_record['Staff_ID'] ? 'selected' : ''; ?>>
                                        <?php echo h($staff['Staff_ID'] . ' - ' . $staff['name'] . ' (' . $staff['position'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <label>餐點內容</label>
                        <?php $dish_row_count = max(4, count($edit_dishes) + 1); ?>
                        <?php for ($i = 0; $i < $dish_row_count; $i++): ?>
                            <?php
                                $current_dish_id = isset($edit_dishes[$i]) ? $edit_dishes[$i]['Dish_ID'] : '';
                                $current_quantity = isset($edit_dishes[$i]) ? $edit_dishes[$i]['number'] : 0;
                            ?>
                            <div class="dish-row">
                                <select name="dish_id[]" class="dish-select">
                                    <option value="" data-price="0">-- 選擇餐點 --</option>
                                    <?php foreach ($dishes as $dish): ?>
                                        <option value="<?php echo h($dish['Dish_ID']); ?>" data-price="<?php echo h($dish['price']); ?>" <?php echo $dish['Dish_ID'] === $current_dish_id ? 'selected' : ''; ?>>
                                            <?php echo h($dish['Dish_ID'] . ' - ' . $dish['name'] . ' ($' . number_format((float)$dish['price'], 0) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantity[]" class="quantity-input" min="0" value="<?php echo h($current_quantity); ?>" placeholder="數量">
                            </div>
                        <?php endfor; ?>

                        <div class="form-group">
                            <label for="subtotal">小計</label>
                            <input type="number" id="subtotal" step="1" min="0" readonly>
                        </div>

                        <div class="form-group">
                            <label for="discount">折扣</label>
                            <input type="number" id="discount" name="discount" min="0" max="1" step="0.01" value="<?php echo h($edit_record['discount']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="amount">總金額</label>
                            <input type="number" id="amount" name="amount" step="1" min="0" value="<?php echo h($edit_record['amount']); ?>" readonly required>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="record_list.php" class="btn btn-back">取消修改</a>
                    <button type="submit" class="btn btn-submit">儲存修改</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>紀錄編號</th>
                        <th>日期時間</th>
                        <th>桌號</th>
                        <th>人數</th>
                        <th>顧客</th>
                        <th>服務員</th>
                        <th>餐點內容</th>
                        <th>折扣</th>
                        <th>金額</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge"><?php echo h($row['Record_ID']); ?></span></td>
                                <td><?php echo h($row['date'] . ' ' . $row['time']); ?></td>
                                <td><?php echo h($row['table_number']); ?> 桌</td>
                                <td><?php echo h($row['number_of_customers']); ?> 人</td>
                                <td><?php echo h($row['customer_name'] ?? '未設定'); ?></td>
                                <td><?php echo h($row['staff_name'] ?? '未設定'); ?></td>
                                <td><div class="dish-list"><?php echo h($row['dish_summary'] ?? '尚無餐點'); ?></div></td>
                                <td><?php echo h($row['discount']); ?></td>
                                <td class="amount">$<?php echo h(number_format((float)$row['amount'], 0)); ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="record_list.php?edit_id=<?php echo urlencode($row['Record_ID']); ?>" class="btn btn-edit">修改</a>
                                        <form method="POST" action="record_list.php" onsubmit="return confirm('確定要刪除此筆用餐紀錄嗎？相關餐點、服務關係與回饋資料也會一併刪除。');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="record_id" value="<?php echo h($row['Record_ID']); ?>">
                                            <button type="submit" class="btn btn-delete">刪除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="no-data">目前沒有用餐紀錄。</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($edit_record): ?>
<script>
    function calculateTotal() {
        let subtotal = 0;
        const dishSelects = document.querySelectorAll('.dish-select');
        const quantityInputs = document.querySelectorAll('.quantity-input');

        dishSelects.forEach(function(select, index) {
            const price = parseFloat(select.options[select.selectedIndex].dataset.price || 0);
            const quantity = parseInt(quantityInputs[index].value || 0, 10);
            subtotal += price * quantity;
        });

        const discount = parseFloat(document.getElementById('discount').value || 1);
        document.getElementById('subtotal').value = subtotal.toFixed(0);
        document.getElementById('amount').value = (subtotal * discount).toFixed(0);
    }

    document.querySelectorAll('.dish-select, .quantity-input, #discount').forEach(function(element) {
        element.addEventListener('change', calculateTotal);
        element.addEventListener('input', calculateTotal);
    });

    calculateTotal();
</script>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>
