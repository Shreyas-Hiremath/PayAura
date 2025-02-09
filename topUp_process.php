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

    // Get the top-up amount from POST request
    $amount = trim($_POST['amount']);
    $userId = $_SESSION['user_id'];

    // Input validation
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $response = ["success" => false, "message" => "Invalid top-up amount."];
        echo json_encode($response);
        exit;
    }

    // Fetch the user's current balance
    $stmt = $conn->prepare("SELECT topUpAmount FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentTopUp = $row['topUpAmount'];

        // Update the user's top-up amount
        $newTopUp = $currentTopUp + $amount;
        $updateStmt = $conn->prepare("UPDATE users SET topUpAmount = ? WHERE id = ?");
        $updateStmt->bind_param("di", $newTopUp, $userId);

        if ($updateStmt->execute()) {
            // Log the top-up transaction
            $insertStmt = $conn->prepare("INSERT INTO topups (user_id, amount, new_top_up, topup_time) VALUES (?, ?, ?, NOW())");
            $insertStmt->bind_param("idi", $userId, $amount, $newTopUp);
            if ($insertStmt->execute()) {
                $response = ["success" => true, "message" => "Top-up successful. Your new balance is ₹$newTopUp."];
            } else {
                $response = ["success" => false, "message" => "Failed to log top-up transaction."];
            }
            $insertStmt->close();
        } else {
            $response = ["success" => false, "message" => "Top-up failed. Please try again."];
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
