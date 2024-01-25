<?php

session_start();

$db = include '../../database/database.php';
$stmt = $db->prepare('SELECT * FROM moves WHERE previous_id = ' . ($_SESSION['last_move'] - 1));
$stmt->execute();
$result = $stmt->get_result()->fetch_array();
var_dump($_SESSION['last_move']);
// var_dump($result);
$_SESSION['last_move'] = $result[5];
set_state($result[6]);
// header('Location: index.php');
