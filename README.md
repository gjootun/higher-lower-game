# PHP Session Guessing Game

A "Higher/Lower" number guessing game built to demonstrate PHP session state management - the app remembers the secret number, guess count, and win state across page loads using `$_SESSION`, with no database required.

## Features
- Session-based game state (secret number, guess count, win status persist across requests)
- Input validation (numeric checks, range checks)
- XSS-safe output via `htmlspecialchars()`
- **Bonus:** persistent top-3 highscore board (fewest guesses wins), stored in a JSON file so scores survive across different players and sessions

## Tech Stack
PHP · PHP Sessions · JSON file storage

## Setup
1. Place `HigherLower.php` in a PHP-enabled server directory (e.g. XAMPP's `htdocs`)
2. Ensure the `data/` folder is writable — the highscore JSON file is created automatically on first win
3. Open the file in a browser and start guessing
