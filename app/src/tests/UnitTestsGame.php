<?php

use main\DatabaseHandler;
use main\GameLogic;
use main\Game;

use PHPUnit\Framework\TestCase;

// TEST-commando: /vendor/bin/phpunit tests/UnitTest.php

class UnitTestsGame extends TestCase
{
    private Game $game;

    public function setUp(): void
    {
        $this->game = new Game(self::createStub(DatabaseHandler::class), new GameLogic());
    }

    public function test_Piece_Not_In_Player_Hand()
    {
        /* BUG 1: De dropdown die aangeeft welke stenen een speler kan plaatsen bevat ook stenen die
            de speler niet meer heeft. Bovendien bevat de dropdown die aangeeO waar een speler
            stenen kan plaatsen ook velden waar dat niet mogelijk is, en bevat de dropdown die
            aangeeft vanaf welke positiee een speler een steen wil verplaatsen ook velden die
            stenen van de tegenstander bevatten. */

        $this->game->setBoard('0,0', 'Q'); // play white Q

        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('1,0', 'Q'); // play black Q

        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        $this->game->play('Q', '0,1'); // play white Q again.

        self::assertEquals('Player does not have tile', $this->game->getError());
    }

    public function test_Move_Queen_To_Legal_Place_At_The_Start()
    {
        // BUG 2: Als wit een bijenkoningin speelt op (0, 0), en zwart op (1, 0), dan zou het een legale zet
        // moeten zijn dat wit zijn koningin verplaatst naar (0, 1), maar dat wordt niet toegestaan.

        $this->game->setBoard('0,0', 'Q'); // play white Q
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('1,0', 'Q'); // play black Q

        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,1');

        // Assert that the 'from' position is empty
        self::assertEquals('', $this->game->getError());
    }

    public function test_Queen_Must_Play_After_Three_Pieces_Played()
    {
        // BUG 3: Als wit een bijenkoningin speelt op (0, 0), en zwart op (1, 0), dan zou het een legale zet
        // moeten zijn dat wit zijn koningin verplaatst naar (0, 1), maar dat wordt niet toegestaan.

        # White moves
        $this->game->setBoard('0,0', 'A');
        $this->game->setBoard('0,1', 'B');
        $this->game->setBoard('0,2', 'A');

        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 1, 'B' => 1, 'S' => 2, 'A' => 1, 'G' => 3]);

        # White illegal play
        $this->game->play('B', '0,3');

        self::assertEquals('Must play queen bee', $this->game->getError());
    }

    public function test_Move_From_Position_And_Check_If_Position_Is_Cleared()
    {
        // BUG 4: Als je een steen verplaatst, kan je daarna geen nieuwe steen spelen op het oude veld,
        // ook als dat volgens de regels wel zou mogen.

        $this->game->setBoard('0,0', 'Q'); // play white Q

        $this->game->setPlayer(1); // set player to black 
        $this->game->setBoard('1,0', 'Q'); // play black Q

        // set player back to white
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,1');

        // Assert that the 'from' position is empty
        self::assertEmpty($this->game->getBoard()['0,0']);
    }

    public function test_Play_Piece_Board_Position_Is_Not_Empty()
    {
        $this->game->setBoard('0,0', 'Q'); // play white Q

        $this->game->setPlayer(1); // set player to Black
        $this->game->setBoard('1,0', 'Q'); // play black Q

        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        $this->game->play('B', '1,0'); // play white Q again.

        self::assertEquals('Board position is not empty', $this->game->getError());
    }

    public function test_Play_Board_Position_Has_No_Neighbour()
    {
        $this->game->setBoard('0,0', 'Q'); // play white Q

        $this->game->setPlayer(1); // play black Q
        $this->game->setBoard('0,1', 'Q');

        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        $this->game->play('B', '1,1'); // play white Q again.

        self::assertEquals('Board position has opposing neighbour', $this->game->getError());
    }

    public function test_Move_Piece_Tile_Not_Empty()
    {
        $this->game->setBoard('0,0', 'Q'); // play white Q

        $this->game->setPlayer(1); // play black Q
        $this->game->setBoard('0,1', 'Q'); // play black Q

        $this->game->setPlayer(0); // set white Q again.
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,1');

        // Assert that the 'from' position is empty
        self::assertEquals('Tile not empty', $this->game->getError());
    }

    public function test_Move_Tile_Must_Slide()
    {
        $this->game->setBoard('0,0', 'Q'); // play white Q

        $this->game->setPlayer(1); // play black Q
        $this->game->setBoard('0,1', 'Q'); // play black Q

        $this->game->setPlayer(0); // set white Q again.
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,2');

        // Assert that the 'from' position is empty
        self::assertEquals('Tile must slide', $this->game->getError());
    }

    public function test_Move_Would_Split_Hive()
    {
        $this->game->setBoard('0,0', 'Q'); // play white Q

        $this->game->setPlayer(1); // play black Q
        $this->game->setBoard('0,1', 'Q'); // play black Q

        $this->game->setPlayer(0); // set white Q again.
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,4');

        // Assert that the 'from' position is empty
        self::assertEquals('Move would split hive', $this->game->getError());
    }
}
