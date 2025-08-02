<?php
include("db_config.php");

if (isset($_POST['query'])) {
    $search = pg_escape_string($conn, $_POST['query']);
    $query = "SELECT DISTINCT team_name FROM teams WHERE team_name ILIKE '%$search%'";
    $result = pg_query($conn, $query);

    if (pg_num_rows($result) > 0) {
        while ($row = pg_fetch_assoc($result)) {
            echo "<div class='suggestion-item'>{$row['team_name']}</div>";
        }
    } else {
        echo "<div class='suggestion-item'>No teams found</div>";
    }
}
?>
