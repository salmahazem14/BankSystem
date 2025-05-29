<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please log in.");
}

$user_id = $_SESSION['user_id'];
$admin_id = 1; // Constant admin ID

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['message'])) {
    $message = trim($_POST['message']);

    // Insert message into database
    $stmt = $conn->prepare("INSERT INTO messages (user_id, admin_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $admin_id, $sanitized_message);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>Message sent successfully!</p>";
        // Log the action
        $log_action = "User $user_id sent a message to admin";
        $log_stmt = $conn->prepare("INSERT INTO loggings (user_id, action, timestamp) VALUES (?, ?, NOW())");
        
        if ($log_stmt === false) {
            die("Logging prepare failed: " . $conn->error);
        }

        $log_stmt->bind_param("is", $user_id, $log_action);
        $log_stmt->execute();
        $log_stmt->close();
    } else {
        echo "<p style='color: red;'>Error sending message.</p>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message to Admin</title>
</head>
<body style="text-align: center; font-family: Arial, sans-serif; padding: 20px;">

    <h1>Message</h1>
    <form action="" method="POST">
        <textarea name="message" rows="5" cols="40" required placeholder="Type your message here..."></textarea><br><br>
        <button type="submit">Send Message</button>
    </form>

</body>
</html>
