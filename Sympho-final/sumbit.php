<?php
session_start();
require 'db_config.php';

$response = ['success' => false, 'redirect' => false, 'html' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next'])) {
        // Handle next question
        $_SESSION['current_question']++;
        $response['success'] = true;
    } elseif (isset($_POST['prev'])) {
        // Handle previous question
        $_SESSION['current_question']--;
        $response['success'] = true;
    } elseif (isset($_POST['hint'])) {
        // Handle hint
        $_SESSION['hints_used']++;
        $_SESSION['time_left'] -= 60;
        $response['success'] = true;
    } elseif (isset($_POST['submit'])) {
        // Handle final submission
        $response['redirect'] = 'results.php';
        $response['success'] = true;
    }

    // Fetch the updated question
    $current_question = $_SESSION['current_question'];
    $query = "SELECT * FROM questions ORDER BY difficulty";
    $result = pg_query($conn, $query);
    $questions = pg_fetch_all($result);
    $question = $questions[$current_question];

    ob_start();
    ?>
    <h2>Question <?php echo ($current_question + 1) . " of " . count($questions); ?></h2>
    <p><?php echo htmlspecialchars($question['question']); ?></p>
    <form id="quiz-form" method="POST">
        <input type="hidden" name="answer" id="selected_answer" value="<?php echo $_SESSION['answers'][$current_question] ?? ''; ?>">
        <div class="options">
            <button type="submit" name="answer" value="1" class="<?php echo ($_SESSION['answers'][$current_question] == '1') ? 'selected' : ''; ?>">
                <?php echo htmlspecialchars($question['option1']); ?>
            </button>
            <button type="submit" name="answer" value="2" class="<?php echo ($_SESSION['answers'][$current_question] == '2') ? 'selected' : ''; ?>">
                <?php echo htmlspecialchars($question['option2']); ?>
            </button>
            <button type="submit" name="answer" value="3" class="<?php echo ($_SESSION['answers'][$current_question] == '3') ? 'selected' : ''; ?>">
                <?php echo htmlspecialchars($question['option3']); ?>
            </button>
            <button type="submit" name="answer" value="4" class="<?php echo ($_SESSION['answers'][$current_question] == '4') ? 'selected' : ''; ?>">
                <?php echo htmlspecialchars($question['option4']); ?>
            </button>
        </div>

        <div class="nav-buttons">
            <button type="submit" name="hint">Hint (Used: <?php echo $_SESSION['hints_used']; ?>/5)</button>
            <?php if ($current_question > 0): ?>
                <button type="submit" name="prev">Previous</button>
            <?php endif; ?>
            <?php if ($current_question < count($questions) - 1): ?>
                <button type="submit" name="next">Next</button>
            <?php else: ?>
                <button type="submit" name="submit">Submit</button>
            <?php endif; ?>
            <button type="submit" name="exit">Exit</button>
        </div>
    </form>
    <?php
    $response['html'] = ob_get_clean();
}

echo json_encode($response);