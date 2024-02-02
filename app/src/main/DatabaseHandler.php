<?php

namespace main;

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

    public function play($gameId, $piece, $moveTo, $previousId)
    {
        $stmt = $this->connection->prepare('INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "play", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $piece, $moveTo, $previousId, $this->getState());
        $stmt->execute();

        return $this->connection->insert_id;
    }

    public function pass($gameId, $previousId)
    {
        $stmt = $this->connection->prepare('INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "pass", null, null, ?, ?)');
        $stmt->bind_param('iis', $gameId, $previousId, $this->getState());
        $stmt->execute();

        return $this->connection->insert_id;
    }

    public function move($gameId, $piece, $moveTo, $previousId)
    {
        $stmt = $this->connection->prepare('INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "move", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $piece, $moveTo, $previousId, $this->getState());
        $stmt->execute();

        return $this->connection->insert_id;
    }

    public function restart()
    {
        $stmt = $this->connection->prepare('INSERT INTO games VALUES ()');
        $stmt->execute();

        return $this->connection->insert_id;
    }

    public function getMoves($gameId)
    {
        $stmt = $this->connection->prepare('SELECT * FROM moves WHERE game_id = ?');
        $stmt->bind_param('i', $gameId);
        $stmt->execute();
        return $stmt->get_result();
    }


    public function getUndoMove($prevMove)
    {
        $stmt = $this->connection->prepare('SELECT * FROM moves WHERE id = ?');
        $stmt->bind_param('i', $prevMove);
        $stmt->execute();
        return $stmt->get_result()->fetch_array();
    }

    public function deleteAction($id)
    {
        $stmt = $this->connection->prepare('DELETE FROM moves WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
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
