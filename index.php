<?php
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>餐廳顧客回饋管理系統</title>
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
            --accent-color: #f59e0b;
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

        h1 {
            text-align: center;
            color: var(--text-main);
            font-size: 2.5rem;
            margin-bottom: 30px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #10b981;
            color: white;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        .btn-record {
            background-color: #f59e0b;
            color: white;
            margin-left: 10px;
        }

        .btn-record:hover {
            background-color: #d97706;
            transform: translateY(-1px);
        }

        .btn-manager {
            background-color: #0f766e;
            color: white;
            margin-left: 10px;
        }

        .btn-manager:hover {
            background-color: #115e59;
            transform: translateY(-1px);
        }

        .btn-customer {
            background-color: #64B5F6;
            margin-left: 10px;
            color: white;
        }

        .btn-customer:hover {
            background-color: #42A5F5;
            transform: translateY(-1px);
        }

        .search-box {
            display: flex;
            gap: 8px;
        }

        .search-input {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
            width: 250px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-color);
            overflow: hidden;
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
            padding: 16px 20px;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 16px 20px;
            font-size: 0.95rem;
            color: #4b5563;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f9fafb;
        }

        .rating-stars {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 1.1rem;
        }

        .opinion-text {
            max-width: 350px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-links {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }

        .edit-btn {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .edit-btn:hover {
            background-color: #bae6fd;
        }

        .delete-btn {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .delete-btn:hover {
            background-color: #fecaca;
        }

        .no-data {
            padding: 40px;
            text-align: center;
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .badge {
            background-color: #f3f4f6;
            color: #4b5563;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>餐廳顧客回饋管理系統</h1>

    <div class="action-bar">
        <div>
            <a href="create.html" class="btn btn-primary">✍️ 新增回饋表單</a>
            <a href="advanced_query.php" class="btn btn-secondary">📊 進階統計報表</a>
            <a href="manager_home.php" class="btn btn-manager">管理者首頁</a>
            <a href="create_record.php" class="btn btn-record">建立用餐紀錄</a>
            <a href="customer_data.php" class="btn btn-customer">顧客總覽</a>
        </div>
        <form method="GET" action="index.php" class="search-box">
            <input type="text" name="search" placeholder="搜尋顧客、桌號或意見..." 
                   class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="btn btn-primary">搜尋</button>
            <?php if (isset($_GET['search']) && $_GET['search'] !== ''): ?>
                <a href="index.php" class="btn" style="background-color: #9ca3af; color: white;">清除</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>編號</th>
                    <th>顧客姓名</th>
                    <th>桌號</th>
                    <th>整體評分</th>
                    <th>日期時間</th>
                    <th>整體意見</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                
                // JOIN query: Feedback_forms + Customers + Consumption_records
                $sql = "SELECT f.Feedback_ID, f.date, f.time, f.opinion, f.rating, c.name AS customer_name, cr.table_number
                        FROM Feedback_forms f
                        JOIN Customers c ON f.Customer_ID = c.Customer_ID
                        JOIN Consumption_records cr ON f.Record_ID = cr.Record_ID";

                if ($search !== '') {
                    $search_esc = $conn->real_escape_string($search);
                    $sql .= " WHERE c.name LIKE '%$search_esc%' 
                              OR cr.table_number LIKE '%$search_esc%' 
                              OR f.opinion LIKE '%$search_esc%'";
                }
                
                $sql .= " ORDER BY f.Feedback_ID DESC";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $stars = str_repeat("★", $row['rating']) . str_repeat("☆", 5 - $row['rating']);
                        echo "<tr>";
                        echo "<td><span class='badge'>" . htmlspecialchars($row['Feedback_ID']) . "</span></td>";
                        echo "<td><strong>" . htmlspecialchars($row['customer_name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['table_number']) . " 桌</td>";
                        echo "<td><span class='rating-stars'>" . $stars . "</span> (" . $row['rating'] . ")</td>";
                        echo "<td>" . htmlspecialchars($row['date'] . " " . $row['time']) . "</td>";
                        echo "<td><div class='opinion-text' title='" . htmlspecialchars($row['opinion']) . "'>" . htmlspecialchars($row['opinion']) . "</div></td>";
                        echo "<td class='action-links'>
                                <a href='update.php?id=" . urlencode($row['Feedback_ID']) . "' class='action-btn edit-btn'>編輯</a>
                                <a href='delete.php?id=" . urlencode($row['Feedback_ID']) . "' onclick=\"return confirm('確定要刪除此筆回饋嗎？相關的細部評價也將一併刪除！')\" class='action-btn delete-btn'>刪除</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='no-data'>查無回饋資料</td></tr>";
                }
                
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
