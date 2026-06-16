<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: login.php');
    exit;
}
require_once 'db_config.php';

$message = "";
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $staff_id = isset($_POST['staff_id']) ? trim($_POST['staff_id']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $position = isset($_POST['position']) ? trim($_POST['position']) : '';

    try {
        if ($action === 'add') {
            if ($staff_id === '' || $name === '' || $position === '') {
                throw new Exception("請填寫完整員工資料。");
            }

            $stmt = $conn->prepare("INSERT INTO Staffs (Staff_ID, name, position) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $staff_id, $name, $position);
            if (!$stmt->execute()) {
                throw new Exception("新增失敗：" . $stmt->error);
            }
            $stmt->close();
            header('Location: staff.php');
            exit;
        }

        if ($action === 'update') {
            if ($staff_id === '' || $name === '' || $position === '') {
                throw new Exception("請填寫完整員工資料。");
            }

            $stmt = $conn->prepare("UPDATE Staffs SET name = ?, position = ? WHERE Staff_ID = ?");
            $stmt->bind_param("sss", $name, $position, $staff_id);
            if (!$stmt->execute()) {
                throw new Exception("修改失敗：" . $stmt->error);
            }
            $stmt->close();
            header('Location: staff.php');
            exit;
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $error = true;
    }
}

if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    $delete_id = $_GET['delete'];

    try {
        $stmt = $conn->prepare("DELETE FROM Staffs WHERE Staff_ID = ?");
        $stmt->bind_param("s", $delete_id);
        if (!$stmt->execute()) {
            throw new Exception("刪除失敗：" . $stmt->error);
        }
        $stmt->close();
        header('Location: staff.php');
        exit;
    } catch (Exception $e) {
        $message = "刪除失敗，可能此員工已被服務紀錄或回饋資料使用。";
        $error = true;
    }
}

$edit_staff = null;
if (isset($_GET['edit']) && $_GET['edit'] !== '') {
    $edit_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT Staff_ID, name, position FROM Staffs WHERE Staff_ID = ?");
    $stmt->bind_param("s", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_staff = $result->fetch_assoc();
    }
    $stmt->close();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$staffs = [];

if ($search !== '') {
    $keyword = "%" . $search . "%";
    $stmt = $conn->prepare("SELECT Staff_ID, name, position FROM Staffs WHERE Staff_ID LIKE ? OR name LIKE ? OR position LIKE ? ORDER BY Staff_ID");
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
} else {
    $stmt = $conn->prepare("SELECT Staff_ID, name, position FROM Staffs ORDER BY Staff_ID");
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staffs[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>員工資料表格</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 30px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-gray {
            background-color: #9ca3af;
            color: white;
        }

        .btn-edit {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 6px 12px;
        }

        .btn-delete {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 6px 12px;
        }

        .search-box {
            display: flex;
            gap: 8px;
        }

        input[type="text"] {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            box-sizing: border-box;
            font-family: inherit;
        }

        .search-input {
            width: 260px;
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            padding: 24px;
            margin-bottom: 24px;
        }

        .form-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--primary-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            align-items: end;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
            color: #4b5563;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .badge {
            background-color: #f3f4f6;
            color: #374151;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .no-data {
            text-align: center;
            color: var(--text-muted);
            padding: 30px;
        }

        @media (max-width: 800px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .search-input {
                width: 180px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #ef4444; padding-bottom: 15px;">
        <h1 style="margin: 0; color: #ef4444; font-size: 2.2rem; display: flex; align-items: center; gap: 8px;">🍅 紅番茄</h1>
        <div>
            <span style="font-size: 1rem; color: #4b5563; margin-right: 15px;">經理 <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> 您好</span>
            <a href="logout.php" style="background-color: #fee2e2; color: #b91c1c; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#fecaca'" onmouseout="this.style.backgroundColor='#fee2e2'">登出系統</a>
        </div>
    </div>

    <h2>員工資料管理</h2>

    <div class="top-bar">
        <a href="manager_home.php" class="btn btn-gray">⭠ 返回管理者首頁</a>
        <form method="GET" action="staff.php" class="search-box">
            <input type="text" name="search" class="search-input" placeholder="查詢員工編號、姓名或職位..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">查詢</button>
            <?php if ($search !== ''): ?>
                <a href="staff.php" class="btn btn-gray">清除</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="form-title"><?php echo $edit_staff ? '修改員工資料' : '新增員工資料'; ?></div>
        <form method="POST" action="staff.php">
            <input type="hidden" name="action" value="<?php echo $edit_staff ? 'update' : 'add'; ?>">
            <div class="form-grid">
                <div>
                    <label for="staff_id">員工編號</label>
                    <input type="text" id="staff_id" name="staff_id" value="<?php echo $edit_staff ? htmlspecialchars($edit_staff['Staff_ID']) : ''; ?>" <?php echo $edit_staff ? 'readonly' : ''; ?> required>
                </div>
                <div>
                    <label for="name">姓名</label>
                    <input type="text" id="name" name="name" value="<?php echo $edit_staff ? htmlspecialchars($edit_staff['name']) : ''; ?>" required>
                </div>
                <div>
                    <label for="position">職位</label>
                    <input type="text" id="position" name="position" value="<?php echo $edit_staff ? htmlspecialchars($edit_staff['position']) : ''; ?>" required>
                </div>
            </div>
            <div style="margin-top: 16px;">
                <button type="submit" class="btn btn-primary"><?php echo $edit_staff ? '送出修改' : '新增'; ?></button>
                <?php if ($edit_staff): ?>
                    <a href="staff.php" class="btn btn-gray">取消修改</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>員工編號</th>
                    <th>姓名</th>
                    <th>職位</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($staffs) > 0): ?>
                    <?php foreach ($staffs as $staff): ?>
                        <tr>
                            <td><span class="badge"><?php echo htmlspecialchars($staff['Staff_ID']); ?></span></td>
                            <td><strong><?php echo htmlspecialchars($staff['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($staff['position']); ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-edit" href="staff.php?edit=<?php echo urlencode($staff['Staff_ID']); ?>">修改</a>
                                    <a class="btn btn-delete" href="staff.php?delete=<?php echo urlencode($staff['Staff_ID']); ?>" onclick="return confirm('確定要刪除此員工資料嗎？')">刪除</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="no-data">查無員工資料</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); ?>
</body>
</html>
