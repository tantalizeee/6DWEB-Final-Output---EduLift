<?php
session_start();
include("config/database.php");

// No login required — this is a public page

// Get search and filter values from the URL
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$field_filter = isset($_GET['field'])  ? $_GET['field']        : '';
$level_filter = isset($_GET['level'])  ? $_GET['level']        : '';

// Build the scholarship query dynamically based on active filters
$sql    = "SELECT s.scholarship_id, s.scholarship_name, s.scholarship_type, s.amount,
                  s.education_level, s.gpa_requirement, s.application_deadline,
                  i.institution_name, i.location,
                  GROUP_CONCAT(sf.field_of_study ORDER BY sf.field_of_study SEPARATOR ', ') AS fields
           FROM scholarships s
           JOIN provider_profiles pp ON s.provider_id = pp.provider_id
           JOIN institutions i ON pp.institution_id = i.institution_id
           LEFT JOIN scholarship_fields sf ON s.scholarship_id = sf.scholarship_id
           WHERE s.status = 'active'
           AND s.application_deadline >= CURDATE()";
$params = [];
$types  = "";

// Add keyword search across name, institution, and description
if (!empty($search)) {
    $sql    .= " AND (s.scholarship_name LIKE ? OR i.institution_name LIKE ? OR s.description LIKE ?)";
    $like    = "%$search%";
    $params  = array_merge($params, [$like, $like, $like]);
    $types  .= "sss";
}

// Add field of study filter using a subquery
if (!empty($field_filter)) {
    $sql    .= " AND EXISTS (SELECT 1 FROM scholarship_fields
                             WHERE scholarship_id = s.scholarship_id AND field_of_study = ?)";
    $params[] = $field_filter;
    $types   .= "s";
}

// Add education level filter
if (!empty($level_filter)) {
    $sql    .= " AND s.education_level = ?";
    $params[] = $level_filter;
    $types   .= "s";
}

$sql .= " GROUP BY s.scholarship_id ORDER BY s.application_deadline ASC";

// Execute the query with a prepared statement
$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count  = mysqli_num_rows($result);

// Fetch summary stats for the browse hero section
$total_scholarships = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM scholarships WHERE status='active' AND application_deadline >= CURDATE()"))['c'];
$total_institutions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM institutions"))['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Scholarships - EduLift</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/img/eduliftlogo.ico">
</head>
<body>
    <header class="header">
        <a class="header-brand" href="index.php"><img src="assets/img/eduliftlogo.png" alt="EduLift Logo" class="header-logo"><h1 class="logo">EduLift</h1></a>
        <button class="nav-toggle" onclick="this.nextElementSibling.classList.toggle('open')" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <a href="index.php">Home</a>
            <a href="browse.php">Browse</a>
            <!-- Show dashboard or login links depending on session -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_type'] === 'student'): ?>
                    <a href="student/dashboard.php">Dashboard</a>
                <?php elseif ($_SESSION['user_type'] === 'provider'): ?>
                    <a href="provider/provider_dashboard.php">Dashboard</a>
                <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                    <a href="admin/admin_dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
                <a href="auth/register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Browse hero with search and filters -->
    <div class="browse-hero">
        <h2>Find Your Scholarship</h2>
        <p><?php echo $total_scholarships; ?> scholarships from <?php echo $total_institutions; ?> institutions</p>

        <form method="GET" class="browse-search-form">
            <div class="browse-search-row">
                <input type="text" name="search" placeholder="Search scholarships or institutions..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">Search</button>
            </div>
            <div class="browse-filter-row">
                <select name="field">
                    <option value="">All Fields</option>
                    <?php
                    $fields = ['Engineering', 'Medicine', 'Business', 'Science', 'Arts', 'Law', 'Education', 'All Fields'];
                    foreach ($fields as $f):
                    ?>
                        <option value="<?php echo $f; ?>" <?php echo $field_filter === $f ? 'selected' : ''; ?>>
                            <?php echo $f; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="level">
                    <option value="">All Levels</option>
                    <?php
                    $levels = ['Senior High School', 'College Undergraduate', 'Graduate Studies', 'All Levels'];
                    foreach ($levels as $l):
                    ?>
                        <option value="<?php echo $l; ?>" <?php echo $level_filter === $l ? 'selected' : ''; ?>>
                            <?php echo $l; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($search) || !empty($field_filter) || !empty($level_filter)): ?>
                    <a href="browse.php" class="btn-outline">Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="container">
        <!-- Show how many results are currently displayed -->
        <p class="result-count">
            <?php if (!empty($search) || !empty($field_filter) || !empty($level_filter)): ?>
                Showing <strong><?php echo $count; ?></strong> result<?php echo $count !== 1 ? 's' : ''; ?>
            <?php else: ?>
                Showing all <strong><?php echo $count; ?></strong> active scholarships
            <?php endif; ?>
        </p>

        <!-- Scholarship cards -->
        <div class="scholarship-grid">
            <?php if ($count > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="card">
                        <h4><?php echo htmlspecialchars($row['scholarship_name']); ?></h4>
                        <p><strong>Institution:</strong> <?php echo htmlspecialchars($row['institution_name']); ?></p>
                        <p><strong>Type:</strong>        <?php echo htmlspecialchars($row['scholarship_type']); ?></p>
                        <p><strong>Amount:</strong>      <?php echo htmlspecialchars($row['amount']); ?></p>
                        <p><strong>Fields:</strong>      <?php echo htmlspecialchars($row['fields'] ?? 'N/A'); ?></p>
                        <p><strong>Level:</strong>       <?php echo htmlspecialchars($row['education_level']); ?></p>
                        <p><strong>Deadline:</strong>    <?php echo htmlspecialchars($row['application_deadline']); ?></p>
                        <div class="card-actions">
                            <!-- Students can view details; guests are prompted to log in -->
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'student'): ?>
                                <a href="student/scholarship_view.php?id=<?php echo $row['scholarship_id']; ?>" class="btn">View Details</a>
                            <?php else: ?>
                                <a href="auth/login.php" class="btn">Login to Apply</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No scholarships found matching your search.</p>
                    <a href="browse.php" class="btn">View All Scholarships</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> EduLift. All rights reserved.</p>
    </footer>
</body>
</html>