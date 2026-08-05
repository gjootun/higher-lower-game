<?php

    /*******w******** 

        Name:   Gyanee Jootun
        Date:   July 14th 2026
        Description: A "Higher/Lower" number guessing game that uses
                      PHP sessions to remember the secret number and the
                      guess count between page loads. Also includes the
                      optional "Super Gonzo Extreme Wizard" bonus: a
                      persistent top-3 highscore board (lowest guess
                      counts win), stored in a small JSON file so scores
                      survive across different players.

    ****************/

    session_start();

    define("RANDOM_NUMBER_MAXIMUM", 100);
    define("RANDOM_NUMBER_MINIMUM", 1);
    define("HIGHSCORE_FILE", __DIR__ . "/data/highscores.json");
    define("HIGHSCORE_MAX_ENTRIES", 3);

    $user_submitted_a_guess = isset($_POST['guess']);
    $user_requested_a_reset = isset($_POST['reset']);
    $user_submitted_a_name  = isset($_POST['save_score']);

    // Helper functions
    function startNewGame() {
        $_SESSION['secret_number'] = random_int(RANDOM_NUMBER_MINIMUM, RANDOM_NUMBER_MAXIMUM);
        $_SESSION['guess_count']   = 0;
        $_SESSION['game_won']      = false;
        unset($_SESSION['feedback']);
    }

    function loadHighscores() {
        if (!file_exists(HIGHSCORE_FILE)) {
            return [];
        }
        $json = file_get_contents(HIGHSCORE_FILE);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    function saveHighscores(array $scores) {
        $dir = dirname(HIGHSCORE_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents(HIGHSCORE_FILE, json_encode($scores, JSON_PRETTY_PRINT));
    }

    function addHighscore(string $name, int $count) {
        $scores   = loadHighscores();
        $scores[] = ['name' => $name, 'count' => $count];

        // Lowest guess count is best, so sort ascending.
        usort($scores, fn($a, $b) => $a['count'] <=> $b['count']);

        // Keep only the top entries.
        $scores = array_slice($scores, 0, HIGHSCORE_MAX_ENTRIES);

        saveHighscores($scores);
        return $scores;
    }

    // Game logic
    // First visit (or session expired): start a fresh game.
    if (!isset($_SESSION['secret_number'])) {
        startNewGame();
    }

    if ($user_requested_a_reset) {
        startNewGame();
    } elseif ($user_submitted_a_guess && empty($_SESSION['game_won'])) {

        $raw_guess = trim($_POST['user_guess'] ?? '');

        if ($raw_guess === '' || !is_numeric($raw_guess)) {
            $_SESSION['feedback'] = "Please enter a valid number.";
        } else {
            $guess = (int) $raw_guess;
            $_SESSION['guess_count']++;

            if ($guess < RANDOM_NUMBER_MINIMUM || $guess > RANDOM_NUMBER_MAXIMUM) {
                $_SESSION['feedback'] = "Pick a number between " . RANDOM_NUMBER_MINIMUM . " and " . RANDOM_NUMBER_MAXIMUM . ".";
            } elseif ($guess < $_SESSION['secret_number']) {
                $_SESSION['feedback'] = "Higher! 📈";
            } elseif ($guess > $_SESSION['secret_number']) {
                $_SESSION['feedback'] = "Lower! 📉";
            } else {
                $_SESSION['feedback'] = "Correct! 🎉 You got it in " . $_SESSION['guess_count'] . " guesses.";
                $_SESSION['game_won'] = true;
            }
        }
    } elseif ($user_submitted_a_name && !empty($_SESSION['game_won'])) {

        $player_name = trim($_POST['player_name'] ?? '');
        if ($player_name === '') {
            $player_name = 'Anonymous';
        }

        $highscores = addHighscore($player_name, $_SESSION['guess_count']);
        $_SESSION['feedback']        = "Nice! Your score has been saved.";
        $_SESSION['score_was_saved'] = true;
    }

    $feedback     = $_SESSION['feedback']      ?? "I'm thinking of a number between " . RANDOM_NUMBER_MINIMUM . " and " . RANDOM_NUMBER_MAXIMUM . ". Take a guess!";
    $guess_count  = $_SESSION['guess_count']   ?? 0;
    $game_won     = $_SESSION['game_won']      ?? false;
    $score_saved  = $_SESSION['score_was_saved'] ?? false;
    $highscores   = loadHighscores();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Number Guessing Game</title>
    <style>
        body {
            font-family: system-ui, sans-serif;
            max-width: 420px;
            margin: 3rem auto;
            padding: 0 1rem;
            color: #222;
        }
        h1 { margin-bottom: 0.25rem; }
        .feedback {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            background: #f1f1f1;
            margin: 1rem 0;
        }
        .feedback.won { background: #d9f3dd; }
        .count { color: #555; font-size: 0.95rem; }
        form { margin: 1rem 0; }
        input[type="text"], input:not([type]) {
            padding: 0.4rem;
            font-size: 1rem;
            width: 8rem;
        }
        input[type="submit"] {
            padding: 0.4rem 0.9rem;
            font-size: 1rem;
            margin-left: 0.4rem;
            cursor: pointer;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 0.5rem;
        }
        th, td {
            text-align: left;
            padding: 0.4rem 0.6rem;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Guessing Game</h1>

    <p class="feedback<?= $game_won ? ' won' : '' ?>"><?= htmlspecialchars($feedback) ?></p>
    <p class="count">Guesses so far: <?= (int) $guess_count ?></p>

    <?php if (!$game_won): ?>
        <form method="post">
            <label for="user_guess">Your Guess</label><br>
            <input id="user_guess" name="user_guess" autofocus>
            <input type="submit" name="guess" value="Guess">
            <input type="submit" name="reset" value="Reset">
        </form>
    <?php else: ?>
        <?php if (!$score_saved): ?>
            <form method="post">
                <label for="player_name">You won! Enter your name for the highscore board:</label><br>
                <input id="player_name" name="player_name" autofocus>
                <input type="submit" name="save_score" value="Save Score">
            </form>
        <?php endif; ?>

        <form method="post">
            <input type="submit" name="reset" value="Play Again">
        </form>
    <?php endif; ?>

    <?php if ($game_won && !empty($highscores)): ?>
        <h2>🏆 Highscores (fewest guesses wins)</h2>
        <table>
            <tr><th>#</th><th>Name</th><th>Guesses</th></tr>
            <?php foreach ($highscores as $i => $entry): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($entry['name']) ?></td>
                    <td><?= (int) $entry['count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</body>
</html>