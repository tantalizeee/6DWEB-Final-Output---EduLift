<?php
include("../config/database.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form input
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $institution_name = trim($_POST['institution_name']);
    $institution_type = $_POST['institution_type'];
    $location         = trim($_POST['location']);
    $contact_phone    = trim($_POST['contact_phone']);
    $description      = isset($_POST['description']) ? trim($_POST['description']) : '';

    // Check if the email is already registered
    $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $error = "This email address is already registered. Please use a different email or login instead.";
    } else {
        // Step 1: Insert institution details into the institutions table
        $stmt = mysqli_prepare($conn, "INSERT INTO institutions
                                       (institution_name, institution_type, location, contact_email, contact_phone, description)
                                       VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssss", $institution_name, $institution_type, $location, $email, $contact_phone, $description);

        if (mysqli_stmt_execute($stmt)) {
            $institution_id = mysqli_insert_id($conn);

            // Step 2: Create a user account for the provider
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (username, email, password, user_type)
                                            VALUES (?, ?, ?, 'provider')");
            mysqli_stmt_bind_param($stmt2, "sss", $username, $email, $password);

            if (mysqli_stmt_execute($stmt2)) {
                $user_id = mysqli_insert_id($conn);

                // Step 3: Link the user to their institution in provider_profiles (status starts as pending)
                $stmt3 = mysqli_prepare($conn, "INSERT INTO provider_profiles (user_id, institution_id, status)
                                                VALUES (?, ?, 'pending')");
                mysqli_stmt_bind_param($stmt3, "ii", $user_id, $institution_id);

                if (mysqli_stmt_execute($stmt3)) {
                    // Redirect to login with a success message
                    header("Location: login.php?registered=1");
                    exit();
                }
            }
        }

        $error = "Registration failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Registration - EduLift</title>
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
        <div class="auth-card">
            <h2>Register as Scholarship Provider</h2>

            <!-- Show error if registration failed -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="text"     name="username"         placeholder="Username"                   required>
                <input type="email"    name="email"            placeholder="Email"                      required>
                <input type="password" name="password"         placeholder="Password"                   required>
                <input type="text"     name="institution_name" placeholder="Institution Name"           required>
                <select name="institution_type" required>
                    <option value="">Select Institution Type</option>
                    <option value="University">University</option>
                    <option value="College">College</option>
                    <option value="Government Agency">Government Agency</option>
                    <option value="Private Organization">Private Organization</option>
                    <option value="Foundation">Foundation</option>
                </select>
                <input type="text" name="location"      placeholder="Location (City, Province)" required>
                <input type="text" name="contact_phone" placeholder="Contact Phone"             required>
                <textarea name="description" placeholder="Brief description of your institution (optional)" rows="3"></textarea>
                <button type="submit" class="btn">Register</button>
            </form>
            <p class="auth-footer">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>