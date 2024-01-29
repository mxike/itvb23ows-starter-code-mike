<?php

namespace main;

class Game
{

    private DatabaseHandler $database;
    private GameLogic $gameLogic;

    private $gameId;
    private $hand;
    private $player;
    private $board;
    private $lastMove;
    private $error;

    public function __construct(DatabaseHandler $database, GameLogic $gameLogic)
    {
        $this->gameLogic = $gameLogic;
        $this->database = $database;

        $this->initializeSession();
    }

    private function initializeSession()
    {
        $this->gameId = $_SESSION['game_id'];
        $this->hand = $_SESSION['hand'];
        $this->player = $_SESSION['player'];
        $this->board = $_SESSION['board'];
        $this->gameId = $_SESSION['game_id'];
        $this->lastMove = $_SESSION['lastMove'] ?? null;
        $this->error = $_SESSION['error'] ?? null;
    }

    public function updatingSession()
    {
        $_SESSION["game_id"] = $this->gameId;
        $_SESSION["player"] = $this->player;
        $_SESSION["hand"] = $this->hand;
        $_SESSION["board"] = $this->board;
        $_SESSION["last_move"] = $this->lastMove;
        $_SESSION["error"] = $this->error;
    }

    public function waitAction()
    {
        $this->error = '';

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'play':
                    $piece = $_POST['piece'];
                    $toPosition = $_POST['toPosition'];
                    $this->play($piece, $toPosition);
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
            $this->updatingSession();
            $this->redirect();
        }
    }

    public function redirect()
    {
        header('Location: index.php');
    }

    public function setBoard($to, $piece)
    {
        $this->board[$to] = [[$this->player, $piece]];
    }

    public function getBoard()
    {
        return $this->board;
    }

    public function getPlayer()
    {
        return $this->player;
    }

    public function getPlayerHand($index)
    {
        return $this->hand[$index];
    }


    public function play($piece, $toPosition)
    {
        $player = $this->player;
        $board = $this->board;
        $hand = $this->hand[$player];

        if (!$hand[$piece])
            $this->error = "Player does not have tile";
        elseif (isset($board[$toPosition]))
            $this->error = 'Board position is not empty';
        elseif (count($this->board) && !$this->gameLogic->hasNeighBour($toPosition, $this->board))
            $this->error = "board position has no neighbour";
        elseif (array_sum($hand) < 11 && !$this->gameLogic->neighboursAreSameColor($this->player, $toPosition, $this->board))
            $this->error = "Board position has opposing neighbour";
        elseif (array_sum($hand) <= 8 && $hand['Q']) {
            // BUG #3 must play queen
            if ($hand[$piece] !== $hand['Q']) {
                $this->error = 'Must play queen bee';
            } else {
                $this->setBoard($toPosition, $piece);
                $this->hand[$player][$piece]--;
                $this->player = 1 - $this->player;
                $this->lastMove = $this->database->play($this->gameId, $piece, $toPosition, $this->lastMove);
            }
        } else {
            $this->setBoard($toPosition, $piece);
            $this->hand[$player][$piece]--;
            $this->player = 1 - $this->player;
            $this->lastMove = $this->database->play($this->gameId, $piece, $toPosition, $this->lastMove);
        }
    }

    public function pass()
    {
        $_SESSION['last_move'] =  $this->database->pass($_SESSION['game_id'], $_SESSION['last_move']);
        $_SESSION['player'] = 1 - $_SESSION['player'];
    }

    public function restart()
    {
        $this->board = [];
        $this->hand = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $this->player = 0;
        $this->gameId = $this->database->restart();
        $this->error = '';
        $this->lastMove = null;
    }

    public function undo()
    {
        // TODO LATER SINCE THERE IS A BUG IN THERE
    }

    public function move($fromPosition, $toPosition)
    {

        $player = $this->player;
        $board = $this->board;
        $hand = $this->hand[$player];

        if (!isset($board[$fromPosition]))
            $this->error = 'Board position is empty';
        elseif ($board[$fromPosition][count($board[$fromPosition]) - 1][0] != $player)
            $this->error = "Tile is not owned by player";
        elseif ($hand['Q'])
            $this->error = "Queen bee is not played";
        else {
            $tile = array_pop($board[$fromPosition]);
            if (!$this->gameLogic->hasNeighBour($toPosition, $board))
                $this->error = "Move would split hive";
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
                    $this->error = "Move would split hive";
                } else {
                    if ($fromPosition == $toPosition) $this->error = 'Tile must move';
                    elseif (isset($board[$toPosition]) && $tile[1] != "B") $this->error = 'Tile not empty';
                    elseif ($tile[1] == "Q" || $tile[1] == "B") {
                        if (!$this->gameLogic->slide($board, $fromPosition, $toPosition))
                            $this->error = 'Tile must slide';
                    }
                }
            }
            if (isset($_SESSION['error'])) {
                if (isset($board[$fromPosition])) array_push($board[$fromPosition], $tile);
                else $board[$fromPosition] = [$tile];
            } else {
                if (isset($board[$toPosition])) array_push($board[$toPosition], $tile);
                else $board[$toPosition] = [$tile];
                $player = 1 - $player;
                $this->lastMove = $this->database->move($this->gameId, $fromPosition, $toPosition, $this->lastMove);
            }
            $_SESSION['board'] = $board;
        }
    }
}
