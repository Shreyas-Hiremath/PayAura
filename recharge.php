<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        $response = ["success" => false, "message" => "User not logged in."];
        echo json_encode($response);
        exit;
    }

    // Get the recharge amount and phone number from POST request
    $amount = trim($_POST['rechargeAmount']);
    $phoneNumber = trim($_POST['phoneNumber']);
    $userId = $_SESSION['user_id'];

    // Input validation
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $response = ["success" => false, "message" => "Invalid recharge amount."];
        echo json_encode($response);
        exit;
    }

    // Fetch the user's current balance and username
    $stmt = $conn->prepare("SELECT topUpAmount, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentTopUp = $row['topUpAmount'];
        $username = $row['username'];

        // Update the user's top-up amount
        $newTopUp = $currentTopUp - $amount;
        $updateStmt = $conn->prepare("UPDATE users SET topUpAmount = ? WHERE id = ?");
        $updateStmt->bind_param("di", $newTopUp, $userId);

        if ($updateStmt->execute()) {
            // Record the recharge in the `recharge` table with username
            $insertStmt = $conn->prepare("INSERT INTO recharge (userId, username, rechargeAmount, newTopUp) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("isid", $userId, $username, $amount, $newTopUp);
            if ($insertStmt->execute()) {
                $response = ["success" => true, "message" => "Recharge successful!. Your new top-up amount is ₹$newTopUp."];
            } else {
                $response = ["success" => false, "message" => "Failed to record recharge details."];
            }
            $insertStmt->close();
        } else {
            $response = ["success" => false, "message" => "Recharge failed. Please try again."];
        }
        $updateStmt->close();
    } else {
        $response = ["success" => false, "message" => "User data not found."];
    }

    $stmt->close();
    $conn->close();

    echo json_encode($response);
}
?>
    