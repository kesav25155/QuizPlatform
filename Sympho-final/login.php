<?php
session_start();

// If the user is already logged in, redirect to quiz.php
if (isset($_SESSION['team_id']) && isset($_SESSION['user_name'])) {
    // Check the score of the logged-in user
    $team_id = $_SESSION['team_id'];
    $user_name = $_SESSION['user_name'];

    // Query to get the user's score
    require 'db_config.php';
    $query_score = "SELECT score FROM users WHERE team_id = $1 AND name = $2";
    $score_result = pg_query_params($conn, $query_score, array($team_id, $user_name));

    if (pg_num_rows($score_result) > 0) {
        $score_data = pg_fetch_assoc($score_result);
        $score = $score_data['score'];

        // Redirect logic based on score
        if (is_null($score)) { 
            header("Location: quiz.php");
            exit();
        } else { 
            header("Location: leaderboard.php");
            exit();
        }
    }
}

require 'db_config.php';

$query = "SELECT id, team_name FROM teams";
$result = pg_query($conn, $query);
$teams = pg_fetch_all($result);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['team_login'])) {
    $team_id = $_POST['team_id'];
    $user_name = $_POST['user_name'];
    $password = $_POST['password'];

    $query = "SELECT * FROM teams WHERE id = $1 AND password = $2";
    $result = pg_query_params($conn, $query, array($team_id, $password));

    if (pg_num_rows($result) > 0) {
        $team_data = pg_fetch_assoc($result);
        $team_name = $team_data['team_name'];

        $query_user = "SELECT * FROM users WHERE team_id = $1 AND name = $2 AND is_logged_in = FALSE";
        $user_result = pg_query_params($conn, $query_user, array($team_id, $user_name));

        if (pg_num_rows($user_result) > 0) {
            $_SESSION['team_id'] = $team_id;
            $_SESSION['user_name'] = $user_name;
            $_SESSION['team'] = $team_name;
            $_SESSION['team_name'] = $team_data; 

            $update_query = "UPDATE users SET is_logged_in = TRUE WHERE team_id = $1 AND name = $2";
            pg_query_params($conn, $update_query, array($team_id, $user_name));

            // Check the score after login
            $query_score = "SELECT score FROM users WHERE team_id = $1 AND name = $2";
            $score_result = pg_query_params($conn, $query_score, array($team_id, $user_name));

            if (pg_num_rows($score_result) > 0) {
                $score_data = pg_fetch_assoc($score_result);
                $score = $score_data['score'];

                // Redirect based on the score
                if (is_null($score)) { 
                    header("Location: quiz.php");
                    exit();
                } else { 
                    header("Location: leaderboard.php");
                    exit();
                }
            }
        } else {
            $error = "User already logged in. Please logout from the other session.";
        }
    } else {
        $error = "Invalid Team or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Login - WebTechXpo</title>
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
        
        .login-container {
            background: rgba(0, 0, 0, 0.9);
            padding: 3vw;
            width: 40vw;
            max-width: 400px;
            border-radius: 15px;
            border: 2px solid #00d9ff;
            box-shadow: 0 0 15px rgba(0, 217, 255, 0.5);
            text-align: center;
        }
        
        h2 {
            font-size: 2.5rem;
            text-shadow: 0 0 5px #00d9ff;
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
            box-shadow: 0 0 8px rgba(0, 217, 255, 0.3);
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
            text-shadow: 0 0 3px #00d9ff;
        }

        .btn:hover {
            background: #004466;
            transform: scale(1.05);
            box-shadow: 0 0 15px #00d9ff;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #111;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #00d9ff;
            border-radius: 10px;
            box-shadow: 0 0 5px rgba(0, 217, 255, 0.3);
        } 
        
        ::-webkit-scrollbar-thumb:hover {
            background: #00d9ff;
        }

        .autocomplete-container {
            position: relative;
            width: 100%;
        }

        .autocomplete-list {
            display: none; /* Hide autocomplete lists by default */
            position: absolute;
            width: 100%;
            background: #111;
            border: 1px solid #00d9ff;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0, 217, 255, 0.3);
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
        }
        .autocomplete-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #00d9ff;
            color: #00d9ff;
        }
        .autocomplete-item:hover {
            background: #004466;
        }
    </style>
</head>
<body>
<div class="glow-effect"></div>
    <div class="login-container">
        <h2>Team Login</h2>
        <?php if(isset($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form method="post">
            <!-- Team Selection -->
            <div class="autocomplete-container">
                <input type="text" id="team_name" name="team_name" placeholder="Enter Team Name" class="input-box" required autocomplete="off">
                <div id="autocomplete-list" class="autocomplete-list"></div>
            </div>
            
            <!-- Hidden Field for Team ID -->
            <input type="hidden" id="team_id" name="team_id">

            <!-- User Selection -->
            <div class="autocomplete-container">
                <input type="text" id="user_name" name="user_name" placeholder="Enter Your Name" class="input-box" required autocomplete="off">
                <div id="user-autocomplete-list" class="autocomplete-list"></div>
            </div>

            <input type="password" name="password" placeholder="Enter Password" class="input-box" required><br>
            <button type="submit" name="team_login" class="btn">Login</button>
        </form>
    </div>
    <script>
        const teamsData = <?php echo json_encode($teams); ?>;
        const teamInput = document.getElementById('team_name');
        const teamIdInput = document.getElementById('team_id');
        const autocompleteList = document.getElementById('autocomplete-list');

        const userInput = document.getElementById('user_name');
        const userAutocompleteList = document.getElementById('user-autocomplete-list');

        // Handle team input autocomplete
        teamInput.addEventListener('input', function () {
            let input = this.value.toLowerCase();
            autocompleteList.innerHTML = '';
            userAutocompleteList.style.display = 'none'; // Hide user list when team input is active

            if (!input) {
                autocompleteList.style.display = 'none'; // Hide team autocomplete if input is empty
                return;
            }

            teamsData.filter(team => team.team_name.toLowerCase().includes(input)).forEach(team => {
                let item = document.createElement('div');
                item.classList.add('autocomplete-item');
                item.textContent = team.team_name;
                item.onclick = function () {
                    teamInput.value = team.team_name;
                    teamIdInput.value = team.id; // Set hidden team_id
                    autocompleteList.innerHTML = '';
                    autocompleteList.style.display = 'none'; // Hide team autocomplete after selection
                    fetchUsers(team.id);
                };
                autocompleteList.appendChild(item);
            });

            autocompleteList.style.display = 'block'; // Show team autocomplete when there is input
        });

        // Fetch users based on selected team
        function fetchUsers(teamId) {
            fetch(`fetch_users.php?team_id=${encodeURIComponent(teamId)}`)
                .then(response => response.json())
                .then(users => {
                    userAutocompleteList.innerHTML = '';
                    userInput.value = '';
                    userAutocompleteList.style.display = users.length ? 'block' : 'none'; // Show user autocomplete if users exist

                    users.forEach(user => {
                        let item = document.createElement('div');
                        item.classList.add('autocomplete-item');
                        item.textContent = user;
                        item.onclick = function () {
                            userInput.value = user;
                            userAutocompleteList.innerHTML = '';
                            userAutocompleteList.style.display = 'none'; // Hide user autocomplete after selection
                        };
                        userAutocompleteList.appendChild(item);
                    });
                });
        }

        // Handle user input autocomplete
        userInput.addEventListener('input', function () {
            let input = this.value.toLowerCase();
            userAutocompleteList.innerHTML = '';

            if (!input) {
                userAutocompleteList.style.display = 'none'; // Hide user autocomplete if input is empty
                return;
            }

            // Filter and display user autocomplete items
            const userItems = Array.from(userAutocompleteList.querySelectorAll('.autocomplete-item'));
            userItems.forEach(item => {
                if (item.textContent.toLowerCase().includes(input)) {
                    userAutocompleteList.style.display = 'block'; // Show user autocomplete when there is input
                }
            });
        });

        // Hide lists when clicking outside
        document.addEventListener('click', function (event) {
            if (!teamInput.contains(event.target) && !autocompleteList.contains(event.target)) {
                autocompleteList.style.display = 'none';
            }
            if (!userInput.contains(event.target) && !userAutocompleteList.contains(event.target)) {
                userAutocompleteList.style.display = 'none';
            }
        });
    </script>
</body>
</html>