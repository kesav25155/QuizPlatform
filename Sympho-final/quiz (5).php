<?php
session_start();
require 'db_config.php';

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
    $_SESSION['time_left'] = 1800; // 30 minutes in seconds
}
if (!isset($_SESSION['answers'])) {
    $_SESSION['answers'] = array(); // Store user answers
}

// Fetch questions from the database
$query = "SELECT * FROM questions ORDER BY difficulty";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Quiz</title>
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

    function startTimer() {
        timerInterval = setInterval(function () {
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                $('#quiz-form').submit();
            } else {
                timeLeft--;
                $('#timer').text("Time Left: " + Math.floor(timeLeft / 60) + ":" + (timeLeft % 60 < 10 ? "0" : "") + (timeLeft % 60));
            }
        }, 1000);
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
});


    </script>
</head>
<body>
    <div class="glow-effect"></div>
    <div class="quiz-container">
        <!-- Timer is now outside the #quiz-content div -->
        <h2 id="timer">Time Left: <?php echo floor($_SESSION['time_left'] / 60) . ":" . ($_SESSION['time_left'] % 60 < 10 ? "0" : "") . ($_SESSION['time_left'] % 60); ?></h2>

        <?php if (!$_SESSION['fullscreen_enabled']): ?>
            <div id="warnings">
                <h2>Important Warnings</h2>
                <ul>
                    <li>The test consists of 30 MCQs (10 easy, 10 medium, 10 hard).</li>
                    <li>You have 30 minutes to complete the test.</li>
                    <li>Maximum of 5 hints can be used. Each hint deducts 1 minute.</li>
                    <li>Once the test starts, you cannot exit fullscreen mode.</li>
                    <li>Your final score will be the average of both team members.</li>
                    <li>Tab switching is not allowed. If done more than once, you will be terminated.</li>
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
                    <p><?php echo htmlspecialchars($question['question']); ?></p>
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
                            <button type="submit" name="hint">Hint (Used: <?php echo $_SESSION['hints_used']; ?>/5)</button>
                            <?php if ($current_question > 0): ?>
                                <button type="button" name="prev">Previous</button>
                            <?php endif; ?>
                            <?php if ($current_question < $total_questions - 1): ?>
                                <button type="button" name="next">Next</button>
                            <?php else: ?>
                                <button type="submit" name="submit">Submit</button>
                            <?php endif; ?>
                            <button type="submit" name="exit">Exit</button>
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

<!-- Popup Modal -->
<div id="tab-switch-warning" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.3); z-index: 1000;">
    <p id="tab-warning-text" style="font-size: 16px; text-align: center;"></p>
    <button id="close-warning-btn" style="display: block; margin: 10px auto; padding: 5px 10px; background: red; color: white; border: none; border-radius: 5px;">OK</button>
</div>

</body>
</html>