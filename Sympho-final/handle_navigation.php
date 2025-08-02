<?php
session_start();
require 'db_config.php';


$query = "SELECT * FROM questions 
          ORDER BY CASE 
              WHEN difficulty = 'easy' THEN 1
              WHEN difficulty = 'medium' THEN 2
              WHEN difficulty = 'hard' THEN 3
          END";
$result = pg_query($conn, $query);
$questions = pg_fetch_all($result);
$total_questions = $questions ? count($questions) : 0;


if (isset($_POST['action'])) {
    $action = $_POST['action'];

    
    if (isset($_POST['answer'])) {
        $_SESSION['answers'][$_SESSION['current_question']] = $_POST['answer'];
    }

    
    if ($action === 'submit_all') {
        
        $_SESSION['score'] = 0;
        foreach ($_SESSION['answers'] as $index => $selected_answer) {
            if (isset($questions[$index])) { 
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
        
        echo json_encode(['status' => 'success', 'redirect' => 'submission_processing.php']);
        exit();
    }

    
    if ($action === 'next' && $_SESSION['current_question'] < $total_questions - 1) {
        $_SESSION['current_question']++; 
    } elseif ($action === 'prev' && $_SESSION['current_question'] > 0) {
        $_SESSION['current_question']--; 
    }

    
    $current_question = $_SESSION['current_question'];
    $question = $questions[$current_question];

    
    ob_start();
    ?>
    <h2>Question <?php echo ($current_question + 1) . " of " . $total_questions; ?></h2>
    <div class="difficulty-level <?php echo $question['difficulty']; ?>">
        <?php echo ucfirst($question['difficulty']); ?>
    </div>
    <p><?php echo strip_tags(htmlspecialchars_decode($question['question']), '<pre><br>'); ?></p>
    <form id="quiz-form" method="POST">
        <input type="hidden" name="answer" id="selected_answer" value="<?php echo $_SESSION['answers'][$current_question] ?? ''; ?>">
        <div class="options">
            <?php for ($i = 1; $i <= 4; $i++): ?>
                <button type="button" name="answer" value="<?php echo $i; ?>" class="<?php echo ($_SESSION['answers'][$current_question] == $i) ? 'selected' : ''; ?>">
                    <?php echo htmlspecialchars($question['option' . $i]); ?>
                </button>
            <?php endfor; ?>
        </div>

        <div class="nav-buttons">
            <?php if ($current_question > 0): ?>
                <button type="button" name="prev">Previous</button>
            <?php endif; ?>
            <?php if ($current_question < $total_questions - 1): ?>
                <button type="button" name="next">Next</button>
            <?php else: ?>
                <button type="submit" name="submit">Submit</button>
            <?php endif; ?><br>
            <?php if ($current_question < $total_questions - 1): ?>
        <button type="submit" name="submit-all">Submit All</button>
    <?php endif; ?>
        </div>
    </form>
    <!-- Hint Popup -->
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
    <script>
        
        function showHintPopup() {
            document.getElementById('hint-popup').style.display = 'block';
        }

        
        function hideHintPopup() {
            document.getElementById('hint-popup').style.display = 'none';
        }

        
        $(document).on('click', 'button[name="hint"]', function (event) {
            event.preventDefault();
            const difficulty = "<?php echo $question['difficulty']; ?>";
            if (difficulty === 'easy') {
                return;
            }
            showHintPopup();
        });

function handleConnectionHint() {
    $.ajax({
        url: 'handle_hint.php',
        type: 'POST',
        data: { action: 'connection', question_id: <?php echo $question['id']; ?> },
        success: function (response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                
                document.getElementById('hint-popup-content').innerHTML = `
                    <img src="${data.hint1}" alt="Hint 1" style="max-width: 100%; height: auto;">
                    <span style="font-size: 24px; margin: 0 10px;">+</span>
                    <img src="${data.hint2}" alt="Hint 2" style="max-width: 100%; height: auto;">
                    <button id="close-hint-popup-btn">Close</button>
                `;
                document.getElementById('hint-popup').style.display = 'block';
            } else {
                alert(data.message); 
            }
        }
    });
}


$(document).on('click', '#hint-connection-btn', function (event) {
    event.preventDefault();
    handleConnectionHint();
});

        
        function handle5050Hint() {
            $.ajax({
                url: 'handle_hint.php',
                type: 'POST',
                data: { action: '5050', question_id: <?php echo $question['id']; ?> },
                success: function (response) {
                    const data = JSON.parse(response);
                    if (data.status === 'success') {
                        
                        hideHintPopup();

                        
                        data.disabled_options.forEach(option => {
                            document.querySelector(`.options button[value="${option}"]`).disabled = true;
                        });

                        
                        $.ajax({
                            url: 'handle_hint.php',
                            type: 'POST',
                            data: { action: 'increment_5050', question_id: <?php echo $question['id']; ?> },
                            success: function () {
                                location.reload(); 
                            }
                        });
                    } else {
                       return;
                    }
                }
            });
        }

        
        document.getElementById('hint-btn').textContent = `Hint (Connection: ${<?php echo 3 - $_SESSION['connection_hints_used']; ?>} left, 50/50: ${<?php echo 2 - $_SESSION['5050_hints_used']; ?>} left)`;

        
        $(document).on('click', '#hint-connection-btn', function () {
            if (<?php echo $_SESSION['connection_hints_used']; ?> >= 3) {
                return;
            }
            handleConnectionHint();
        });

        
        $(document).on('click', '#hint-5050-btn', function () {
            if (<?php echo $_SESSION['5050_hints_used']; ?> >= 2) {
                return;
            }
            handle5050Hint();
        });

        
        $(document).on('click', '#close-hint-popup-btn', function () {
            hideHintPopup();
        });
    </script>
    <?php
    echo ob_get_clean();
}
?>