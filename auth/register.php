<?php
session_start();

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header("Location: ../admin/admin_dashboard.php");
    } elseif ($_SESSION['user_type'] === 'provider') {
        header("Location: ../provider/provider_dashboard.php");
    } else {
        header("Location: ../student/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EduLift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/eduliftlogo.ico">
</head>
<body>
    <header class="header">
        <a class="header-brand" href="../index.php"><img src="../assets/img/eduliftlogo.png" alt="EduLift Logo" class="header-logo"><h1 class="logo">EduLift</h1></a>
        <button class="nav-toggle" onclick="this.nextElementSibling.classList.toggle('open')" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <nav>
            <a href="../index.php">Home</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <div class="auth-page">
        <div class="auth-card register-choice-card">
            <h2>Create an Account</h2>
            <p class="register-subtitle">Are you a student looking for scholarships, or an institution offering them?</p>

            <!-- Two buttons leading to the respective registration pages -->
            <div class="register-choices">
                <a href="register_student.php"  class="btn register-choice-btn">&nbsp; Register as Student</a>
                <a href="register_provider.php" class="btn register-choice-btn">&nbsp; Register as Provider</a>
            </div>

            <p class="auth-footer">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>