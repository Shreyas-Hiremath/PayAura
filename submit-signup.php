<?php
// Database connection
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $phoneNumber = $_POST['phoneNumber'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $paymentMethod = $_POST['paymentMethod'];
    $cardNumber = $_POST['cardNumber'];
    $expiryDate = $_POST['expiryDate'];
    $cvv = $_POST['cvv'];
    $billingAddress = $_POST['billingAddress'];
    $securityQuestion = $_POST['securityQuestion'];
    $securityAnswer = $_POST['securityAnswer'];
    $topUpAmount = $_POST['topUpAmount'];

    // Check if passwords match
    if ($password !== $_POST['confirmPassword']) {
        die("Passwords do not match.");
    }

    // Insert user details into the database
    $sql = "INSERT INTO users (fullName, email, phoneNumber, username, password, paymentMethod, cardNumber, expiryDate, cvv, billingAddress, securityQuestion, securityAnswer, topUpAmount)
            VALUES ('$fullName', '$email', '$phoneNumber', '$username', '$password', '$paymentMethod', '$cardNumber', '$expiryDate', '$cvv', '$billingAddress', '$securityQuestion', '$securityAnswer', '$topUpAmount')";

    if (mysqli_query($conn, $sql)) {
        echo "New record created successfully";
        // Redirect to login page after successful signup
        header("Location: login.html");
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
