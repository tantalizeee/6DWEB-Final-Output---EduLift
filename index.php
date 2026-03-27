<?php
session_start();
include("config/database.php");

// Redirect logged-in users straight to their dashboard
// For providers, check verification status first before redirecting
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: admin/admin_dashboard.php");
        exit();
    } elseif ($_SESSION['user_type'] === 'provider') {
        // Only redirect if the provider is verified
        if (isset($_SESSION['provider_id'])) {
            $stmt_check = mysqli_prepare($conn, "SELECT status FROM provider_profiles WHERE provider_id = ?");
            mysqli_stmt_bind_param($stmt_check, "i", $_SESSION['provider_id']);
            mysqli_stmt_execute($stmt_check);
            $status_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_check));
            if ($status_row && $status_row['status'] === 'verified') {
                header("Location: provider/provider_dashboard.php");
                exit();
            }
        }
        // Pending or rejected — destroy session and let them see index
        session_destroy();
    } elseif ($_SESSION['user_type'] === 'student') {
        header("Location: student/dashboard.php");
        exit();
    }
}

// Fetch summary stats for the homepage
$total_scholarships = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM scholarships WHERE status='active' AND application_deadline >= CURDATE()"))['c'];
$total_institutions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM institutions"))['c'];
$total_students     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM student_profiles"))['c'];

// Fetch the 3 most recently added active scholarships for the featured section
$stmt = mysqli_prepare($conn, "SELECT s.scholarship_id, s.scholarship_name, s.scholarship_type,
                                       s.amount, s.education_level, s.application_deadline,
                                       i.institution_name,
                                       GROUP_CONCAT(sf.field_of_study SEPARATOR ', ') AS fields
                                FROM scholarships s
                                JOIN provider_profiles pp ON s.provider_id = pp.provider_id
                                JOIN institutions i ON pp.institution_id = i.institution_id
                                LEFT JOIN scholarship_fields sf ON s.scholarship_id = sf.scholarship_id
                                WHERE s.status = 'active'
                                GROUP BY s.scholarship_id
                                ORDER BY s.created_at DESC
                                LIMIT 3");
mysqli_stmt_execute($stmt);
$featured_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduLift - Find Your Scholarship</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/img/eduliftlogo.ico">
</head>
<body class="index-page">
    <header class="header">
        <a class="header-brand" href="index.php"><img src="assets/img/eduliftlogo.png" alt="EduLift Logo" class="header-logo"><h1 class="logo">EduLift</h1></a>
        <button class="nav-toggle" onclick="this.nextElementSibling.classList.toggle('open')" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <a href="index.php">Home</a>
            <a href="browse.php">Browse</a>
            <a href="auth/login.php">Login</a>
            <a href="auth/register.php">Register</a>
        </nav>
    </header>

    <!-- Hero section with search bar and call-to-action buttons -->
    <div class="hero">
        <h2>Find the Right Scholarship for You</h2>
        <p>EduLift connects Filipino students with scholarship opportunities from universities, foundations, and government institutions across the country.</p>

        <!-- Quick search — submits to browse.php -->
        <form method="GET" action="browse.php" class="hero-search-form">
            <div class="hero-search-bar">
                <input type="text" name="search" placeholder="Search scholarships, institutions, or fields...">
                <button type="submit" class="btn">Search</button>
            </div>
        </form>

        <div class="hero-buttons">
            <a href="browse.php"        class="btn">Browse All Scholarships</a>
            <a href="auth/register.php" class="btn secondary">Create an Account</a>
        </div>
    </div>

    <!-- Summary stats section -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_scholarships; ?></h3>
                <p>Active Scholarships</p>
            </div>
            <div class="stat-card highlight">
                <h3><?php echo $total_institutions; ?></h3>
                <p>Partner Institutions</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_students; ?></h3>
                <p>Registered Students</p>
            </div>
        </div>
    </div>

    <!-- Featured scholarships — 3 most recently added -->
    <div class="featured-section">
        <div class="container">
            <h2 class="section-title">Featured Scholarships</h2>
            <p class="section-subtitle">Recently added opportunities you don't want to miss</p>

            <div class="scholarship-grid">
                <?php while ($row = mysqli_fetch_assoc($featured_result)): ?>
                    <div class="card">
                        <h4><?php echo htmlspecialchars($row['scholarship_name']); ?></h4>
                        <p><strong>Institution:</strong> <?php echo htmlspecialchars($row['institution_name']); ?></p>
                        <p><strong>Type:</strong>        <?php echo htmlspecialchars($row['scholarship_type']); ?></p>
                        <p><strong>Amount:</strong>      <?php echo htmlspecialchars($row['amount']); ?></p>
                        <p><strong>Fields:</strong>      <?php echo htmlspecialchars($row['fields'] ?? 'N/A'); ?></p>
                        <p><strong>Deadline:</strong>    <?php echo htmlspecialchars($row['application_deadline']); ?></p>
                        <div class="card-actions">
                            <a href="auth/login.php" class="btn">Login to Apply</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="featured-cta">
                <a href="browse.php" class="btn">View All Scholarships</a>
            </div>
        </div>
    </div>

    <!-- Mission and Vision cards -->
    <div class="mv">
        <div class="mv-card">
            <h3>Our Mission</h3>
            <p>To bridge the gap between deserving Filipino students and scholarship providers, making quality education accessible to all regardless of financial background.</p>
        </div>
        <div class="mv-card">
            <h3>Our Vision</h3>
            <p>A Philippines where every talented student has an equal opportunity to pursue their educational dreams through accessible and transparent scholarship programs.</p>
        </div>
    </div>

    <!-- How It Works steps -->
    <div class="how-it-works">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4>Create an Account</h4>
                    <p>Register as a student and fill in your academic profile.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4>Browse Scholarships</h4>
                    <p>Search and filter scholarships that match your field and level.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4>Apply Directly</h4>
                    <p>Get full details and contact the institution to apply.</p>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> EduLift. All rights reserved.</p>
    </footer>
</body>
</html>