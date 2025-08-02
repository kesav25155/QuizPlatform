<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="refresh" content="3;url=index.php">
    <title>Logging Out...</title>
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
            background-size: 40px 40px;
            z-index: -1;
            animation: glow 3s infinite alternate;
        }

        @keyframes glow {
            0% {
                opacity: 0.5;
            }
            100% {
                opacity: 1;
            }
        }

        h1 {
            font-size: 2.5rem;
            text-shadow: 0 0 10px rgba(0, 217, 255, 0.8);
            animation: fade 3s infinite alternate;
        }

        @keyframes fade {
            0% {
                opacity: 0.8;
            }
            100% {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
<div class="glow-effect"></div>
    <h1>You have been logged out. Redirecting...</h1>
</body>
</html>