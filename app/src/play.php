<?php

session_start();
require_once 'main/DatabaseHandler.php';
include_once 'util.php';

use Main\DatabaseHandler;

$db = new DatabaseHandler('localhost', 'root', 'password', 'hive');

$piece = $_POST['piece'];
$toPosition = $_POST['toPosition'];

$player = $_SESSION['player'];
$board = $_SESSION['board'];
$hand = $_SESSION['hand'][$player];

if (!$hand[$piece])
    $_SESSION['error'] = "Player does not have tile";
elseif (isset($board[$toPosition]))
    $_SESSION['error'] = 'Board position is not empty';
elseif (count($board) && !hasNeighBour($toPosition, $board))
    $_SESSION['error'] = "board position has no neighbour";
elseif (array_sum($hand) < 11 && !neighboursAreSameColor($player, $toPosition, $board))
    $_SESSION['error'] = "Board position has opposing neighbour";
elseif (array_sum($hand) <= 8 && $hand['Q']) {
    $_SESSION['error'] = 'Must play queen bee';
} else {
    $_SESSION['board'][$toPosition] = [[$_SESSION['player'], $piece]];
    $_SESSION['hand'][$player][$piece]--;
    $_SESSION['player'] = 1 - $_SESSION['player'];
    $_SESSION['last_move']  = $db->doAction($_SESSION['game_id'], "play", null, $toPosition, $_SESSION['last_move'], $db->getState());
}

header('Location: index.php');
