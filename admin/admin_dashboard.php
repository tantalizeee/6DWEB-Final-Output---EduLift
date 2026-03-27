<?php
session_start();
include("../config/database.php");

// Only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Count total providers, students, scholarships, and pending verifications
$total_providers   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM provider_profiles"))['c'];
$total_students    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM student_profiles"))['c'];
$total_scholarships = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM scholarships"))['c'];
$pending_providers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM provider_profiles WHERE status = 'pending'"))['c'];

// Fetch all providers with a pending status, including institution and user info
$stmt = mysqli_prepare($conn, "SELECT pp.provider_id, pp.created_at,
                                       i.institution_name, i.institution_type, i.location,
                                       u.email
                                FROM provider_profiles pp
                                JOIN institutions i ON pp.institution_id = i.institution_id
                                JOIN users u ON pp.user_id = u.user_id
                                WHERE pp.status = 'pending'
                                ORDER BY pp.created_at DESC");
mysqli_stmt_execute($stmt);
$providers_result = mysqli_stmt_get_result($stmt);

// Fetch the 5 most recently added scholarships
$stmt2 = mysqli_prepare($conn, "SELECT s.scholarship_name, s.scholarship_type, s.amount, s.status,
                                        i.institution_name
                                 FROM scholarships s
                                 JOIN provider_profiles pp ON s.provider_id = pp.provider_id
                                 JOIN institutions i ON pp.institution_id = i.institution_id
                                 ORDER BY s.created_at DESC
                                 LIMIT 5");
mysqli_stmt_execute($stmt2);
$scholarships_result = mysqli_stmt_get_result($stmt2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduLift</title>
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
        <h2>Admin Dashboard</h2>

        <!-- Show result of verify/reject action -->
        <?php if (isset($_GET['verified'])): ?>
            <div class="success-message">
                Provider has been <?php echo $_GET['verified'] === 'approve' ? 'approved' : 'rejected'; ?> successfully.
            </div>
        <?php endif; ?>

        <!-- Show error messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php if ($_GET['error'] === 'verify_failed'): ?>
                    Error: Could not update provider status.
                <?php elseif ($_GET['error'] === 'not_found'): ?>
                    Error: Provider not found.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Summary stat cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_providers; ?></h3>
                <p>Total Providers</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_scholarships; ?></h3>
                <p>Total Scholarships</p>
            </div>
            <div class="stat-card highlight">
                <h3><?php echo $pending_providers; ?></h3>
                <p>Pending Verifications</p>
            </div>
        </div>

        <!-- Pending providers table — only shown if there are any -->
        <?php if ($pending_providers > 0): ?>
        <div class="section">
            <h3>Pending Provider Verifications</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Institution</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Email</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($provider = mysqli_fetch_assoc($providers_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($provider['institution_name']); ?></td>
                            <td><?php echo htmlspecialchars($provider['institution_type']); ?></td>
                            <td><?php echo htmlspecialchars($provider['location']); ?></td>
                            <td><?php echo htmlspecialchars($provider['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($provider['created_at'])); ?></td>
                            <td>
                                <a href="verify_provider.php?id=<?php echo $provider['provider_id']; ?>&action=approve" class="btn-small">Approve</a>
                                <a href="verify_provider.php?id=<?php echo $provider['provider_id']; ?>&action=reject"  class="btn-small btn-danger">Reject</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- 5 most recently added scholarships -->
        <div class="section">
            <h3>Recent Scholarships</h3>
            <div class="scholarship-grid">
                <?php while ($scholarship = mysqli_fetch_assoc($scholarships_result)): ?>
                <div class="card">
                    <h4><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></h4>
                    <p><strong>Provider:</strong> <?php echo htmlspecialchars($scholarship['institution_name']); ?></p>
                    <p><strong>Type:</strong>     <?php echo htmlspecialchars($scholarship['scholarship_type']); ?></p>
                    <p><strong>Amount:</strong>   <?php echo htmlspecialchars($scholarship['amount']); ?></p>
                    <p><strong>Status:</strong>
                        <span class="status-<?php echo $scholarship['status']; ?>">
                            <?php echo htmlspecialchars($scholarship['status']); ?>
                        </span>
                    </p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>