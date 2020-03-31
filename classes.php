<?php

class Me
{
    public $status;
    public $name;
    public $rId;
    public $isRdy = false;
    public $handCards = array();
    function __construct($n, $rid)
    {
        $this->name = $n;
        $this->rId = $rid;
    }
}

class Cards
{
    public $number;
    public $color;

    function __construct($nr, $c)
    {
        $this->number = $nr;
        $this->color = $c;
    }
}

class Game
{
    public $players = array();
    public $round;
    public $giver;
    public $giverName;
    public $trumpfCard;
    public $stechenTurn;
    public $stechenTurnName;
    public $stechenAnz;
    public $gestochen = array();
    public $stecherTest = array();
    public $roundSeq = array();
    public $playedCardThisRounds = array();
    public $cardsThisDrawPhase = 0;
    public $cardsThisRounds = 0;
    public $playersTurn;
    public $winnerLasTRound;
    public $winnerNameLastRound;
    public $pointsInEachRound = array(); //Punkte für Player z.B Round 1 => array("ayy" => 30);
    public $winnersInDrawPhase = array();



    function __construct()
    {
    }

    public function set($data)
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    function calculateRoundPoints()
    {
        $lastRound = null;
        foreach ($this->winnersInDrawPhase as $name => $wins) {
            if ($this->round == 1) {
                $points = 0;
                $stich = $this->gestochen[$this->round][$name];
                if ($stich == $wins) {
                    $points += 20;
                    $winsPunkte = $wins * 10;
                    $points += $winsPunkte;
                } else {

                    if ($wins > $stich) {
                        $ueber = $wins - $stich;
                        $points = $ueber * (-10);
                    } else {
                        $unter = $stich - $wins;
                        $points = $unter * (-10);
                    }
                }

                $this->pointsInEachRound[$this->round][$name] = $points;
            } else {
                $lastRound = (string) $this->round - 1;
                $points = $this->pointsInEachRound[$lastRound][$name];
                $stich = $this->gestochen[$this->round][$name];
                if ($stich == $wins) {
                    $points += 20;
                    $winsPunkte = $wins * 10;
                    $points += $winsPunkte;
                } else {

                    if ($wins > $stich) {
                        $ueber = $wins - $stich;
                        $points += $ueber * (-10);
                    } else {
                        $unter = $stich - $wins;
                        $points += $unter * (-10);
                    }
                }
                $this->pointsInEachRound[$this->round][$name] = $points;
            }
        }
        if($this->round > 1){
            unset($this->pointsInEachRound[$lastRound]);
        }
    }


    function resetStechenGiverRoundSeq()
    {
        $this->stechenTurn = 0;
        $this->stechenAnz = 0;
        $this->stecherTest = 0;
        //$this->giver = 0;
        $this->winnersInDrawPhase = array();
        //$this->playedCardThisRounds = array();
        $this->roundSeq = array();
    }

    function initWinnersInDraw()
    {
        for ($i = 0; $i < count($this->players); $i++) {
            $this->winnersInDrawPhase[$this->players[$i]["name"]] = 0;
        }
    }
}



class Player
{

    public $name;
    public $status;
    public $rId;
    public $isRdy = false;


    function __construct($n, $rId)
    {
        $this->name = $n;
        $this->rId = $rId;
        $this->status = "online";
    }

    public function set($data)
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}



class lobby
{

    public $players = array();
    public $sittingPlayers = array();
    public $rdy = 0;
    public $game;
    public $pointsInEachRound = array();
    public $gestochen = array();

    function __construct()
    {
    }

    public function set($data)
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}
