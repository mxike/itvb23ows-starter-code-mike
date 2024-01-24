<?php

session_start();
require_once 'main/DatabaseHandler.php';

use Main\DatabaseHandler;

$db = new DatabaseHandler('localhost', 'root', 'password', 'hive');

$_SESSION['last_move'] =  $db->pass($_SESSION['game_id'], $_SESSION['last_move']);
$_SESSION['player'] = 1 - $_SESSION['player'];

header('Location: index.php');
