<?php
session_start(); // Start a session at the top
include 'db.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if email and password are provided
    if (empty($email) || empty($password)) {
        echo "<script>alert('Please enter both email and password.');</script>";
        exit;
    }

    // Prepare SQL query to fetch user by email
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param("s", $email);

    // Execute the statement
    if ($stmt->execute()) {
        $stmt->store_result();

        // Check if any user exists with the provided email
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $username, $hashed_password);
            $stmt->fetch();

            // Verify the password without hashing
            if ($password === $hashed_password) {
                // Store user details in the session
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;

                echo "<script>alert('Login successful!'); window.location.href = 'home.html';</script>";
            } else {
                echo "<script>alert('Invalid password.');</script>";
            }
        } else {
            echo "<script>alert('User not found.');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Error executing statement: " . $stmt->error . "');</script>";
    }

    $conn->close();
}
?>
