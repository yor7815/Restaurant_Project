<?php
require_once 'db_config.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rating_text($value) {
    return $value === null ? '尚無評分' : number_format((float)$value, 1);
}

function stars($value) {
    if ($value === null) {
        return '<span class="muted">尚無</span>';
    }

    $filled = max(0, min(5, (int)round((float)$value)));
    return '<span class="stars">' . str_repeat('★', $filled) . str_repeat('☆', 5 - $filled) . '</span>';
}

$selected_staff_id = isset($_GET['staff_id']) ? trim($_GET['staff_id']) : '';

$staff_options = [];
$options_result = $conn->query("SELECT Staff_ID, name, position FROM Staffs ORDER BY Staff_ID");
if ($options_result) {
    while ($row = $options_result->fetch_assoc()) {
        $staff_options[] = $row;
    }
}

$staff_sql = "
    SELECT
        s.Staff_ID,
        s.name,
        s.position,
        COALESCE(r.review_count, 0) AS review_count,
        r.avg_rating,
        COALESCE(r.five_star_count, 0) AS five_star_count,
        COALESCE(r.low_score_count, 0) AS low_score_count,
        r.last_feedback_date,
        COALESCE(svc.service_count, 0) AS service_count
    FROM Staffs s
    LEFT JOIN (
        SELECT
            rs.Staff_ID,
            COUNT(*) AS review_count,
            AVG(fd.rating) AS avg_rating,
            SUM(CASE WHEN fd.rating = 5 THEN 1 ELSE 0 END) AS five_star_count,
            SUM(CASE WHEN fd.rating <= 2 THEN 1 ELSE 0 END) AS low_score_count,
            MAX(ff.date) AS last_feedback_date
        FROM Rate_staff rs
        JOIN Feedback_details fd
          ON rs.Feedback_ID = fd.Feedback_ID
         AND rs.Detail_ID = fd.Detail_ID
        LEFT JOIN Feedback_forms ff
          ON fd.Feedback_ID = ff.Feedback_ID
        GROUP BY rs.Staff_ID
    ) r ON s.Staff_ID = r.Staff_ID
    LEFT JOIN (
        SELECT Staff_ID, COUNT(DISTINCT Record_ID) AS service_count
        FROM Service
        GROUP BY Staff_ID
    ) svc ON s.Staff_ID = svc.Staff_ID
";

if ($selected_staff_id !== '') {
    $staff_sql .= " WHERE s.Staff_ID = ?";
}

$staff_sql .= " ORDER BY r.avg_rating IS NULL, r.avg_rating DESC, r.review_count DESC, s.Staff_ID";

if ($selected_staff_id !== '') {
    $stmt = $conn->prepare($staff_sql);
    $stmt->bind_param('s', $selected_staff_id);
    $stmt->execute();
    $staff_result = $stmt->get_result();
} else {
    $staff_result = $conn->query($staff_sql);
}

$detail_sql = "
    SELECT
        fd.Feedback_ID,
        fd.Detail_ID,
        fd.rating,
        fd.opinion,
        ff.date,
        ff.time,
        c.name AS customer_name,
        cr.table_number,
        s.Staff_ID,
        s.name AS staff_name
    FROM Rate_staff rs
    JOIN Feedback_details fd
      ON rs.Feedback_ID = fd.Feedback_ID
     AND rs.Detail_ID = fd.Detail_ID
    JOIN Feedback_forms ff
      ON fd.Feedback_ID = ff.Feedback_ID
    JOIN Customers c
      ON ff.Customer_ID = c.Customer_ID
    JOIN Consumption_records cr
      ON ff.Record_ID = cr.Record_ID
    JOIN Staffs s
      ON rs.Staff_ID = s.Staff_ID
";

if ($selected_staff_id !== '') {
    $detail_sql .= " WHERE s.Staff_ID = ?";
}

$detail_sql .= " ORDER BY ff.date DESC, ff.time DESC, fd.Feedback_ID DESC";

