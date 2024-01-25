<?php

namespace main;

class Game
{

    private DatabaseHandler $database;
    private GameLogic $gameUtil;

    public function __construct(DatabaseHandler $database, GameLogic $gameUtil)
    {
        $this->gameUtil = $gameUtil;
        $this->database = $database;
    }

    public function waitAction()
    {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'play':
                    $this->play();
                    break;
                case 'restart':
                    $this->restart();
                    break;
                case 'pass':
                    $this->pass();
                    break;
                case 'move':
                    $this->move();
                    break;
                case 'undo':
                    $this->undo();
                    break;
            }
            $this->redirect();
        }
    }

    public function redirect()
    {
        header('Location: index.php');
    }

    public function play()
    {
        $piece = $_POST['piece'];
        $toPosition = $_POST['toPosition'];

        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];

        if (!$hand[$piece])
            $_SESSION['error'] = "Player does not have tile";
        elseif (isset($board[$toPosition]))
            $_SESSION['error'] = 'Board position is not empty';
        elseif (count($board) && !$this->gameUtil->hasNeighBour($toPosition, $board))
            $_SESSION['error'] = "board position has no neighbour";
        elseif (array_sum($hand) < 11 && !$this->gameUtil->neighboursAreSameColor($player, $toPosition, $board))
            $_SESSION['error'] = "Board position has opposing neighbour";
        elseif (array_sum($hand) <= 8 && $hand['Q']) {
            $_SESSION['error'] = 'Must play queen bee';
        } else {
            $_SESSION['board'][$toPosition] = [[$_SESSION['player'], $piece]];
            $_SESSION['hand'][$player][$piece]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $_SESSION['last_move']  = $this->database->play($_SESSION['game_id'], $piece, $toPosition, $_SESSION['last_move']);
        }
    }

    public function pass()
    {
        $_SESSION['last_move'] =  $this->database->pass($_SESSION['game_id'], $_SESSION['last_move']);
        $_SESSION['player'] = 1 - $_SESSION['player'];
    }

    public function restart()
    {
        $_SESSION['board'] = [];
        $_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $_SESSION['player'] = 0;
        $_SESSION['game_id'] = $this->database->restart();
    }

    public function undo()
    {
        // TODO LATER SINCE THERE IS A BUG IN THERE
    }

    public function move()
    {
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
            if (!$this->gameUtil->hasNeighBour($toPosition, $board))
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
                        if (!slide($board, $fromPosition, $toPosition))
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
                $_SESSION['last_move'] = $this->database->move($_SESSION['game_id'], $fromPosition, $toPosition, $_SESSION['last_move']);
            }
            $_SESSION['board'] = $board;
        }
    }
}
