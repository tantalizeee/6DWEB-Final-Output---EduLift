<?php
session_start();
include("../config/database.php");

// Only providers can access this page
if (!isset($_SESSION['provider_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$provider_id = (int) $_SESSION['provider_id'];

// Fetch the provider's institution and account info
$stmt = mysqli_prepare($conn, "SELECT pp.provider_id, pp.status,
                                       i.institution_id, i.institution_name, i.institution_type,
                                       i.location, i.contact_email, i.contact_phone,
                                       u.username, u.email AS user_email
                                FROM provider_profiles pp
                                JOIN institutions i ON pp.institution_id = i.institution_id
                                JOIN users u ON pp.user_id = u.user_id
                                WHERE pp.provider_id = ?");
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$provider = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $institution_id   = (int) $provider['institution_id'];
    $institution_name = trim($_POST['institution_name']);
    $institution_type = $_POST['institution_type'];
    $location         = trim($_POST['location']);
    $contact_email    = trim($_POST['contact_email']);
    $contact_phone    = trim($_POST['contact_phone']);

    // Validate required fields
    if (empty($institution_name)) {
        $error = "Institution name is required.";
    } elseif (empty($contact_email) || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error = "A valid contact email is required.";
    } else {
        // Update the institution record in the institutions table
        $stmt2 = mysqli_prepare($conn, "UPDATE institutions SET
                                        institution_name = ?, institution_type = ?,
                                        location = ?, contact_email = ?, contact_phone = ?
                                        WHERE institution_id = ?");
        mysqli_stmt_bind_param($stmt2, "sssssi",
            $institution_name, $institution_type,
            $location, $contact_email, $contact_phone,
            $institution_id
        );

        if (mysqli_stmt_execute($stmt2)) {
            $success = "Profile updated successfully!";
            // Refresh provider data to reflect the changes
            mysqli_stmt_execute($stmt);
            $provider = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
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
    <title>Institution Profile - EduLift</title>
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
            <a href="provider_dashboard.php">Dashboard</a>
            <a href="profile.php">Institution Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="form-page">
        <div class="form-card">
            <h2>Institution Profile</h2>

            <!-- Show success or error messages -->
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Read-only account info — cannot be edited here -->
            <div class="profile-meta">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($provider['username']); ?></p>
                <p><strong>Login Email:</strong> <?php echo htmlspecialchars($provider['user_email']); ?></p>
                <p><strong>Verification Status:</strong>
                    <span class="status-<?php echo $provider['status']; ?>">
                        <?php echo ucfirst($provider['status']); ?>
                    </span>
                </p>
            </div>

            <form method="POST" class="scholarship-form">
                <div class="form-group">
                    <label>Institution Name</label>
                    <input type="text" name="institution_name"
                           value="<?php echo htmlspecialchars($provider['institution_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Institution Type</label>
                    <select name="institution_type">
                        <option value="">Select Type</option>
                        <?php
                        $types = ['University', 'College', 'Technical-Vocational', 'Government Agency', 'Private Foundation', 'Other'];
                        foreach ($types as $t):
                        ?>
                            <option value="<?php echo $t; ?>"
                                <?php echo ($provider['institution_type'] ?? '') === $t ? 'selected' : ''; ?>>
                                <?php echo $t; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location"
                           value="<?php echo htmlspecialchars($provider['location'] ?? ''); ?>"
                           placeholder="e.g., Quezon City, Metro Manila">
                </div>
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email"
                           value="<?php echo htmlspecialchars($provider['contact_email'] ?? ''); ?>"
                           placeholder="scholarships@institution.edu" required>
                    <small>This email is shown to students on scholarship listings.</small>
                </div>
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="contact_phone"
                           value="<?php echo htmlspecialchars($provider['contact_phone'] ?? ''); ?>"
                           placeholder="e.g., 02-8981-8500">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Save Changes</button>
                    <a href="provider_dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>