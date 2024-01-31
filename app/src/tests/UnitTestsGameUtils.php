<?php

use main\DatabaseHandler;
use main\GameLogic;
use main\Game;

use PHPUnit\Framework\TestCase;

// TEST-commando: /vendor/bin/phpunit tests/UnitTest.php

class UnitTestsGameUtils extends TestCase
{
    private Game $game;

    public function setUp(): void
    {
        $this->game = new Game(self::createStub(DatabaseHandler::class), new GameLogic());
    }

    public function test_Grasshopper_Can_Move_Over_Atleast_One_Tile()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("-1,0", "G");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("1,0", "Q");
        $this->game->setBoard("2,0", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("-1,0", "3,0"); // jump over 2 tiles.

        // Assert
        self::assertEquals('', $this->game->getError());
    }

    public function test_Grasshopper_Can_Not_Move_Without_Hopping_Over_Atleast_One_Tile()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("-1,0", "G");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("1,0", "Q");
        $this->game->setBoard("2,0", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("-1,0", "3,0");
        $this->game->pass(); // pass for black
        $this->game->setPlayer(0); // set player to white
        $this->game->move("3,0", "2,1");

        // Assert
        self::assertEquals('Grasshopper cannot move to this position', $this->game->getError());
    }

    public function test_Grasshopper_Can_Not_Jump_To_Current_Position()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("-1,0", "G");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("1,0", "Q");
        $this->game->setBoard("2,0", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("-1,0", "-1,0");

        // Assert
        self::assertEquals('Grasshopper cannot move to this position', $this->game->getError());
    }
}
