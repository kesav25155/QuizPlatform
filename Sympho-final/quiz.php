<?php
session_start();
require 'db_config.php';


// Check if the quiz has already been attempted
if (isset($_SESSION['quiz_completed']) && $_SESSION['quiz_completed'] === true) {
    header("Location: leaderboard.php");
    exit();
}
// Ensure user is logged in
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

// Fetch user details from the database
$user_name = $_SESSION['user_name'];
$query_user = "SELECT * FROM users WHERE name = $1";
$result_user = pg_query_params($conn, $query_user, [$user_name]);
$user = pg_fetch_assoc($result_user);

if (!$user) {
    echo "User not found.";
    exit();
}
// Check if the quiz has already been attempted (Persistent check using DB)
$query_check_attempt = "SELECT score FROM users WHERE name = $1";
$result_check_attempt = pg_query_params($conn, $query_check_attempt, array($user_name));
$row_check_attempt = pg_fetch_assoc($result_check_attempt);

if ($row_check_attempt && $row_check_attempt['score'] !== null) {
    // If a score exists, the quiz was already completed
    $_SESSION['quiz_completed'] = true;
    header("Location: leaderboard.php");
    exit();
}

// Initialize session variables if not set
if (!isset($_SESSION['fullscreen_enabled'])) {
    $_SESSION['fullscreen_enabled'] = false;
}
if (!isset($_SESSION['tab_switch_count'])) {
    $_SESSION['tab_switch_count'] = 0;
}
if (!isset($_SESSION['current_question'])) {
    $_SESSION['current_question'] = 0;
}
if (!isset($_SESSION['score'])) {
    $_SESSION['score'] = 0;
}
if (!isset($_SESSION['hints_used'])) {
    $_SESSION['hints_used'] = 0;
}
if (!isset($_SESSION['time_left'])) {
    $_SESSION['time_left'] = 30; // 30 minutes in seconds
}
if (!isset($_SESSION['answers'])) {
    $_SESSION['answers'] = array(); // Store user answers
}
if (!isset($_SESSION['connection_hints_used'])) {
    $_SESSION['connection_hints_used'] = 0;
}
if (!isset($_SESSION['5050_hints_used'])) {
    $_SESSION['5050_hints_used'] = 0;
}
// Fetch questions from the database
$query = "SELECT * FROM questions 
          ORDER BY CASE 
              WHEN difficulty = 'easy' THEN 1
              WHEN difficulty = 'medium' THEN 2
              WHEN difficulty = 'hard' THEN 3
          END";

$result = pg_query($conn, $query);
$questions = pg_fetch_all($result);
$total_questions = $questions ? count($questions) : 0;

// Store total questions in session
$_SESSION['total_questions'] = $total_questions;

// Handle navigation and hints
$current_question = $_SESSION['current_question'];
$question = $total_questions > 0 ? $questions[$current_question] : null;

if (isset($_POST['next']) && $current_question < $total_questions - 1) {
    // Save the selected answer
    if (isset($_POST['answer'])) {
        $_SESSION['answers'][$current_question] = $_POST['answer'];
    }
    $_SESSION['current_question']++; // Move to the next question
} elseif (isset($_POST['prev']) && $current_question > 0) {
    $_SESSION['current_question']--; // Move to the previous question
} elseif (isset($_POST['submit'])) {
    // Save the selected answer for the last question
    if (isset($_POST['answer'])) {
        $_SESSION['answers'][$current_question] = $_POST['answer'];
    }

    // Calculate score on final submit
    $_SESSION['score'] = 0;
    foreach ($_SESSION['answers'] as $index => $selected_answer) {
        if (isset($questions[$index])) { // Ensure the question exists
            $correct_answer = $questions[$index]['correct_option'];
            $difficulty = $questions[$index]['difficulty'];
            if ($selected_answer == $correct_answer) {
                if ($difficulty == 'easy') {
                    $_SESSION['score'] += 2;
                } elseif ($difficulty == 'medium') {
                    $_SESSION['score'] += 4;
                } elseif ($difficulty == 'hard') {
                    $_SESSION['score'] += 8;
                }
            }
        }
    }
    header("Location: submission_processing.php");
    exit();
} elseif (isset($_POST['hint']) && $_SESSION['hints_used'] < 5) {
    $_SESSION['hints_used']++;
    $_SESSION['time_left'] -= 60; // Deduct 1 minute
    $hint_text = $questions[$current_question]['hint'] ?? "No hint available.";
}

