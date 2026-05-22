<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Choose Role</title>
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
        
        .container {
            width: 100%;
            max-width: 900px;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        
        .header h1 {
            font-size: 42px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .login-options {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 380px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .login-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .login-card.user {
            border-top: 4px solid #667eea;
        }
        
        .login-card.admin {
            border-top: 4px solid #ff6b6b;
        }
        
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .login-card h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .login-card p {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-user:hover {
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .btn-admin:hover {
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4);
        }
        
        .footer {
            text-align: center;
            color: white;
            margin-top: 50px;
            font-size: 14px;
            opacity: 0.8;
        }
        
        .footer a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 32px;
            }
            
            .login-options {
                gap: 20px;
            }
            
            .login-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome Back</h1>
            <p>Choose your login type to continue</p>
        </div>

        <div class="login-options">
            <div class="login-card user">
                <div class="icon">👤</div>
                <h2>User Login</h2>
                <p>Login as a regular user to access your account and manage your profile.</p>
                <a href="user_login.php" class="btn btn-user">User Login</a>
            </div>

            <div class="login-card admin">
                <div class="icon">🔐</div>
                <h2>Admin Login</h2>
                <p>Login as an administrator to manage users and system settings.</p>
                <a href="admin_login.php" class="btn btn-admin">Admin Login</a>
            </div>
        </div>

        <div class="footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</body>
</html>