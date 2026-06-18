<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
        header('Location: customer_dashboard.php');
    } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
        header('Location: staff_dashboard.php');
    } else {
        header('Location: login.php');
    }
    exit;
}
require_once 'db_config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>進階統計報表 - 餐廳顧客回饋系統</title>
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
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 40px;
            font-size: 1.1rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 24px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .report-section {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            padding: 24px;
            margin-bottom: 30px;
        }

        .report-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 8px;
            border-bottom: 2px solid #eef2ff;
            padding-bottom: 8px;
        }

        .report-description {
            font-size: 0.95rem;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .sql-box {
            background-color: #f3f4f6;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
            color: #374151;
            margin-bottom: 16px;
            overflow-x: auto;
            border-left: 4px solid #9ca3af;
            white-space: pre-wrap;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin-top: 10px;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            padding: 12px 16px;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 12px 16px;
            font-size: 0.9rem;
            color: #4b5563;
            border-bottom: 1px solid var(--border-color);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .rating-stars {
            color: var(--accent-color);
            font-weight: bold;
        }

        .badge-info {
            background-color: #e0e7ff;
            color: #3730a3;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #ef4444; padding-bottom: 15px;">
        <h1 style="margin: 0; color: #ef4444; font-size: 2.2rem; display: flex; align-items: center; gap: 8px;">🍅 紅番茄</h1>
        <div>
            <span style="font-size: 1rem; color: #4b5563; margin-right: 15px;"><strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> 您好</span> 
            <a href="logout.php" style="background-color: #fee2e2; color: #b91c1c; padding: 8px 16px; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#fecaca'" onmouseout="this.style.backgroundColor='#fee2e2'">登出系統</a>
        </div>
    </div>

    <a href="manager_home.php" class="back-link">⭠ 返回管理者首頁</a>
    <h2>📊 進階統計報表</h2>

    <!-- Query 1: Aggregate Function (AVG, COUNT) on Dishes -->
    <div class="report-section">
        <div class="report-title">1. 餐點平均滿意度與點餐熱門次數</div>
        <div class="report-description">統計每項餐點在回饋明細中的歷史平均得分與評價次數，可用於廚房內場評估餐點口味。</div>
        
        <table>
            <thead>
                <tr>
                    <th>餐點編號</th>
                    <th>餐點名稱</th>
                    <th>餐點種類</th>
                    <th>單價</th>
                    <th>平均滿意度</th>
                    <th>評價次數</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql1 = "SELECT d.Dish_ID, d.name, d.type, d.price, 
                                AVG(fd.rating) AS avg_rating, 
                                COUNT(fd.rating) AS review_count
                         FROM Dishes d
                         LEFT JOIN Rate_dish rd ON d.Dish_ID = rd.Dish_ID
                         LEFT JOIN Feedback_details fd ON rd.Feedback_ID = fd.Feedback_ID AND rd.Detail_ID = fd.Detail_ID
                         GROUP BY d.Dish_ID, d.name, d.type, d.price
                         ORDER BY avg_rating DESC, review_count DESC";
                $res1 = $conn->query($sql1);
                if ($res1 && $res1->num_rows > 0) {
                    while($row = $res1->fetch_assoc()) {
                        $avg = $row['avg_rating'] !== null ? number_format($row['avg_rating'], 1) : '尚無評分';
                        $stars = $row['avg_rating'] !== null ? str_repeat("★", round($row['avg_rating'])) . str_repeat("☆", 5 - round($row['avg_rating'])) : '無';
                        echo "<tr>";
                        echo "<td><span class='badge-info'>" . htmlspecialchars($row['Dish_ID']) . "</span></td>";
                        echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                        echo "<td>$" . number_format($row['price'], 0) . "</td>";
                        echo "<td><span class='rating-stars'>$stars</span> ($avg)</td>";
                        echo "<td>" . $row['review_count'] . " 次</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>無統計資料</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Query 2: Aggregate Function (AVG, COUNT) on Staffs -->
    <div class="report-section">
        <div class="report-title">2. 外場員工服務滿意度統計</div>
        <div class="report-description">統計外場員工獲得的服務星等，作為績效考核的基準。</div>
        
        <table>
            <thead>
                <tr>
                    <th>員工編號</th>
                    <th>姓名</th>
                    <th>職位</th>
                    <th>服務平均評分</th>
                    <th>獲評次數</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql2 = "SELECT s.Staff_ID, s.name, s.position, 
                                AVG(fd.rating) AS avg_rating, 
                                COUNT(fd.rating) AS review_count
                         FROM Staffs s
                         LEFT JOIN Rate_staff rs ON s.Staff_ID = rs.Staff_ID
                         LEFT JOIN Feedback_details fd ON rs.Feedback_ID = fd.Feedback_ID AND rs.Detail_ID = fd.Detail_ID
                         GROUP BY s.Staff_ID, s.name, s.position
                         ORDER BY avg_rating DESC, review_count DESC";
                $res2 = $conn->query($sql2);
                if ($res2 && $res2->num_rows > 0) {
                    while($row = $res2->fetch_assoc()) {
                        $avg = $row['avg_rating'] !== null ? number_format($row['avg_rating'], 1) : '尚無評分';
                        $stars = $row['avg_rating'] !== null ? str_repeat("★", round($row['avg_rating'])) . str_repeat("☆", 5 - round($row['avg_rating'])) : '無';
                        echo "<tr>";
                        echo "<td><span class='badge-info'>" . htmlspecialchars($row['Staff_ID']) . "</span></td>";
                        echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['position']) . "</td>";
                        echo "<td><span class='rating-stars'>$stars</span> ($avg)</td>";
                        echo "<td>" . $row['review_count'] . " 次</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>無統計資料</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Query 3: Set Comparison (Subquery) -->
    <div class="report-section">
        <div class="report-title">3. 高於平均整體評分的回饋表單</div>
        <div class="report-description">查詢整體評分大於「所有回饋平均分」的表單，過濾出正面且高於平均的顧客聲音。</div>
        
        <table>
            <thead>
                <tr>
                    <th>回饋編號</th>
                    <th>顧客姓名</th>
                    <th>評分</th>
                    <th>整體意見</th>
                    <th>填寫日期</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Subquery to compare with overall average
                $sql3 = "SELECT f.Feedback_ID, f.rating, f.opinion, c.name AS customer_name, f.date
                         FROM Feedback_forms f
                         JOIN Customers c ON f.Customer_ID = c.Customer_ID
                         WHERE f.rating > (SELECT AVG(rating) FROM Feedback_forms)
                         ORDER BY f.rating DESC, f.Feedback_ID DESC";
                $res3 = $conn->query($sql3);
                if ($res3 && $res3->num_rows > 0) {
                    while($row = $res3->fetch_assoc()) {
                        $stars = str_repeat("★", $row['rating']) . str_repeat("☆", 5 - $row['rating']);
                        echo "<tr>";
                        echo "<td><span class='badge-success'>" . htmlspecialchars($row['Feedback_ID']) . "</span></td>";
                        echo "<td><strong>" . htmlspecialchars($row['customer_name']) . "</strong></td>";
                        echo "<td><span class='rating-stars'>$stars</span> (" . $row['rating'] . ")</td>";
                        echo "<td>" . htmlspecialchars($row['opinion']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>尚無符合條件的資料</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Query 4: Set Membership (IN Subquery) -->
    <div class="report-section">
        <div class="report-title">4. 給過 5 星滿分好評的顧客名單</div>
        <div class="report-description">查詢所有曾給予過整體 5 星最高滿意度評價的顧客資料，以便進行精準行銷與VIP關懷。</div>
        
        <table>
            <thead>
                <tr>
                    <th>顧客編號</th>
                    <th>姓名</th>
                    <th>會員等級</th>
                    <th>電話號碼</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Set membership query
                $sql4 = "SELECT Customer_ID, name, member_level, phone_number
                         FROM Customers
                         WHERE Customer_ID IN (
                             SELECT DISTINCT Customer_ID 
                             FROM Feedback_forms 
                             WHERE rating = 5
                         )";
                $res4 = $conn->query($sql4);
                if ($res4 && $res4->num_rows > 0) {
                    while($row = $res4->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><span class='badge-info'>" . htmlspecialchars($row['Customer_ID']) . "</span></td>";
                        echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['member_level']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>目前尚無給予五星好評的顧客</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Query 5: Customer Most Ordered Dish (Window Function & CTE) -->
    <div class="report-section">
        <div class="report-title">5. 各顧客點購最多次的餐點</div>
        <div class="report-description">統計每位顧客點購次數最多的餐點，包含顧客姓名與會員等級，可用於針對個人偏好的精準餐點行銷。</div>
        
        <table>
            <thead>
                <tr>
                    <th>顧客編號</th>
                    <th>姓名</th>
                    <th>會員等級</th>
                    <th>最常點餐點</th>
                    <th>總點購數量</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql5 = "WITH CustomerDishQty AS (
                            SELECT c.Customer_ID, c.name AS customer_name, c.member_level, co.Dish_ID, d.name AS dish_name, SUM(co.number) AS total_qty
                            FROM Customers c
                            JOIN Has h ON c.Customer_ID = h.Customer_ID
                            JOIN Contain co ON h.Record_ID = co.Record_ID
                            JOIN Dishes d ON co.Dish_ID = d.Dish_ID
                            GROUP BY c.Customer_ID, c.name, c.member_level, co.Dish_ID, d.name
                        ),
                        RankedDishes AS (
                            SELECT Customer_ID, customer_name, member_level, Dish_ID, dish_name, total_qty,
                                   RANK() OVER (PARTITION BY Customer_ID ORDER BY total_qty DESC) AS rk
                            FROM CustomerDishQty
                        )
                        SELECT Customer_ID, customer_name, member_level, Dish_ID, dish_name, total_qty
                        FROM RankedDishes
                        WHERE rk = 1
                        ORDER BY Customer_ID ASC";
                $res5 = $conn->query($sql5);
                if ($res5 && $res5->num_rows > 0) {
                    while($row = $res5->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td><span class='badge-info'>" . htmlspecialchars($row['Customer_ID']) . "</span></td>";
                        echo "<td><strong>" . htmlspecialchars($row['customer_name']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($row['member_level']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['dish_name']) . " (" . htmlspecialchars($row['Dish_ID']) . ")</td>";
                        echo "<td>" . htmlspecialchars($row['total_qty']) . " 份</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>目前尚無消費紀錄統計資料</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); ?>
</body>
</html>