// Handle "Proceed to Test" button click
if (isset($_POST['proceed'])) {
    $_SESSION['fullscreen_enabled'] = true;
    $_SESSION['timer_started'] = true; // Start the timer
}

$current_question = $_SESSION['current_question'];
$question = $total_questions > 0 ? $questions[$current_question] : null;
$selected_answer = $_SESSION['answers'][$current_question] ?? '';

// Handle "Submit All" button
if (isset($_POST['submit_all'])) {
    // Save the selected answer for the current question
    if (isset($_POST['answer'])) {
        $_SESSION['answers'][$current_question] = $_POST['answer'];
    }

    // Calculate score on final submit
    $_SESSION['score'] = 0;
    foreach ($_SESSION['answers'] as $index => $selected_answer) {
        if (isset($questions[$index])) { // Ensure the question exists
            $correct_answer = $questions[$index]['correct_option'];
            $difficulty = $questions[$index]['difficulty'];
            if ($selected_answer == $correct_answer) {
                if ($difficulty == 'easy') {
                    $_SESSION['score'] += 2;
                } elseif ($difficulty == 'medium') {
                    $_SESSION['score'] += 4;
                } elseif ($difficulty == 'hard') {
                    $_SESSION['score'] += 8;
                }
            }
        }
    }
    // Redirect to submission processing page
    header("Location: submission_processing.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Quiz - WebTechExpo</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let timerInterval;
            let timeLeft = <?php echo $_SESSION['time_left']; ?>;
            localStorage.setItem("tabSwitchCount", 0);

            function enterFullScreen() {
                let elem = document.documentElement;
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.mozRequestFullScreen) {
                    elem.mozRequestFullScreen();
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) {
                    elem.msRequestFullscreen();
                }
            }

            function checkFullScreen() {
                if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
                    document.getElementById('fullscreen-warning').style.display = 'block';
                }
            }



            document.getElementById('proceed-btn').addEventListener('click', function (event) {
                event.preventDefault();
                enterFullScreen();
                document.getElementById('warnings').style.display = 'none';
                document.getElementById('quiz-content').style.display = 'block';
                startTimer();
            });

            document.addEventListener("contextmenu", function (event) { event.preventDefault(); });

            document.addEventListener("keydown", function (event) {
                if ((event.ctrlKey && [67, 88, 85, 80, 83, 73].includes(event.keyCode)) || event.keyCode === 123) {
                    event.preventDefault();
                    alert("Keyboard shortcuts are disabled.");
                }

                if (event.altKey) {
                    event.preventDefault();
                    alert("Alt+Tab is disabled during the test.");
                }
            });

            document.addEventListener("visibilitychange", function () {
                if (document.hidden) {
                    let tabSwitchCount = parseInt(localStorage.getItem("tabSwitchCount")) || 0;
                    tabSwitchCount++;
                    localStorage.setItem("tabSwitchCount", tabSwitchCount);

                    if (tabSwitchCount >= 2) {
                        document.getElementById("tab-warning-text").textContent = "You have switched tabs twice. Your test will be submitted now.";
                        document.getElementById("tab-switch-warning").style.display = "block";
                        setTimeout(() => { $('#quiz-form').submit(); window.location.href = "results.php"; }, 3000);
                    } else {
                        document.getElementById("tab-warning-text").textContent = "Tab switching is not allowed. This is your first warning.";
                        document.getElementById("tab-switch-warning").style.display = "block";
                    }
                }
            });

            document.getElementById('close-warning-btn').addEventListener('click', function () {
                document.getElementById('tab-switch-warning').style.display = 'none';
            });

            document.addEventListener("fullscreenchange", checkFullScreen);
            document.addEventListener("webkitfullscreenchange", checkFullScreen);
            document.addEventListener("mozfullscreenchange", checkFullScreen);
            document.addEventListener("MSFullscreenChange", checkFullScreen);

            $(document).on('click', '.nav-buttons button[name="next"], .nav-buttons button[name="prev"]', function (event) {
                event.preventDefault();
                const action = $(this).attr('name');
                const selectedAnswer = $('#selected_answer').val();

                $.ajax({
                    url: 'handle_navigation.php',
                    type: 'POST',
                    data: { action: action, answer: selectedAnswer },
                    success: function (response) { $('#quiz-content').html(response); }
                });
            });

            $(document).on('click', '.options button', function () {
                $('.options button').removeClass('selected');
                $(this).addClass('selected');
                $('#selected_answer').val($(this).val());
            });

            document.getElementById('reenter-fullscreen-btn').addEventListener('click', function () {
                enterFullScreen();
                document.getElementById('fullscreen-warning').style.display = 'none';
            });

            function showSubmitPopup(message, callback, isTimeout = false) {
    document.getElementById('submit-popup-text').textContent = message;
    document.getElementById('submit-popup').style.display = 'block';

    // Hide the "Cancel" button for timeout submissions
    if (isTimeout) {
        document.getElementById('submit-popup-cancel').style.display = 'none';
        document.getElementById('submit-popup-confirm').textContent = 'View Results'; // Change button text for timeout
    } else {
        document.getElementById('submit-popup-cancel').style.display = 'inline-block';
        document.getElementById('submit-popup-confirm').textContent = 'OK'; // Default text for Submit All
    }

    document.getElementById('submit-popup-confirm').onclick = function () {
        document.getElementById('submit-popup').style.display = 'none';
        if (callback) callback(true);
    };

    document.getElementById('submit-popup-cancel').onclick = function () {
        document.getElementById('submit-popup').style.display = 'none';
        if (callback) callback(false);
    };
}
// Handle "Submit All" button click
$(document).on('click', 'button[name="submit-all"]', function (event) {
    event.preventDefault();
    showSubmitPopup("Are you sure you want to submit all answers and complete the quiz?", function (confirmed) {
        if (confirmed) {
            $('#quiz-form').append('<input type="hidden" name="submit_all" value="1">');
            $('#quiz-form').submit();
        }
    });
});

