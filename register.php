<?php
include("config.php");
$error = "";
$success = "";

if (isset($_POST['submit'])) {
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']??'');
    $Name    = mysqli_real_escape_string($conn, $_POST['Name']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $cpassword = mysqli_real_escape_string($conn, $_POST['cpassword']);

    if ($mobile == "" || $Name == "" || $email == "" || $password == "" || $cpassword == "") {
        $error = "Must fill every field!";
    } else if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile number must be 10 digits!";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else if ($password != $cpassword) {
        $error = "Passwords do not match!";
    } else if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $check_mobile = mysqli_query($conn, "SELECT * FROM users WHERE `Mobile_number`='$mobile'");
        if (mysqli_num_rows($check_mobile) > 0) {
            $error = "Mobile number already registered!";
        } else {
            $check_name = mysqli_query($conn, "SELECT * FROM users WHERE `Name`='$Name'");
            if (mysqli_num_rows($check_name) > 0) {
                $error = "Username already taken!";
            } else {
                $sql = "INSERT INTO `users` (`Name`, `Mobile_number`, `Email_id`, `password`, `cpassword`) 
                        VALUES ('$Name', '$mobile', '$email', '$password', '$cpassword')";
                
                if (mysqli_query($conn, $sql)) {
                    $success = "Registration successful! <a href='user_login.php'>Login here</a>";
                    $_POST = array();
                } else {
                    $error = "Registration failed. Error: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
        <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        #form {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        
        #btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        
        #btn:hover {
            transform: translateY(-2px);
        }
        
        #btn:active {
            transform: translateY(0);
        }
        
        p {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        a {
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .error {
            color: #d32f2f;
            padding: 15px;
            background: #ffebee;
            border-left: 4px solid #d32f2f;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            color: #388e3c;
            padding: 15px;
            background: #e8f5e9;
            border-left: 4px solid #388e3c;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div id="form">
        <h1>Register</h1>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form name="form" method="POST" onsubmit="return isvalid()">

            <div class="form-group">
                <label>Mobile Number:</label>
                <input type="text" id="mobile" name="mobile"
                    value="<?php echo isset($_POST['mobile']) ? htmlspecialchars($_POST['mobile']) : ''; ?>"
                    placeholder="Enter 10 digit mobile number">
            </div>

            <div class="form-group">
                <label>Name:</label>
                <input type="text" id="Name" name="Name"
                    value="<?php echo isset($_POST['Name']) ? htmlspecialchars($_POST['Name']) : ''; ?>"
                    placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label>Email ID:</label>
                <input type="email" id="email" name="email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label>Password:</label>
                <div style="position:relative">
                    <input type="password" id="password" name="password"
                        placeholder="Min 6 characters"
                        style="width:100%;padding-right:42px;box-sizing:border-box">
                    <span id="eye_pass" onclick="togglePass('password','eye_pass')"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:18px;user-select:none">👁️</span>
                </div>
            </div>

            <div class="form-group">
                <label>Confirm Password:</label>
                <div style="position:relative">
                    <input type="password" id="cpassword" name="cpassword"
                        placeholder="Re-enter password"
                        style="width:100%;padding-right:42px;box-sizing:border-box">
                    <span id="eye_cpass" onclick="togglePass('cpassword','eye_cpass')"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:18px;user-select:none">👁️</span>
                </div>
            </div>

            <input type="submit" id="btn" value="Register" name="submit"/>
        </form>

        <p>Already have an account? <a href="user_login.php">Login here</a></p>
    </div>

    <script>
        function togglePass(inputId, eyeId) {
            var input = document.getElementById(inputId);
            var eye   = document.getElementById(eyeId);
            input.type = input.type === 'password' ? 'text' : 'password';
            eye.textContent = input.type === 'password' ? '👁️' : '🙈';
        }
        function isvalid() {
            var mobile = document.getElementById("mobile").value.trim();
            var Name = document.getElementById("Name").value.trim();
            var email = document.getElementById("email").value.trim();
            var password = document.getElementById("password").value.trim();
            var cpassword = document.getElementById("cpassword").value.trim();

            if (mobile == "" || Name == "" || email == "" || password == "" || cpassword == "") {
                alert("Must fill every field!");
                return false;
            }

            if (!/^[0-9]{10}$/.test(mobile)) {
                alert("Mobile number must be exactly 10 digits!");
                return false;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert("Please enter a valid email address!");
                return false;
            }

            if (password.length < 6) {
                alert("Password must be at least 6 characters!");
                return false;
            }

            if (password != cpassword) {
                alert("Passwords do not match!");
                return false;
            }

            return true;
        }
    </script>
</body>
</html>