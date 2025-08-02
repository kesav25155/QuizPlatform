<?php
include("db_config.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - WebTechExpo</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="glow-effect"></div>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="add_team.php" class="menu-item  active">Add Team</a>
        <a href="view_teams.php" class="menu-item">View Teams</a>
        <a href="admin_leaderboard.php" class="menu-item">Leaderboard</a>
        <a href="admin_logout.php" class="menu-item">Logout</a>
    </div>
    <div class="main-content">
        <h3>Admin Dashboard</h3>
        <?php include("add_teams.php"); ?>
    </div>
</body>
</html>
