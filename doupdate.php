<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['feedback_id']) && isset($_POST['rating']) && isset($_POST['opinion'])) {
        $feedback_id = $_POST['feedback_id'];
        $rating = intval($_POST['rating']);
        $opinion = $_POST['opinion'];

        // Multiple Dishes feedback
        $dishes_posted = isset($_POST['dishes']) ? $_POST['dishes'] : [];

        // Optional Staff feedback
        $staff_id = isset($_POST['staff_id']) ? $_POST['staff_id'] : '';
        $staff_rating = isset($_POST['staff_rating']) ? intval($_POST['staff_rating']) : 0;
        $staff_opinion = isset($_POST['staff_opinion']) ? $_POST['staff_opinion'] : '';

        // Start Transaction
        $conn->begin_transaction();

        try {
            // 1. Update overall Feedback_forms
            $stmt = $conn->prepare("UPDATE Feedback_forms SET rating = ?, opinion = ? WHERE Feedback_ID = ?");
            $stmt->bind_param("iss", $rating, $opinion, $feedback_id);
            if (!$stmt->execute()) {
                throw new Exception("更新主要回饋表單失敗: " . $stmt->error);
            }
            $stmt->close();

            // 2. Rebuild multiple Dish feedbacks: Delete previous ones (Detail_ID < 100)
            // Cascade delete will automatically clean up Rate_dish
            $stmt = $conn->prepare("DELETE FROM Feedback_details WHERE Feedback_ID = ? AND Detail_ID < 100");
            $stmt->bind_param("s", $feedback_id);
            if (!$stmt->execute()) {
                throw new Exception("清理舊有餐點評價失敗: " . $stmt->error);
            }
            $stmt->close();

            // Insert new ones
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
                        throw new Exception("寫入新餐點評價細節失敗: " . $stmt->error);
                    }
                    $stmt->close();

                    $stmt = $conn->prepare("INSERT INTO Rate_dish (Feedback_ID, Detail_ID, Dish_ID) VALUES (?, ?, ?)");
                    $stmt->bind_param("sis", $feedback_id, $detail_id, $dish_id);
                    if (!$stmt->execute()) {
                        throw new Exception("寫入新餐點關係失敗: " . $stmt->error);
                    }
                    $stmt->close();

                    $detail_id++;
                }
            }

            // 3. Handle Staff Feedback (Detail_ID = 100)
            $res = $conn->query("SELECT 1 FROM Feedback_details WHERE Feedback_ID = '$feedback_id' AND Detail_ID = 100");
            $staff_exists = ($res && $res->num_rows > 0);

            if (!empty($staff_id) && $staff_rating > 0) {
                if ($staff_exists) {
                    // Update Feedback_details
                    $stmt = $conn->prepare("UPDATE Feedback_details SET rating = ?, opinion = ? WHERE Feedback_ID = ? AND Detail_ID = 100");
                    $stmt->bind_param("iss", $staff_rating, $staff_opinion, $feedback_id);
                    if (!$stmt->execute()) throw new Exception("更新員工評價細節失敗: " . $stmt->error);
                    $stmt->close();

                    // Update Rate_staff
                    $stmt = $conn->prepare("UPDATE Rate_staff SET Staff_ID = ? WHERE Feedback_ID = ? AND Detail_ID = 100");
                    $stmt->bind_param("ss", $staff_id, $feedback_id);
                    if (!$stmt->execute()) throw new Exception("更新員工評分關係失敗: " . $stmt->error);
                    $stmt->close();
                } else {
                    // Insert Feedback_details
                    $stmt = $conn->prepare("INSERT INTO Feedback_details (Feedback_ID, Detail_ID, opinion, rating) VALUES (?, 100, ?, ?)");
                    $stmt->bind_param("ssi", $feedback_id, $staff_opinion, $staff_rating);
                    if (!$stmt->execute()) throw new Exception("新增員工評價細節失敗: " . $stmt->error);
                    $stmt->close();

                    // Insert Rate_staff
                    $stmt = $conn->prepare("INSERT INTO Rate_staff (Feedback_ID, Detail_ID, Staff_ID) VALUES (?, 100, ?)");
                    $stmt->bind_param("ss", $feedback_id, $staff_id);
                    if (!$stmt->execute()) throw new Exception("建立員工評分關係失敗: " . $stmt->error);
                    $stmt->close();
                }
            } else {
                if ($staff_exists) {
                    // Delete details (will cascade delete Rate_staff)
                    $conn->query("DELETE FROM Feedback_details WHERE Feedback_ID = '$feedback_id' AND Detail_ID = 100");
                }
            }

            // Commit Transaction
            $conn->commit();
            header('Location: index.php');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            echo "<h2 align='center'><font color='red'>修改失敗，交易已回復！</font></h2>";
            echo "<p align='center'>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p align='center'><a href='index.php'>返回主頁</a></p>";
        }
    } else {
        echo "資料不完全";
    }
} else {
    header('Location: index.php');
}
?>