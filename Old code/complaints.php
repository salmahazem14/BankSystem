<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id']; // Logged-in user ID
    $admin_id = 1; // Constant admin ID

    // Ensure the uploads directory exists
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    if (isset($_FILES['complaint_file']) && $_FILES['complaint_file']['error'] == 0) {
        $file_tmp = $_FILES['complaint_file']['tmp_name'];
        $file_name = basename($_FILES['complaint_file']['name']);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = "complaint_" . uniqid() . "." . $file_ext;
        $file_path = "uploads/" . $new_file_name;

        // Move uploaded file to the uploads directory
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Prepare SQL statement
            $stmt = $conn->prepare("INSERT INTO complaints (user_id, admin_id, complaint) VALUES (?, ?, ?)");

            if (!$stmt) {
                die("Prepare failed: " . $conn->error); // Debugging
            }

            $stmt->bind_param("iis", $user_id, $admin_id, $file_path);

            if ($stmt->execute()) {
                $success = "Complaint submitted successfully.";
                // Log the complaint submission
                $log_action = "User $user_id submitted a complaint: $file_path";
                $log_stmt = $conn->prepare("INSERT INTO loggings (user_id, action, timestamp) VALUES (?, ?, NOW())");

                if (!$log_stmt) {
                    die("Logging prepare failed: " . $conn->error);
                }

                $log_stmt->bind_param("is", $user_id, $log_action);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                $error = "Database error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $error = "Failed to upload file.";
        }
    } else {
        $error = "Please select a file.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint</title>
</head>
<body style="text-align: center; font-family: Arial, sans-serif; padding: 20px;">

    <h1>Submit a Complaint</h1>
    
    <?php if ($error): ?>
        <div style="color: red;"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="color: green;"><?php echo $success; ?></div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <input type="file" name="complaint_file" required>
        <br><br>
        <button type="submit">Submit Complaint</button>
    </form>

</body>
</html>