function startTimer() {
    timerInterval = setInterval(function () {
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            // Automatically submit the quiz when time ends
            showSubmitPopup("Time's up! Your quiz has been submitted. Click 'View Results' to see your score.", function (confirmed) {
                // No need to check for confirmation, just submit
                $('#quiz-form').append('<input type="hidden" name="submit_all" value="1">');
                $('#quiz-form').submit();
            }, true); // Pass `true` to indicate timeout submission (no cancel option)
        } else {
            timeLeft--;
            $('#timer').text("Time Left: " + Math.floor(timeLeft / 60) + ":" + (timeLeft % 60 < 10 ? "0" : "") + (timeLeft % 60));
        }
    }, 1000);
}

// Function to show the hint popup
function showHintPopup() {
    document.getElementById('hint-popup').style.display = 'block';
}

// Function to hide the hint popup
function hideHintPopup() {
    document.getElementById('hint-popup').style.display = 'none';
}

// Handle "Hint" button click
$(document).on('click', 'button[name="hint"]', function (event) {
    event.preventDefault();
    showHintPopup();
});

// Handle "Close" button in the hint popup
$(document).on('click', '#close-hint-popup-btn', function (event) {
    event.preventDefault();
    hideHintPopup();
});

// Handle Connection Hint
// Handle Connection Hint
function handleConnectionHint() {
    $.ajax({
        url: 'handle_hint.php',
        type: 'POST',
        data: { action: 'connection', question_id: <?php echo $question['id']; ?> },
        success: function (response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                // Display the hint images in the popup
                document.getElementById('hint-popup-content').innerHTML = `
                    <img src="${data.hint1}" alt="Hint 1" style="max-width: 100%; height: auto;">
                    <span style="font-size: 24px; margin: 0 10px;">+</span>
                    <img src="${data.hint2}" alt="Hint 2" style="max-width: 100%; height: auto;">
                    <button id="close-hint-popup-btn">Close</button>
                `;
                document.getElementById('hint-popup').style.display = 'block';
            } else {
                alert(data.message); // Show error message
            }
        }
    });
}

// Handle "Connection" button click in the hint popup
$(document).on('click', '#hint-connection-btn', function (event) {
    event.preventDefault();
    handleConnectionHint();
});

