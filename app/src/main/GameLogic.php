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
        if (!isset($board[$common[0]]) && !isset($board[$common[1]]) && !isset($board[$fromPosition]) && !isset($board[$toPosition])) return false; //BUG 2
        return min($this->len($board[$common[0]]), $this->len($board[$common[1]])) <= max($this->len($board[$fromPosition]), $this->len($board[$toPosition]));
    }

    public function canGrassHopperMove($board, $fromPosition, $toPosition)
    {
        // Cannot move to itself
        if ($fromPosition === $toPosition) {
            return false;
        }

        // Split coordinates
        $fromExplode = explode(',', $fromPosition);
        $toExplode = explode(',', $toPosition);

        // Calculate offset
        $offset = [$toExplode[0] - $fromExplode[0], $toExplode[1] - $fromExplode[1]];

        // Check offset (vertical, horizontal, or diagonal)
        if (!(($offset[0] == 0 && $offset[1] != 0) || ($offset[1] == 0 && $offset[0] != 0) || ($offset[0] == $offset[1]))) {
            return false;
        }

        $p = $fromExplode[0] + $offset[0];
        $q = $fromExplode[1] + $offset[1];

        while ($p != $toExplode[0] || $q != $toExplode[1]) {
            $pos = $p . "," . $q;

            if (isset($board[$pos])) {
                return false;
            }

            // move to the next tile
            $p += $offset[0];
            $q += $offset[1];
        }

        return true;
    }

    public function canAntMove($board, $fromPosition, $toPosition)
    {
        if ($toPosition === $fromPosition) {
            return false;
        }

        if (!isset($board[$toPosition])) {
            return false;
        }

        if (!$this->hasNeighBour($toPosition, $board)) {
            return false;
        }

        return true;
    }
}
