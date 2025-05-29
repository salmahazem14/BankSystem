<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed."]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $hashedPassword = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if (!$email || !$hashedPassword || !$userType) {
        echo json_encode(["status" => "error", "message" => "Missing data."]);
        exit;
    }

    $table = ($userType === 'admin') ? 'admin' : 'user';
    $stmt = $conn->prepare("SELECT id, email, password FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Email not found."]);
        exit;
    }

    $user = $result->fetch_assoc();
    $storedPassword = $user['password']; // this is plain text

    // Hash the stored plain password (like the frontend did)
    $hashedStoredPassword = hash('sha256', $storedPassword);

    if ($hashedPassword === $hashedStoredPassword) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $userType;

        $action = "Login successful: $email";
        $log = $conn->prepare("INSERT INTO loggings (action) VALUES (?)");
        $log->bind_param("s", $action);
        $log->execute();

        echo json_encode(["status" => "success", "redirect" => $userType === 'admin' ? 'admin-dashboard.php' : 'user-dashboard.php']);
    } else {
        $action = "Failed login for $email from $ip (wrong password)";
        $log = $conn->prepare("INSERT INTO loggings (action) VALUES (?)");
        $log->bind_param("s", $action);
        $log->execute();

        echo json_encode(["status" => "error", "message" => "Incorrect password."]);
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <script>
    async function hashPassword(password) {
      const encoder = new TextEncoder();
      const data = encoder.encode(password);
      const hashBuffer = await crypto.subtle.digest('SHA-256', data);
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    window.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('loginForm');
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const userType = document.getElementById('userType').value;

        const hashed = await hashPassword(password);

        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', hashed);
        formData.append('userType', userType);

        fetch('login.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            window.location.href = data.redirect;
          } else {
            document.getElementById('error').innerText = data.message;
          }
        })
        .catch(console.error);
      });
    });
  </script>
</head>
<body style="background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; color: #333; text-align: center; padding: 20px;">
  <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px;">
    <h1 style="font-size: 32px; margin-bottom: 10px; font-weight: bold;">Login</h1>
    <form id="loginForm" style="display: flex; flex-direction: column; gap: 15px; text-align: left;">
      <input type="hidden" id="userType" name="userType" value="<?php echo htmlspecialchars($_GET['userType'] ?? 'user'); ?>">

      <div>
        <label for="email" style="font-weight: bold;">Email</label>
        <input type="email" id="email" name="email" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 5px;">
      </div>

      <div>
        <label for="password" style="font-weight: bold;">Password</label>
        <input type="password" id="password" name="password" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; margin-top: 5px;">
      </div>

      <p id="error" style="color:red; margin-top: 10px;"></p>

      <button type="submit" style="width: 100%; padding: 12px; background: #007bff; color: white; font-size: 18px; font-weight: bold; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s ease;">Login</button>
    </form>
  </div>
</body>
</html>

