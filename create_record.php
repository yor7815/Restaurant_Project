<?php
require_once 'db_config.php';

$message = "";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$next_record_id = 'R001';
$res = $conn->query("SELECT MAX(CAST(SUBSTRING(Record_ID, 2) AS UNSIGNED)) AS max_id FROM Consumption_records WHERE Record_ID REGEXP '^R[0-9]+$'");
if ($res && $row = $res->fetch_assoc()) {
    $next_number = ((int)$row['max_id']) + 1;
    $next_record_id = 'R' . str_pad((string)$next_number, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_id = isset($_POST['record_id']) ? trim($_POST['record_id']) : $next_record_id;
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
            $stmt = $conn->prepare("INSERT INTO Consumption_records (Record_ID, date, time, table_number, number_of_customers, discount, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssidd", $record_id, $date, $time, $table_number, $number_of_customers, $discount, $amount);
            if (!$stmt->execute()) {
                throw new Exception("新增用餐紀錄失敗：" . $stmt->error);
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO `Has` (Customer_ID, Record_ID) VALUES (?, ?)");
            $stmt->bind_param("ss", $customer_id, $record_id);
            if (!$stmt->execute()) {
                throw new Exception("建立顧客與用餐紀錄關係失敗：" . $stmt->error);
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO `Contain` (Record_ID, Dish_ID, number) VALUES (?, ?, ?)");
            foreach ($selected_dishes as $dish) {
                $dish_id = $dish['dish_id'];
                $quantity = $dish['quantity'];
                $stmt->bind_param("ssi", $record_id, $dish_id, $quantity);
                if (!$stmt->execute()) {
                    throw new Exception("建立餐點明細失敗：" . $stmt->error);
                }
            }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO `Service` (Record_ID, Staff_ID, Customer_ID) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $record_id, $staff_id, $customer_id);
            if (!$stmt->execute()) {
                throw new Exception("建立服務員關係失敗：" . $stmt->error);
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>建立用餐紀錄</title>
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
        }

        body {
            font-family: 'Noto Sans TC', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        h1 {
            text-align: center;
            margin: 0 0 24px;
            font-size: 2rem;
        }

        .alert {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .form-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px 36px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
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
            box-sizing: border-box;
        }

        input[readonly] {
            background-color: #f3f4f6;
            color: var(--text-muted);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 12px;
        }

        .dish-row {
            display: grid;
            grid-template-columns: 1fr 90px;
            gap: 10px;
            margin-bottom: 10px;
        }

        .btn-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
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

        @media (max-width: 760px) {
            .form-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h1>建立用餐紀錄</h1>

        <?php if ($message !== ""): ?>
            <div class="alert"><?php echo h($message); ?></div>
        <?php endif; ?>

        <form action="create_record.php" method="POST">
            <div class="form-layout">
                <div>
                    <div class="section-title">基本資料</div>

                    <div class="form-group">
                        <label for="record_id">Record ID</label>
                        <input type="text" id="record_id" name="record_id" value="<?php echo h(isset($_POST['record_id']) ? $_POST['record_id'] : $next_record_id); ?>" readonly required>
                    </div>

                    <div class="form-group">
                        <label for="customer_id">Customer ID</label>
                        <select id="customer_id" name="customer_id" required>
                            <option value="">-- 請選擇顧客 --</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo h($customer['Customer_ID']); ?>">
                                    <?php echo h($customer['Customer_ID'] . " - " . $customer['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date">日期</label>
                        <input type="date" id="date" name="date" required>
                    </div>

                    <div class="form-group">
                        <label for="time">時間</label>
                        <input type="time" id="time" name="time" required>
                    </div>

                    <div class="form-group">
                        <label for="table_number">桌號</label>
                        <input type="text" id="table_number" name="table_number" placeholder="例如 T06" required>
                    </div>

                    <div class="form-group">
                        <label for="number_of_customers">顧客人數</label>
                        <input type="number" id="number_of_customers" name="number_of_customers" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="subtotal">小計</label>
                        <input type="number" id="subtotal" step="1" min="0" readonly>
                    </div>

                    <div class="form-group">
                        <label for="discount">折扣</label>
                        <input type="number" id="discount" name="discount" min="0" max="1" step="0.01" value="1.00" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">總金額</label>
                        <input type="number" id="amount" name="amount" step="1" min="0" readonly required>
                    </div>
                </div>

                <div>
                    <div class="section-title">選擇菜色</div>
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="dish-row">
                            <select name="dish_id[]" class="dish-select">
                                <option value="" data-price="0">-- 選擇餐點 --</option>
                                <?php foreach ($dishes as $dish): ?>
                                    <option value="<?php echo h($dish['Dish_ID']); ?>" data-price="<?php echo h($dish['price']); ?>">
                                        <?php echo h($dish['Dish_ID'] . " - " . $dish['name'] . " ($" . number_format((float)$dish['price'], 0) . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="quantity[]" class="quantity-input" min="0" value="0" placeholder="數量">
                        </div>
                    <?php endfor; ?>

                    <div class="form-group" style="margin-top: 32px;">
                        <label for="staff_id">服務員編號</label>
                        <select id="staff_id" name="staff_id" required>
                            <option value="">-- 請選擇服務員 --</option>
                            <?php foreach ($staffs as $staff): ?>
                                <option value="<?php echo h($staff['Staff_ID']); ?>">
                                    <?php echo h($staff['Staff_ID'] . " - " . $staff['name'] . " (" . $staff['position'] . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="btn-row">
                <a href="staff_dashboard.php" class="btn btn-cancel">回首頁</a>
                <button type="submit" class="btn btn-submit">建立</button>
            </div>
        </form>
    </div>
</div>

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

</body>
</html>
<?php $conn->close(); ?>
