<?php
session_start();
include("../config/database.php");

// Only students can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Fetch the student's profile and account info
$stmt = mysqli_prepare($conn, "SELECT sp.*, u.username, u.email
                                FROM student_profiles sp
                                JOIN users u ON sp.user_id = u.user_id
                                WHERE sp.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name         = trim($_POST['full_name']);
    $contact_phone     = trim($_POST['contact_phone']);
    $location          = trim($_POST['location']);
    $education_level   = $_POST['education_level'];
    $field_of_interest = trim($_POST['field_of_interest']);
    $gpa               = trim($_POST['gpa']);

    // Validate required fields and GPA range
    if (empty($full_name)) {
        $error = "Full name is required.";
    } elseif (!empty($gpa) && ($gpa < 1.00 || $gpa > 4.00)) {
        $error = "GPA must be between 1.00 and 4.00.";
    } else {
        // Convert GPA to null if left empty
        $gpa_value = !empty($gpa) ? (float) $gpa : null;

        // Update the student's profile record
        $stmt2 = mysqli_prepare($conn, "UPDATE student_profiles SET
                                        full_name = ?, contact_phone = ?, location = ?,
                                        education_level = ?, field_of_interest = ?, gpa = ?
                                        WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt2, "sssssdi",
            $full_name, $contact_phone, $location,
            $education_level, $field_of_interest, $gpa_value,
            $user_id
        );

        if (mysqli_stmt_execute($stmt2)) {
            $success = "Profile updated successfully!";
            // Refresh profile data to reflect changes
            mysqli_stmt_execute($stmt);
            $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        } else {
            $error = "Error updating profile. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EduLift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/eduliftlogo.ico">
</head>
<body>
    <header class="header">
        <a class="header-brand" href="#"><img src="../assets/img/eduliftlogo.png" alt="EduLift Logo" class="header-logo"><h1 class="logo">EduLift</h1></a>
        <button class="nav-toggle" onclick="this.nextElementSibling.classList.toggle('open')" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="form-page">
        <div class="form-card">
            <h2>My Profile</h2>

            <!-- Show success or error messages -->
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Read-only account info — cannot be edited here -->
            <div class="profile-meta">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?></p>
                <p><strong>Email:</strong>    <?php echo htmlspecialchars($student['email']); ?></p>
            </div>

            <form method="POST" class="scholarship-form">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name"
                           value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="contact_phone"
                           value="<?php echo htmlspecialchars($student['contact_phone'] ?? ''); ?>"
                           placeholder="e.g., 09171234567">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location"
                           value="<?php echo htmlspecialchars($student['location'] ?? ''); ?>"
                           placeholder="e.g., Manila, Metro Manila">
                </div>
                <div class="form-group">
                    <label>Education Level</label>
                    <select name="education_level">
                        <option value="">Select Education Level</option>
                        <?php
                        $levels = ['Senior High School', 'College Undergraduate', 'Graduate Studies'];
                        foreach ($levels as $level):
                        ?>
                            <option value="<?php echo $level; ?>"
                                <?php echo ($student['education_level'] ?? '') === $level ? 'selected' : ''; ?>>
                                <?php echo $level; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Field of Interest</label>
                    <select name="field_of_interest">
                        <option value="">Select Field</option>
                        <?php
                        $fields = ['Engineering', 'Medicine', 'Business', 'Science', 'Arts', 'Law', 'Education'];
                        foreach ($fields as $f):
                        ?>
                            <option value="<?php echo $f; ?>"
                                <?php echo ($student['field_of_interest'] ?? '') === $f ? 'selected' : ''; ?>>
                                <?php echo $f; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>GPA</label>
                    <input type="number" name="gpa" step="0.01" min="1.00" max="4.00"
                           value="<?php echo htmlspecialchars($student['gpa'] ?? ''); ?>"
                           placeholder="e.g., 3.75">
                    <small>Scale of 1.00 – 4.00</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>