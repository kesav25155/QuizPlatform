<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

function generatePassword($length = 6) {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, $length);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_team'])) {
    $team_name = $_POST['team_name'];
    $member1 = $_POST['member1'];
    $email1 = $_POST['email1'];
    $phone1 = $_POST['phone1'];
    
    $member2 = $_POST['member2'];
    $email2 = $_POST['email2'];
    $phone2 = $_POST['phone2'];

    $college_name = $_POST['college_name']; // College name input

    $password = generatePassword(); // Generate a random 6-character password

    // Insert into teams table (without college_name)
    $query = "INSERT INTO teams (team_name, password) VALUES ($1, $2) RETURNING id";
    $result = pg_query_params($conn, $query, array($team_name, $password));

    if ($result) {
        $team_id = pg_fetch_result($result, 0, 'id');

        // Insert into users table (including college_name for both users)
        $query_users = "INSERT INTO users (team_id, name, email, phone, college_name) VALUES 
                        ($1, $2, $3, $4, $5), 
                        ($1, $6, $7, $8, $5)";
        pg_query_params($conn, $query_users, array($team_id, $member1, $email1, $phone1, $college_name, $member2, $email2, $phone2));

        echo "<script>alert('Team \"$team_name\" added successfully! Password: $password');</script>";
    } else {
        echo "<script>alert('Error adding team. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WebTechExpo</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');
    * {
        font-family: 'Orbitron', sans-serif;
    }

    .content {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        height: auto;
        width: 100%;
        padding: 5px; /* Reduced padding */
        box-sizing: border-box;
        margin-top: 0;
        gap: 0;
    }

    .dashboard-container {
        background: rgba(0, 0, 0, 0.9);
        padding: 15px; /* Reduced padding */
        width: 89%;
        max-width: 450px; /* Slightly smaller max-width */
        min-height: auto;
        border-radius: 15px;
        border: 0 solid #00d9ff;
        box-shadow: 0 0 25px rgba(0, 217, 255, 0.8);
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin-bottom: 0;
    }

    h3 {
        font-size: 1.8rem; /* Slightly smaller font size */
        text-shadow: 0 0 10px #00d9ff;
        margin-top: 0;
        margin-bottom: 8px; /* Reduced margin */
    }

    .dashboard-container form {
        display: flex;
        flex-direction: column;
        text-align: center;
        align-items: center;
        gap: 0px; /* Reduced gap between form elements */
    }

    .dashboard-container input {
        width: 90%;
        padding: 6px; /* Reduced padding for smaller height */
        background: transparent;
        border: 1px solid #00d9ff;
        border-radius: 8px; /* Slightly smaller border radius */
        color: #00d9ff;
        font-size: 0.9rem; /* Slightly smaller font size */
        outline: none;
        text-align: center;
        box-shadow: 0 0 10px rgba(0, 217, 255, 0.5); /* Reduced shadow */
        margin-bottom: 0px; /* Reduced margin */
        transition: 0.3s;
    }

    .dashboard-container button {
        padding: 6px 8px; /* Reduced padding for smaller height */
        background: #002244;
        color: #00d9ff;
        border: 2px solid #00d9ff;
        border-radius: 8px; /* Slightly smaller border radius */
        font-size: 0.9rem; /* Slightly smaller font size */
        cursor: pointer;
        transition: 0.3s;
        text-shadow: 0 0 5px #00d9ff;
        width: 95%;
    }

    .dashboard-container input:focus {
        background: rgba(0, 217, 255, 0.1);
        transform: scale(1.03); /* Slightly smaller scale */
    }

    .dashboard-container button:hover {
        background: #004466;
        transform: scale(1.03); /* Slightly smaller scale */
        box-shadow: 0 0 15px #00d9ff; /* Reduced shadow */
    }
</style>
   
</head>
<body>
    
<div class="content">
    <div class="dashboard-container">
        <h3>Add a team</h3>
        <form method="post">
            <input type="text" name="team_name" placeholder="Enter Team Name" required autocomplete="off"><br>
            <input type="text" name="college_name" placeholder="College Name" required autocomplete="off"><br> 
            <input type="text" name="member1" placeholder="Teammate 1 Name" required autocomplete="off"><br>
            <input type="email" name="email1" placeholder="Teammate 1 Email" required autocomplete="off"><br>
            <input type="text" name="phone1" placeholder="Teammate 1 Phone No" required autocomplete="off"><br>
            <input type="text" name="member2" placeholder="Teammate 2 Name" required autocomplete="off"><br>
            <input type="email" name="email2" placeholder="Teammate 2 Email" required autocomplete="off"><br>
            <input type="text" name="phone2" placeholder="Teammate 2 Phone No" required autocomplete="off"><br>
            <button type="submit" name="add_team">Add Team</button>
        </form>
    </div>
</div>
</body>
</html>
