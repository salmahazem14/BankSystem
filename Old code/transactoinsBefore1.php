<?php

session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_system";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to transfer money.");
}

$sender_id = $_SESSION['user_id']; // Logged-in user
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipient_id = intval($_POST['recipient_id']);
    $amount = floatval($_POST['amount']);

    if ($recipient_id == $sender_id) {
        $error = "You cannot transfer money to yourself.";
    } else {
        $conn->begin_transaction();

        try {
            // Set the direction of transfer
            $from_id = $amount >= 0 ? $sender_id : $recipient_id;
            $to_id = $amount >= 0 ? $recipient_id : $sender_id;
            $transfer_amount = abs($amount);

            // Check sender's balance
            $stmt = $conn->prepare("SELECT balance FROM user WHERE id = ?");
            $stmt->bind_param("i", $from_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                throw new Exception("Sender account not found.");
            }
            $sender = $result->fetch_assoc();
            if ($sender['balance'] < $transfer_amount) {
                throw new Exception("Insufficient balance.");
            }
            $stmt->close();

            // Check recipient exists
            $stmt = $conn->prepare("SELECT balance FROM user WHERE id = ?");
            $stmt->bind_param("i", $to_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                throw new Exception("Recipient not found.");
            }
            $stmt->close();

            // Deduct from sender (from_id)
            $stmt = $conn->prepare("UPDATE user SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $transfer_amount, $from_id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating sender's balance.");
            }
            $stmt->close();

            // Add to recipient (to_id)
            $stmt = $conn->prepare("UPDATE user SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $transfer_amount, $to_id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating recipient's balance.");
            }
            $stmt->close();

            // Log transaction
            $stmt = $conn->prepare("INSERT INTO loggings (user_id, action) VALUES (?, ?)");
            $action = $amount >= 0 
                ? "Transferred $amount to user $recipient_id" 
                : "Received " . abs($amount) . " from user $recipient_id";
            $stmt->bind_param("is", $sender_id, $action);
            if (!$stmt->execute()) {
                throw new Exception("Error logging transaction.");
            }
            $stmt->close();

            $conn->commit();
            $success = "Transfer successful!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Money</title>
</head>
<body style="background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); max-width: 400px; width: 100%;">
        <h1>Transfer Money</h1>
        <form action="" method="POST" style="display: flex; flex-direction: column; gap: 15px; text-align: left;">
            <div>
                <label for="recipient_id">Recipient ID</label>
                <input type="number" id="recipient_id" name="recipient_id" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
            </div>
            <div>
                <label for="amount">Amount</label>
                <input type="number" step="0.01" id="amount" name="amount" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
            </div>
            <?php if ($error): ?>
                <div style="color: red;"> <?php echo $error; ?> </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="color: green;"> <?php echo $success; ?> </div>
            <?php endif; ?>
            <button type="submit" style="width: 100%; padding: 12px; background: #007bff; color: white; font-size: 18px; font-weight: bold; border-radius: 8px; border: none; cursor: pointer;">Transfer</button>
        </form>
    </div>
</body>
</html>