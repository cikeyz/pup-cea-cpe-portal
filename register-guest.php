<?php
/* =====================================================================
   register-guest.php  ·  Activity-08 Group 4  ·  Guest registration
   ---------------------------------------------------------------------
   Lightweight sign-up for non-enrolled visitors. Collects only what a
   guest needs: purpose of visit, username, email, password + confirm.
   Inserts one row into `users` (account_type='guest') and a matching
   row into `guests` inside a transaction, so a half-written account is
   never left behind if either insert fails.
   ===================================================================== */

include 'db_connect.php';

$message = '';
$ok = false;
$successDetails = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purpose   = trim($_POST['purpose'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    /* --- Server-side validation (authoritative; JS is courtesy only) --- */
    if ($purpose === '' || $username === '' || $email === '' || $password === '') {
        $message = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        /* Uniqueness check before we try to insert. */
        $check = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $check->bind_param('ss', $username, $email);
        $check->execute();
        $check->store_result();
        $taken = $check->num_rows > 0;
        $check->close();

        if ($taken) {
            $message = 'That username or email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $type = 'guest';

            /* Two related inserts → wrap in a transaction. */
            $conn->begin_transaction();
            try {
                $stmtUser = $conn->prepare(
                    'INSERT INTO users (username, email, password_hash, account_type) VALUES (?, ?, ?, ?)'
                );
                $stmtUser->bind_param('ssss', $username, $email, $hash, $type);
                $stmtUser->execute();
                $userId = $stmtUser->insert_id;
                $stmtUser->close();

                $stmtGuest = $conn->prepare(
                    'INSERT INTO guests (user_id, purpose_of_visit) VALUES (?, ?)'
                );
                $stmtGuest->bind_param('is', $userId, $purpose);
                $stmtGuest->execute();
                $stmtGuest->close();

                $conn->commit();
                $ok = true;
                $message = 'Guest account created.';
                $successDetails = [
                    'Username' => $username,
                    'Email' => $email,
                    'Account Type' => 'Guest',
                    'Purpose of Visit' => $purpose,
                ];
            } catch (Throwable $e) {
                $conn->rollback();
                $message = 'Could not create the account. Please try again.';
            }
        }
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
        <h1>Guest Registration</h1>
        <p class="auth-subtitle">For Computer Engineering portal visitors who are not enrolling as students.</p>

        <?php if ($message !== '' && !$ok): ?>
            <div class="alert alert-error" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($ok): ?>
            <section class="success-screen" role="status" aria-labelledby="success-title">
                <span class="success-kicker">Registration complete</span>
                <h2 id="success-title">Your guest account is ready.</h2>
                <p>Use this username and email when signing in to the PUP CEA-CpE Portal.</p>
                <dl class="success-data">
                    <?php foreach ($successDetails as $label => $value): ?>
                        <div>
                            <dt><?= htmlspecialchars($label) ?></dt>
                            <dd><?= htmlspecialchars($value) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
                <div class="success-actions">
                    <a href="index.php" class="btn">Go to login</a>
                </div>
            </section>
        <?php else: ?>
        <form action="register-guest.php" method="post" novalidate>
            <div class="field">
                <label for="purpose">Purpose of Visit <span class="req">*</span></label>
                <input type="text" id="purpose" name="purpose" required
                       placeholder="e.g. Campus tour, library access"
                       value="<?= htmlspecialchars($_POST['purpose'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="username">Username <span class="req">*</span></label>
                <input type="text" id="username" name="username" required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="password">Password <span class="req">*</span></label>
                <div class="pwd-wrap">
                    <input type="password" id="password" name="password" required minlength="8">
                    <button type="button" class="pwd-toggle" data-toggle-password="password">show</button>
                </div>
            </div>

            <div class="field">
                <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                <div class="pwd-wrap">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button type="button" class="pwd-toggle" data-toggle-password="confirm_password">show</button>
                </div>
            </div>

            <button type="submit" class="btn">Create Guest Account</button>
        </form>

        <a class="back-link" href="index.php">Back to login</a>
        <?php endif; ?>
    </main>

    <script src="js/auth.js"></script>
</body>
</html>
