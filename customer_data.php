<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

// 新增資料
if (isset($_POST['action']) && $_POST['action'] == 'create') {
    $Customer_ID = $_POST['Customer_ID'];
    $member_level = $_POST['member_level'];
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $phone_number = $_POST['phone_number'];

    $sql = "INSERT INTO `Customers` 
            (`Customer_ID`, `member_level`, `name`, `gender`, `age`, `phone_number`)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssis", $Customer_ID, $member_level, $name, $gender, $age, $phone_number);

    if ($stmt->execute()) {
        echo "<script>alert('新增成功！'); window.location.href='customer_data.php';</script>";
        exit;
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('createDialog').showModal();
                alert('新增失敗：Customer_ID 可能已存在');
            });
        </script>";
    }
}

// 修改資料
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $Customer_ID = $_POST['Customer_ID'];
    $member_level = $_POST['member_level'];
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $phone_number = $_POST['phone_number'];

    $sql = "UPDATE `Customers`
            SET `member_level` = ?,
                `name` = ?,
                `gender` = ?,
                `age` = ?,
                `phone_number` = ?
            WHERE `Customer_ID` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiss", $member_level, $name, $gender, $age, $phone_number, $Customer_ID);

    if ($stmt->execute()) {
        echo "<script>alert('修改成功！'); window.location.href='customer_data.php';</script>";
        exit;
    } else {
        echo "<script>alert('修改失敗！');</script>";
    }
}

// 刪除資料
if (isset($_GET['delete_id'])) {
    $Customer_ID = $_GET['delete_id'];
    $sql = "DELETE FROM `Customers` WHERE `Customer_ID` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $Customer_ID);

    if ($stmt->execute()) {
        echo "<script>alert('刪除成功！'); window.location.href='customer_data.php';</script>";
        exit;
    } else {
        echo "<script>alert('刪除失敗！');</script>";
    }
}

