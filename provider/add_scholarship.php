<?php
session_start();
include("../config/database.php");

// Only providers can access this page
if (!isset($_SESSION['provider_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $provider_id          = (int) $_SESSION['provider_id'];
    $scholarship_name     = trim($_POST['scholarship_name']);
    $scholarship_type     = $_POST['scholarship_type'];
    $description          = trim($_POST['description']);
    $amount               = trim($_POST['amount']);
    $education_level      = $_POST['education_level'];
    $gpa_requirement      = !empty($_POST['gpa_requirement']) ? (float) $_POST['gpa_requirement'] : null;
    $requirements         = trim($_POST['requirements']);
    $application_deadline = $_POST['application_deadline'];

    // Insert the new scholarship into the scholarships table
    $stmt = mysqli_prepare($conn, "INSERT INTO scholarships
                                   (provider_id, scholarship_name, scholarship_type, description,
                                    amount, education_level, gpa_requirement, requirements,
                                    application_deadline, status)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    mysqli_stmt_bind_param($stmt, "isssssdss",
        $provider_id, $scholarship_name, $scholarship_type, $description,
        $amount, $education_level, $gpa_requirement, $requirements, $application_deadline
    );

    if (mysqli_stmt_execute($stmt)) {
        $scholarship_id = mysqli_insert_id($conn);

        // Insert each selected field of study into the scholarship_fields table
        if (!empty($_POST['fields'])) {
            $stmt2 = mysqli_prepare($conn, "INSERT INTO scholarship_fields (scholarship_id, field_of_study)
                                            VALUES (?, ?)");
            foreach ($_POST['fields'] as $field) {
                $field = trim($field);
                mysqli_stmt_bind_param($stmt2, "is", $scholarship_id, $field);
                mysqli_stmt_execute($stmt2);
            }
        }

        header("Location: provider_dashboard.php?success=1");
        exit();

    } else {
        $error = "Error adding scholarship. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Scholarship - EduLift</title>
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
            <a href="../auth/logout.php">Logout</a>
        </nav>
    </header>

    <div class="form-page">
        <div class="form-card">
            <h2>Add New Scholarship</h2>

            <!-- Show error if submission failed -->
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="scholarship-form">
                <div class="form-group">
                    <label>Scholarship Name</label>
                    <input type="text" name="scholarship_name" placeholder="e.g., Merit-Based Scholarship" required>
                </div>
                <div class="form-group">
                    <label>Scholarship Type</label>
                    <select name="scholarship_type" required>
                        <option value="">Select Type</option>
                        <option value="Full Scholarship">Full Scholarship</option>
                        <option value="Partial Scholarship">Partial Scholarship</option>
                        <option value="Tuition Discount">Tuition Discount</option>
                        <option value="Financial Grant">Financial Grant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Describe the scholarship program..." rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="text" name="amount" placeholder="e.g., Full Tuition, PHP 50,000" required>
                </div>
                <div class="form-group">
                    <label>Education Level</label>
                    <select name="education_level" required>
                        <option value="">Select Education Level</option>
                        <option value="Senior High School">Senior High School</option>
                        <option value="College Undergraduate">College Undergraduate</option>
                        <option value="Graduate Studies">Graduate Studies</option>
                        <option value="All Levels">All Levels</option>
                    </select>
                </div>
                <!-- Fields stored separately in scholarship_fields table -->
                <div class="form-group">
                    <label>Fields of Study</label>
                    <div class="checkbox-group" id="fields-group">
                        <label><input type="checkbox" name="fields[]" value="Engineering" class="field-checkbox"> Engineering</label>
                        <label><input type="checkbox" name="fields[]" value="Medicine"    class="field-checkbox"> Medicine</label>
                        <label><input type="checkbox" name="fields[]" value="Business"    class="field-checkbox"> Business</label>
                        <label><input type="checkbox" name="fields[]" value="Science"     class="field-checkbox"> Science</label>
                        <label><input type="checkbox" name="fields[]" value="Arts"        class="field-checkbox"> Arts</label>
                        <label><input type="checkbox" name="fields[]" value="Law"         class="field-checkbox"> Law</label>
                        <label><input type="checkbox" name="fields[]" value="Education"   class="field-checkbox"> Education</label>
                        <label><input type="checkbox" name="fields[]" value="All Fields"  class="field-checkbox" id="all-fields-check"> All Fields</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>GPA Requirement</label>
                    <input type="number" step="0.01" min="1.00" max="4.00" name="gpa_requirement"
                           placeholder="e.g., 3.50"
                           oninput="this.setCustomValidity('')"
                           oninvalid="this.setCustomValidity('Please enter a valid GPA between 1.00 and 4.00.')">
                </div>
                <div class="form-group">
                    <label>Requirements</label>
                    <textarea name="requirements" placeholder="List all requirements..." rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Application Deadline</label>
                    <input type="date" name="application_deadline" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Add Scholarship</button>
                    <a href="provider_dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        // When "All Fields" is checked, uncheck all other options
        // When any other option is checked, uncheck "All Fields"
        const allFieldsCheck = document.getElementById('all-fields-check');
        const fieldCheckboxes = document.querySelectorAll('.field-checkbox:not(#all-fields-check)');

        allFieldsCheck.addEventListener('change', function () {
            if (this.checked) {
                fieldCheckboxes.forEach(cb => cb.checked = false);
            }
        });

        fieldCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (this.checked) {
                    allFieldsCheck.checked = false;
                }
            });
        });
    </script>
</body>
</html>