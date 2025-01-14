<?php
session_start();

use main\DatabaseHandler;
use main\Game;
use main\GameLogic;

require_once './vendor/autoload.php';

$db = new DatabaseHandler('localhost', 'root', 'password', 'hive');
$gameLogic = new GameLogic();
$game = new Game($db, $gameLogic);
$game->waitAction();

if (!isset($_SESSION['board'])) {
    $game->restart();
    header('Location: index.php');
    exit(0);
}

$board = $game->getBoard();
$player = $game->getPlayer();
// $hand = $_SESSION['hand'];

$toPosition = [];
foreach ($GLOBALS['OFFSETS'] as $pq) {
    foreach (array_keys($board) as $pos) {
        $pq2 = explode(',', $pos);
        // echo ($pq[0] + $pq2[0]) . ',' . ($pq[1] + $pq2[1]);
        $toPosition[] = ($pq[0] + $pq2[0]) . ',' . ($pq[1] + $pq2[1]);
    }
}

$toPosition = array_unique($toPosition);
if (!count($toPosition)) $toPosition[] = '0,0';
?>
<!DOCTYPE html>
<html xml:lang="en">

<head>
    <title>Hive</title>
    <link rel="stylesheet" type="text/css" href="style/style.css">
</head>

<body>
    <div class="board">
        <?php
        $min_p = 1000;
        $min_q = 1000;
        foreach ($board as $pos => $tile) {
            $pq = explode(',', $pos);
            if ($pq[0] < $min_p) $min_p = $pq[0];
            if ($pq[1] < $min_q) $min_q = $pq[1];
        }
        foreach (array_filter($board) as $pos => $tile) {
            $pq = explode(',', $pos);
            $pq[0];
            $pq[1];
            $h = count($tile);
            echo '<div class="tile player';
            echo $tile[$h - 1][0];
            if ($h > 1) echo ' stacked';
            echo '" style="left: ';
            echo ($pq[0] - $min_p) * 4 + ($pq[1] - $min_q) * 2;
            echo 'em; top: ';
            echo ($pq[1] - $min_q) * 4;
            echo "em;\">($pq[0],$pq[1])<span>";
            echo $tile[$h - 1][1];
            echo '</span></div>';
        }
        ?>
    </div>
    <div class="hand">
        White:
        <?php
        foreach ($game->getPlayerHand(0) as $tile => $ct) {
            for ($i = 0; $i < $ct; $i++) {
                echo '<div class="tile player0"><span>' . $tile . "</span></div> ";
            }
        }
        ?>
    </div>
    <div class="hand">
        Black:
        <?php
        foreach ($game->getPlayerHand(1) as $tile => $ct) {
            for ($i = 0; $i < $ct; $i++) {
                echo '<div class="tile player1"><span>' . $tile . "</span></div> ";
            }
        }
        ?>
    </div>
    <div class="turn">
        Turn: <?php if ($player == 0) echo "White";
                else echo "Black"; ?>
    </div>
    <form method="post">
        <select name="piece">
            <?php
            foreach ($game->getPlayerHand($player) as $tile => $ct) {
                // BUG 1 load available pieces only
                if ($ct > 0) {
                    echo "<option value=\"$tile\">$tile</option>";
                }
            }
            ?>
        </select>
        <select name="toPosition">
            <?php
            foreach ($toPosition as $pos) {
                // BUG 1 load available position places
                if (!isset($board[$pos])) {
                    echo "<option value=\"$pos\">$pos</option>";
                }
            }
            ?>
        </select>
        <input type="hidden" name="action" value="play">
        <input type="submit" value="Play">
    </form>

    <form method="post">
        <select name="fromPosition">
            <?php
            // echo '<pre>';
            // print_r($board);
            // echo '</pre>';
            // BUG 1 load positions
            foreach (array_keys($board) as $pos) {
                if ($board[$pos][0][0] == $player)
                    echo "<option value=\"$pos\">$pos</option>";
            }
            ?>
        </select>
        <select name="toPosition">
            <?php
            // You dont need to do this one, read BUG 1.
            foreach ($toPosition as $pos) {
                echo "<option value=\"$pos\">$pos</option>";
            }
            ?>
        </select>
        <input type="hidden" name="action" value="move">
        <input type="submit" value="Move">
    </form>

    <form method="post">
        <input type="hidden" name="action" value="pass">
        <input type="submit" value="Pass">
    </form>

    <form method="post">
        <input type="hidden" name="action" value="restart">
        <input type="submit" value="Restart">
    </form>

    <form method="post">
        <input type="hidden" name="action" value="play_ai">
        <input type="submit" value="Play_ai">
    </form>
    <form method="post">
        <input type="hidden" name="action" value="undo">
        <input type="submit" value="Undo">
    </form>

    <strong><?php if (isset($_SESSION['error'])) echo ($_SESSION['error']); ?></strong>

    <ol>
        <?php
        $result = $db->getMoves($_SESSION['game_id']);
        while ($row = $result->fetch_array()) {
            echo '<li>' . $row[2] . ' ' . $row[3] . ' ' . $row[4] . '</li>';
        }
        ?>
    </ol>


</body>

</html>