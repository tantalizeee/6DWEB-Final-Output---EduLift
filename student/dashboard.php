<?php
session_start();
include("../config/database.php");

// Only students can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Fetch the student's profile to use for matching
$stmt = mysqli_prepare($conn, "SELECT full_name, gpa, education_level, field_of_interest
                                FROM student_profiles WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Check if the profile has enough info to run scholarship matching
$profile_complete = !empty($student['gpa'])
                 && !empty($student['education_level'])
                 && !empty($student['field_of_interest']);

// Base SELECT used across all three scholarship queries
// Only shows active scholarships whose deadline hasn't passed yet
$base_select = "SELECT s.scholarship_id, s.scholarship_name, s.scholarship_type,
                       s.amount, s.education_level, s.gpa_requirement, s.application_deadline,
                       i.institution_name,
                       GROUP_CONCAT(sf.field_of_study ORDER BY sf.field_of_study SEPARATOR ', ') AS fields
                FROM scholarships s
                JOIN provider_profiles pp ON s.provider_id = pp.provider_id
                JOIN institutions i ON pp.institution_id = i.institution_id
                LEFT JOIN scholarship_fields sf ON s.scholarship_id = sf.scholarship_id
                WHERE s.status = 'active'
                AND s.application_deadline >= CURDATE()";

// --- FEATURE 1: SCHOLARSHIP MATCHING ---
// Find scholarships that match the student's GPA, level, and field
$matched_result = null;
if ($profile_complete) {
    $gpa            = (float) $student['gpa'];
    $edu_level      = $student['education_level'];
    $field_interest = $student['field_of_interest'];

    $match_sql = $base_select . "
        AND s.gpa_requirement <= ?
        AND (s.education_level = ? OR s.education_level = 'All Levels')
        AND EXISTS (
            SELECT 1 FROM scholarship_fields sf2
            WHERE sf2.scholarship_id = s.scholarship_id
            AND sf2.field_of_study IN (?, 'All Fields')
        )
        GROUP BY s.scholarship_id
        ORDER BY s.application_deadline ASC
        LIMIT 6";

    $stmt2 = mysqli_prepare($conn, $match_sql);
    mysqli_stmt_bind_param($stmt2, "dss", $gpa, $edu_level, $field_interest);
    mysqli_stmt_execute($stmt2);
    $matched_result = mysqli_stmt_get_result($stmt2);
}

// --- FEATURE 2: DEADLINE TRACKER ---
// Find active scholarships closing within the next 30 days
$deadline_sql = $base_select . "
    AND s.application_deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    GROUP BY s.scholarship_id
    ORDER BY s.application_deadline ASC";

$stmt3 = mysqli_prepare($conn, $deadline_sql);
mysqli_stmt_execute($stmt3);
$deadline_result = mysqli_stmt_get_result($stmt3);
$deadline_count  = mysqli_num_rows($deadline_result);

// --- MAIN LISTING with search and filters ---
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$field_filter = isset($_GET['field'])  ? $_GET['field']        : '';
$level_filter = isset($_GET['level'])  ? $_GET['level']        : '';

// Build the main query dynamically based on active filters
$main_sql = $base_select;
$params   = [];
$types    = "";

if (!empty($search)) {
    $main_sql .= " AND (s.scholarship_name LIKE ? OR i.institution_name LIKE ? OR s.description LIKE ?)";
    $like      = "%$search%";
    $params    = array_merge($params, [$like, $like, $like]);
    $types    .= "sss";
}

if (!empty($field_filter)) {
    $main_sql .= " AND EXISTS (SELECT 1 FROM scholarship_fields
                               WHERE scholarship_id = s.scholarship_id AND field_of_study = ?)";
    $params[]  = $field_filter;
    $types    .= "s";
}

if (!empty($level_filter)) {
    $main_sql .= " AND s.education_level = ?";
    $params[]  = $level_filter;
    $types    .= "s";
}

$main_sql .= " GROUP BY s.scholarship_id ORDER BY s.application_deadline ASC";

$stmt4 = mysqli_prepare($conn, $main_sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt4, $types, ...$params);
}
mysqli_stmt_execute($stmt4);
$main_result = mysqli_stmt_get_result($stmt4);
$main_count  = mysqli_num_rows($main_result);

// Return how many days until a deadline date
function days_until($date_str) {
    $today    = new DateTime('today');
    $deadline = new DateTime($date_str);
    return (int) $today->diff($deadline)->days;
}

