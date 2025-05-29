<?php
// Security headers
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline';");
header("Referrer-Policy: no-referrer");

session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Connection check
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to transfer money.");
}

$sender_id = $_SESSION['user_id'];
$error = "";
$success = "";

// CSRF token setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Secret key for HMAC (store this securely!)
define('HMAC_SECRET_KEY', 'your_very_secret_key_123');

function generateHmac($amount) {
    return hash_hmac('sha256', $amount, HMAC_SECRET_KEY);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $recipient_id = intval($_POST['recipient_id']);
    $amount = $_POST['amount'];
    $received_hmac = $_POST['amount_hmac'];

    // Verify HMAC
    $expected_hmac = generateHmac($amount);
    if (!hash_equals($expected_hmac, $received_hmac)) {
        die("Tampering detected in amount.");
    }

    $amount = floatval($amount);

    // Validate amount
    if (!is_numeric($amount) || $amount <= 0 || $amount > 10000) {
        $error = "Invalid or suspicious amount.";
    } elseif ($recipient_id == $sender_id) {
        $error = "You cannot transfer money to yourself.";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Check sender balance
            $stmt = $conn->prepare("SELECT balance FROM user WHERE id = ?");
            $stmt->bind_param("i", $sender_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                throw new Exception("Sender account not found.");
            }
            $sender = $result->fetch_assoc();
            if ($sender['balance'] < $amount) {
                throw new Exception("Insufficient balance.");
            }
            $stmt->close();

            // Check recipient exists
            $stmt = $conn->prepare("SELECT balance FROM user WHERE id = ?");
            $stmt->bind_param("i", $recipient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                throw new Exception("Recipient not found.");
            }
            $stmt->close();

            // Deduct from sender
            $stmt = $conn->prepare("UPDATE user SET balance = balance - ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $sender_id);
            if (!$stmt->execute()) {
                throw new Exception("Error deducting balance.");
            }
            $stmt->close();

            // Add to recipient
            $stmt = $conn->prepare("UPDATE user SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $recipient_id);
            if (!$stmt->execute()) {
                throw new Exception("Error adding balance.");
            }
            $stmt->close();

            // Log transaction
            $stmt = $conn->prepare("INSERT INTO loggings (user_id, action) VALUES (?, ?)");
            $action = "Transferred $amount to user $recipient_id";
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

$conn->close();

// HMAC for initial form
$default_amount = 0;
$default_hmac = generateHmac($default_amount);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer Money</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Update HMAC client-side when amount changes
        async function updateHmac() {
            const amount = document.getElementById("amount").value;
            const response = await fetch('generate_hmac.php?amount=' + amount);
            const token = await response.text();
            document.getElementById("amount_hmac").value = token;
        }
    </script>
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
                <input type="number" step="0.01" id="amount" name="amount" required onchange="updateHmac()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
            </div>
            <input type="hidden" name="amount_hmac" id="amount_hmac" value="<?php echo $default_hmac; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <?php if ($error): ?>
                <div style="color: red;"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="color: green;"><?php echo $success; ?></div>
            <?php endif; ?>

            <button type="submit" style="width: 100%; padding: 12px; background: #007bff; color: white; font-size: 18px; font-weight: bold; border-radius: 8px; border: none; cursor: pointer;">
                Transfer
            </button>
        </form>
    </div>
</body>
</html>
