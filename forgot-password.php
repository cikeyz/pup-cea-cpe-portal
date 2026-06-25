<?php
/* =====================================================================
   forgot-password.php  ·  Activity-08 Group 4  ·  Password reset
   ---------------------------------------------------------------------
   Two-step flow driven by a hidden `step` field and a mode switch:
     step 1 - verify identity using the student number + email for
              students, or username + email for guests.
     step 2 - set a new password (with confirm), then UPDATE users.
   The reset page supports both account types so guests are not blocked
   from recovering their own portal password.
   ===================================================================== */

session_start();
include 'db_connect.php';

$mode = $_GET['mode'] ?? ($_POST['mode'] ?? 'student');
if (!in_array($mode, ['student', 'guest'], true)) {
    $mode = 'student';
}

$step = $_POST['step'] ?? '1';
$message = '';
$ok = false;
$successDetails = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['reset_user_id']);
}

function resetCopy($mode) {
    if ($mode === 'guest') {
        return [
            'subtitle' => 'Guests reset with their username and email address.',
            'empty' => 'Please enter both your username and email.',
            'missing' => 'No matching guest record. Check the username and email.',
        ];
    }

    return [
        'subtitle' => 'Use the student number issued after enrollment with your email address.',
        'empty' => 'Please enter both your student number and email.',
        'missing' => 'No matching student record. Check the student number and email.',
    ];
}

$copy = resetCopy($mode);

/* ---------- STEP 1: verify identity ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '1') {
    if ($mode === 'guest') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
    } else {
        $studentNumber = trim($_POST['student_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
    }

    if (($mode === 'guest' && ($username === '' || $email === '')) || ($mode === 'student' && ($studentNumber === '' || $email === ''))) {
        $message = $copy['empty'];
    } else {
        if ($mode === 'guest') {
            $stmt = $conn->prepare(
                'SELECT u.id, u.username
                   FROM users u
                   JOIN guests g ON g.user_id = u.id
                  WHERE u.username = ? AND u.email = ? AND u.account_type = ?'
            );
            $accountType = 'guest';
            $stmt->bind_param('sss', $username, $email, $accountType);
        } else {
            /* Join students→users so one query proves both fields belong
               to the same account. */
            $stmt = $conn->prepare(
                'SELECT u.id, u.username
                   FROM users u
                   JOIN students s ON s.user_id = u.id
                  WHERE s.student_number = ? AND u.email = ? AND u.account_type = ?'
            );
            $accountType = 'student';
            $stmt->bind_param('sss', $studentNumber, $email, $accountType);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            /* Remember who is resetting so step 2 does not re-scan input. */
            session_regenerate_id(true);
            $_SESSION['reset_user_id'] = $user['id'];
            $step = '2';
        } else {
            $message = $copy['missing'];
        }
    }
}

/* ---------- STEP 2: set the new password ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2') {
    /* If the user skipped step 1, send them back to start. */
    if (!isset($_SESSION['reset_user_id'])) {
        $step = '1';
        $message = 'Your reset session expired. Please verify again.';
    } else {
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($newPass) < 8) {
            $message = 'Password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $message = 'Passwords do not match.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $userId = $_SESSION['reset_user_id'];

            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $userId);
            $stmt->execute();
            $stmt->close();

            /* Clear the reset id so the same session cannot reuse it. */
            unset($_SESSION['reset_user_id']);
            $ok = true;
            $message = 'Your password has been updated.';
            $successDetails = [
                'Reset Type' => ucfirst($mode),
                'Account Status' => 'Password updated',
                'Next Step' => 'Sign in with your new password',
            ];
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
        <div class="mode-switch" aria-label="Password reset type">
            <a href="forgot-password.php?mode=student" class="<?= $mode === 'student' ? 'active' : '' ?>">Student reset</a>
            <a href="forgot-password.php?mode=guest" class="<?= $mode === 'guest' ? 'active' : '' ?>">Guest reset</a>
        </div>
        <h1>Reset Password</h1>

        <?php if ($message !== '' && !$ok): ?>
            <div class="alert alert-error" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($ok): ?>
            <section class="success-screen" role="status" aria-labelledby="success-title">
                <span class="success-kicker">Reset complete</span>
                <h2 id="success-title">Your password was updated.</h2>
                <p>You can now return to the portal and sign in with the new password.</p>
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
        <?php elseif ($step === '2'): ?>
            <!-- ----- STEP 2: new password -----
                 Why: identity is already proven in step 1, so we only ask
                 for the new password + confirmation here. -->
            <p class="auth-subtitle">Enter your new <?= $mode === 'guest' ? 'guest' : 'student' ?> portal password below.</p>
            <form action="forgot-password.php" method="post" novalidate>
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
                <div class="field">
                    <label for="new_password">New Password <span class="req">*</span></label>
                    <div class="pwd-wrap">
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                        <button type="button" class="pwd-toggle" data-toggle-password="new_password">show</button>
                    </div>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                    <div class="pwd-wrap">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="pwd-toggle" data-toggle-password="confirm_password">show</button>
                    </div>
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>
        <?php else: ?>
            <!-- ----- STEP 1: verify identity -----
                 Why: the identity fields differ by account type, but each
                 pair still proves the reset belongs to the right person. -->
            <p class="auth-subtitle"><?= htmlspecialchars($copy['subtitle']) ?></p>
            <form action="forgot-password.php" method="post" novalidate>
                <input type="hidden" name="step" value="1">
                <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
                <?php if ($mode === 'guest'): ?>
                <div class="field">
                    <label for="username">Username <span class="req">*</span></label>
                    <input type="text" id="username" name="username" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <?php else: ?>
                <div class="field">
                    <label for="student_number">Student Number <span class="req">*</span></label>
                    <input type="text" id="student_number" name="student_number" required
                           value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>">
                </div>
                <?php endif; ?>
                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <button type="submit" class="btn">Verify Identity</button>
            </form>
            <p class="auth-links">
                Reset as a <a href="forgot-password.php?mode=student">student</a> or <a href="forgot-password.php?mode=guest">guest</a>.
            </p>
        <?php endif; ?>

        <a class="back-link" href="index.php">Back to login</a>
    </main>

    <script src="js/auth.js"></script>
</body>
</html>
