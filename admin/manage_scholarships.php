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
$field_filter  = isset($_GET['field'])  ? $_GET['field']        : '';

// Validate status filter to only allow known values
if (!in_array($status_filter, ['active', 'inactive'])) {
    $status_filter = '';
}

// Build the query dynamically based on active filters
// Base query joins institutions and scholarship_fields for full scholarship info
$sql    = "SELECT s.scholarship_id, s.scholarship_name, s.scholarship_type, s.amount,
                  s.education_level, s.application_deadline, s.status, s.created_at,
                  i.institution_name,
                  GROUP_CONCAT(sf.field_of_study ORDER BY sf.field_of_study SEPARATOR ', ') AS fields
           FROM scholarships s
           JOIN provider_profiles pp ON s.provider_id = pp.provider_id
           JOIN institutions i ON pp.institution_id = i.institution_id
           LEFT JOIN scholarship_fields sf ON s.scholarship_id = sf.scholarship_id
           WHERE 1=1";
$params = [];
$types  = "";

// Add status filter if selected
if (!empty($status_filter)) {
    $sql    .= " AND s.status = ?";
    $params[] = $status_filter;
    $types   .= "s";
}

// Add keyword search across name, institution, and description
if (!empty($search)) {
    $sql    .= " AND (s.scholarship_name LIKE ? OR i.institution_name LIKE ? OR s.description LIKE ?)";
    $like    = "%$search%";
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= "sss";
}

// Add field of study filter using a subquery
if (!empty($field_filter)) {
    $sql    .= " AND EXISTS (SELECT 1 FROM scholarship_fields WHERE scholarship_id = s.scholarship_id AND field_of_study = ?)";
    $params[] = $field_filter;
    $types   .= "s";
}

$sql .= " GROUP BY s.scholarship_id ORDER BY s.created_at DESC";

// Execute the dynamic query with prepared statement
$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count scholarships by status for the summary cards
$count_all      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM scholarships"))['c'];
$count_active   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM scholarships WHERE status='active'"))['c'];
$count_inactive = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM scholarships WHERE status='inactive'"))['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scholarships - EduLift</title>
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
        <h2>Manage Scholarships</h2>

        <!-- Show success or error messages -->
        <?php if (isset($_GET['toggled'])): ?>
            <div class="success-message">Scholarship <?php echo $_GET['toggled'] === 'activate' ? 'activated' : 'deactivated'; ?> successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="success-message">Scholarship deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php if ($_GET['error'] === 'toggle_failed'): ?>
                    Error: Could not update scholarship status.
                <?php elseif ($_GET['error'] === 'delete_failed'): ?>
                    Error: Could not delete scholarship.
                <?php elseif ($_GET['error'] === 'not_found'): ?>
                    Error: Scholarship not found.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Scholarship count summary cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $count_all; ?></h3>
                <p>Total Scholarships</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $count_active; ?></h3>
                <p>Active</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $count_inactive; ?></h3>
                <p>Inactive</p>
            </div>
        </div>

        <!-- Search and filter form -->
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Search by name, institution, or description..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="field">
                    <option value="">All Fields</option>
                    <?php
                    $fields_list = ['Engineering', 'Medicine', 'Business', 'Science', 'Arts', 'Law', 'Education', 'All Fields'];
                    foreach ($fields_list as $f):
                    ?>
                        <option value="<?php echo $f; ?>" <?php echo $field_filter === $f ? 'selected' : ''; ?>>
                            <?php echo $f; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="active"   <?php echo $status_filter === 'active'   ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" class="btn">Filter</button>
                <?php if (!empty($search) || !empty($status_filter) || !empty($field_filter)): ?>
                    <a href="manage_scholarships.php" class="btn-cancel">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Scholarships table -->
        <div class="section">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Scholarship Name</th>
                            <th>Institution</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Fields</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['scholarship_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['institution_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['scholarship_type']); ?></td>
                                <td><?php echo htmlspecialchars($row['amount']); ?></td>
                                <td><?php echo htmlspecialchars($row['fields'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['application_deadline']); ?></td>
                                <td>
                                    <span class="status-<?php echo htmlspecialchars($row['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                    </span>
                                </td>
                                <!-- Toggle and delete buttons -->
                                <td class="action-cell">
                                    <?php if ($row['status'] === 'active'): ?>
                                        <a href="toggle_scholarship.php?id=<?php echo $row['scholarship_id']; ?>&action=deactivate"
                                           class="btn-small btn-danger"
                                           onclick="return confirm('Deactivate this scholarship?')">Deactivate</a>
                                    <?php else: ?>
                                        <a href="toggle_scholarship.php?id=<?php echo $row['scholarship_id']; ?>&action=activate"
                                           class="btn-small"
                                           onclick="return confirm('Activate this scholarship?')">Activate</a>
                                    <?php endif; ?>
                                    <a href="#"
                                       class="btn-small btn-danger"
                                       onclick="showDeleteModal('delete_scholarship.php?id=<?php echo $row['scholarship_id']; ?>', '<?php echo htmlspecialchars($row['scholarship_name'], ENT_QUOTES); ?>')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; color:#888; padding:30px;">
                                    No scholarships found.
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