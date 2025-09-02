<?php
// auth/register.php
require_once '../includes/config.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "An account with this email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
        $stmt->bind_param("sss", $fullname, $email, $password);

        if ($stmt->execute()) {
            $success = "Application submitted successfully! You may now log in.";
        } else {
            $error = "Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply Now - MyCamp Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- optional -->
    <style>
        body {
            background: #f0f2f5;
            font-family: Arial, sans-serif;
        }

        .form-container {
            width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #4a90e2;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background: #357abd;
        }

        .error, .success {
            text-align: center;
            margin-bottom: 15px;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Apply Now</h2>
        <?php if ($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>

        <form action="" method="POST">
            <label for="fullname">Full Name</label>
            <input type="text" name="fullname" id="fullname" required>

            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Create Password</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" class="btn">Submit Application</button>
        </form>
    </div>
</body>
</html>