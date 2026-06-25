<?php

session_start();
include 'db_connect.php';

/* No session → back to login. */
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* Pull the account row from the database because session values can be stale. */
$stmt = $conn->prepare('SELECT username, email, account_type, profile_photo FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* If the user id has no row anymore, log out cleanly. */
if (!$account) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

$type = $account['account_type'];
$displayName = $account['username'];
$initials = strtoupper(mb_substr($account['username'], 0, 1));
$photoPath = '';
$details = [];

if ($type === 'student') {
    $stmt = $conn->prepare(
        'SELECT student_number, first_name, last_name, dob, gender, phone,
                address, program_track, year_level, enrollment_type, short_bio
           FROM students WHERE user_id = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($s) {
        $displayName = $s['first_name'] . ' ' . $s['last_name'];
        $initials = strtoupper(mb_substr($s['first_name'], 0, 1));
        $details = [
            'Student Number'  => $s['student_number'],
            'Date of Birth'   => $s['dob'],
            'Gender'          => $s['gender'],
            'Email'           => $account['email'],
            'Phone'           => $s['phone'],
            'Address'         => $s['address'],
            'Program Track'   => $s['program_track'],
            'Year Level'      => $s['year_level'],
            'Enrollment Type' => $s['enrollment_type'],
            'Short Bio'       => $s['short_bio'],
        ];
    }
} else {
    $stmt = $conn->prepare('SELECT purpose_of_visit FROM guests WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $g = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($g) {
        $details = [
            'Username'        => $account['username'],
            'Email'           => $account['email'],
            'Purpose of Visit'=> $g['purpose_of_visit'],
        ];
    }
}

$hasPhoto = false;
if ($type === 'student' && !empty($account['profile_photo'])) {
    $fullPath = __DIR__ . '/uploads/' . $account['profile_photo'];
    if (is_file($fullPath)) {
        $hasPhoto = true;
        $photoPath = 'uploads/' . rawurlencode($account['profile_photo']);
    }
}

$conn->close();

/* Helper so empty fields do not render blank lines. */
function cell($label, $value) {
    if ($value === '' || $value === null) return;
    echo '<div class="detail-item"><label>' . htmlspecialchars($label) . '</label>'
       . '<span>' . htmlspecialchars($value) . '</span></div>';
}
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
<body class="dashboard">
    <main class="dash">
        
        <header class="dash-banner">
            <img src="assets/pup-logo.png" alt="PUP seal" class="logo">
            <h1>PUP CEA-CpE Portal</h1>
        </header>

        <section class="dash-body">
            
            <div class="avatar-row">
                <?php if ($hasPhoto): ?>
                    <img src="<?= $photoPath ?>" alt="Your profile photo" class="avatar-photo">
                <?php else: ?>
                    <div class="avatar-initials"><?= htmlspecialchars($initials) ?></div>
                <?php endif; ?>
                    <div class="avatar-meta">
                        <h2><?= htmlspecialchars($displayName) ?></h2>
                        <span class="badge <?= $type === 'student' ? 'badge-student' : 'badge-guest' ?>">
                        <?= $type === 'student' ? 'Student' : 'Guest' ?>
                    </span>
                </div>
            </div>

            <!-- ----- PROFILE DETAILS ----- -->
            <div class="detail-grid">
                <?php
                foreach ($details as $label => $value) {
                    if ($label === 'Short Bio') {
                        echo '<div class="detail-item detail-full"><label>' . htmlspecialchars($label) . '</label>'
                           . '<span>' . htmlspecialchars($value) . '</span></div>';
                    } else {
                        cell($label, $value);
                    }
                }
                ?>
            </div>

            <div class="dash-actions">
                <a href="logout.php" class="btn btn-secondary">Log out</a>
            </div>
        </section>
    </main>
</body>
</html>
