<?php

session_start();
include 'db_connect.php';

/* Already logged in? Skip the form and go straight to the dashboard. */
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /* Server-side guard so empty submissions never reach the database. */
    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        /* Prepared lookup: never concatenate user input into the query. */
        $stmt = $conn->prepare('SELECT id, username, password_hash, account_type FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']      = $user['id'];
            $_SESSION['username']     = $user['username'];
            $_SESSION['account_type'] = $user['account_type'];
            header('Location: home.php');
            exit;
        }
        $error = 'Incorrect username or password.';
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUP CEA-CpE Portal</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-bg">
    
    <main class="auth-card">
        <div class="brand-row">
            <img src="assets/pup-logo.png" alt="PUP seal">
            <div class="brand-divider"></div>
            <img src="assets/cpe-logo.png" alt="Computer Engineering Department logo">
        </div>
        <h1>PUP CEA-CpE Portal</h1>
        <p class="auth-subtitle">Polytechnic University of the Philippines<br>Computer Engineering Department</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        
        <form action="index.php" method="post" novalidate>
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="pwd-wrap">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="pwd-toggle" data-toggle-password="password">show</button>
                </div>
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>

        
        <div class="auth-links">
            <a href="forgot-password.php">Forgot password?</a><br>
            Not yet enrolled? <a href="enroll.php">Enroll here</a><br>
            Just visiting? <a href="register-guest.php">Register as guest</a>
        </div>
    </main>

    <script src="js/auth.js"></script>
</body>
</html>
