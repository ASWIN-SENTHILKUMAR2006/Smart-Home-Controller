<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: dashboard_$role.php");
    exit();
}

require_once 'db.php';

$error = '';
$success = '';

// Handle timeout message
if (isset($_GET['timeout'])) {
    $error = 'Session expired. Please login again.';
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Simple password check (no encryption as per requirement)
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            header("Location: dashboard_{$user['role']}.php");
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Email not found';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Home - Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1>🏠 Smart Home</h1>
            <h2>Login</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <p class="auth-link">Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>
</body>

</html>