<?php

namespace Main;

use Exception;
use mysqli;

class DatabaseHandler
{
    private mysqli $connection;

    public function __construct($host, $username, $password, $database)
    {
        $this->connection = new mysqli($host, $username, $password, $database);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    public function doAction(
        int $gameId,
        string $action,
        string | null $fromPosition,
        string | null $toPosition,
        int | null $prevId,
        string $state
    ) {
        $cmd = "INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, ?, ?, ?, ?, ?);";
        $stmt = $this->connection->prepare($cmd);
        $stmt->bind_param("issiis", $gameId, $action, $fromPosition, $toPosition, $prevId, $state);
        $stmt->execute();

        return $this->connection->insert_id;
    }

    public function getMoves($gameId)
    {
        $stmt = $this->connection->prepare('SELECT * FROM moves WHERE game_id = ' . $gameId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function restart()
    {
        $this->connection->prepare('INSERT INTO games VALUES ()')->execute();
        // $_SESSION['game_id'] = $this->connection->insert_id;
        return $this->connection->insert_id;
    }

    public function getState()
    {
        return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
    }

    public function setState($state)
    {
        list($a, $b, $c) = unserialize($state);
        $_SESSION['hand'] = $a;
        $_SESSION['board'] = $b;
        $_SESSION['player'] = $c;
    }
}
