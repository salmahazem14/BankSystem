<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Check if the user is logged in and redirect them based on their role
if (isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    if ($role == 'admin') {
        header("Location: admin-dashboard.php");
        exit();
    } elseif ($role == 'user') {
        header("Location: user-dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank System - Welcome</title>
</head>
<body style="background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; color: #333; text-align: center; padding: 20px;">

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px;">
        <h1 style="font-size: 32px; margin-bottom: 10px; font-weight: bold;">Bank System</h1>
        <p style="font-size: 18px; margin-bottom: 20px;">Choose your role to continue:</p>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <a href="login.php?userType=admin" style="background: #007bff; color: white; padding: 12px 20px; font-size: 18px; font-weight: bold; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-align: center; display: block;">Admin</a>
            <a href="login.php?userType=user" style="background: #007bff; color: white; padding: 12px 20px; font-size: 18px; font-weight: bold; border-radius: 8px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); text-align: center; display: block;">User</a>
        </div>
    </div>

</body>
</html>
