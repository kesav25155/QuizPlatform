<?php
session_start();
require 'db_config.php';
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Teams - WebTechExpo</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');

    * {
        font-family: 'Orbitron', sans-serif;
    }

    .main-content {
        width: 80%;
        max-width: 900px;
        height: 85vh;
        margin: auto;
        position: absolute;
        top: 50%;
        left: calc(50% + 120px);
        transform: translate(-50%, -50%);
        overflow-x: auto;
        padding: 20px;
        background: rgba(0, 0, 0, 0.9);
        border-radius: 10px;
        border: 3px solid #00d9ff;
        box-shadow: 0 0 15px rgba(0, 217, 255, 0.5); /* Reduced glow */
    }

    .search-container {
        text-align: center;
        margin-bottom: 20px;
    }

    #team-search {
        width: 50%;
        padding: 10px;
        border: 2px solid #00d9ff;
        border-radius: 5px;
        background: transparent;
        color: #00d9ff;
        text-align: center;
        box-shadow: 0 0 8px rgba(0, 217, 255, 0.4); /* Reduced glow */
    }

    .dropdown {
        width: 50%;
        margin: 0 auto;
        position: relative;
    }

    .dropdown-content {
        position: absolute;
        width: 100%;
        background: rgba(0, 0, 0, 0.9);
        border: 2px solid #00d9ff;
        max-height: 200px;
        overflow-y: auto;
        display: none;
        box-shadow: 0 0 8px rgba(0, 217, 255, 0.4); /* Reduced glow */
    }

    .dropdown-content div {
        padding: 10px;
        cursor: pointer;
        color: #00d9ff;
    }

    .dropdown-content div:hover {
        background: rgba(0, 217, 255, 0.1);
    }

    .team-list {
        text-align: center;
        margin-bottom: 20px;
    }

    .team-button {
        background: #002244;
        color: #00d9ff;
        border: 2px solid #00d9ff;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        text-shadow: 0 0 5px #00d9ff;
        margin: 5px;
    }

    .team-button:hover {
        background: #004466;
        transform: scale(1.07);
        box-shadow: 0 0 10px #00d9ff; /* Reduced glow */
    }

    .team-details {
        overflow-x: auto;
        text-align: center;
        margin-top: 20px;
    }

    #students-table {
        width: 100%;
        border-collapse: collapse;
        border: 2px solid #00d9ff;
        box-shadow: 0 0 8px rgba(0, 217, 255, 0.4); /* Reduced glow */
        background: rgba(0, 0, 0, 0.9);
        table-layout: fixed;
        margin-top: 10px;
    }

    #students-table th, #students-table td {
        border: 1px solid #00d9ff;
        padding: 10px;
        text-align: center;
        color: #00d9ff;
    }

    #students-table th {
        background: #002244;
        text-shadow: 0 0 5px #00d9ff;
        font-size: 1.2rem;
    }

    #students-table tr:nth-child(even) {
        background: rgba(0, 34, 68, 0.5);
    }

    #students-table tr:hover {
        background: rgba(0, 68, 102, 0.5);
        transition: 0.3s ease-in-out;
    }

    .action-button {
        padding: 8px 15px;
        background: #002244;
        color: #00d9ff;
        border: 2px solid #00d9ff;
        border-radius: 5px;
        cursor: pointer;
        transition: 0.3s;
        text-shadow: 0 0 5px #00d9ff;
        display: inline-block;
        margin: 5px;
    }

    .action-button:hover {
        background: #004466;
        transform: scale(1.05);
        box-shadow: 0 0 8px #00d9ff; /* Reduced glow */
    }

    .glow-effect {
        position: absolute;
        width: 100vw;
        height: 100vh;
        background: radial-gradient(circle, rgba(0, 217, 255, 0.36) 10%, transparent 10.01%);
        background-size: 40px 40px;
        z-index: -1;
    }

    .dropdown-content div {
        font-family: 'Digital7', sans-serif;
    }
</style>
</head>
<body>
<div class="glow-effect"></div>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="admin.php" class="menu-item">Add Team</a>
        <a href="view_teams.php" class="menu-item active">View Teams</a>
        <a href="admin_leaderboard.php" class="menu-item">Leaderboard</a>
        <a href="admin_logout.php" class="menu-item">Logout</a>
    </div>

    <div class="main-content" >
        <h1>View Teams</h1>

        <div class="search-container">
            <input type="text" id="team-search" placeholder="Search for a team...">
            <div class="dropdown">
                <div class="dropdown-content" id="team-dropdown"></div>
            </div>
        </div>

        <div class="team-details">
            <h2>Team Details</h2>
            <table id="students-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>College Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="students-data" >
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $("#team-search").keyup(function() {
                var query = $(this).val();
                if (query.length > 0) {
                    $.ajax({
                        url: "search_teams.php",
                        method: "POST",
                        data: { query: query },
                        success: function(response) {
                            $("#team-dropdown").html(response).show();
                        }
                    });
                } else {
                    $("#team-dropdown").hide();
                }
            });

            $(document).on("click", ".dropdown-content div", function() {
                var teamName = $(this).text();
                $("#team-search").val(teamName);
                $("#team-dropdown").hide();
                
                $.ajax({
                    url: "fetch_team_details.php",
                    method: "POST",
                    data: { team_name: teamName },
                    success: function(response) {
                        $("#students-data").html(response);
                    }
                });
            });
            
        });
    </script>
</body>
</html>