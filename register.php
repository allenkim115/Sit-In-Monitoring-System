<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="w3.css">
    <style>
        body
        {
            background: rgb(100,25,117);
            background: linear-gradient(133deg, rgba(100,25,117,1) 15%, rgba(249,249,249,1) 48%, rgba(233,236,107,1) 82%);
            height: 120vh;
            font-size: 14px;
        }
        select.w3-select option[disabled] {
            color: #aaa; /* Light gray color, similar to placeholder text */
            background-color: transparent; /* Make background transparent if needed */
        }

    </style>
</head>
<body>
    <div class=" container w3-container w3-margin">
        <div class="w3-mobile w3-round-xlarge w3-card-4 w3-container w3-padding w3-animate-top" style="width: 35%; margin:auto; background-color:#ffff;">
            <form action="register.php" method="POST">
                <div class="logo w3-center">
                    <img src="images/ucheader.png" alt="ucheader" style="width: 60px; height: 60px;">
                    <img src="images/OIP.png" alt="ccs" style="width: 60px; height: 60px;">
                </div>
                <h2 class="w3-center w3-margin" style="text-transform: uppercase; font-weight: 600;">Register</h2>
                <input type="text" name="IDNO" placeholder="IDNO" class="w3-input w3-border w3-round" required><br>
                <input type="text" name="lastname" placeholder="Lastname" class="w3-input w3-border w3-round" required><br>
                <input type="text" name="firstname" placeholder="Firstname" class="w3-input w3-border w3-round" required><br>
                <input type="text" name="midname" placeholder="Middlename" class="w3-input w3-border w3-round"><br>
                <select class=" w3-input w3-border w3-round w3-select" name="course" required>
                    <option value=""disabled selected>Course</option>
                    <option value="1">BSIT</option>
                    <option value="2">BSCS</option>
                    <option value="3">BSCpE</option>
                  </select><br>
                <select class=" w3-input w3-border w3-round w3-select" name="year_lvl" required>
                    <option value=""disabled selected>Year Level</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                  </select><br>
                <input type="text" name="username" placeholder="Username" class="w3-input w3-border w3-round" required><br>
                <input type="password" name="password" placeholder="Password" class="w3-input w3-border w3-round" required><br>
                <input type="password" name="confirm_password" placeholder="Confirm Password" class="w3-input w3-border w3-round" required><br>
                <button type="submit"  class="w3-input w3-blue w3-round-xlarge w3-center" name="Register">Register</button><br>
                <p class="w3-center" style="margin: 0; padding: 0;">Already have an account? <a style="color: blue;" href="login.php">Login Here.</a></p>
            </form>
        </div>
    </div>
</body>
</html>

<?php
 include 'connect.php';
 
 if($_SERVER["REQUEST_METHOD"]=="POST"){
     
     $IDNO = mysqli_real_escape_string($conn, $_POST['IDNO']);
     $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
     $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
     $midname = mysqli_real_escape_string($conn, $_POST['midname']);
     $course = mysqli_real_escape_string($conn, $_POST['course']);
     $year_lvl = mysqli_real_escape_string($conn, $_POST['year_lvl']);
     $username = mysqli_real_escape_string($conn, $_POST['username']);
     $password = mysqli_real_escape_string($conn, $_POST['password']);
     $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
 
     if ($password !== $confirm_password) {
         echo "<script>alert('Passwords do not match.');</script>";
     } else {
         $checkusername = "SELECT * FROM user WHERE USERNAME = '$username'";
         $checkIDNO = "SELECT * FROM user WHERE IDNO = '$IDNO'";
         $result = $conn->query($checkusername);
 
         if($result->num_rows > 0){
             echo "<script>alert('Username already taken.');</script>";
         } else {
             $hashedpassword = password_hash($password, PASSWORD_BCRYPT);
 
             $sql = "INSERT INTO user (IDNO, LASTNAME, FIRSTNAME, MIDDLENAME, COURSE, YEAR_LEVEL, USERNAME, PASSWORD) VALUES ('$IDNO', '$lastname', '$firstname', '$midname', '$course', '$year_lvl', '$username', '$hashedpassword')";
             if($conn->query($sql) === TRUE){
                 echo "<script>
                         alert('Registration Successful. You can now login your account');
                         window.location.href = 'login.php';
                       </script>";
                 exit();
             } else {
                 echo "<script>alert('Registration Failed.');</script>";
             }
         }
     }
 }
?>