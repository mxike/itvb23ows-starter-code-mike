<?php

session_start();

include_once 'util.php';

$from = $_POST['from'];
$toPosition = $_POST['toPosition'];

$player = $_SESSION['player'];
$board = $_SESSION['board'];
$hand = $_SESSION['hand'][$player];
unset($_SESSION['error']);

if (!isset($board[$from]))
    $_SESSION['error'] = 'Board position is empty';
elseif ($board[$from][count($board[$from]) - 1][0] != $player)
    $_SESSION['error'] = "Tile is not owned by player";
elseif ($hand['Q'])
    $_SESSION['error'] = "Queen bee is not played";
else {
    $tile = array_pop($board[$from]);
    if (!hasNeighBour($toPosition, $board))
        $_SESSION['error'] = "Move would split hive";
    else {
        $all = array_keys($board);
        $queue = [array_shift($all)];
        while ($queue) {
            $next = explode(',', array_shift($queue));
            foreach ($GLOBALS['OFFSETS'] as $pq) {
                list($p, $q) = $pq;
                $p += $next[0];
                $q += $next[1];
                if (in_array("$p,$q", $all)) {
                    $queue[] = "$p,$q";
                    $all = array_diff($all, ["$p,$q"]);
                }
            }
        }
        if ($all) {
            $_SESSION['error'] = "Move would split hive";
        } else {
            if ($from == $toPosition) $_SESSION['error'] = 'Tile must move';
            elseif (isset($board[$toPosition]) && $tile[1] != "B") $_SESSION['error'] = 'Tile not empty';
            elseif ($tile[1] == "Q" || $tile[1] == "B") {
                if (!slide($board, $from, $toPosition))
                    $_SESSION['error'] = 'Tile must slide';
            }
        }
    }
    if (isset($_SESSION['error'])) {
        if (isset($board[$from])) array_push($board[$from], $tile);
        else $board[$from] = [$tile];
    } else {
        if (isset($board[$toPosition])) array_push($board[$toPosition], $tile);
        else $board[$toPosition] = [$tile];
        $_SESSION['player'] = 1 - $_SESSION['player'];
        $db = include '../../database/database.php';
        $stmt = $db->prepare('INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "move", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $_SESSION['game_id'], $from, $toPosition, $_SESSION['last_move'], get_state());
        $stmt->execute();
        $_SESSION['last_move'] = $db->insert_id;
    }
    $_SESSION['board'] = $board;
}

header('Location: index.php');