if ($selected_staff_id !== '') {
    $detail_stmt = $conn->prepare($detail_sql);
    $detail_stmt->bind_param('s', $selected_staff_id);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();
} else {
    $detail_result = $conn->query($detail_sql);
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>員工評分統計 - 餐廳顧客回饋管理系統</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f7fb;
            --card: #ffffff;
            --text: #172033;
            --muted: #667085;
            --border: #dfe4ea;
            --primary: #3157d5;
            --primary-dark: #2445ac;
            --amber: #b76e00;
            --red: #bc3030;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Noto Sans TC', Arial, sans-serif;
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 20px 44px;
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
        }

        h1 {
            margin: 0;
            font-size: 2rem;
        }

        .subtitle {
            margin: 6px 0 0;
            color: var(--muted);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            border-radius: 8px;
            border: 1px solid transparent;
            padding: 9px 15px;
            background: var(--primary);
            color: #ffffff;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .btn-light {
            background: #ffffff;
            color: var(--text);
            border-color: var(--border);
        }

        .btn-light:hover {
            background: #f8fafc;
        }

        .filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 18px;
            padding: 16px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        select {
            min-height: 40px;
            min-width: 260px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            font: inherit;
            background: #ffffff;
        }

        .panel {
            margin-bottom: 18px;
            overflow: hidden;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(23, 32, 51, 0.06);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--border);
        }

        .panel-title {
            margin: 0;
            font-size: 1.12rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
            font-size: 0.92rem;
        }

        th {
            background: #f8fafc;
            color: #344054;
            font-size: 0.82rem;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .stars {
            color: var(--amber);
            white-space: nowrap;
        }

        .muted {
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            border-radius: 999px;
            padding: 3px 9px;
            background: #eef2ff;
            color: var(--primary-dark);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .badge-red {
            background: #fdecec;
            color: var(--red);
        }

        .link {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .link:hover {
            text-decoration: underline;
        }

        .opinion {
            max-width: 420px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .empty {
            padding: 28px 20px;
            color: var(--muted);
            text-align: center;
        }

        @media (max-width: 900px) {
            .topbar {
                display: grid;
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: flex-start;
            }

            .table-wrap {
                overflow-x: auto;
            }

            table {
                min-width: 820px;
            }
        }
    </style>
</head>
<body>
<main class="page">
    <header class="topbar">
        <div>
            <h1>員工評分統計</h1>
            <p class="subtitle">依員工彙整服務評分，並列出每筆服務意見明細。</p>
        </div>
        <nav class="actions">
            <a class="btn btn-light" href="index.php">回到回饋列表</a>
            <a class="btn btn-light" href="manager_home.php">管理者首頁</a>
            <a class="btn" href="create.php">新增回饋</a>
        </nav>
    </header>

    <form class="filter" method="get" action="staff_stats.php">
        <label for="staff_id">篩選員工</label>
        <select id="staff_id" name="staff_id">
            <option value="">全部員工</option>
            <?php foreach ($staff_options as $staff): ?>
                <option value="<?php echo e($staff['Staff_ID']); ?>" <?php echo $selected_staff_id === $staff['Staff_ID'] ? 'selected' : ''; ?>>
                    <?php echo e($staff['name'] . ' (' . $staff['Staff_ID'] . ' / ' . $staff['position'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">套用</button>
        <?php if ($selected_staff_id !== ''): ?>
            <a class="btn btn-light" href="staff_stats.php">清除</a>
        <?php endif; ?>
    </form>

    <section class="panel">
        <div class="panel-header">
            <h2 class="panel-title">員工評分總覽</h2>
            <span class="muted">平均分數、評分筆數與服務紀錄</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>員工編號</th>
                        <th>姓名</th>
                        <th>職位</th>
                        <th>平均評分</th>
                        <th>評分筆數</th>
                        <th>五星數</th>
                        <th>低分數</th>
                        <th>服務紀錄</th>
                        <th>最近評分日</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($staff_result && $staff_result->num_rows > 0): ?>
                        <?php while ($staff = $staff_result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge"><?php echo e($staff['Staff_ID']); ?></span></td>
                                <td><strong><?php echo e($staff['name']); ?></strong></td>
                                <td><?php echo e($staff['position']); ?></td>
                                <td><?php echo stars($staff['avg_rating']); ?> <?php echo e(rating_text($staff['avg_rating'])); ?></td>
                                <td><?php echo e($staff['review_count']); ?></td>
                                <td><?php echo e($staff['five_star_count']); ?></td>
                                <td>
                                    <?php if ((int)$staff['low_score_count'] > 0): ?>
                                        <span class="badge badge-red"><?php echo e($staff['low_score_count']); ?> 筆</span>
                                    <?php else: ?>
                                        <span class="badge">0 筆</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($staff['service_count']); ?></td>
                                <td><?php echo e($staff['last_feedback_date'] ?: '尚無'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="empty">目前沒有符合條件的員工資料。</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <h2 class="panel-title">員工評分明細</h2>
            <span class="muted">來自 Feedback_details 與 Rate_staff 的服務評價</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>回饋編號</th>
                        <th>員工</th>
                        <th>顧客</th>
                        <th>桌號</th>
                        <th>評分</th>
                        <th>服務意見</th>
                        <th>日期時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detail_result && $detail_result->num_rows > 0): ?>
                        <?php while ($detail = $detail_result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge"><?php echo e($detail['Feedback_ID']); ?></span></td>
                                <td><a class="link" href="staff_stats.php?staff_id=<?php echo urlencode($detail['Staff_ID']); ?>"><?php echo e($detail['staff_name']); ?></a></td>
                                <td><?php echo e($detail['customer_name']); ?></td>
                                <td><?php echo e($detail['table_number']); ?></td>
                                <td><?php echo stars($detail['rating']); ?> <?php echo e($detail['rating']); ?></td>
                                <td><div class="opinion" title="<?php echo e($detail['opinion']); ?>"><?php echo e($detail['opinion']); ?></div></td>
                                <td><?php echo e($detail['date'] . ' ' . substr($detail['time'], 0, 5)); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty">目前沒有員工評分明細。</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
<?php
if (isset($stmt)) {
    $stmt->close();
}
if (isset($detail_stmt)) {
    $detail_stmt->close();
}
$conn->close();
?>
