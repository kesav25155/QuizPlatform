<?php
session_start();

// Check if the user is logged in
if (isset($_SESSION['team_id']) && isset($_SESSION['user_name'])) {
    require 'db_config.php';

    $team_id = $_SESSION['team_id'];
    $user_name = $_SESSION['user_name'];

    // Set is_logged_in to false for the user
    $update_query = "UPDATE users SET is_logged_in = FALSE WHERE team_id = $1 AND name = $2";
    pg_query_params($conn, $update_query, array($team_id, $user_name));
    
    // Destroy the session
    session_destroy();
    
    // Redirect to a different page
    header("Location: redirect.php");
    exit();
} else {
    // If no user is logged in, redirect to the login page
    header("Location: login.php");
    exit();
}
?>