// 查詢資料
$keyword = "";
if (isset($_GET['keyword'])) {
    $keyword = $_GET['keyword'];
    $search = "%" . $keyword . "%";
    $sql = "SELECT * FROM `Customers`
            WHERE `Customer_ID` LIKE ?
               OR `name` LIKE ?
               OR `member_level` LIKE ?
               OR `phone_number` LIKE ?
            ORDER BY `Customer_ID` ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $customers = $stmt->get_result();
} else {
    $sql = "SELECT * FROM `Customers` ORDER BY `Customer_ID` ASC";
    $customers = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>顧客資料管理系統</title>
    <style>
        body {
            font-family: Arial, "Microsoft JhengHei", sans-serif;
            background-color: #f5f5f5;
        }
        h1, h2 { text-align: center; }
        .container {
            width: 90%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border: 1px solid #ccc;
        }
        /* 表格容器 */
        .table-wrap {
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            background-color: white;
        }

        thead tr {
            background: #f8f8f8;
        }

        thead th {
            padding: 11px 14px;
            text-align: left;
            font-weight: 500;
            color: #555;
            font-size: 13px;
            border-bottom: 1px solid #e8e8e8;
        }

        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background .15s;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        tbody td {
            padding: 11px 14px;
            color: #222;
            vertical-align: middle;
        }

        /* 顧客編號 */
        .id-code {
            font-family: monospace;
            font-size: 13px;
            color: #555;
            background: #f5f5f5;
            padding: 2px 7px;
            border-radius: 4px;
        }

        /* 會員等級徽章 */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-gold   { background: #FEF3C7; color: #92400E; }
        .badge-silver { background: #F1F5F9; color: #475569; }
        .badge-normal { background: #F0FDF4; color: #166534; }

        /* 性別圓點 */
        .gender-dot {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 500;
        }
        .gender-m { background: #EFF6FF; color: #1D4ED8; }
        .gender-f { background: #FDF2F8; color: #9D174D; }
        .gender-o { background: #F5F3FF; color: #5B21B6; }

        /* 操作按鈕 */
        .action-btn {
            padding: 5px 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 13px;
            cursor: pointer;
            background: #fff;
            text-decoration: none;
            color: #222;
            display: inline-block;
        }
        .action-btn:hover { background: #f0f0f0; }

        .del-btn { color: #dc2626; border-color: #fecaca; }
        .del-btn:hover { background: #fff5f5; }

        /* 新增按鈕 */
        .add-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #222;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        .add-btn:hover { background: #444; }
        .btn-button { background: #64B5F6; }
        input, select { padding: 6px; margin: 4px; }
        .form-table { width: 70%; margin: 0 auto 30px auto; }
        .form-table th { width: 30%; }
        .btn {
            padding: 6px 12px;
            text-decoration: none;
            border: 1px solid #333;
            background-color: #eee;
            color: black;
            cursor: pointer;
        }
        .btn:hover { background-color: #ddd; }
        .delete-btn { color: red; }

        dialog {
            border: none;
            border-radius: 8px;
            padding: 0;
            width: 460px;
            max-width: 95vw;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        }
        dialog::backdrop {
            background: rgba(0, 0, 0, 0.45);
        }
        .dialog-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #ddd;
        }
        .dialog-header h2 {
            margin: 0;
            font-size: 18px;
            text-align: left;
        }
        .dialog-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #666;
            line-height: 1;
            padding: 0;
        }
        .dialog-close:hover { color: #000; }
        .dialog-body { padding: 20px; }
        .dialog-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 20px;
            border-top: 1px solid #ddd;
            background: #f9f9f9;
            border-radius: 0 0 8px 8px;
        }
        .btn-primary {
            padding: 7px 16px;
            background: #333;
            color: white;
            border: 1px solid #333;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-primary:hover { background: #555; }
        .btn-secondary {
            padding: 7px 16px;
            background: #eee;
            color: #333;
            border: 1px solid #ccc;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-secondary:hover { background: #ddd; }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #ef4444; padding-bottom: 15px;">
        <h1 style="margin: 0; color: #ef4444; font-size: 2.2rem; display: flex; align-items: center; gap: 8px;">🍅 紅番茄</h1>
        <div>
            <span style="font-size: 1rem; color: #4b5563; margin-right: 15px;">經理您好</span>
            <a href="logout.php" style="background-color: #fee2e2; color: #b91c1c; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#fecaca'" onmouseout="this.style.backgroundColor='#fee2e2'">登出系統</a>
        </div>
    </div>

    <h2>顧客資料管理</h2>

    <!-- 工具列：新增按鈕 + 查詢 -->
    <div class="toolbar">
        <div>
            <a href="manager_home.php" style="text-decoration: none; margin-right: 10px; color: #4f46e5; font-weight: 500;">⭠ 返回管理者首頁</a>
            <button class="add-btn" onclick="document.getElementById('createDialog').showModal()">＋ 新增顧客</button>
        </div>
        <form method="get" action="customer_data.php" style="display:flex;gap:6px;">
            <input type="text" name="keyword" placeholder="顧客編號、姓名、會員等級或電話"
                   value="<?php echo htmlspecialchars($keyword); ?>" style="width:280px;">
            <input type="submit" class="btn" value="查詢">
        </form>
    </div>

    <!-- 顧客資料列表 -->
    <h2>顧客資料列表</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>顧客編號</th>
                    <th>姓名</th>
                    <th>會員等級</th>
                    <th>性別</th>
                    <th>年齡</th>
                    <th>電話</th>
                    <th style="text-align:center">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($customers && $customers->num_rows > 0): ?>
                    <?php while ($row = $customers->fetch_assoc()): ?>
                        <?php
                            // 會員等級對應 class
                            $badge_class = 'badge-normal';
                            if ($row['member_level'] == '金卡會員') $badge_class = 'badge-gold';
                            elseif ($row['member_level'] == '銀卡會員') $badge_class = 'badge-silver';

                            // 性別對應 class
                            $gender_class = 'gender-o';
                            if ($row['gender'] == 'M') $gender_class = 'gender-m';
                            elseif ($row['gender'] == 'F') $gender_class = 'gender-f';
                        ?>
                        <tr>
                            <td><span class="id-code"><?php echo htmlspecialchars($row['Customer_ID']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['member_level']); ?></span></td>
                            <td><span class="gender-dot <?php echo $gender_class; ?>"><?php echo htmlspecialchars($row['gender']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['age']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td style="text-align:center">
                                <button class="action-btn" onclick="openEditDialog(
                                    '<?php echo htmlspecialchars($row['Customer_ID'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['member_level'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['gender'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['age'], ENT_QUOTES); ?>',
                                    '<?php echo htmlspecialchars($row['phone_number'], ENT_QUOTES); ?>'
                                )">修改</button>
                                <a class="action-btn del-btn"
                                href="customer_data.php?delete_id=<?php echo urlencode($row['Customer_ID']); ?>"
                                onclick="return confirm('確定要刪除這筆顧客資料嗎？');">刪除</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;padding:24px;color:#888;">查無資料</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== 新增顧客 Dialog ===== -->
<dialog id="createDialog">
    <div class="dialog-header">
        <h2>新增顧客資料</h2>
        <button class="dialog-close" onclick="document.getElementById('createDialog').close()" type="button">✕</button>
    </div>
    <form method="post" action="customer_data.php">
        <input type="hidden" name="action" value="create">
        <div class="dialog-body">
            <table class="form-table" style="width:100%">
                <tr>
                    <th>顧客編號</th>
                    <td><input type="text" name="Customer_ID" required style="width:90%"></td>
                </tr>
                <tr>
                    <th>會員等級</th>
                    <td>
                        <select name="member_level" required style="width:90%">
                            <option value="">請選擇會員等級</option>
                            <option value="一般顧客">一般顧客</option>
                            <option value="白銀會員">白銀會員</option>
                            <option value="黃金會員">黃金會員</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>姓名</th>
                    <td><input type="text" name="name" required style="width:90%"></td>
                </tr>
                <tr>
                    <th>性別</th>
                    <td>
                        <select name="gender" required>
                            <option value="">請選擇</option>
                            <option value="M">M</option>
                            <option value="F">F</option>
                            <option value="O">O</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>年齡</th>
                    <td><input type="number" name="age" min="0" style="width:90%"></td>
                </tr>
                <tr>
                    <th>電話</th>
                    <td><input type="text" name="phone_number" style="width:90%"></td>
                </tr>
            </table>
        </div>
        <div class="dialog-footer">
            <button type="button" class="btn-secondary"
                    onclick="document.getElementById('createDialog').close()">取消</button>
            <button type="submit" class="btn-primary">新增資料</button>
        </div>
    </form>
</dialog>

<!-- ===== 修改顧客 Dialog ===== -->
<dialog id="editDialog">
    <div class="dialog-header">
        <h2>修改顧客資料</h2>
        <button class="dialog-close" onclick="document.getElementById('editDialog').close()" type="button">✕</button>
    </div>
    <form method="post" action="customer_data.php">
        <input type="hidden" name="action" value="update">
        <div class="dialog-body">
            <table class="form-table" style="width:100%">
                <tr>
                    <th>顧客編號</th>
                    <td>
                        <!-- readonly，值由 JS 填入 -->
                        <input type="text" name="Customer_ID" id="edit_Customer_ID" readonly style="width:90%; background:#f0f0f0;">
                    </td>
                </tr>
                <tr>
                    <th>會員等級</th>
                    <td>
                        <select name="member_level" id="edit_member_level" required style="width:90%">
                            <option value="">請選擇會員等級</option>
                            <option value="一般顧客">一般顧客</option>
                            <option value="白銀會員">白銀會員</option>
                            <option value="黃金會員">黃金會員</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>姓名</th>
                    <td><input type="text" name="name" id="edit_name" required style="width:90%"></td>
                </tr>
                <tr>
                    <th>性別</th>
                    <td>
                        <select name="gender" id="edit_gender" required>
                            <option value="">請選擇</option>
                            <option value="M">M</option>
                            <option value="F">F</option>
                            <option value="O">O</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>年齡</th>
                    <td><input type="number" name="age" id="edit_age" min="0" style="width:90%"></td>
                </tr>
                <tr>
                    <th>電話</th>
                    <td><input type="text" name="phone_number" id="edit_phone_number" style="width:90%"></td>
                </tr>
            </table>
        </div>
        <div class="dialog-footer">
            <button type="button" class="btn-secondary"
                    onclick="document.getElementById('editDialog').close()">取消</button>
            <button type="submit" class="btn-primary">更新資料</button>
        </div>
    </form>
</dialog>

<script>
function openEditDialog(id, member_level, name, gender, age, phone) {
    document.getElementById('edit_Customer_ID').value   = id;
    document.getElementById('edit_member_level').value  = member_level;
    document.getElementById('edit_name').value          = name;
    document.getElementById('edit_gender').value        = gender;
    document.getElementById('edit_age').value           = age;
    document.getElementById('edit_phone_number').value  = phone;

    document.getElementById('editDialog').showModal();
}
</script>

</body>
</html>
