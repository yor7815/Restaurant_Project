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

$summary_sql = "
    SELECT
        (SELECT COUNT(*) FROM Feedback_forms) AS feedback_count,
        (SELECT AVG(rating) FROM Feedback_forms) AS avg_feedback_rating,
        (SELECT COUNT(*) FROM Feedback_forms WHERE rating <= 2) AS low_feedback_count,
        (SELECT COUNT(*) FROM Staffs) AS staff_count,
        (
            SELECT AVG(fd.rating)
            FROM Feedback_details fd
            JOIN Rate_staff rs
              ON fd.Feedback_ID = rs.Feedback_ID
             AND fd.Detail_ID = rs.Detail_ID
        ) AS avg_staff_rating,
        (SELECT COUNT(*) FROM Rate_staff) AS staff_rating_count,
        (SELECT COALESCE(SUM(amount), 0) FROM Consumption_records) AS total_amount
";
$summary = $conn->query($summary_sql)->fetch_assoc();

$staff_sql = "
    SELECT
        s.Staff_ID,
        s.name,
        s.position,
        COALESCE(r.review_count, 0) AS review_count,
        r.avg_rating,
        COALESCE(r.low_score_count, 0) AS low_score_count,
        COALESCE(svc.service_count, 0) AS service_count
    FROM Staffs s
    LEFT JOIN (
        SELECT
            rs.Staff_ID,
            COUNT(*) AS review_count,
            AVG(fd.rating) AS avg_rating,
            SUM(CASE WHEN fd.rating <= 2 THEN 1 ELSE 0 END) AS low_score_count
        FROM Rate_staff rs
        JOIN Feedback_details fd
          ON rs.Feedback_ID = fd.Feedback_ID
         AND rs.Detail_ID = fd.Detail_ID
        GROUP BY rs.Staff_ID
    ) r ON s.Staff_ID = r.Staff_ID
    LEFT JOIN (
        SELECT Staff_ID, COUNT(DISTINCT Record_ID) AS service_count
        FROM Service
        GROUP BY Staff_ID
    ) svc ON s.Staff_ID = svc.Staff_ID
    ORDER BY r.avg_rating IS NULL, r.avg_rating DESC, r.review_count DESC, s.Staff_ID
    LIMIT 6
";
$staff_result = $conn->query($staff_sql);

$low_feedback_sql = "
    SELECT
        f.Feedback_ID,
        f.date,
        f.time,
        f.rating,
        f.opinion,
        c.name AS customer_name,
        cr.table_number
    FROM Feedback_forms f
    JOIN Customers c ON f.Customer_ID = c.Customer_ID
    JOIN Consumption_records cr ON f.Record_ID = cr.Record_ID
    WHERE f.rating <= 3
    ORDER BY f.rating ASC, f.date DESC, f.time DESC
    LIMIT 6
";
$low_feedback_result = $conn->query($low_feedback_sql);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理者首頁 - 餐廳顧客回饋管理系統</title>
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
            --green: #0f8b5f;
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
            letter-spacing: 0;
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

        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .metric,
        .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(23, 32, 51, 0.06);
        }

        .metric {
            padding: 18px;
        }

        .metric-label {
            color: var(--muted);
            font-size: 0.86rem;
            font-weight: 600;
        }

        .metric-value {
            margin-top: 8px;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .metric-note {
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .panel {
            overflow: hidden;
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
            max-width: 290px;
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
            .topbar,
            .grid {
                display: grid;
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: flex-start;
            }

            .metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .metrics {
                grid-template-columns: 1fr;
            }

            .table-wrap {
                overflow-x: auto;
            }

            table {
                min-width: 620px;
            }
        }
    </style>
</head>
<body>
<main class="page">
    <header class="topbar">
        <div>
            <h1>管理者首頁</h1>
            <p class="subtitle">快速查看營運概況、整體滿意度與員工服務評分。</p>
        </div>
        <nav class="actions">
            <a class="btn btn-light" href="index.php">回到回饋列表</a>
            <a class="btn" href="staff_stats.php">員工評分統計</a>
            <a class="btn btn-light" href="advanced_query.php">進階統計報表</a>
        </nav>
    </header>

    <section class="metrics">
        <div class="metric">
            <div class="metric-label">總回饋數</div>
            <div class="metric-value"><?php echo e($summary['feedback_count']); ?></div>
            <div class="metric-note">目前系統中的顧客回饋</div>
        </div>
        <div class="metric">
            <div class="metric-label">整體平均評分</div>
            <div class="metric-value"><?php echo e(rating_text($summary['avg_feedback_rating'])); ?></div>
            <div class="metric-note"><?php echo stars($summary['avg_feedback_rating']); ?></div>
        </div>
        <div class="metric">
            <div class="metric-label">員工平均評分</div>
            <div class="metric-value"><?php echo e(rating_text($summary['avg_staff_rating'])); ?></div>
            <div class="metric-note"><?php echo e($summary['staff_rating_count']); ?> 筆員工評分</div>
        </div>
        <div class="metric">
            <div class="metric-label">累計消費金額</div>
            <div class="metric-value">$<?php echo e(number_format((float)$summary['total_amount'], 0)); ?></div>
            <div class="metric-note"><?php echo e($summary['staff_count']); ?> 位員工，<?php echo e($summary['low_feedback_count']); ?> 筆低分回饋</div>
        </div>
    </section>

    <section class="grid">
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">員工評分排行</h2>
                <a class="link" href="staff_stats.php">查看全部</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>員工</th>
                            <th>職位</th>
                            <th>平均評分</th>
                            <th>評分數</th>
                            <th>低分數</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($staff_result && $staff_result->num_rows > 0): ?>
                            <?php while ($staff = $staff_result->fetch_assoc()): ?>
                                <tr>
                                    <td><a class="link" href="staff_stats.php?staff_id=<?php echo urlencode($staff['Staff_ID']); ?>"><?php echo e($staff['name']); ?></a></td>
                                    <td><?php echo e($staff['position']); ?></td>
                                    <td><?php echo stars($staff['avg_rating']); ?> <?php echo e(rating_text($staff['avg_rating'])); ?></td>
                                    <td><?php echo e($staff['review_count']); ?></td>
                                    <td>
                                        <?php if ((int)$staff['low_score_count'] > 0): ?>
                                            <span class="badge badge-red"><?php echo e($staff['low_score_count']); ?> 筆</span>
                                        <?php else: ?>
                                            <span class="badge">0 筆</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty">目前沒有員工資料。</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">待關注回饋</h2>
                <span class="muted">整體評分 3 分以下</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>編號</th>
                            <th>顧客</th>
                            <th>評分</th>
                            <th>意見</th>
                            <th>日期</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($low_feedback_result && $low_feedback_result->num_rows > 0): ?>
                            <?php while ($feedback = $low_feedback_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge"><?php echo e($feedback['Feedback_ID']); ?></span></td>
                                    <td><?php echo e($feedback['customer_name']); ?></td>
                                    <td><?php echo stars($feedback['rating']); ?> <?php echo e($feedback['rating']); ?></td>
                                    <td><div class="opinion" title="<?php echo e($feedback['opinion']); ?>"><?php echo e($feedback['opinion']); ?></div></td>
                                    <td><?php echo e($feedback['date']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="empty">目前沒有低分回饋。</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
</body>
</html>
<?php $conn->close(); ?>
