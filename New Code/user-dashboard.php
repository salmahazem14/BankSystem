<?php

// Disable detailed error messages
ini_set('display_errors', 0);
error_reporting(0);

// Log errors privately
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php-error.log'); // Make sure this folder exists and is writable


session_start();  // Start the session

// Check if the user is logged in as a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    // Redirect to login page if not logged in as company
   header("Location: login.php?type=user");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
</head>
<body style="font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f4f4f4; text-align: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px;">
        <h1 style="font-size: 24px; margin-bottom: 20px; font-weight: bold;">Bank Dashboard</h1>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <a href="transactions.php" style="background: #007bff; color: white; padding: 12px; font-size: 18px; font-weight: bold; border-radius: 8px; text-decoration: none; transition: 0.3s; display: block;">Transactions</a>
            <a href="statement.php" style="background: #007bff; color: white; padding: 12px; font-size: 18px; font-weight: bold; border-radius: 8px; text-decoration: none; transition: 0.3s; display: block;">View Statement</a>
            <a href="complaints.php" style="background: #007bff; color: white; padding: 12px; font-size: 18px; font-weight: bold; border-radius: 8px; text-decoration: none; transition: 0.3s; display: block;">Complaints</a>
            <a href="messages.php" style="background: #007bff; color: white; padding: 12px; font-size: 18px; font-weight: bold; border-radius: 8px; text-decoration: none; transition: 0.3s; display: block;">Messages to admin</a>

        </div>
    </div>

</body>
</html>