// Function to handle the 50/50 hint
function handle5050Hint() {
    $.ajax({
        url: 'handle_hint.php',
        type: 'POST',
        data: { action: '5050', question_id: <?php echo $question['id']; ?> },
        success: function (response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                // Hide the hint popup
                hideHintPopup();

                // Disable and strike out two incorrect options
                data.disabled_options.forEach(option => {
                    const optionButton = document.querySelector(`.options button[value="${option}"]`);
                    optionButton.disabled = true;
                    optionButton.style.textDecoration = 'line-through';
                    optionButton.style.color = 'red';
                });

                // Increment hint count
                $.ajax({
                    url: 'handle_hint.php',
                    type: 'POST',
                    data: { action: 'increment_5050', question_id: <?php echo $question['id']; ?> },
                    success: function () {
                        location.reload(); // Refresh to update hint count
                    }
                });
            } else {
                // Display error message inline instead of alert
                document.getElementById('hint-popup-content').innerHTML = `
<p style="color: #ff4d4d; text-shadow: 0 0 10px #ff4d4d; font-family: 'Orbitron', sans-serif;">${data.message}</p>
<button id="close-hint-popup-btn" style="font-size: 20px; padding: 12px 24px; background: #002244; color: #00d9ff;
border: 2px solid #00d9ff; border-radius: 8px; cursor: pointer; transition: 0.3s; text-shadow: 0 0 8px #00d9ff;
box-shadow: 0 0 15px rgba(0, 217, 255, 0.6); margin-top: 20px;">Close</button>
                `;
                document.getElementById('hint-popup').style.display = 'block';
            }
        }
    });
}

// Handle "50/50" button click in the hint popup
$(document).on('click', '#hint-5050-btn', function (event) {
    event.preventDefault();
    handle5050Hint();
});

   

document.getElementById('hint-btn').textContent = `Hint (Connection: ${<?php echo 3 - $_SESSION['connection_hints_used']; ?>} left, 50/50: ${<?php echo 2 - $_SESSION['5050_hints_used']; ?>} left)`;
document.getElementById('close-warnings-btn').addEventListener('click', function () {
        document.getElementById('warnings').style.display = 'none';
    });
      });
    </script>

</head>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap');
    * {
        font-family: 'Orbitron', sans-serif;
    }
    .quiz-container {
    position: relative; /* Add this to make child elements with absolute positioning relative to this container */
}
pre {
    background-color: #2d2d2d; /* Dark background for code blocks */
    color: #f8f8f2; /* Light text color for contrast */
    padding: 15px; /* Increased padding for better spacing */
    border-radius: 8px; /* Rounded corners */
    font-family: "Courier New", monospace; /* Monospace font for code */
    font-size: 14px; /* Slightly larger font size */
    line-height: 1.5; /* Improved line spacing */
    white-space: pre-wrap; /* Preserve line breaks */
    word-wrap: break-word; /* Break long lines */
    border: 1px solid #444; /* Subtle border */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Soft shadow for depth */
    text-align: left;
    overflow-x: auto; /* Add horizontal scrollbar if content overflows */
}
.difficulty-level {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: bold;
}

.easy {
    background-color: green;
    color: white;
}

.medium {
    background-color: orange;
    color: white;
}

.hard {
    background-color: red;
    color: white;
}
.options button[disabled] {
    text-decoration: line-through;
    color: red;
    cursor: not-allowed;
}
body, * {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

body {
    font-family: 'Orbitron', sans-serif; /* Updated font */
    background: #000;
    color: #00d9ff; /* Updated text color */
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
    position: relative;
    -webkit-touch-callout: none;
}

.glow-effect {
    position: absolute;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(0, 217, 255, 0.36) 10%, transparent 10.01%);
    background-size: 40px 40px;
    z-index: -1;
}

.quiz-container {
    background: rgba(0, 0, 0, 0.9);
    padding: 20px; /* Reduced padding for better spacing */
    width: 90%; /* Responsive width */
    max-width: 800px; /* Maximum width for larger screens */
    max-height: 90vh; /* Constrain height to 90% of the viewport */
    border-radius: 15px;
    border: 3px solid #00d9ff; /* Updated border color */
    box-shadow: 0 0 20px rgba(0, 217, 255, 0.8); /* Updated glow color */
    text-align: center;
    overflow-y: auto; /* Enable vertical scrolling if content overflows */
    overflow-x: hidden; /* Disable horizontal scrolling */
}

