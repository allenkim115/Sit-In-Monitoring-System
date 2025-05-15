<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Sit-in Monirtoring System</title>
    <link rel="stylesheet" href="css/w3.css">
    <style>
        body {
            background: rgb(100, 25, 117);
            background: linear-gradient(133deg, rgba(100, 25, 117, 1) 15%, rgba(249, 249, 249, 1) 48%, rgba(233, 236, 107, 1) 82%);
            height: 100vh;
        }
    </style>
</head>
<body>
<div class="container w3-container w3-margin">
    <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top"
         style="width: 25%; margin:auto; margin-top: 5%; background-color:#ffff;">
        <form action="login.php" method="POST">
            <div class="logo w3-center">
                <img src="images/ucheader.png" alt="ucheader" style="width: 60px; height: 60px;">
                <img src="images/OIP.png" alt="ccs" style="width: 60px; height: 60px;">
            </div>
            <h2 class="w3-center" style="font-weight: 600;">CCS Sit-in Monitoring System</h2>
            <input type="text" name="username" placeholder="Username" class="w3-input w3-border w3-round" required><br>
            <input type="password" name="password" placeholder="Password" class="w3-input w3-border w3-round" required><br>
            <button type="submit" class="w3-btn w3-purple w3-round-xlarge" style="width: 30%;" name="Login">Login</button><br>
            <p class="w3-center">Don't have an account? <a style="color: blue; text-decoration:none;" href="register.php">Register here.</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>

<?php
include 'includes/connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // First, check if it's an admin login
    $sql_admin = "SELECT * FROM admin WHERE USERNAME = ?";
    $stmt_admin = $conn->prepare($sql_admin);
    $stmt_admin->bind_param("s", $username);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();

    if ($result_admin->num_rows > 0) {
        $admin = $result_admin->fetch_assoc();
        if (password_verify($password, $admin['PASSWORD'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user'] = $admin;
            $_SESSION['is_admin'] = true;
            header("Location: admin/admin.php");
            exit();
        } else {
            echo "<script>alert('Invalid Admin Username/Password');</script>";
        }
        $stmt_admin->close(); // Close the admin statement
    } else {
        // If not an admin, check if it's a regular user
        $sql_user = "SELECT * FROM user WHERE USERNAME = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("s", $username);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();

        if ($result_user->num_rows > 0) {
            $user = $result_user->fetch_assoc();
            if (password_verify($password, $user['PASSWORD'])) {
                $_SESSION['loggedin'] = true;
                $_SESSION['user'] = $user;
                $_SESSION['is_admin'] = false;
                header("Location: student/dashboard.php");
                exit();
            } else {
                echo "<script>alert('Invalid Username/Password');</script>";
            }
            $stmt_user->close(); // Close the user statement
        } else {
            echo "<script>alert('Invalid Username/Password');</script>";
        }
    }
}
?>
