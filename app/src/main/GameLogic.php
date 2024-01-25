<?php

namespace main;

$GLOBALS['OFFSETS'] = [[0, 1], [0, -1], [1, 0], [-1, 0], [-1, 1], [1, -1]];

class GameLogic
{
    public function isNeighbour($a, $b)
    {
        $a = explode(',', $a);
        $b = explode(',', $b);
        if ($a[0] == $b[0] && abs($a[1] - $b[1]) == 1) return true;
        if ($a[1] == $b[1] && abs($a[0] - $b[0]) == 1) return true;
        if ($a[0] + $a[1] == $b[0] + $b[1]) return true;
        return false;
    }

    public function hasNeighBour($a, $board)
    {
        foreach (array_keys($board) as $b) {
            if ($this->isNeighbour($a, $b)) return true;
        }
    }

    public function neighboursAreSameColor($player, $a, $board)
    {
        foreach ($board as $b => $st) {
            if (!$st) continue;
            $c = $st[count($st) - 1][0];
            if ($c != $player && $this->isNeighbour($a, $b)) return false;
        }
        return true;
    }

    public function len($tile)
    {
        return $tile ? count($tile) : 0;
    }

    public function slide($board, $fromPosition, $toPosition)
    {
        if (!$this->hasNeighBour($toPosition, $board)) return false;
        if (!$this->isNeighbour($fromPosition, $toPosition)) return false;
        $b = explode(',', $toPosition);
        $common = [];
        foreach ($GLOBALS['OFFSETS'] as $pq) {
            $p = $b[0] + $pq[0];
            $q = $b[1] + $pq[1];
            if ($this->isNeighbour($fromPosition, $p . "," . $q)) $common[] = $p . "," . $q;
        }
        if (!$board[$common[0]] && !$board[$common[1]] && !$board[$fromPosition] && !$board[$toPosition]) return false;
        return min(len($board[$common[0]]), len($board[$common[1]])) <= max(len($board[$fromPosition]), len($board[$toPosition]));
    }
}