#timer {
    font-size: 48px;
    text-shadow: 0 0 5px #00d9ff; /* Updated glow color */
    padding: 10px;
    border: 2px solid #00d9ff; /* Updated border color */
    border-radius: 5px;
    display: inline-block;
    margin-bottom: 10px;
}

.options {
    display: flex;
    flex-direction: column; /* Stack options vertically */
    gap: 10px; /* Spacing between buttons */
    width: 100%; /* Full width */
}

.options button {
    width: 100%; /* Full width for buttons */
    padding: 15px;
    background: #002244; /* Updated button background */
    color: #00d9ff; /* Updated text color */
    border: 2px solid #00d9ff; /* Updated border color */
    border-radius: 10px;
    font-size: 20px;
    cursor: pointer;
    transition: 0.3s;
    text-shadow: 0 0 1px #00d9ff; /* Updated glow color */
}

.options button:hover, .options button.selected {
    background: #004466; /* Updated hover background */
    transform: scale(1.05);
    box-shadow: 0 0 5px #00d9ff; /* Updated glow color */
}

.nav-buttons {
    display: flex;
    flex-wrap: wrap; /* Allow navigation buttons to wrap */
    gap: 10px; /* Spacing between buttons */
    justify-content: center;
    width: 100%; /* Full width */
    margin-top: 20px;
}

.nav-buttons button {
    padding: 15px 25px;
    background: #002244; /* Updated button background */
    color: #00d9ff; /* Updated text color */
    border: 2px solid #00d9ff; /* Updated border color */
    border-radius: 10px;
    cursor: pointer;
    font-size: 20px;
    transition: 0.3s;
}

.nav-buttons button:hover {
    background: #004466; /* Updated hover background */
    transform: scale(1.05);
    box-shadow: 0 0 5px #00d9ff; /* Updated glow color */
}

.quiz-container h2 {
    font-size: 36px;
    margin-bottom: 25px;
    text-shadow: 0 0 2px #00d9ff; /* Updated glow color */
}

#hint-text {
    font-size: 24px;
    font-style: italic;
    color: #00b3ff; /* Updated hint text color */
    margin-top: 20px;
    text-shadow: 0 0 2px #00b3ff; /* Updated glow color */
}

.quiz-container p {
    font-size: 24px; /* Adjusted font size for better readability */
    font-weight: bold;
    text-shadow: 0 0 1px #00d9ff; /* Updated glow color */
    margin-bottom: 20px;
    white-space: pre-wrap; /* Allow text to wrap within the container */
    word-wrap: break-word; /* Break long words */
    max-width: 100%; /* Ensure text doesn't overflow */
}

#warnings {
    text-align: left;
    margin-bottom: 20px;
}

#warnings ul {
    list-style-type: square;
    padding-left: 20px;
}

#warnings ul li {
    font-size: 18px;
    margin-bottom: 10px;
}

#proceed-btn {
    padding: 15px 25px;
    background: #002244; /* Updated button background */
    color: #00d9ff; /* Updated text color */
    border: 2px solid #00d9ff; /* Updated border color */
    border-radius: 10px;
    cursor: pointer;
    font-size: 20px;
    transition: 0.3s;
    margin-top: 20px;
}

#proceed-btn:hover {
    background: #004466; /* Updated hover background */
    transform: scale(1.05);
    box-shadow: 0 0 15px #00d9ff; /* Updated glow color */
}

#tab-switch-warning {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 40%;
    background: #000000; /* Pure black background */
    color: #00d9ff; /* Updated text color */
    text-align: center;
    padding: 30px;
    border: 3px solid #00d9ff; /* Updated border color */
    border-radius: 12px;
    box-shadow: 0 0 25px rgba(0, 217, 255, 0.9); /* Updated glow color */
    font-family: 'Orbitron', sans-serif; /* Updated font */
    z-index: 1000;
}

#tab-warning-text {
    font-size: 24px;
    text-shadow: 0 0 2px #00d9ff; /* Updated glow color */
    margin-bottom: 20px;
}

#close-warning-btn {
    font-size: 22px;
    padding: 12px 24px;
    background: #001a33; /* Updated button background */
    color: #00d9ff; /* Updated text color */
    border: 2px solid #00d9ff; /* Updated border color */
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
    text-shadow: 0 0 8px #00d9ff; /* Updated glow color */
    box-shadow: 0 0 15px rgba(0, 217, 255, 0.6); /* Updated glow color */
}

