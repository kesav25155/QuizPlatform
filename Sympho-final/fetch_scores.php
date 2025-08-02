<?php
require 'db_config.php';

$query = "
    SELECT t.team_name as team_names, COALESCE(ts.avg_score, 0) as avg_score 
    FROM team_scores ts
    JOIN teams t ON ts.team_id = t.id
    ORDER BY ts.avg_score DESC;
";
$result = pg_query($conn, $query);
$teams = pg_fetch_all($result);

header('Content-Type: application/json');
echo json_encode(array_map(function($team) {
    return [
        "name" => htmlspecialchars($team['team_names']),
        "score" => floatval($team['avg_score'])
    ];
}, $teams));

?>
