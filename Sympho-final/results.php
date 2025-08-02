<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['team'])) {
    header("Location: index.php");
    exit();
}

// Fetch all questions
$query1 = "SELECT * FROM questions";
$result1 = pg_query($conn, $query1);
$questions = pg_fetch_all($result1);

if (!isset($_SESSION['questions'])) {
    $_SESSION['questions'] = $questions;
}

// Fetch user and team details
$team_id = $_SESSION['team_id'];
$user_id = $_SESSION['user_name'];
$total_questions = $_SESSION['questions'];
$t_q = count($total_questions);
$correct_answers = $_SESSION['score'];

// Calculate score
$score = $correct_answers;

// Store individual score in `users` table
pg_query($conn, "UPDATE users SET score = $score WHERE name = '$user_id'");

// Check if team already has an entry
$query = "SELECT * FROM team_scores WHERE team_id = '$team_id'";
$result = pg_query($conn, $query);
$team_data = pg_fetch_assoc($result);

// Fetch teammate’s name and score
$teammate_query = "SELECT name, score FROM users WHERE team_id = '$team_id' AND name != '$user_id'";
$teammate_result = pg_query($conn, $teammate_query);
$teammate_data = pg_fetch_assoc($teammate_result);

$teammate_name = $teammate_data ? $teammate_data['name'] : "Waiting for teammate...";
$teammate_score = ($teammate_data && !is_null($teammate_data['score'])) ? $teammate_data['score'] : "Pending";
if (is_null($teammate_data['score'])) {
    // Teammate hasn't finished yet
    $message = "Your score: $score%. Waiting for teammate...";
    $average_score = "Pending";
    $show_refresh = true;
} else {
    // Both players have completed the quiz
    $average_score = ($teammate_data['score'] + $score) / 2;
    
    $check_query = pg_query($conn, "SELECT * FROM team_scores WHERE team_id = '$team_id'");
    
    if (pg_num_rows($check_query) == 0) {
        // Insert if not present
        pg_query($conn, "INSERT INTO team_scores (team_id, avg_score) VALUES ('$team_id', '$average_score')");
    } else {
        // Update if already exists
        pg_query($conn, "UPDATE team_scores SET avg_score = '$average_score' WHERE team_id = '$team_id'");
    }

    $message = "Your team's average score: $average_score%";
    $show_refresh = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - WebTechExpo</title>
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
            background-size: 40px 40px;
            z-index: -1;
            opacity: 0.5; /* Reduced glow intensity */
        }
        
        .result-container {
            background: rgba(0, 0, 0, 0.9);
            padding: 3vw;
            width: 40vw;
            max-width: 400px;
            border-radius: 15px;
            border: 2px solid #00d9ff; /* Reduced border thickness */
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.5); /* Reduced glow */
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        h2 {
            font-size: 2.5rem;
            text-shadow: 0 0 5px #00d9ff; /* Reduced text shadow */
        }

        h3, p {
            font-size: 1.2rem;
            margin: 10px 0;
        }

        .leaderboard-link, .refresh-button {
            padding: 10px 20px;
            background: #002244;
            color: #00d9ff;
            border: 2px solid #00d9ff;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            text-shadow: 0 0 3px #00d9ff; /* Reduced text shadow */
            text-decoration: none;
            display: inline-block;
        }

        .leaderboard-link:hover, .refresh-button:hover {
            background: #004466;
            transform: scale(1.05);
            box-shadow: 0 0 10px #00d9ff; /* Reduced glow */
        }
    </style>
</head>
<body>
<div class="glow-effect"></div>
    <div class="result-container">
        <h2>Quiz Results</h2>
        <h3><?php echo $message; ?></h3>
        <p>Your Score: <?php echo $score; ?>%</p>
        <p>Teammate: <?php echo $teammate_name; ?></p>
        <p>Teammate Score: <?php echo $teammate_score; ?>%</p>
        <p>Average Score: <?php echo $average_score; ?>%</p>
        
        <?php {?>
            <a class="refresh-button" href="results.php">Refresh</a>

            <a class="leaderboard-link" href="leaderboard.php">View Leaderboard</a>
        <?php } ?>
    </div>
</body>
</html>