// Return an HTML badge for deadlines within 30 days
function deadline_badge($date_str) {
    $days = days_until($date_str);
    if ($days <= 7)  return '<span class="deadline-badge urgent">Closes in ' . $days . 'd</span>';
    if ($days <= 30) return '<span class="deadline-badge soon">Closes in ' . $days . 'd</span>';
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EduLift</title>
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
            <a href="dashboard.php">Browse Scholarships</a>
            <a href="profile.php">My Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="container student-dashboard">
        <h2>Welcome<?php echo !empty($student['full_name']) ? ', ' . htmlspecialchars(explode(' ', $student['full_name'])[0]) : ''; ?>!</h2>

        <!-- FEATURE 1: SCHOLARSHIP MATCHING -->
        <div class="dashboard-section">
            <h3 class="section-heading">Recommended for You</h3>

            <?php if (!$profile_complete): ?>
                <!-- Prompt student to complete their profile to enable matching -->
                <div class="profile-prompt">
                    <div class="profile-prompt-text">
                        <strong>Complete your profile to get matched!</strong>
                        <p>Add your GPA, education level, and field of interest so we can show scholarships you actually qualify for.</p>
                    </div>
                    <a href="profile.php" class="btn">Complete Profile</a>
                </div>

            <?php elseif (mysqli_num_rows($matched_result) > 0): ?>
                <!-- Show matched scholarships with matching criteria -->
                <p class="result-count">Based on your GPA (<?php echo htmlspecialchars($student['gpa']); ?>),
                   level (<?php echo htmlspecialchars($student['education_level']); ?>),
                   and field (<?php echo htmlspecialchars($student['field_of_interest']); ?>)</p>
                <div class="scholarship-grid">
                    <?php while ($row = mysqli_fetch_assoc($matched_result)): ?>
                        <div class="card card-matched">
                            <?php echo deadline_badge($row['application_deadline']); ?>
                            <h3><?php echo htmlspecialchars($row['scholarship_name']); ?></h3>
                            <p><strong>Institution:</strong> <?php echo htmlspecialchars($row['institution_name']); ?></p>
                            <p><strong>Type:</strong>        <?php echo htmlspecialchars($row['scholarship_type']); ?></p>
                            <p><strong>Amount:</strong>      <?php echo htmlspecialchars($row['amount']); ?></p>
                            <p><strong>Fields:</strong>      <?php echo htmlspecialchars($row['fields'] ?? 'N/A'); ?></p>
                            <p><strong>Deadline:</strong>    <?php echo htmlspecialchars($row['application_deadline']); ?></p>
                            <a href="scholarship_view.php?id=<?php echo $row['scholarship_id']; ?>" class="btn">View Details</a>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <p class="no-match-msg">No scholarships match your profile right now. Check back as new ones are added, or <a href="profile.php">update your profile</a>.</p>
            <?php endif; ?>
        </div>

        <!-- FEATURE 2: DEADLINE TRACKER — only shown if there are closing scholarships -->
        <?php if ($deadline_count > 0): ?>
        <div class="dashboard-section">
            <h3 class="section-heading">Closing Soon <span class="deadline-count"><?php echo $deadline_count; ?></span></h3>
            <p class="result-count">These scholarships close within the next 30 days — don't miss out.</p>
            <div class="scholarship-grid">
                <?php while ($row = mysqli_fetch_assoc($deadline_result)): ?>
                    <?php $days = days_until($row['application_deadline']); ?>
                    <div class="card card-deadline">
                        <span class="deadline-badge <?php echo $days <= 7 ? 'urgent' : 'soon'; ?>">
                            <?php echo $days === 0 ? 'Closes today!' : 'Closes in ' . $days . 'd'; ?>
                        </span>
                        <h3><?php echo htmlspecialchars($row['scholarship_name']); ?></h3>
                        <p><strong>Institution:</strong> <?php echo htmlspecialchars($row['institution_name']); ?></p>
                        <p><strong>Type:</strong>        <?php echo htmlspecialchars($row['scholarship_type']); ?></p>
                        <p><strong>Amount:</strong>      <?php echo htmlspecialchars($row['amount']); ?></p>
                        <p><strong>Fields:</strong>      <?php echo htmlspecialchars($row['fields'] ?? 'N/A'); ?></p>
                        <p><strong>Deadline:</strong>    <strong><?php echo htmlspecialchars($row['application_deadline']); ?></strong></p>
                        <a href="scholarship_view.php?id=<?php echo $row['scholarship_id']; ?>" class="btn">View Details</a>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- MAIN LISTING with search and filters -->
        <div class="dashboard-section">
            <h3 class="section-heading">All Scholarships</h3>

            <!-- Search and filter form -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <input type="text" name="search" placeholder="Search scholarships or institutions..."
                           value="<?php echo htmlspecialchars($search); ?>">
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
                    <button type="submit" class="btn">Search</button>
                    <?php if (!empty($search) || !empty($field_filter) || !empty($level_filter)): ?>
                        <a href="dashboard.php" class="btn-cancel">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Result count -->
            <p class="result-count">
                <?php if (!empty($search) || !empty($field_filter) || !empty($level_filter)): ?>
                    Showing <strong><?php echo $main_count; ?></strong> result<?php echo $main_count !== 1 ? 's' : ''; ?>
                <?php else: ?>
                    Showing all <strong><?php echo $main_count; ?></strong> active scholarships
                <?php endif; ?>
            </p>

            <!-- All scholarships grid -->
            <div class="scholarship-grid">
                <?php if ($main_count > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($main_result)): ?>
                        <div class="card">
                            <?php echo deadline_badge($row['application_deadline']); ?>
                            <h3><?php echo htmlspecialchars($row['scholarship_name']); ?></h3>
                            <p><strong>Institution:</strong> <?php echo htmlspecialchars($row['institution_name']); ?></p>
                            <p><strong>Type:</strong>        <?php echo htmlspecialchars($row['scholarship_type']); ?></p>
                            <p><strong>Amount:</strong>      <?php echo htmlspecialchars($row['amount']); ?></p>
                            <p><strong>Fields:</strong>      <?php echo htmlspecialchars($row['fields'] ?? 'N/A'); ?></p>
                            <p><strong>Level:</strong>       <?php echo htmlspecialchars($row['education_level']); ?></p>
                            <p><strong>Deadline:</strong>    <?php echo htmlspecialchars($row['application_deadline']); ?></p>
                            <a href="scholarship_view.php?id=<?php echo $row['scholarship_id']; ?>" class="btn">View Details</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>No scholarships found matching your search.</p>
                        <a href="dashboard.php" class="btn">View All Scholarships</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> EduLift. All rights reserved.</p>
    </footer>
</body>
</html>