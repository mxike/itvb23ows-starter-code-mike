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

    public function __construct($database, GameLogic $gameLogic)
    {
        $this->gameLogic = $gameLogic;
        $this->database = $database;

        $this->initializeSession();
    }

    public function initializeSession()
    {
        $this->gameId = $_SESSION['game_id'];
        $this->hand = $_SESSION['hand'];
        $this->player = $_SESSION['player'];
        $this->board = $_SESSION['board'];
        $this->lastMove = $_SESSION['last_move'] ?? null;
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
                    $fromPosition = $_POST['fromPosition'];
                    $toPosition = $_POST['toPosition'];
                    $this->move($fromPosition, $toPosition);
                    break;
                case 'undo':
                    $this->undo();
                    break;
                case 'play_ai':
                    $this->playAi();
            }
            if ($this->hasWon($this->board)) {
                if ($this->player === 0) echo "WHITE IS THE WINNER!!";
                elseif ($this->player === 1) echo "BLACK IS THE WINNER!!";
                else echo "DRAW!!";
                return;
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

    public function setPlayer($player)
    {
        $this->player = $player;
    }

    public function setPlayerHand($index, $hand)
    {
        $this->hand[$index] = $hand;
    }

    public function getError()
    {
        return $this->error;
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
        elseif (count($board) && !$this->gameLogic->hasNeighBour($toPosition, $board))
            $this->error = "board position has no neighbour";
        elseif (array_sum($hand) < 11 && !$this->gameLogic->neighboursAreSameColor($player, $toPosition, $board))
            $this->error = "Board position has opposing neighbour";
        elseif (array_sum($hand) <= 8 && $hand['Q']) {
            if ($piece != 'Q') {
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
        if (!empty($this->board)) {
            $currentAction = $this->database->getUndoMove($this->lastMove);

            if ($currentAction[5] !== null) {
                $prevAction = $this->database->getUndoMove($currentAction[5]);

                $this->database->deleteAction($this->lastMove);

                list($hand, $board, $player) = unserialize($currentAction[6]);

                $this->hand = $hand;
                $this->board = $board;
                $this->player = $player;
                $this->lastMove = $prevAction[0];
                $this->error = '';

                return true;
            } else {
                $this->error = "No action to undo";
            }
        } else {
            $this->error = "Cannot undo when there are no pieces on the board";
        }

        return false;
    }

    public function move($fromPosition, $toPosition)
    {
        $player = $this->player;
        $board = $this->board;
        $hand = $this->hand[$player];
        unset($_SESSION['error']);

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

                switch ($tile[1]) {
                    case 'Q':
                        break;
                    case 'B':
                        break;
                    case 'S':
                        if (!$this->gameLogic->canSpiderMove($board, $fromPosition, $toPosition)) {
                            $this->error = "Spider cannot move to this position";
                            return;
                        }
                        break;
                    case 'A':
                        if (!$this->gameLogic->canAntMove($board, $fromPosition, $toPosition)) {
                            $this->error = "Ant cannot move to this position";
                            return;
                        }
                        break;
                    case 'G':
                        if (!$this->gameLogic->canGrassHopperMove($board, $fromPosition, $toPosition)) {
                            $this->error = "Grasshopper cannot move to this position";
                            return;
                        }
                        break;
                }

                if ($all) {
                    $this->error = "Move would split hive";
                } else {
                    if ($fromPosition == $toPosition) $_SESSION['error'] = 'Tile must move';
                    elseif (isset($board[$toPosition]) && $tile[1] != "B") $this->error = 'Tile not empty';
                    elseif ($tile[1] == "Q" || $tile[1] == "B") {
                        if (!$this->gameLogic->slide($board, $fromPosition, $toPosition))
                            $this->error = 'Tile must slide';
                    }
                }
            }
            if (isset($_SESSION['error'])) {
                if (isset($board[$fromPosition])) {
                    array_push($board[$fromPosition], $tile);
                } else $board[$fromPosition] = [$tile];
            } else {
                if (isset($board[$toPosition])) array_push($board[$toPosition], $tile);
                else $board[$toPosition] = [$tile];
                $this->player = 1 - $this->player;
                $this->lastMove = $this->database->move($this->gameId, $fromPosition, $toPosition, $this->lastMove);
                unset($board[$fromPosition]); // BUG 4.
            }
            $this->board = $board;
        }
        return $this->error !== '';
    }

    public function pass()
    {
        if (!$this->canPlayerPass()) {
            $this->error = 'You cannot pass';
        } else {
            $this->lastMove = $this->database->pass($this->gameId, $this->lastMove);
            $this->player = 1 - $this->player;
        }
    }

    public function canPlayerPass()
    {
        if (empty($this->getAllValidBoardPositions()) && count($this->hand[$this->player]) !== 0) {
            return false;
        }

        foreach ($this->board as $position) {
            if ($position[0][0] == $this->player) {
                foreach ($this->getAllBoardPositions() as $possiblePosition => $keyTo) {
                    if ($this->move($keyTo, $possiblePosition)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function getAllBoardPositions()
    {
        $toPositions = [];
        foreach ($GLOBALS['OFFSETS'] as $pq) {
            foreach (array_keys($this->board) as $pos) {
                $pq2 = explode(',', $pos);
                $toPositions[] = ($pq[0] + $pq2[0]) . ',' . ($pq[1] + $pq2[1]);
            }
        }
        $toPositions = array_unique($toPositions);
        if (!count($toPositions)) $toPositions[] = '0,0';

        return $toPositions;
    }

    public function getAllValidBoardPositions()
    {
        $allPositions = $this->getAllBoardPositions();
        $validPositions = array_filter($allPositions, [$this, 'validPosition']);
        return array_values($validPositions);
    }

    public function validPosition($position)
    {
        if (isset($this->board[$position])) {
            return false;
        }

        if (!$this->gameLogic->hasNeighBour($position, $this->board)) {
            return false;
        }
        if (array_sum($this->hand[$this->player]) < 11 && !$this->gameLogic->neighboursAreSameColor($this->player, $position, $this->board)) {
            return false;
        }
        if (array_sum($this->hand[$this->player]) <= 8 && $this->hand[$this->player]['Q']) {
            return false;
        }
        return true;
    }

    public function hasWon($board)
    {
        $allBoardPositions = $this->getAllBoardPositions();
        $opponent = 1 - $this->player;

        foreach ($allBoardPositions as $position) {
            $neigbourCount = $this->countNeighbours($board, $position, $this->player);
            $neigbourCountOpponent = $this->countNeighbours($board, $position, $opponent);

            if ($neigbourCount === 6) {
                return true;
            } elseif ($neigbourCountOpponent === 6) {
                return true;
            }
        }

        if ($this->isDraw($neigbourCount, $neigbourCountOpponent)) {
            return true;
        }
        return false;
    }

    public function countNeighbours($board, $position, $player)
    {
        $neighbourCount = 0;
        $positionCoord = explode(',', $position);

        foreach ($GLOBALS['OFFSETS'] as $offset) {
            $neighbour = ($positionCoord[0] + $offset[0]) . ',' . ($positionCoord[1] + $offset[1]);

            if (isset($board[$neighbour]) && isset($board[$neighbour][0][1]) == 'Q' && isset($board[$neighbour][0][0]) == $player) {
                $neighbourCount++;
            }
        }
        return $neighbourCount;
    }

    public function isDraw($neighbourCount, $neighbourCountOpponent)
    {
        return $neighbourCount === 6 && $neighbourCountOpponent === 6;
    }

    public function playAi(): void
    {
        $url = "http://hive-ai:5000";

        $requestData = [
            "hand" => $this->hand,
            "board" => $this->board,
            "player" => $this->player,
        ];

        $options = [
            "http" => [
                "header" => "Content-Type: application/json\r\n",
                "method" => "POST",
                "content" => json_encode($requestData)
            ]
        ];

        $response = json_decode(@file_get_contents($url, false, stream_context_create($options)));

        if ($response !== false) {

            [$action, $param1, $param2] = $response;

            switch ($action) {
                case "play":
                    $this->play($param1, $param2);
                    break;
                case "move":
                    $this->move($param1, $param2);
                    break;
                case "pass":
                    $this->pass();
                    break;
                default:
                    $this->error = "error Hive-AI";
            }
        } else {
            $this->error = "Unable to connect to the Hive-AI.";
        }
    }
}
