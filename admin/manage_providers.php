<?php
session_start();
include("../config/database.php");

// Only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Get search and filter values from the URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate status filter to only allow known values
$allowed_statuses = ['verified', 'pending', 'rejected'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = '';
}

// Build the query dynamically based on active filters
// Base query joins institutions and users to get full provider info
$sql    = "SELECT pp.provider_id, pp.status, pp.created_at,
                  i.institution_name, i.institution_type, i.location, i.contact_email,
                  u.username, u.email AS user_email
           FROM provider_profiles pp
           JOIN institutions i ON pp.institution_id = i.institution_id
           JOIN users u ON pp.user_id = u.user_id
           WHERE 1=1";
$params = [];
$types  = "";

// Add status filter if selected
if (!empty($status_filter)) {
    $sql    .= " AND pp.status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}

// Add search filter if entered
if (!empty($search)) {
    $sql    .= " AND (i.institution_name LIKE ? OR i.location LIKE ? OR u.email LIKE ?)";
    $like    = "%$search%";
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= "sss";
}

$sql .= " ORDER BY pp.created_at DESC";

// Execute the dynamic query with prepared statement
$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count providers by status for the summary cards
$count_all      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM provider_profiles"))['c'];
$count_verified = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM provider_profiles WHERE status='verified'"))['c'];
$count_pending  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM provider_profiles WHERE status='pending'"))['c'];
$count_rejected = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM provider_profiles WHERE status='rejected'"))['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Providers - EduLift</title>
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
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_providers.php">Providers</a>
            <a href="manage_scholarships.php">Scholarships</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <h2>Manage Providers</h2>

        <?php if (isset($_GET['verified'])): ?>
            <div class="success-message">
                Provider has been <?php echo $_GET['verified'] === 'approve' ? 'approved' : 'rejected'; ?> successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">Provider account deleted successfully.</div>
        <?php endif; ?>

        <!-- Show error messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php if ($_GET['error'] === 'verify_failed'): ?>
                    Error: Could not update provider status.
                <?php elseif ($_GET['error'] === 'not_found'): ?>
                    Error: Provider not found.
                <?php elseif ($_GET['error'] === 'delete_failed'): ?>
                    Error: Could not delete provider.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Provider count summary cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $count_all; ?></h3>
                <p>All Providers</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $count_verified; ?></h3>
                <p>Verified</p>
            </div>
            <div class="stat-card highlight">
                <h3><?php echo $count_pending; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $count_rejected; ?></h3>
                <p>Rejected</p>
            </div>
        </div>

        <!-- Search and filter form -->
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Search by institution, location, or email..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="pending"  <?php echo $status_filter === 'pending'  ? 'selected' : ''; ?>>Pending</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <button type="submit" class="btn">Filter</button>
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="manage_providers.php" class="btn-cancel">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Providers table -->
        <div class="section">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Institution</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Contact Email</th>
                            <th>Date Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['institution_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['institution_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="status-<?php echo htmlspecialchars($row['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                    </span>
                                </td>
                                <!-- Action buttons change based on current status -->
                                <td class="action-cell">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <a href="verify_provider.php?id=<?php echo $row['provider_id']; ?>&action=approve" class="btn-small">Approve</a>
                                        <a href="verify_provider.php?id=<?php echo $row['provider_id']; ?>&action=reject"  class="btn-small btn-danger">Reject</a>
                                    <?php elseif ($row['status'] === 'verified'): ?>
                                        <a href="verify_provider.php?id=<?php echo $row['provider_id']; ?>&action=reject"  class="btn-small btn-danger">Revoke</a>
                                    <?php elseif ($row['status'] === 'rejected'): ?>
                                        <a href="verify_provider.php?id=<?php echo $row['provider_id']; ?>&action=approve" class="btn-small">Re-approve</a>
                                    <?php endif; ?>
                                    <a href="#"
                                       class="btn-small btn-danger"
                                       onclick="showDeleteModal('delete_provider.php?id=<?php echo $row['provider_id']; ?>', '<?php echo htmlspecialchars($row['institution_name'], ENT_QUOTES); ?>')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center; color:#888; padding:30px;">
                                    No providers found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<!-- Delete confirmation modal -->
<div id="delete-modal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Delete Provider</h3>
        <p id="modal-message"></p>
        <p class="modal-warning">This will permanently delete the provider account, their institution, and all their scholarships. This cannot be undone.</p>
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