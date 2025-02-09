<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $response = ["success" => false, "message" => "User not logged in."];
        echo json_encode($response);
        exit;
    }

    $amount = trim($_POST['amount']);
    $userId = $_SESSION['user_id'];
    $billType = isset($_POST['billType']) ? $_POST['billType'] : null; // Correct key: 'billType'

    if (empty($billType)) {
        $response = ["success" => false, "message" => "Bill type is required."];
        echo json_encode($response);
        exit;
    }

    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $response = ["success" => false, "message" => "Invalid amount."];
        echo json_encode($response);
        exit;
    }

    try {
        // Fetch user's current top-up amount
        $stmt = $conn->prepare("SELECT topUpAmount, username FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database prepare statement failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $currentTopUp = $row['topUpAmount'];
            $username = $row['username'];

            if ($currentTopUp >= $amount) {
                // Deduct the amount from the current top-up
                $newTopUp = $currentTopUp - $amount;

                // Update the user's top-up amount
                $updateStmt = $conn->prepare("UPDATE users SET topUpAmount = ? WHERE id = ?");
                if (!$updateStmt) {
                    throw new Exception("Database prepare statement failed for update: " . $conn->error);
                }

                $updateStmt->bind_param("di", $newTopUp, $userId);
                if (!$updateStmt->execute()) {
                    throw new Exception("Recharge failed: " . $updateStmt->error);
                }

                // Insert bill details
                $insertStmt = $conn->prepare("INSERT INTO bill (username, userId, billType, amount, newTopUp) VALUES (?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    throw new Exception("Database prepare statement failed for insert: " . $conn->error);
                }
                
                // Corrected bind_param type and variable order
                $insertStmt->bind_param("sissd", $username, $userId, $billType, $amount, $newTopUp);
                
                if (!$insertStmt->execute()) {
                    throw new Exception("Failed to record bill details: " . $insertStmt->error);
                }

                $response = ["success" => true, "message" => "Bill payment successful. Your new top-up amount is ₹$newTopUp."];
            } else {
                $response = ["success" => false, "message" => "Insufficient balance."];
            }
        } else {
            $response = ["success" => false, "message" => "User data not found."];
        }

        $stmt->close();
    } catch (Exception $e) {
        $response = ["success" => false, "message" => $e->getMessage()];
    }

    $conn->close();
    echo json_encode($response);
}
?>
