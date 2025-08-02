<?php
session_start();

// Ensure score and answers are set in session
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 0;
}
if (!isset($_SESSION['answers'])) {
    $_SESSION['answers'] = array();
}

// Redirect to results after 5 seconds
header("refresh:5;url=results.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitting Answers...</title>
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

        /* Low glow effect */
        .glow-effect {
            position: absolute;
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle, rgba(0, 217, 255, 0.36) 10%, transparent 10.01%);
            background-size: 40px 40px;
            z-index: -1;
            animation: glow-pulse 3s infinite alternate;
        }

        @keyframes glow-pulse {
            0% {
                opacity: 0.5;
            }
            100% {
                opacity: 1;
            }
        }

        .quiz-container {
            background: rgba(0, 0, 0, 0.8);
            padding: 3vw;
            width: 40vw;
            max-width: 400px;
            border-radius: 15px;
            border: 2px solid #00d9ff;
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.3);
            text-align: center;
        }

        h2 {
            font-size: 2rem;
            text-shadow: 0 0 5px #00d9ff;
        }

        p {
            font-size: 1.2rem;
            margin-top: 10px;
        }

        #countdown {
            font-weight: bold;
            color: #00d9ff;
            text-shadow: 0 0 5px #00d9ff;
        }
    </style>
</head>
<body>
    <div class="glow-effect"></div> <!-- Background glow effect -->

    <div class="quiz-container">
        <h2>Submitting Your Answers</h2>
        <p id="redirect-message">Your answers have been submitted.<br>You will be redirected to the results page in <span id="countdown">5</span> seconds...</p>
    </div>

    <script>
        let timeLeft = 5;

        function updateTimer() {
            document.getElementById('countdown').innerText = timeLeft; 
            if (timeLeft <= 0) {
                window.location.href = "results.php";
            } else {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            }
        }

        // Start countdown when page loads
        window.onload = function() {
            updateTimer();
        };
    </script>
</body>
</html>