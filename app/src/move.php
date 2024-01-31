<?php

session_start();

include_once 'util.php';
require_once 'main/DatabaseHandler.php';
require_once 'main/GameLogic.php';

use Main\DatabaseHandler;
use main\GameLogic;

$db = new DatabaseHandler('localhost', 'root', 'password', 'hive');
$gameLogic = new GameLogic();

$fromPosition = $_POST['fromPosition'];
$toPosition = $_POST['toPosition'];

$player = $_SESSION['player'];
$board = $_SESSION['board'];
$hand = $_SESSION['hand'][$player];
unset($_SESSION['error']);

if (!isset($board[$fromPosition]))
    $_SESSION['error'] = 'Board position is empty';
elseif ($board[$fromPosition][count($board[$fromPosition]) - 1][0] != $player)
    $_SESSION['error'] = "Tile is not owned by player";
elseif ($hand['Q'])
    $_SESSION['error'] = "Queen bee is not played";
else {
    $tile = array_pop($board[$fromPosition]);
    if (!$gameLogic->hasNeighBour($toPosition, $board))
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
            if ($fromPosition == $toPosition) $_SESSION['error'] = 'Tile must move';
            elseif (isset($board[$toPosition]) && $tile[1] != "B") $_SESSION['error'] = 'Tile not empty';
            elseif ($tile[1] == "Q" || $tile[1] == "B") {
                if (!$gameLogic->slide($board, $fromPosition, $toPosition))
                    $_SESSION['error'] = 'Tile must slide';
            }
        }
    }
    if (isset($_SESSION['error'])) {
        if (isset($board[$fromPosition])) array_push($board[$fromPosition], $tile);
        else $board[$fromPosition] = [$tile];
    } else {
        if (isset($board[$toPosition])) array_push($board[$toPosition], $tile);
        else $board[$toPosition] = [$tile];
        $_SESSION['player'] = 1 - $_SESSION['player'];
        $_SESSION['last_move'] = $db->move($_SESSION['game_id'], $from, $to, $_SESSION['last_move']);
    }
    $_SESSION['board'] = $board;
}

header('Location: index.php');
