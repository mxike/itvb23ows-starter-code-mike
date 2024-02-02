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

    // Test case to check if a piece not in player hand cannot be played
    public function test_Piece_Not_In_Player_Hand()
    {
        /*  BUG 1: De dropdown die aangeeft welke stenen een speler kan plaatsen bevat ook stenen die
            de speler niet meer heeft. Bovendien bevat de dropdown die aangeeO waar een speler
            stenen kan plaatsen ook velden waar dat niet mogelijk is, en bevat de dropdown die
            aangeeft vanaf welke positiee een speler een steen wil verplaatsen ook velden die
            stenen van de tegenstander bevatten. */

        // Initialize 
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('1,0', 'Q');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->play('Q', '0,1');

        // Assert
        self::assertEquals('Player does not have tile', $this->game->getError());
    }

    // Test case to check if a Queen can move to a legal place at the start
    public function test_Move_Queen_To_Legal_Place_At_The_Start()
    {
        /*  BUG 2: Als wit een bijenkoningin speelt op (0, 0), en zwart op (1, 0), dan zou het een legale zet
            moeten zijn dat wit zijn koningin verplaatst naar (0, 1), maar dat wordt niet toegestaan. */

        // Initialize 
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('1,0', 'Q');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->move('0,0', '0,1');

        // Assert
        self::assertEquals('', $this->game->getError());
    }

    // Test case to check if Queen must play after three pieces played
    public function test_Queen_Must_Play_After_Three_Pieces_Played()
    {
        /*  BUG 3: Als wit een bijenkoningin speelt op (0, 0), en zwart op (1, 0), dan zou het een legale zet
            moeten zijn dat wit zijn koningin verplaatst naar (0, 1), maar dat wordt niet toegestaan. */

        // Initialize white setup
        $this->game->setBoard('0,0', 'A');
        $this->game->setBoard('0,1', 'B');
        $this->game->setBoard('0,2', 'A');
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 1, 'B' => 1, 'S' => 2, 'A' => 1, 'G' => 3]);

        // Action 4th white
        $this->game->play('B', '0,3');

        // Assert
        self::assertEquals('Must play queen bee', $this->game->getError());
    }

    // Test case to check if a piece can be moved and the previous position becomes empty
    public function test_Move_From_Position_And_Check_If_Position_Is_Cleared()
    {
        /*  BUG 4: Als je een steen verplaatst, kan je daarna geen nieuwe steen spelen op het oude veld,
            ook als dat volgens de regels wel zou mogen. */

        // Initialize 
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black 
        $this->game->setBoard('1,0', 'Q');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->move('0,0', '0,1');

        // Assert
        self::assertEmpty($this->game->getBoard()['0,0']);
    }

    // Test case to check if playing a piece on a non-empty board position is disallowed
    public function test_Play_Piece_Board_Position_Is_Not_Empty()
    {
        // Initialize 
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('1,0', 'Q');
        $this->game->setPlayer(0); // play white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->play('B', '1,0');

        // Assert
        self::assertEquals('Board position is not empty', $this->game->getError());
    }

    // Test case to check if playing a piece on a board position with an opposing neighbor is disallowed
    public function test_Play_Board_Position_Has_No_Neighbour()
    {
        // Initialize 
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('0,1', 'Q');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->play('B', '1,1'); // play white B again.

        // Assert
        self::assertEquals('Board position has opposing neighbour', $this->game->getError());
    }

    // Test case to check if moving a piece to a non-empty tile is disallowed
    public function test_Move_Piece_Tile_Not_Empty()
    {
        // Initialize 
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('0,1', 'Q');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->move('0,0', '0,1');

        // Assert
        self::assertEquals('Tile not empty', $this->game->getError());
    }

    // Test case to check if moving a piece requires sliding instead of jumping
    public function test_Move_Tile_Must_Slide()
    {
        // Initialize
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('0,1', 'Q');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->move('0,0', '0,2');

        // Assert
        self::assertEquals('Tile must slide', $this->game->getError());
    }

    // Test case to check if moving a piece would split the hive
    public function test_Move_Would_Split_Hive()
    {
        // Initialize 
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('0,1', 'Q');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->move('0,0', '0,4');

        // Assert
        self::assertEquals('Move would split hive', $this->game->getError());
    }

    public function test_Player_Cannot_Pass_Without_Any_Pieces_Played()
    {
        // Initialize
        $this->game->setBoard('0', '0');
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 1, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->pass(); // pass white

        // Assert
        self::assertEquals("You cannot pass", $this->game->getError());
    }

    public function test_Player_Cannot_Pass_When_Hand_Is_Not_Empty()
    {
        // Initialize
        $this->game->setBoard('0,0', 'Q');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('0,1', 'B');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 1, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->pass(); // pass white

        // Assert
        self::assertEquals("You cannot pass", $this->game->getError());
    }

    public function test_Player_Cannot_Pass_When_Possible_Move_Can_Be_Played()
    {
        // Initialize
        $this->game->setBoard('0,0', 'Q');
        $this->game->setBoard('0,-1', 'B');
        $this->game->setBoard('0,-2', 'B');
        $this->game->setPlayer(1); // set player to black
        $this->game->setBoard('0,1', 'B');
        $this->game->setBoard('0,2', 'B');
        $this->game->setBoard('0,3', 'B');
        $this->game->setPlayer(0); // set player to white
        $this->game->setPlayerHand(0, ['Q' => 0, 'B' => 0, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Action
        $this->game->pass(); // pass white

        // Assert
        self::assertEquals("You cannot pass", $this->game->getError());
    }

    public function test_Player_White_Has_Won_The_Game()
    {
        // Initialize
        $this->game->setPlayer(1);
        $this->game->setBoard('0,2', 'B');
        $this->game->setBoard('0,1', 'Q');
        $this->game->setBoard('0,0', 'B');
        $this->game->setBoard('1,1', 'S');
        $this->game->setBoard('-1,1', 'A');
        $this->game->setBoard('-1,2', 'A');

        // Action
        $this->game->setPlayer(0);
        $this->game->setBoard('1,0', 'S');

        // Assert
        self::assertTrue($this->game->hasWon($this->game->getBoard()));
    }

    public function test_Player_Black_Has_Won_The_Game()
    {
        // Initialize
        $this->game->setPlayer(0);
        $this->game->setBoard('0,2', 'B');
        $this->game->setBoard('0,1', 'Q');
        $this->game->setBoard('0,0', 'B');
        $this->game->setBoard('1,1', 'S');
        $this->game->setBoard('-1,1', 'A');
        $this->game->setBoard('-1,2', 'A');

        // Action
        $this->game->setPlayer(1);
        $this->game->setBoard('1,0', 'S');

        // Assert
        self::assertTrue($this->game->hasWon($this->game->getBoard()));
    }

    public function test_Draw_Game()
    {
        // Initialize
        $this->game->setPlayer(0);
        $this->game->setBoard('0,0', 'Q');
        $this->game->setBoard('-1,0', 'B');
        $this->game->setBoard('1,-1', 'A');
        $this->game->setBoard('0,-1', 'B');
        $this->game->setBoard('-1,1', 'A');

        $this->game->setPlayer(1);
        $this->game->setBoard('0,1', 'Q');
        $this->game->setBoard('-1,2', 'B');
        $this->game->setBoard('0,2', 'B');
        $this->game->setBoard('1,1', 'A');

        //Action
        $this->game->setPlayer(0);
        $this->game->setBoard('1,0', 'A');

        // Assert
        self::assertTrue($this->game->hasWon($this->game->getBoard()));
    }

    public function test_Undo_When_There_Are_No_Pieces_Played()
    {
        // Initialize
        $this->game->setPlayer(0);

        // Action
        $this->game->undo();

        // Assert
        self::assertEquals('Cannot undo when there are no pieces on the board', $this->game->getError());
    }

    public function test_When_There_Are_No_Actions_To_Undo()
    {
        // Initialize
        $this->game->setPlayer(0);
        $this->game->setBoard('0,0', 'Q'); // cannot undo Q piece without other pieces placed

        // Action
        $this->game->undo();

        // Assert
        self::assertEquals('No action to undo', $this->game->getError());
    }
}
