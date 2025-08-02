<?php
include("db_config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update"])) {
        // Update student details
        $student_id = $_POST["student_id"];
        $student_name = $_POST["student_name"];
        $college_name = $_POST["college_name"]; // Updated from roll_no to college_name
        $phone = $_POST["phone"];
        $email = $_POST["email"];

        $query_update = "UPDATE users SET name = $1, college_name = $2, phone = $3, email = $4 WHERE id = $5";
        $result_update = pg_query_params($conn, $query_update, array($student_name, $college_name, $phone, $email, $student_id));

        if ($result_update) {
            echo "Student details updated successfully.";
        } else {
            echo "Error updating student details.";
        }
        exit;
    }

    if (isset($_POST['team_name'])) {
        $team_name = $_POST['team_name'];

        // Get team_id and password based on team_name
        $query_team = "SELECT id, password FROM teams WHERE team_name = $1";
        $result_team = pg_query_params($conn, $query_team, array($team_name));

        if (!$result_team) {
            echo "<tr><td colspan='6'>Error fetching team details.</td></tr>";
            exit;
        }

        $team_data = pg_fetch_assoc($result_team);
        if (!$team_data) {
            echo "<tr><td colspan='6'>No students found for this team.</td></tr>";
            exit;
        }

        $team_id = $team_data['id']; // Fetch the team_id
        $team_password = $team_data['password']; // Fetch the password from teams table

        // Fetch students based on team_id
        $query_students = "SELECT id, name, COALESCE(college_name::TEXT, '') AS college_name, COALESCE(phone::TEXT, '') AS phone, COALESCE(email, '') AS email FROM users WHERE team_id = $1";
        $result_students = pg_query_params($conn, $query_students, array($team_id));

        if (!$result_students) {
            echo "<tr><td colspan='6'>Error fetching students.</td></tr>";
            exit;
        }

        while ($row = pg_fetch_assoc($result_students)) {
            // Ensuring no undefined array key errors
            $college_name = isset($row['college_name']) ? $row['college_name'] : '';
            $phone = isset($row['phone']) ? $row['phone'] : '';
            $email = isset($row['email']) ? $row['email'] : '';
            echo "<tr data-id='{$row['id']}' style='background: #002244; color: #00d9ff; text-align: center;'>
                <td><input type='text' class='student-name editable' value='{$row['name']}' readonly style='background: transparent; color: #00d9ff; border: 2px solid #00d9ff; text-align: center; height: 30px; width: 90%; padding: 5px; box-shadow: 0 0 10px rgba(0, 217, 255, 0.5);'></td>
                <td><input type='text' class='college-name editable' value='{$college_name}' readonly style='background: transparent; color: #00d9ff; border: 2px solid #00d9ff; text-align: center; height: 30px; width: 90%; padding: 5px; box-shadow: 0 0 10px rgba(0, 217, 255, 0.5);'></td>
                <td><input type='text' class='phone editable' value='{$phone}' readonly style='background: transparent; color: #00d9ff; border: 2px solid #00d9ff; text-align: center; height: 30px; width: 90%; padding: 5px; box-shadow: 0 0 10px rgba(0, 217, 255, 0.5);'></td>
                <td><input type='text' class='email editable' value='{$email}' readonly style='background: transparent; color: #00d9ff; border: 2px solid #00d9ff; text-align: center; height: 30px; width: 90%; padding: 5px; box-shadow: 0 0 10px rgba(0, 217, 255, 0.5);'></td>
                <td><input type='text' class='password' value='{$team_password}' readonly style='background: transparent; color: #00d9ff; border: 2px solid #00d9ff; text-align: center; height: 30px; width: 90%; padding: 5px; box-shadow: 0 0 10px rgba(0, 217, 255, 0.5);'></td>
                <td>
                    <button class='edit-button' onclick='editRow(this)' style='background: #002244; color: #00d9ff; border: 2px solid #00d9ff; padding: 5px 10px; cursor: pointer; text-shadow: 0 0 5px #00d9ff; transition: 0.3s;'>Edit</button>
                    <button class='save-button' onclick='saveRow(this)' style='background: #002244; color: #00d9ff; border: 2px solid #00d9ff; padding: 5px 10px; cursor: pointer; text-shadow: 0 0 5px #00d9ff; transition: 0.3s; display: none;'>Save</button>
                </td>
            </tr>";
        }
    }
}
?>
<script>
function editRow(button) {
    var row = button.closest("tr");
    row.querySelectorAll(".editable").forEach(input => input.removeAttribute("readonly"));
    row.querySelector(".edit-button").style.display = "none";
    row.querySelector(".save-button").style.display = "inline-block";
}

function saveRow(button) {
    var row = button.closest("tr");
    var studentId = row.getAttribute("data-id");
    var studentName = row.querySelector(".student-name").value;
    var collegeName = row.querySelector(".college-name").value; // Updated from roll-no to college-name
    var phone = row.querySelector(".phone").value;
    var email = row.querySelector(".email").value;

    var formData = new FormData();
    formData.append("update", true);
    formData.append("student_id", studentId);
    formData.append("student_name", studentName);
    formData.append("college_name", collegeName); // Updated from roll_no to college_name
    formData.append("phone", phone);
    formData.append("email", email);

    fetch("fetch_team_details.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => {
        alert(result);
        row.querySelectorAll(".editable").forEach(input => input.setAttribute("readonly", true));
        row.querySelector(".edit-button").style.display = "inline-block";
        row.querySelector(".save-button").style.display = "none";
    })
    .catch(error => console.error("Error:", error));
}
</script>
