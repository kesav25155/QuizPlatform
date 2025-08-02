<?php
require 'db_config.php';

if (isset($_GET['team_id'])) {
    $team_id = $_GET['team_id'];

    $user_query = "SELECT name FROM users WHERE team_id = $1 AND is_logged_in = FALSE";
    $user_result = pg_query_params($conn, $user_query, array($team_id));

    $users = pg_fetch_all_columns($user_result);
    echo json_encode($users);
} else {
    echo json_encode([]);
}
?>
