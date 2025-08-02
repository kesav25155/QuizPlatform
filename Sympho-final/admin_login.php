<?php
session_start();
require 'db_config.php';

// Admin login verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM admin WHERE username = $1 AND password = $2";
    $result = pg_query_params($conn, $query, array($username, $password));
    
    if (pg_num_rows($result) > 0) {
        $_SESSION['admin'] = $username;
        header("Location: admin.php");
        exit();
    } else {
        $error = "Invalid Credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - WebTechExpo</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');
        *{
            font-family: 'Orbitron', sans-serif;
        }
        body {
            font-family: 'Orbitron', sans-serif;
            background: #000;
            color: #00d9ff;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .glow-effect {
            position: absolute;
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle, rgba(0, 217, 255, 0.36) 10%, transparent 10.01%);
            background-size: 30px 30px;
            z-index: -1;
        }
        
        .login-container {
            background: rgba(0, 0, 0, 0.9);
            padding: 3vw;
            width: 40vw;
            max-width: 400px;
            border-radius: 15px;
            border: 3px solid #00d9ff;
            box-shadow: 0 0 15px rgba(0, 217, 255, 0.6);
            text-align: center;
        }
        
        h3 {
            font-size: 2.5rem;
            text-shadow: 0 0 10px #00d9ff;
            margin-bottom: 20px;
        }

        .input-box {
            width: 90%;
            padding: 12px;
            background: transparent;
            border: 2px solid #00d9ff;
            border-radius: 10px;
            color: #00d9ff;
            font-size: 1rem;
            outline: none;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.4);
            margin-bottom: 10px;
            transition: 0.3s;
        }
        .input-box:focus {
            background: rgba(0, 217, 255, 0.1);
            transform: scale(1.05);
        }

        .btn {
            padding: 10px 20px;
            background: #002244;
            color: #00d9ff;
            border: 2px solid #00d9ff;
            border-radius: 10px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: 0.3s;
            text-shadow: 0 0 5px #00d9ff;
        }

        .btn:hover {
            background: #004466;
            transform: scale(1.05);
            box-shadow: 0 0 15px #00d9ff;
        }

        .error-message {
            color: #ff4444;
            font-size: 1rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="glow-effect"></div>
    <div class="login-container">
        <h3>Admin Login</h3>
        <?php if(isset($error)) echo "<p class='error-message'>$error</p>"; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" class="input-box" required autocomplete="off"><br>
            <input type="password" name="password" placeholder="Password" class="input-box" required autocomplete="off"><br>
            <button type="submit" name="admin_login" class="btn">Login</button>
        </form>
    </div>
</body>
</html>