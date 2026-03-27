<?php
session_start();
include("../config/database.php");

// Only verified providers can access this page
if (!isset($_SESSION['provider_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$provider_id = (int) $_SESSION['provider_id'];

// Double-check verification status in case session is stale
$status_stmt = mysqli_prepare($conn, "SELECT status FROM provider_profiles WHERE provider_id = ?");
mysqli_stmt_bind_param($status_stmt, "i", $provider_id);
mysqli_stmt_execute($status_stmt);
$status_row = mysqli_fetch_assoc(mysqli_stmt_get_result($status_stmt));
if (!$status_row || $status_row['status'] !== 'verified') {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// Fetch the provider's institution info via a JOIN on institutions
$stmt = mysqli_prepare($conn, "SELECT pp.*, i.institution_name, i.contact_email, i.contact_phone, i.location
                                FROM provider_profiles pp
                                JOIN institutions i ON pp.institution_id = i.institution_id
                                WHERE pp.provider_id = ?");
mysqli_stmt_bind_param($stmt, "i", $provider_id);
mysqli_stmt_execute($stmt);
$provider = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Fetch all scholarships for this provider, including their fields of study
$stmt2 = mysqli_prepare($conn, "SELECT s.*,
                                        GROUP_CONCAT(sf.field_of_study ORDER BY sf.field_of_study SEPARATOR ', ') AS fields
                                 FROM scholarships s
                                 LEFT JOIN scholarship_fields sf ON s.scholarship_id = sf.scholarship_id
                                 WHERE s.provider_id = ?
                                 GROUP BY s.scholarship_id
                                 ORDER BY s.created_at DESC");
mysqli_stmt_bind_param($stmt2, "i", $provider_id);
mysqli_stmt_execute($stmt2);
$scholarship_result = mysqli_stmt_get_result($stmt2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - EduLift</title>
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

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($provider['institution_name']); ?></h2>

        <!-- Show success or error messages from add/edit/delete actions -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Scholarship added successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="success-message">Scholarship updated successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">Scholarship deleted successfully!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php if ($_GET['error'] == 'delete_failed'): ?>
                    Error: Could not delete scholarship.
                <?php elseif ($_GET['error'] == 'unauthorized'): ?>
                    Error: You do not have permission to perform this action.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-actions">
            <a href="add_scholarship.php" class="btn">+ Add New Scholarship</a>
        </div>

        <h3>Your Scholarships</h3>

        <!-- List all scholarships posted by this provider -->
        <div class="scholarship-grid">
            <?php if (mysqli_num_rows($scholarship_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($scholarship_result)): ?>
                    <div class="card">
                        <h4><?php echo htmlspecialchars($row['scholarship_name']); ?></h4>
                        <p><strong>Type:</strong>     <?php echo htmlspecialchars($row['scholarship_type']); ?></p>
                        <p><strong>Amount:</strong>   <?php echo htmlspecialchars($row['amount']); ?></p>
                        <p><strong>Fields:</strong>   <?php echo htmlspecialchars($row['fields'] ?? 'N/A'); ?></p>
                        <p><strong>Status:</strong>   <?php echo htmlspecialchars($row['status']); ?></p>
                        <p><strong>Deadline:</strong> <?php echo htmlspecialchars($row['application_deadline']); ?></p>
                        <div class="card-actions">
                            <a href="edit_scholarship.php?id=<?php echo $row['scholarship_id']; ?>" class="btn-small">Edit</a>
                            <a href="#"
                               class="btn-small btn-danger"
                               onclick="showDeleteModal('delete_scholarship.php?id=<?php echo $row['scholarship_id']; ?>', '<?php echo htmlspecialchars($row['scholarship_name'], ENT_QUOTES); ?>')">Delete</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You haven't added any scholarships yet. Add your first scholarship to get started!</p>
            <?php endif; ?>
        </div>
    </div>
<!-- Delete confirmation modal -->
<div id="delete-modal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Delete Scholarship</h3>
        <p id="modal-message"></p>
        <p class="modal-warning">This will permanently delete the scholarship and all its related data. This cannot be undone.</p>
        <div class="modal-actions">
            <a id="modal-confirm-btn" href="#" class="btn btn-danger">Yes, Delete</a>
            <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
        </div>
    </div>
</div>

<script>
    function showDeleteModal(url, name) {
        document.getElementById('modal-message').textContent = 'Are you sure you want to delete "' + name + '"?';
        document.getElementById('modal-confirm-btn').href = url;
        document.getElementById('delete-modal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').style.display = 'none';
    }

    // Close modal if clicking outside the box
    document.getElementById('delete-modal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>
</body>
</html>