#close-warning-btn:hover {
    background: #002a4d; /* Updated hover background */
    transform: scale(1.08);
    box-shadow: 0 0 20px #00d9ff; /* Updated glow color */
}
</style>
<body>
    <div class="glow-effect"></div>
    <div class="quiz-container">
        <!-- Timer is now outside the #quiz-content div -->
        <h2 id="timer" style="margin-top:0px;">Time Left: <?php echo floor($_SESSION['time_left'] / 60) . ":" . ($_SESSION['time_left'] % 60 < 10 ? "0" : "") . ($_SESSION['time_left'] % 60); ?></h2>

        <?php if (!$_SESSION['fullscreen_enabled']): ?>
            <!-- Checkbox and Proceed Button -->
<!-- Checkbox and Proceed Button -->
<div id="warnings" style="font-family: Arial, sans-serif; padding: 20px; background-color:black; border-radius: 10px;">
    <h2 style="font-size: 1.8rem; margin-bottom: 0px;margin-top:0px;">Important Warnings</h2>
    <ul style="margin-left: 20px; font-size: 1rem;font-weight: bold;">
        <li>The test consists of 25 multiple-choice questions (10 easy(2 Marks), 10 medium(4 Marks), 5 hard(8 Marks)).</li> 
        <li>You have 30 minutes to complete the test.</li>
        <li>Your final score will be the average of both team members' scores.</li>
        <li>Once the test starts, it will enter fullscreen mode.</li>
        <li><span style="color: red; font-weight: bold;">Tab switching is not allowed. If you switch tabs more than once, you will be disqualified.</span></li>
    </ul>
    
    <form id="proceed-form" method="POST">
        <button type="submit" name="proceed" id="proceed-btn">Proceed to Test</button>
    </form>
</div>


            <div id="quiz-content" style="display: none;">
        <?php else: ?>
            <div id="quiz-content">
        <?php endif; ?>
                <?php if ($question): ?>
                    <h2>Question <?php echo ($current_question + 1) . " of " . $total_questions; ?></h2>
                    <div class="difficulty-level <?php echo $question['difficulty']; ?>">
                        <?php echo ucfirst($question['difficulty']); ?>
                    </div>
                    <p><?php echo strip_tags(htmlspecialchars_decode($question['question']), '<pre><br>'); ?></p>
                    <form id="quiz-form" method="POST">
                        <input type="hidden" name="answer" id="selected_answer" value="<?php echo $selected_answer; ?>">
                        <div class="options">
                            <button type="button" name="answer" value="1" class="<?php echo ($selected_answer == '1') ? 'selected' : ''; ?>">
                                <?php echo htmlspecialchars($question['option1']); ?>
                            </button>
                            <button type="button" name="answer" value="2" class="<?php echo ($selected_answer == '2') ? 'selected' : ''; ?>">
                                <?php echo htmlspecialchars($question['option2']); ?>
                            </button>
                            <button type="button" name="answer" value="3" class="<?php echo ($selected_answer == '3') ? 'selected' : ''; ?>">
                                <?php echo htmlspecialchars($question['option3']); ?>
                            </button>
                            <button type="button" name="answer" value="4" class="<?php echo ($selected_answer == '4') ? 'selected' : ''; ?>">
                                <?php echo htmlspecialchars($question['option4']); ?>
                            </button>
                        </div>

                        <div class="nav-buttons">
    
    <?php if ($current_question > 0): ?>
        <button type="button" name="prev">Previous</button>
    <?php endif; ?>
    <?php if ($current_question < $total_questions - 1): ?>
        <button type="button" name="next">Next</button>
    <?php else: ?>
        <button type="submit" name="submit">Submit</button><br>
    <?php endif; ?><br>
    <?php if ($current_question < $total_questions - 1): ?>
        <button type="submit" name="submit-all">Submit All</button>
    <?php endif; ?>
</div>
                    </form>
                <?php else: ?>
                    <h2>No questions available.</h2>
                <?php endif; ?>
            </div>
    </div>
    <div id="fullscreen-warning" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
