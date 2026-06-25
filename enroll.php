<?php

include 'db_connect.php';

$message = '';
$ok = false;
$assignedStudentNumber = '';
$successDetails = [];

/* Sticky values so the form keeps what the user typed on a failed submit. */
$old = [
    'first_name' => '', 'last_name' => '', 'dob' => '', 'gender' => '',
    'phone' => '', 'email' => '', 'address' => '',
    'program_track' => '', 'year_level' => '',
    'enrollment_type' => 'Regular', 'short_bio' => '', 'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $_v) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    /* --- Required-field check --- */
    $missing = [];
    foreach (['first_name','last_name','dob','username','email'] as $f) {
        if ($old[$f] === '') $missing[] = $f;
    }
    if ($password === '') $missing[] = 'password';

    if ($missing) {
        $message = 'Please complete all required fields.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        /* --- Username / email uniqueness --- */
        $check = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $check->bind_param('ss', $old['username'], $old['email']);
        $check->execute();
        $check->store_result();
        $taken = $check->num_rows > 0;
        $check->close();

        if ($taken) {
            $message = 'That username or email is already registered.';
        } else {
            
            $uploadsDir = __DIR__ . '/uploads';
            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0775, true);

            $photoName = '';
            if (!empty($_FILES['profile_photo']['name']) && is_uploaded_file($_FILES['profile_photo']['tmp_name'])) {
                if (getimagesize($_FILES['profile_photo']['tmp_name']) === false) {
                    $message = 'Profile photo must be a valid image (jpg, png, gif, webp).';
                } else {
                    $photoName = saveUpload('profile_photo', $uploadsDir);
                    if ($photoName === '') $message = 'Could not save the profile photo. Please try again.';
                }
            }

            $docsName = '';
            if ($message === '' && !empty($_FILES['school_docs']['name']) && is_uploaded_file($_FILES['school_docs']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['school_docs']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf','jpg','jpeg','png'], true)) {
                    $message = 'School documents must be a PDF or image (pdf, jpg, png).';
                } else {
                    $docsName = saveUpload('school_docs', $uploadsDir);
                    if ($docsName === '') $message = 'Could not save the school documents. Please try again.';
                }
            }

            /* --- Insert (transaction so a partial account never lands) --- */
            if ($message === '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $type = 'student';

                $conn->begin_transaction();
                try {
                    $stmtUser = $conn->prepare(
                        'INSERT INTO users (username, email, password_hash, account_type, profile_photo)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmtUser->bind_param('sssss', $old['username'], $old['email'], $hash, $type, $photoName);
                    $stmtUser->execute();
                    $userId = $stmtUser->insert_id;
                    $stmtUser->close();
                    $assignedStudentNumber = makeStudentNumber($conn, $userId);

                    $stmtStu = $conn->prepare(
                        'INSERT INTO students
                            (user_id, student_number, first_name, last_name, dob, gender,
                             phone, address, program_track, year_level, enrollment_type,
                             school_docs, short_bio)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmtStu->bind_param(
                        'issssssssssss',
                        $userId, $assignedStudentNumber, $old['first_name'], $old['last_name'],
                        $old['dob'], $old['gender'], $old['phone'], $old['address'],
                        $old['program_track'], $old['year_level'], $old['enrollment_type'],
                        $docsName, $old['short_bio']
                    );
                    $stmtStu->execute();
                    $stmtStu->close();

                    $conn->commit();
                    $ok = true;
                    $message = 'Enrollment complete.';
                    $successDetails = [
                        'Student Number' => $assignedStudentNumber,
                        'Username' => $old['username'],
                        'Email' => $old['email'],
                        'Account Type' => 'Student',
                    ];
                    $old = array_fill_keys(array_keys($old), '');
                    $old['enrollment_type'] = 'Regular';
                } catch (Throwable $e) {
                    $conn->rollback();
                    $assignedStudentNumber = '';
                    $message = 'Enrollment failed. Please try again.';
                }
            }
        }
    }
}
$conn->close();

function saveUpload($field, $dir) {
    if (empty($_FILES[$field]['name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return '';
    }
    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
    $base = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
    $candidate = time() . '_' . $base . '.' . $ext;
    while (is_file($dir . '/' . $candidate)) {
        $candidate = time() . '_' . $base . '_' . bin2hex(random_bytes(2)) . '.' . $ext;
    }
    $target = $dir . '/' . $candidate;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $target)) {
        return '';
    }
    return $candidate;
}

function makeStudentNumber($conn, $userId) {
    $year = date('Y');
    $sequence = $userId % 100000;
    if ($sequence === 0) $sequence = 1;

    for ($attempt = 0; $attempt < 100000; $attempt++) {
        $candidateSequence = (($sequence + $attempt - 1) % 100000) + 1;
        $checkDigit = ($userId + $attempt) % 10;
        $candidate = $year . '-' . str_pad((string) $candidateSequence, 5, '0', STR_PAD_LEFT) . '-MN-' . $checkDigit;

        $stmt = $conn->prepare('SELECT user_id FROM students WHERE student_number = ? LIMIT 1');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if (!$exists) return $candidate;
    }

    throw new RuntimeException('Could not issue a unique student number.');
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
<body class="enroll-bg">
    <a class="skip-link" href="#enroll-form">Skip to enrollment form</a>

    <main class="enroll-shell <?= $ok ? 'success-mode' : '' ?>">
        
        <?php if (!$ok): ?>
        <nav id="stepper" aria-label="Enrollment sections">
            <p class="menu-title">Sections</p>
            <ol>
                <li>
                    <button type="button" data-target="section-personal" aria-controls="section-personal">
                        <span class="step-no">01</span>
                        <span>Personal</span>
                    </button>
                </li>
                <li>
                    <button type="button" data-target="section-academic" aria-controls="section-academic">
                        <span class="step-no">02</span>
                        <span>Academic</span>
                    </button>
                </li>
                <li>
                    <button type="button" data-target="section-account" aria-controls="section-account">
                        <span class="step-no">03</span>
                        <span>Account</span>
                    </button>
                </li>
                <li>
                    <button type="button" data-target="section-profile" aria-controls="section-profile">
                        <span class="step-no">04</span>
                        <span>Photo</span>
                    </button>
                </li>
            </ol>
        </nav>
        <?php endif; ?>

        <section class="enroll-wrap" aria-labelledby="enroll-title">
        <header class="enroll-header">
            <div>
                <span class="eyebrow">computer engineering enrollment</span>
                <h1 id="enroll-title">Enroll at PUP CEA-CpE</h1>
                <?php if (!$ok): ?>
                <p class="helper">Fields marked <span class="req">*</span> are required.</p>
                <?php endif; ?>
            </div>
            <div class="logo-bar">
                <img src="assets/pup-logo.png" alt="PUP seal">
                <div class="brand-divider"></div>
                <img src="assets/cpe-logo.png" alt="Computer Engineering Department logo">
            </div>
        </header>

        <?php if ($message !== '' && !$ok): ?>
            <div class="alert alert-error" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($ok): ?>
            <section class="success-screen" role="status" aria-labelledby="success-title">
                <span class="success-kicker">Enrollment complete</span>
                <h2 id="success-title">Your PUP CEA-CpE account is ready.</h2>
                <p>Keep these details. You will need the issued student number for account recovery.</p>
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
        <!-- ----- ENROLLMENT FORM -----
             enctype=multipart/form-data because of the photo + docs uploads. -->
        <form id="enroll-form" action="enroll.php" method="post" enctype="multipart/form-data" novalidate>

            <!-- ----- 01. PERSONAL INFORMATION ----- -->
            <fieldset class="form-section" id="section-personal">
                <legend>01. Personal Information</legend>
                <div class="grid-2col">
                    <div class="field">
                        <label for="first_name">First Name <span class="req">*</span></label>
                        <input type="text" id="first_name" name="first_name" required value="<?= htmlspecialchars($old['first_name']) ?>">
                    </div>
                    <div class="field">
                        <label for="last_name">Last Name <span class="req">*</span></label>
                        <input type="text" id="last_name" name="last_name" required value="<?= htmlspecialchars($old['last_name']) ?>">
                    </div>
                </div>
                <div class="grid-2col">
                    <div class="field">
                        <label for="dob">Date of Birth <span class="req">*</span></label>
                        <input type="date" id="dob" name="dob" required value="<?= htmlspecialchars($old['dob']) ?>">
                    </div>
                    <div class="field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">-- Select --</option>
                            <?php foreach (['Female','Male','Prefer not to answer'] as $g): ?>
                                <option value="<?= $g ?>" <?= $old['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($old['email']) ?>">
                </div>
                <div class="field">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="0917-000-0000" value="<?= htmlspecialchars($old['phone']) ?>">
                </div>
                <div class="field">
                    <label for="address">Home Address</label>
                    <input type="text" id="address" name="address" placeholder="House, Street, Brgy, City" value="<?= htmlspecialchars($old['address']) ?>">
                </div>
            </fieldset>

            <!-- ----- 02. ACADEMIC DETAILS ----- -->
            <fieldset class="form-section" id="section-academic">
                <legend>02. Academic Details</legend>
                <p class="section-note">Your student number will be issued after enrollment in this format: 2026-02809-MN-0.</p>
                <div class="grid-2col">
                    <div class="field">
                        <label for="program_track">Preferred Program Track</label>
                        <select id="program_track" name="program_track">
                            <option value="">-- Select a track --</option>
                            <?php foreach (['Web Development','Mobile Development','Data Science','Cybersecurity','Embedded Systems'] as $t): ?>
                                <option value="<?= $t ?>" <?= $old['program_track'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level">
                            <option value="">-- Select --</option>
                            <?php foreach (['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $y): ?>
                                <option value="<?= $y ?>" <?= $old['year_level'] === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <p class="field-label" id="enrollment_type_label">Type of Enrollment</p>
                    <div class="radio-row" role="radiogroup" aria-labelledby="enrollment_type_label">
                        <?php foreach (['Regular','Irregular','Transferee','Returnee'] as $e): ?>
                            <label><input type="radio" name="enrollment_type" value="<?= $e ?>" <?= $old['enrollment_type'] === $e ? 'checked' : '' ?>> <?= $e ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="field">
                    <label for="school_docs">Upload School Documents</label>
                    <input type="file" id="school_docs" name="school_docs" accept=".pdf,.jpg,.jpeg,.png">
                    <div id="school-docs-filename" class="file-name">no file selected</div>
                </div>
                <div class="field">
                    <label for="short_bio">Personal Statement</label>
                    <textarea id="short_bio" name="short_bio" rows="4" placeholder="Briefly describe your enrollment purpose."><?= htmlspecialchars($old['short_bio']) ?></textarea>
                    <div id="bio-counter" class="char-counter">0 / 200 characters</div>
                </div>
            </fieldset>

            <!-- ----- 03. ACCOUNT SETUP ----- -->
            <fieldset class="form-section" id="section-account">
                <legend>03. Account Setup</legend>
                <div class="field">
                    <label for="username">Username <span class="req">*</span></label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($old['username']) ?>">
                </div>
                <div class="grid-2col">
                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <div class="pwd-wrap">
                            <input type="password" id="password" name="password" required minlength="8">
                            <button type="button" class="pwd-toggle" data-toggle-password="password">show</button>
                        </div>
                        <div id="pwd-strength">
                            <div class="strength-bars">
                                <div class="bar"></div><div class="bar"></div>
                                <div class="bar"></div><div class="bar"></div>
                            </div>
                            <span class="strength-label"></span>
                        </div>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                        <div class="pwd-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="pwd-toggle" data-toggle-password="confirm_password">show</button>
                        </div>
                    </div>
                </div>
            </fieldset>

            <!-- ----- 04. PROFILE PHOTO ----- -->
            <fieldset class="form-section" id="section-profile">
                <legend>04. Profile Photo</legend>
                <div class="photo-preview">
                    <img id="photo-preview" src="" alt="Profile preview" style="display:none">
                    <span id="photo-placeholder" class="placeholder">no photo selected</span>
                </div>
                <div class="field">
                    <label for="profile_photo">Upload Profile Photo</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                    <div class="helper">Shown on your dashboard after enrollment.</div>
                </div>
            </fieldset>

            <footer class="enroll-actions">
                <a href="index.php" class="btn btn-secondary">Back to login</a>
                <button type="submit" class="btn">Complete Enrollment</button>
            </footer>
        </form>
        <?php endif; ?>
        </section>
    </main>

    <script src="js/auth.js"></script>
    <script src="js/enroll.js"></script>
</body>
</html>
