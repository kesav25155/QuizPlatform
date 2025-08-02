<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="refresh" content="3;url=index.php">
    <title>Welcome - WebTechXpo</title>
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
        }
        .btn-link {
            padding: 2vh 3vw;
            background: #002244;
            color: #00d9ff;
            border: 2px solid #00d9ff;
            border-radius: 10px;
            font-size: 2.5vw;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 2vh;
            text-shadow: 0 0 3px #00d9ff;
            text-decoration: none;
            display: block;
        }
        .btn-link:hover {
            background: #004466;
            transform: scale(1.05);
            box-shadow: 0 0 10px #00d9ff;
        }
        h1 {
            font-size: 3vw;
            text-shadow: 0 0 5px #00d9ff;
        }
    </style>
</head>
<body>
    <div class="glow-effect"></div>
    <h1>Welcome To WebTechXpo- ITRIX' 25&emsp;->&emsp;</h1>
    <br>
    <div>
        <a class="btn-link" href="login.php">Start</a>
    </div>
</body>
</html>