background: rgba(0, 0, 0, 0.8); color: white; text-align: center; padding-top: 20%;">
    <h2>You have exited fullscreen mode.</h2>
    <p>Click the button below to continue your test.</p>
    <button id="reenter-fullscreen-btn" style="font-size: 20px; padding: 10px;">Re-enter Fullscreen</button>
</div>

<!-- Popup Modal for Submit All and Timeout -->
<div id="submit-popup" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
width: 40%; background: rgba(0, 0, 0, 0.9); color: #00d9ff; text-align: center; padding: 30px; border: 3px solid #00d9ff;
border-radius: 12px; box-shadow: 0 0 25px rgba(0, 217, 255, 0.8); font-family: 'Orbitron', sans-serif; z-index: 1000;">
    <p id="submit-popup-text" style="font-size: 16px; text-align: center; text-shadow: 0 0 10px #00d9ff;"></p>
    <button id="submit-popup-confirm" style="font-size: 20px; padding: 12px 24px; background: #002244; color: #00d9ff;
    border: 2px solid #00d9ff; border-radius: 8px; cursor: pointer; transition: 0.3s; text-shadow: 0 0 8px #00d9ff;
    box-shadow: 0 0 15px rgba(0, 217, 255, 0.6);">OK</button>
    <button id="submit-popup-cancel" style="font-size: 20px; padding: 12px 24px; background: #002244; color: #00d9ff;
    border: 2px solid #00d9ff; border-radius: 8px; cursor: pointer; transition: 0.3s; text-shadow: 0 0 8px #00d9ff;
    box-shadow: 0 0 15px rgba(0, 217, 255, 0.6); margin-left: 10px;">Cancel</button>
</div>

<!-- Popup Modal for Tab Switch Warning -->
<div id="tab-switch-warning" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
width: 40%; background: rgba(0, 0, 0, 0.9); color: #00d9ff; text-align: center; padding: 30px; border: 3px solid #00d9ff;
border-radius: 12px; box-shadow: 0 0 25px rgba(0, 217, 255, 0.8); font-family: 'Orbitron', sans-serif; z-index: 1000;">
    <p id="tab-warning-text" style="font-size: 16px; text-align: center; text-shadow: 0 0 10px #00d9ff;"></p>
    <button id="close-warning-btn" style="font-size: 20px; padding: 12px 24px; background: #002244; color: #00d9ff;
    border: 2px solid #00d9ff; border-radius: 8px; cursor: pointer; transition: 0.3s; text-shadow: 0 0 8px #00d9ff;
    box-shadow: 0 0 15px rgba(0, 217, 255, 0.6);">OK</button>
</div>

<!-- Popup Modal for Hints -->
<div id="hint-popup" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
width: 40%; background: rgba(0, 0, 0, 0.9); color: #00d9ff; text-align: center; padding: 30px; border: 3px solid #00d9ff;
border-radius: 12px; box-shadow: 0 0 25px rgba(0, 217, 255, 0.8); font-family: 'Orbitron', sans-serif; z-index: 1000;">
    <div id="hint-popup-content">
        <p style="font-size: 16px; text-align: center; text-shadow: 0 0 10px #00d9ff;">Choose a hint type:</p>
        <button id="hint-connection-btn" style="font-size: 20px; padding: 12px 24px; background: #002244; color: #00d9ff;
        border: 2px solid #00d9ff; border-radius: 8px; cursor: pointer; transition: 0.3s; text-shadow: 0 0 8px #00d9ff;
        box-shadow: 0 0 15px rgba(0, 217, 255, 0.6); margin: 10px;">Connection</button>
        <button id="hint-5050-btn" style="font-size: 20px; padding: 12px 24px; background: #002244; color: #00d9ff;
        border: 2px solid #00d9ff; border-radius: 8px; cursor: pointer; transition: 0.3s; text-shadow: 0 0 8px #00d9ff;
        box-shadow: 0 0 15px rgba(0, 217, 255, 0.6); margin: 10px;">50/50</button>
        <button id="close-hint-popup-btn" style="font-size: 20px; padding: 12px 24px; background: #002244; color: #00d9ff;
        border: 2px solid #00d9ff; border-radius: 8px; cursor: pointer; transition: 0.3s; text-shadow: 0 0 8px #00d9ff;
        box-shadow: 0 0 15px rgba(0, 217, 255, 0.6); margin-top: 20px;">Close</button>
    </div>
</div>
</body>
</html>