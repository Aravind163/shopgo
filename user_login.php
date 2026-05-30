<?php
session_start();
include("config.php");
$error = "";
$success = "";

if (isset($_POST['submit'])) {
    $user = trim(mysqli_real_escape_string($conn, $_POST['name']??''));
    $pass = trim($_POST['password']??'');

    if ($user == "" && $pass == "") {
        $error = "Name and password fields are empty!";
    } elseif ($user == "") {
        $error = "Name field is empty!";
    } elseif ($pass == "") {
        $error = "Password field is empty!";
    } else {
        // Query using Name from database
        $sql = "SELECT * FROM users WHERE `Name`='$user'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            
            // Direct comparison for plain text passwords
            if ($pass == $row['password']) {
                $_SESSION['user'] = $user;
                $_SESSION['user_id'] = isset($row['id']) ? $row['id'] : '';
                $_SESSION['user_type'] = 'user';
                header("Location: user_home.php");
                exit();
            } else {
                $error = "Invalid Name or password!";
            }
        } else {
            $error = "Invalid Name or password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        

        .back-link:hover {
            opacity: 0.8;
        }
        
        #form {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .badge {
            text-align: center;
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 20px;
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
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        #btn:active {
            transform: translateY(0);
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .links p {
            margin: 10px 0;
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
        <h1>User Login</h1>
        <div class="badge">👤 USER LOGIN</div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form name="form" method="POST" onsubmit="return isvalid()">
            
            <div class="form-group">
                <label>User Name:</label>
                <input type="text" id="name" name="name"
                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                    placeholder="Enter your username">
            </div>

            <div class="form-group">
                <label>Password:</label>
                <div style="position:relative">
                    <input type="password" id="password" name="password"
                        placeholder="Enter your password"
                        style="width:100%;padding-right:42px;box-sizing:border-box">
                    <span id="eye_user" onclick="togglePass('password','eye_user')"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:18px;user-select:none">👁️</span>
                </div>
            </div>

            <input type="submit" id="btn" value="Login" name="submit"/>
        </form>

        <div class="links">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p>Are you an admin? <a href="admin_login.php">Admin Login</a></p>
        </div>
    </div>

    <script>
        function togglePass(inputId, eyeId) {
            var input = document.getElementById(inputId);
            var eye   = document.getElementById(eyeId);
            input.type = input.type === 'password' ? 'text' : 'password';
            eye.textContent = input.type === 'password' ? '👁️' : '🙈';
        }
        function isvalid() {
            var name = document.getElementById("name").value.trim();
            var password = document.getElementById("password").value.trim();
            
            if (name == "" && password == "") {
                alert("Name and password fields are empty!");
                return false;
            } else if (name == "") {
                alert("Name field is empty!");
                return false;
            } else if (password == "") {
                alert("Password field is empty!");
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>