<?php
session_start();
include("config.php");
$error = "";
$success = "";

$ADMIN_USERNAME = "siddharth";
$ADMIN_PASSWORD = "123456";

if (isset($_POST['submit'])) {
    $user = trim(mysqli_real_escape_string($conn, $_POST['username']??''));
    $pass = trim($_POST['password']??'');

    if ($user == "" && $pass == "") {
        $error = "Username and password fields are empty!";
    } elseif ($user == "") {
        $error = "Username field is empty!";
    } elseif ($pass == "") {
        $error = "Password field is empty!";
    } else {
        if ($user == $ADMIN_USERNAME && $pass == $ADMIN_PASSWORD) {
            $_SESSION['admin'] = $user;
            $_SESSION['admin_id'] = 'admin_001';
            $_SESSION['user_type'] = 'admin';
            header("Location:admin_home.php");
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.3s;
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
            background: #ffebee;
            color: #d32f2f;
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
            border-color: #ff6b6b;
            box-shadow: 0 0 5px rgba(255, 107, 107, 0.3);
        }
        
        #btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
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
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
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
            color: #ff6b6b;
            text-decoration: none;
            font-weight: bold;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .credentials {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 12px;
            border-radius: 5px;
            font-size: 12px;
            margin-top: 20px;
            color: #e65100;
        }
        
        .credentials strong {
            display: block;
            margin-bottom: 5px;
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
    <a href="index.php" class="back-link">← Back to Home</a>

    <div id="form">
        <h1>Admin Login</h1>
        <div class="badge">🔐 ADMIN LOGIN</div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form name="form" method="POST" onsubmit="return isvalid()">
            
            <div class="form-group">
                <label>Admin Username:</label>
                <input type="text" id="username" name="username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    placeholder="Enter admin username">
            </div>

            <div class="form-group">
                <label>Admin Password:</label>
                <input type="password" id="password" name="password"
                    placeholder="Enter admin password">
            </div>

            <input type="submit" id="btn" value="Admin Login" name="submit"/>
        </form>

        <div class="links">
            <p>Not an admin? <a href="user_login.php">User Login</a></p>
        </div>

        
    </div>

    <script>
        function isvalid() {
            var username = document.getElementById("username").value.trim();
            var password = document.getElementById("password").value.trim();
            
            if (username == "" && password == "") {
                alert("Username and password fields are empty!");
                return false;
            } else if (username == "") {
                alert("Username field is empty!");
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