<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);
    $userType = $_POST['userType'] ?? '';

    if (!$email || !$password || !$userType) {
        echo json_encode(["status" => "error", "message" => "Please fill out all fields."]);
        exit();
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format."]);
        exit();
    } else {
        $table = ($userType === 'user') ? 'user' : 'admin';
        $stmt = $conn->prepare("SELECT id, email, password FROM $table WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $userType;
                
                $redirectPage = ($userType === 'admin') ? 'admin-dashboard.php' : 'user-dashboard.php';
                echo json_encode(["status" => "success", "redirect" => $redirectPage]);
                exit();
            } else {
                echo json_encode(["status" => "error", "message" => "Incorrect password."]);
                exit();
            }
        } else {
            echo json_encode(["status" => "error", "message" => "No user found with this email."]);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("loginForm").addEventListener("submit", function (event) {
                event.preventDefault();
                let formData = new FormData(this);

                fetch("", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        window.location.href = data.redirect;
                    } else {
                        document.getElementById("errorMessage").innerText = data.message;
                    }
                })
                .catch(error => console.error("Error:", error));
            });
        });
    </script>
</head>
<body style="background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; color: #333; text-align: center; padding: 20px;">
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px;">
        <h1 style="font-size: 32px; margin-bottom: 10px; font-weight: bold;">Login</h1>
        <form id="loginForm" style="display: flex; flex-direction: column; gap: 15px; text-align: left;">
            <input type="hidden" name="userType" value="<?php echo htmlspecialchars($_GET['userType'] ?? ''); ?>">
            <div>
                <label for="email" style="font-weight: bold;">Email</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 5px;">
            </div>
            <div>
                <label for="password" style="font-weight: bold;">Password</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 5px;">
            </div>
            <div id="errorMessage" style="color: red; margin-top: 10px;"></div>
            <button type="submit" style="width: 100%; padding: 12px; background: #007bff; color: white; font-size: 18px; font-weight: bold; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s ease; text-align: center; display: block;">Login</button>
        </form>
    </div>
</body>
</html>
