<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "User not logged in."]);
        exit;
    }

    // Retrieve and sanitize inputs
    $recipientPhone = trim($_POST['recipientPhone']);
    $amount = trim($_POST['amount']);
    $userId = $_SESSION['user_id'];

    // Input validation
    if (empty($recipientPhone) || empty($amount) || !is_numeric($amount) || $amount <= 0 || strlen($recipientPhone) !== 10 || !ctype_digit($recipientPhone)) {
        echo json_encode(["success" => false, "message" => "Invalid recipient phone number or transfer amount."]);
        exit;
    }

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Check sender's balance
        $stmt = $conn->prepare("SELECT topUpAmount FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("User data not found.");
        }

        $row = $result->fetch_assoc();
        $currentTopUp = $row['topUpAmount'];

        if ($currentTopUp < $amount) {
            throw new Exception("Insufficient balance.");
        }

        // Deduct the amount from sender's balance
        $newTopUp = $currentTopUp - $amount;
        $updateStmt = $conn->prepare("UPDATE users SET topUpAmount = ? WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $updateStmt->bind_param("di", $newTopUp, $userId);
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update sender's balance.");
        }

        // Record the transfer in the `transfers` table
        $insertStmt = $conn->prepare("INSERT INTO transfers (user_id, recipient_phone, amount, new_top_up) VALUES (?, ?, ?, ?)");
        if (!$insertStmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $insertStmt->bind_param("isdi", $userId, $recipientPhone, $amount, $newTopUp);
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to record transfer details.");
        }

        // Commit transaction
        $conn->commit();

        // Success response
        echo json_encode(["success" => true, "message" => "Transfer successful. Your new top-up amount is ₹$newTopUp."]);

    } catch (Exception $e) {        
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }

    // Close all statements
    if (isset($stmt)) $stmt->close();
    if (isset($updateStmt)) $updateStmt->close();
    if (isset($insertStmt)) $insertStmt->close();
    $conn->close();
}
?>
