<?php
session_start();
include("../config/database.php");

// Require a scholarship ID in the URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$scholarship_id = (int) $_GET['id'];

// Fetch the scholarship — only show if active and deadline hasn't passed
$stmt = mysqli_prepare($conn, "SELECT s.*, i.institution_name, i.contact_email, i.contact_phone,
                                       i.location, i.description AS inst_description
                                FROM scholarships s
                                JOIN provider_profiles pp ON s.provider_id = pp.provider_id
                                JOIN institutions i ON pp.institution_id = i.institution_id
                                WHERE s.scholarship_id = ?
                                AND s.status = 'active'
                                AND s.application_deadline >= CURDATE()");
mysqli_stmt_bind_param($stmt, "i", $scholarship_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data   = mysqli_fetch_assoc($result);

// Redirect if scholarship not found
if (!$data) {
    header("Location: dashboard.php");
    exit();
}

// Fetch the fields of study for this scholarship
$stmt2 = mysqli_prepare($conn, "SELECT field_of_study FROM scholarship_fields WHERE scholarship_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $scholarship_id);
mysqli_stmt_execute($stmt2);
$fields_result = mysqli_stmt_get_result($stmt2);
$fields        = [];
while ($field_row = mysqli_fetch_assoc($fields_result)) {
    $fields[] = $field_row['field_of_study'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($data['scholarship_name']); ?> - EduLift</title>
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
            <a href="dashboard.php">Back to Scholarships</a>
            <a href="profile.php">My Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <div class="scholarship-detail">
            <h2><?php echo htmlspecialchars($data['scholarship_name']); ?></h2>

            <!-- Institution / provider details -->
            <div class="detail-section">
                <h3>Provider Information</h3>
                <p><strong>Institution:</strong> <?php echo htmlspecialchars($data['institution_name']); ?></p>
                <p><strong>Location:</strong>    <?php echo htmlspecialchars($data['location']); ?></p>
                <p><strong>Contact Email:</strong> <?php echo htmlspecialchars($data['contact_email']); ?></p>
                <?php if ($data['contact_phone']): ?>
                    <p><strong>Contact Phone:</strong> <?php echo htmlspecialchars($data['contact_phone']); ?></p>
                <?php endif; ?>
            </div>

            <!-- Scholarship details -->
            <div class="detail-section">
                <h3>Scholarship Details</h3>
                <p><strong>Type:</strong>            <?php echo htmlspecialchars($data['scholarship_type']); ?></p>
                <p><strong>Amount:</strong>          <?php echo htmlspecialchars($data['amount']); ?></p>
                <p><strong>Education Level:</strong> <?php echo htmlspecialchars($data['education_level']); ?></p>
                <?php if (!empty($fields)): ?>
                    <p><strong>Fields of Study:</strong> <?php echo htmlspecialchars(implode(', ', $fields)); ?></p>
                <?php endif; ?>
                <?php if ($data['gpa_requirement']): ?>
                    <p><strong>GPA Requirement:</strong> <?php echo htmlspecialchars($data['gpa_requirement']); ?></p>
                <?php endif; ?>
                <p><strong>Application Deadline:</strong> <?php echo htmlspecialchars($data['application_deadline']); ?></p>
            </div>

            <!-- Scholarship description -->
            <div class="detail-section">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($data['description'])); ?></p>
            </div>

            <!-- Application requirements -->
            <div class="detail-section">
                <h3>Requirements</h3>
                <p><?php echo nl2br(htmlspecialchars($data['requirements'])); ?></p>
            </div>

            <div class="action-buttons">
                <a href="dashboard.php" class="btn">Back to Scholarships</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> EduLift. All rights reserved.</p>
    </footer>
</body>
</html>