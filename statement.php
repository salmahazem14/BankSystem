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

// Fetch loggings for the logged-in user
$sql = "SELECT action, timestamp FROM loggings WHERE user_id = ? ORDER BY timestamp DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Log History</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 20px; }
        table { width: 60%; margin: 20px auto; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #007bff; color: white; }
        .logout-btn { margin-top: 20px; display: inline-block; padding: 10px 20px; background: red; color: white; text-decoration: none; border-radius: 5px; }
        .logout-btn:hover { background: darkred; }
    </style>
</head>
<body>

    <h1>User Log History</h1>

    <table>
        <tr>
            <th>Action</th>
            <th>Timestamp</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['action']); ?></td>
                <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>


</body>
</html>
