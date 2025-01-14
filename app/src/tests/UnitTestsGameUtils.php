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

    public function test_Grasshopper_Must_Move_Over_Atleast_One_Tile()
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

    public function test_Ant_Can_Not_Move_To_Itself()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("0,-1", "A");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("0,1", "Q");
        $this->game->setBoard("0,2", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("0,-1", "0,-1");

        // Assert
        self::assertEquals('Ant cannot move to this position', $this->game->getError()); // if ant tries to go to a place without neighbours it would split the hiv
    }

    public function test_Ant_Can_Not_Move_To_Tile_Without_Neighbours()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("0,-1", "A");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("0,1", "Q");
        $this->game->setBoard("0,2", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("0,-1", "-2,5");

        // Assert
        self::assertEquals('Move would split hive', $this->game->getError()); // if ant tries to go to a place without neighbours it would split the hive.
    }

    public function test_Ant_Must_Move_To_Tile_With_Neighbours()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("0,-1", "A");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("0,1", "Q");
        $this->game->setBoard("0,2", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("0,-1", "-1,2");

        // Assert
        self::assertEquals('', $this->game->getError());
    }

    public function test_Spider_Can_Not_Move_To_Itself()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("0,-1", "S");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("0,1", "Q");
        $this->game->setBoard("0,2", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("0,-1", "0,-1");

        // Assert
        self::assertEquals('Spider cannot move to this position', $this->game->getError());
    }

    public function test_Spider_Can_Not_Move_To_Tile_Without_Neighbours()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("0,-1", "S");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("0,1", "Q");
        $this->game->setBoard("0,2", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("0,-1", "-2,5");

        // Assert
        self::assertEquals('Move would split hive', $this->game->getError());
    }

    public function test_Spider_Must_Move_To_Tile_Without_Neighbours()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("0,-1", "S");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("0,1", "Q");
        $this->game->setBoard("0,2", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("0,-1", "1,1");

        // Assert
        self::assertNull($this->game->getError());
    }

    public function test_Spider_Must_Move_Three_Steps()
    {
        // Initialize
        $this->game->setBoard("0,0", "Q");
        $this->game->setBoard("0,-1", "S");
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard("0,1", "Q");
        $this->game->setBoard("0,2", "B");
        $this->game->setPlayer(0); // set player to white

        // Action
        $this->game->move("0,-1", "-1,1");

        // Assert
        self::assertNull($this->game->getError());
    }
}
