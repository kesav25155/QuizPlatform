<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: index.php");
    exit();
}
require 'db_config.php';
$query = "
    SELECT t.team_name as team_names, COALESCE(ts.avg_score, 0) as avg_score 
    FROM team_scores ts
    JOIN teams t ON ts.team_id = t.id
    ORDER BY ts.avg_score DESC;
";
$result = pg_query($conn, $query);
$teams = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - WebTechExpo</title>
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

    .leaderboard {
        background: rgba(0, 0, 0, 0.9);
        padding: 2vw;
        width: 50vw;
        height: 75vh;
        max-width: 90%;
        border-radius: 15px;
        border: 3px solid #00d9ff;
        box-shadow: 0 0 20px rgba(0, 217, 255, 0.8);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    h2 {
        font-size: 3vw;
        text-shadow: 0 0 10px #00d9ff;
        margin-bottom: 2vh;
    }

    #podium-container {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        width: 100%;
        height: 30vh;
        margin-bottom: 2vh;
    }

    .podium {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 30%;
        text-align: center;
        font-size: 1.5vw;
        font-weight: bold;
        padding: 1vh;
        border-radius: 10px;
        text-shadow: 0 0 10px #00d9ff;
        color: black;
    }

    .first { background: gold; height: 15vh; }
    .second { background: silver; height: 12vh; }
    .third { background: #cd7f32; height: 10vh; }

    .podium span {
        font-size: 1.2vw;
        margin-top: 5px;
        color: black;
        font-weight: bold;
    }

    .leaderboard ul {
        list-style: none;
        padding: 0;
        width: 100%;
        max-height: 35vh;
        overflow-y: auto;
        scrollbar-width: none;
    }

    .leaderboard ul::-webkit-scrollbar {
        display: none;
    }

    .leaderboard li {
        font-size: 1.4vw;
        padding: 1vh;
        margin: 1vh 0;
        border-radius: 10px;
        text-shadow: 0 0 5px #00d9ff;
        transition: all 0.5s ease-in-out;
        opacity: 0;
        transform: translateY(20px);
    }

    .next-round { background: #002244; border: 2px solid #00d9ff; }
    .eliminated { background: darkred; color: white; border: 2px solid red; }
    
    .leaderboard li.animate {
        opacity: 1;
        transform: translateY(0);
    }

    .refresh-btn, .logout-btn {
        padding: 2vh 3vw;
        background: #002244;
        color: #00d9ff;
        border: 2px solid #00d9ff;
        border-radius: 10px;
        font-size: 2vw;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 2vh;
        text-shadow: 0 0 5px #00d9ff;
    }

    .refresh-btn:hover, .logout-btn:hover {
        background: #004466;
        transform: scale(1.05);
        box-shadow: 0 0 15px #00d9ff;
    }

    .button-container {
        display: flex;
        gap: 1vw;
        margin-top: 2vh;
    }
</style>

</head>
<body>
    <div class="glow-effect"></div>
    <div class="leaderboard">
        <h2>Leaderboard</h2>
        <div id="podium-container"></div>
        <ul id="leaderboard-list"></ul>
        <div class="button-container">
            <button class="refresh-btn" onclick="updateLeaderboard()" style="font-family: 'Digital7'; src: url('digital-7.regular.ttf') format('truetype');">Refresh</button>
            <button class="logout-btn" onclick="logout()" style="font-family: 'Digital7'; src: url('digital-7.regular.ttf') format('truetype');">Back</button>
        </div>
    </div>

    <script>
    let players = <?php echo json_encode(array_map(function($team) {
        return [
            "name" => htmlspecialchars($team['team_names']),
            "score" => floatval($team['avg_score'])
        ];
    }, $teams)); ?>;

function updateLeaderboard() {
    fetch('fetch_scores.php')
    .then(response => response.json())
    .then(data => {
        players = data;
        players.sort((a, b) => b.score - a.score);

        const podiumContainer = document.getElementById("podium-container");
        podiumContainer.innerHTML = "";

        const leaderboardList = document.getElementById("leaderboard-list");
        leaderboardList.innerHTML = "";

        if (players.length < 3) {
            podiumContainer.innerHTML = `<p class="warning">At least 3 teams are required to display the leaderboard.</p>`;
            return; // Stop execution if there aren't enough players
        }

        const secondPlace = document.createElement("div");
        secondPlace.className = "podium second";
        secondPlace.id = "second-place";
        secondPlace.innerHTML = `${players[1].name}<br><span>${players[1].score} pts</span>`;

        const firstPlace = document.createElement("div");
        firstPlace.className = "podium first";
        firstPlace.id = "first-place";
        firstPlace.innerHTML = `${players[0].name}<br><span>${players[0].score} pts</span>`;

        const thirdPlace = document.createElement("div");
        thirdPlace.className = "podium third";
        thirdPlace.id = "third-place";
        thirdPlace.innerHTML = `${players[2].name}<br><span>${players[2].score} pts</span>`;

        podiumContainer.appendChild(secondPlace);
        podiumContainer.appendChild(firstPlace);
        podiumContainer.appendChild(thirdPlace);

        players.slice(3).forEach((player, index) => {
            let listItem = document.createElement("li");
            listItem.textContent = `#${index + 4}# ${player.name} - ${player.score} pts`;
            listItem.classList.add(index < 7 ? "next-round" : "eliminated");
            leaderboardList.appendChild(listItem);
            setTimeout(() => {
                listItem.classList.add("animate");
            }, index * 200);
        });
    })
    .catch(error => console.error('Error fetching leaderboard data:', error));
}

function logout() {
        window.location.href = 'admin.php';
    }

window.onload = function() {
        updateLeaderboard();
    };
    console.log("Players Data:", players);
    </script>
</body>
</html>
