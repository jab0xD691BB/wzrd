<?php

namespace MyApp;

require 'C:\xampp\htdocs\wzrd\vendor\autoload.php';
require_once 'C:\xampp\htdocs\wzrd\classes.php';

use Cards;
use Game;
use lobby;
use Me;
use Player;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Socket implements MessageComponentInterface
{

    protected $clients;
    private $me;
    private $meWcards;
    public $lobby;
    public $chat = false;
    private $handCards = array();
    private $cards = array();
    private $connPlayers = array();
    private $playerClients = array();
    public $ich;
    public $once = false;
    public $stechenBlock = false;
    public $winnerNameLastRound = null;
    public $winnerLasTRound = null;


    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->lobby = new lobby();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $me = $conn;

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $numRecv = count($this->clients) - 1;

        echo sprintf(
            'Connection %d sending message "%s" to %d other connection%s' . "\n",
            $from->resourceId,
            $msg,
            $numRecv,
            $numRecv == 1 ? '' : 's'
        );

        $j = json_decode($msg, true);
        reset($j);
        $command = key($j);

        switch ($command) {
            case "newPlayer":
                if (isset($j["newPlayer"])) {
                    echo "Player : " . $j["newPlayer"]  . "\n\n";

                    $p = new Player($j["newPlayer"], $from->resourceId);
                    $m = new Me($j["newPlayer"], $from->resourceId);
                    $ich = $m;
                    if (!array_key_exists($p->name, $this->lobby->players)) {
                        $this->lobby->players[$p->name] = $p;
                        if (array_key_exists($j["newPlayer"], $this->connPlayers)) {
                            $this->connPlayers[$j["newPlayer"]]["rId"] = $from->resourceId;
                            $from->send(json_encode(array("me" => $this->connPlayers[$j["newPlayer"]])));
                        } else {
                            $from->send(json_encode(array("me" => $m)));
                        }
                        $this->sendAll(json_encode(array("lobby" => $this->lobby)));
                    } else {
                        $from->send(json_encode(array("userAlreadyLogged" => $this->lobby)));
                    }
                }
                break;
            case "chat":
                if (isset($j["chat"])) {
                    echo $j["chat"]["who"] . " wrote: " . $j["chat"]["msg"] . "\n";
                    $this->sendAll(json_encode(array("chat" => $j["chat"])));
                    return;
                }
                break;
            case "lobby":
                $this->lobby->set($j["lobby"]);
                $test = json_encode(array("lobby" => $this->lobby));
                $this->sendAll($test);
                break;
            case "sit":
                $this->lobby->sittingPlayers[$j["sit"]["name"]] = $j["sit"];
                $this->sendAll(json_encode(array("lobby" => $this->lobby)));
            break;
            case "leave":
                unset($this->lobby->sittingPlayers[$j["leave"]["name"]]);
                $this->sendAll(json_encode(array("lobby" => $this->lobby)));
            break;
            case "rdy":
                $this->lobby->rdy = $j["rdy"]["rdyCount"];
                $name = $j["rdy"]["who"]["name"];
                $this->lobby->sittingPlayers[$name] = $j["rdy"]["who"];
                $this->sendAll(json_encode(array("lobby" => $this->lobby)));
                break;
            case "startGame":
                echo "startGame\n";
                if ($j["startGame"]["round"] == 1) {
                    $this->lobby->game = new Game();
                    $this->lobby->game->players = $j["startGame"]["players"];
                    $this->lobby->game->round = $j["startGame"]["round"];
                } else {

                    $this->lobby->game = new Game();
                    //$this->lobby->game = $j["startGame"];
                    $this->lobby->game->players = $j["startGame"]["players"];
                    $this->lobby->game->round = $j["startGame"]["round"];
                    $this->lobby->game->giver = $j["startGame"]["giver"];
                    /*$this->lobby->game->giverName = $j["startGame"]["giverName"];
                    $this->lobby->game->trumpfCard = $j["startGame"]["trumpfCard"];
                    $this->lobby->game->stechenTurn = $j["startGame"]["stechenTurn"];
                    $this->lobby->game->stechenTurnName = $j["startGame"]["stechenTurnName"];
                    $this->lobby->game->stechenAnz = $j["startGame"]["stechenAnz"];
                    $this->lobby->game->gestochen = $j["startGame"]["gestochen"];
                    $this->lobby->game->stecherTest = $j["startGame"]["stecherTest"];
                    $this->lobby->game->roundSeq = $j["startGame"]["roundSeq"];
                    $this->lobby->game->playedCardThisRounds = $j["startGame"]["playedCardThisRounds"];
                    $this->lobby->game->cardsThisDrawPhase = $j["startGame"]["cardsThisDrawPhase"];
                    $this->lobby->game->cardsThisRounds = $j["startGame"]["cardsThisRounds"];
                    $this->lobby->game->playersTurn = $j["startGame"]["playersTurn"];
                    $this->lobby->game->winnerLasTRound = $j["startGame"]["winnerLasTRound"];
                    $this->lobby->game->winnerNameLastRound = $j["startGame"]["winnerNameLastRound"];
                    $this->lobby->game->pointsInEachRound = $j["startGame"]["pointsInEachRound"];
                    $this->lobby->game->winnersInDrawPhase = $j["startGame"]["winnersInDrawPhase"];*/
                }
                // nur in der ersten runde, danach der gewinner overall
                if ($this->lobby->game->round == 1) {
                    $this->lobby->game->giver = rand(0, count($this->lobby->game->players) - 1);
                } else {
                    // sleep(5);
                    $this->lobby->game->giver++;
                    if ($this->lobby->game->giver >= count($this->lobby->players) - 1) {
                        $this->lobby->game->giver %= count($this->lobby->players);
                    }
                    //$this->lobby->game->giver = $this->winnerLasTRound;
                }
                $this->sendAll(json_encode(array("chat" => array("who" => "admin", "msg" => $this->lobby->game->players[$this->lobby->game->giver]["name"] . " verteilt die Karten."))));
                $this->lobby->game->giverName = $this->lobby->game->players[$this->lobby->game->giver]["name"];
                $this->lobby->game->stechenTurn = $this->lobby->game->giver + 1;
                $this->lobby->game->stechenAnz = 0;
                //$this->lobby->game->cards = $j["startGame"]["cards"];
                $game = $this->lobby->game;
                $this->cards = array();
                $this->giveCards();
                for ($i = 0; $i < count($game->players); $i++) {
                    $card = array();

                    /* TESTEN TEST 
                  
                    if (!$this->once) {
                        $test[] = array_pop($this->cards);
                        $test[] = array_pop($this->cards);
                        //$test[] = array_pop($this->cards);

                        //var_dump($test);


                        array_push($this->cards, new Cards(0, "grey"));
                        array_push($this->cards, new Cards(0, "grey"));
                        //array_push($this->cards, new Cards(0, "blue"));


                        $this->once = true;
                    }
                      */
                    for ($j = 0; $j < $game->round; $j++) {
                        $card[] = array_pop($this->cards);
                        //$c = new Cards($card["number"], $card["color"]);
                    }

                    $this->handCards[$game->players[$i]["name"]] = $card;

                    foreach ($this->clients as $client) {
                        if ($game->players[$i]["rId"] == $client->resourceId) {
                            // The sender is not the receiver, send to each client connected
                            $me = new Me($game->players[$i]["name"], $game->players[$i]["rId"]);
                            $me = $game->players[$i];
                            $me["handCards"] = $this->handCards[$game->players[$i]["name"]];
                            $this->meWcards = $me;
                            $this->ich = $me;
                            $this->connPlayers[$game->players[$i]["name"]] = $me;
                            $this->playerClients[$game->players[$i]["name"]] = $client;
                            $client->send(json_encode(array("me" => $me)));
                        }
                    }
                }
                /* TESTEN TEST 
                $testTrumpfCard = array_pop($this->cards);
                $testTrumpfCard = new Cards(12, "blue");
                */
                $this->lobby->game->trumpfCard = array_pop($this->cards);
                //$this->lobby->game->trumpfCard = $testTrumpfCard;
                /* TESTEN TEST */


                while ($game->stechenAnz < count($game->players)) {
                    if ($game->stechenTurn >= count($game->players) - 1) {
                        $game->stechenTurn %= count($game->players);
                    }
                    $game->stecherTest[] = $game->players[$game->stechenTurn];
                    $game->stechenTurn++;
                    $game->stechenAnz++;
                }
                $tmpArray = array_reverse($game->stecherTest);
                $game->stecherTest = $tmpArray;
                $game->roundSeq = $game->stecherTest;
                $this->lobby->game = $game;

                $this->sendAll(json_encode(array("startGame" => $this->lobby)));
                //echo "STECHEN: " . $game->players[$game->stechenTurn]["name"];
                //$stecher = $game->players[$game->stechenTurn]["name"];
                //$this->playerClients[$stecher]->send(json_encode(array("stechen" => "")));


                $a = $game->stecherTest[count($game->stecherTest) - 1];
                $stecher = $a["name"];
                $this->playerClients[$stecher]->send(json_encode(array("loop" => "")));
                break;
            case "stechen":
                $game = $this->lobby->game;

                $a = array_pop($game->stecherTest);
                $stecher = $a["name"];
                echo "INDEXXXXXXXX: " . $stecher . "\n";
                $this->lobby->game->stechenTurnName = $stecher;
                $this->playerClients[$stecher]->send(json_encode(array("loop" => "stechen")));
                $this->lobby->game = $game;
                $this->sendAll(json_encode(array("stechen" => $this->lobby)));



                break;
            case "stechenErg":
                $game = $this->lobby->game;
                $game->initWinnersInDraw();
                //$this->sendAll(json_encode(array("loop" => "")));
                if (!empty($j["stechenErg"]["who"])) {
                    $game->gestochen[$game->round][$j["stechenErg"]["who"]] = $j["stechenErg"]["nr"];
                    $this->lobby->gestochen[$game->round][$j["stechenErg"]["who"]] = $j["stechenErg"]["nr"];
                }
                $this->lobby->game = $game;
                if (!empty($game->stecherTest)) {

                    $a = $game->stecherTest[count($game->stecherTest) - 1];
                    $stecher = $a["name"];
                    if ($a) {
                        $this->playerClients[$stecher]->send(json_encode(array("loop" => "")));
                    }
                } else {
                    $a = $game->roundSeq[count($game->roundSeq) - 1];
                    $playCard = $a["name"];
                    $this->lobby->game->playersTurn = $playCard;
                    $this->sendAll(json_encode(array("playSeq" => array("showPlayerTurn" => $this->lobby))));
                    $this->playerClients[$playCard]->send(json_encode(array("playSeq" => "")));
                    $this->lobby->game->stechenTurnName = null;
                    $this->sendAll(json_encode(array("chat" => array("who" => "admin", "msg" => $playCard . " ist dran!"))));
                }
                $this->sendAll(json_encode(array("stechenErg" => $this->lobby)));
                break;
            case "playSeq":
                //$game = $this->lobby->game;

                //array_pop($game->roundSeq);
                $newArray = array_merge(array_splice($this->lobby->game->roundSeq, -1), $this->lobby->game->roundSeq);
                $this->lobby->game->roundSeq = $newArray;
                $this->lobby->game->cardsThisRounds++;
                $this->lobby->game->cardsThisDrawPhase++;
                $this->lobby->game->playedCardThisRounds[] = array($j["playSeq"]["who"]["name"] => $j["playSeq"]["card"]);


                $this->meWcards = $j["playSeq"]["who"];
                $this->meWcards["handCards"] = $this->removeElementWithValue($this->meWcards["handCards"], $j["playSeq"]["card"]["nr"], $j["playSeq"]["card"]["color"]);

                $this->playerClients[$this->meWcards["name"]]->send(json_encode(array("me" => $this->meWcards)));


                //$this->lobby->game = $game;


                if (count($this->lobby->game->playedCardThisRounds) > count($this->lobby->game->players)) {
                    for ($i = 0; $i < count($this->lobby->game->players); $i++) {
                        unset($this->lobby->game->playedCardThisRounds[$i]);
                    }
                    $reIndex = array_values($this->lobby->game->playedCardThisRounds);
                    $this->lobby->game->playedCardThisRounds = $reIndex;
                }




                //muss geändert werden, burr hat gewonnen trotzdem ist ayy dran ?!?!?!?

                if ($this->lobby->game->cardsThisDrawPhase == count($this->lobby->game->players) && $this->lobby->game->cardsThisDrawPhase != 0) {

                    $winner = $this->auswerten($this->lobby->game->gestochen, $this->lobby->game->playedCardThisRounds, $this->lobby->game->trumpfCard);
                    $keys = array_keys($winner);
                    $k = $keys[0];
                    $this->lobby->game->winnersInDrawPhase[$k] += 1;
                    $this->winnerNameLastRound = $k;
                    $this->sendAll(json_encode(array("chat" => array("who" => "admin", "msg" => $k . " hat gewonnen."))));
                    if ($this->lobby->game->round > 1) {
                        $this->sendAll(json_encode(array("deletePlayedCardInDrawPhase" => array("winner" => "$k", "lobby" => $this->lobby))));
                    }
                    $this->lobby->game->cardsThisDrawPhase = 0;



                    if ($this->lobby->game->cardsThisRounds == (count($this->lobby->game->players) * $this->lobby->game->round)) {
                        $this->winnerNameLastRound = null;

                        echo "alle karten gelegt\n";
                        if ($this->lobby->game->round > 1) {
                            $this->lobby->game->pointsInEachRound = $this->lobby->pointsInEachRound[$this->lobby->game->round - 2];
                        }
                        $this->lobby->game->calculateRoundPoints();
                        $this->lobby->pointsInEachRound[] = $this->lobby->game->pointsInEachRound;
                        $this->lobby->game->round++;
                        for ($i = 0; $i < count($this->lobby->game->players); $i++) {
                            $p = $this->lobby->game->players[$i];
                            if ($p["name"] == $k) {
                                $this->lobby->game->winnerLasTRound = $i;
                                $this->winnerLasTRound = $i;
                            }
                        }
                        $this->lobby->game->resetStechenGiverRoundSeq();
                        //$this->lobby->game = $game;
                        $this->sendAll(json_encode(array("deletePlayedCards" => "")));
                        $this->sendAll(json_encode(array("showPlayerPoints" => $this->lobby)));
                        //$this->sendAll(json_encode(array("lobby" => $this->lobby)));
                        $this->playerClients[$this->lobby->game->players[$this->lobby->game->winnerLasTRound]["name"]]->send(json_encode(array("nextRound" => $this->lobby)));
                    }
                }

                if ($this->lobby->game->cardsThisRounds != (count($this->lobby->game->players) * $this->lobby->game->round) && count($this->lobby->game->roundSeq) > 0) {
                    if ($this->winnerNameLastRound != null) {
                        $i = 0;
                        for ($j = 0; $j < count($this->lobby->game->players); $j++) {
                            if ($this->lobby->game->players[$j]["name"] == $this->winnerNameLastRound) {
                                $i = $j;
                                break;
                            }
                        }
                        $tmp = array();
                        $playerCount = 0;
                        while ($playerCount < count($this->lobby->game->players)) {
                            if ($i >= count($this->lobby->game->players) - 1) {
                                $i %= count($this->lobby->game->players);
                            }
                            $tmp[] = $this->lobby->game->players[$i];
                            $i++;
                            $playerCount++;
                        }
                        $this->lobby->game->roundSeq = array_reverse($tmp);
                        $l = 0;
                        $a = $this->lobby->game->roundSeq[count($this->lobby->game->roundSeq) - 1];
                        $playCard = $a["name"];
                        $this->winnerNameLastRound = null;
                        //$this->sendAll(json_encode(array("lobby" => $this->lobby)));
                        $this->lobby->game->playersTurn = $playCard;
                        $this->sendAll(json_encode(array("playSeq" => array("showPlayerTurn" => $this->lobby))));
                        $this->sendAll(json_encode(array("chat" => array("who" => "admin", "msg" => "winner: " . $playCard . " ist dran!"))));
                        $this->playerClients[$playCard]->send(json_encode(array("playSeq" => "")));
                    } else {
                        $a = $this->lobby->game->roundSeq[count($this->lobby->game->roundSeq) - 1];
                        $playCard = $a["name"];
                        //$this->sendAll(json_encode(array("lobby" => $this->lobby)));
                        $this->sendAll(json_encode(array("chat" => array("who" => "admin", "msg" => $playCard . " ist dran!"))));
                        $this->lobby->game->playersTurn = $playCard;
                        $this->sendAll(json_encode(array("playSeq" => array("showPlayerTurn" => $this->lobby))));
                        $this->playerClients[$playCard]->send(json_encode(array("playSeq" => "")));
                    }
                }

                $this->sendAll(json_encode(array("playCard" => $this->lobby)));

                break;
        }





        // echo $msg;




    }

    public function onClose(ConnectionInterface $conn)
    {
        foreach ($this->lobby->players as $obj) {
            $p = (object) $obj;
            if ($p->rId == $conn->resourceId) {
                unset($this->lobby->players[$p->name]);
                break;
            }
        }

        foreach ($this->clients as $client) {
            if ($conn !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send(json_encode(array("lobby" => $this->lobby)));
            }
        }
        $this->clients->detach($conn);


        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public $sperreBreh = false;
    public function sendAll($msg)
    {
        foreach ($this->clients as $client) {
            //if ($from !== $client) {
            // The sender is not the receiver, send to each client connected

            //}
            $client->send($msg);
        }
    }

    public function sendMe($from, $msg)
    {
        $from->send($msg);
    }

    public function giveCards()
    {
        for ($i = 0; $i < 15; $i++) {
            for ($j = 0; $j < 4; $j++) {
                switch ($j) {
                    case 0:
                        array_push($this->cards, new Cards($i, "blue"));
                        break;
                    case 1:
                        array_push($this->cards, new Cards($i, "red"));
                        break;
                    case 2:
                        array_push($this->cards, new Cards($i, "yellow"));
                        break;
                    case 3:
                        array_push($this->cards, new Cards($i, "green"));
                        break;
                }
            }
        }

        shuffle($this->cards);
    }

    function removeElementWithValue($array, $nr, $c)
    {
        $a = array_values($array);
        for ($i = 0; $i < count($a) && 0 <= count($a); $i++) {
            if ($a[$i]["number"] == $nr && $a[$i]["color"] == $c) {
                unset($a[$i]);
            }
        }
        return $a;
    }

    function auswerten($gestochArr, $pctr, $trumpf)
    {
        //UEBERALL PLAYER RETURNEN!!!
        $zauber = array();
        $color = array();
        $firstPlayedPlayer = $pctr[0];
        $firstPlayedCard = null;
        $noTrumpfNoZaubererWinner = $firstPlayedPlayer;

        foreach ($firstPlayedPlayer as $k => $v) {
            $firstPlayedCard = $firstPlayedPlayer[$k];
        }

        if ($firstPlayedCard["nr"] == "0") {
            for ($i = 1; $i < count($pctr); $i++) {
                $player = $pctr[$i];

                foreach ($player as $nkey => $cvalue) {
                    if ($player[$nkey]["nr"] != "0") {
                        $firstPlayedCard = $player[$nkey];
                        $noTrumpfNoZaubererWinner = $player;
                        break;
                    }
                }
            }
        }

        //Zauberer first
        $zero = 0;
        for ($i = 0; $i < count($pctr); $i++) {
            $player = $pctr[$i];
            foreach ($player as $nkey => $cvalue) {
                if ($player[$nkey]["nr"] == "0") {
                    $zero++;
                    if ($zero == count($pctr)) {
                        return $pctr[0];
                    }
                }

                if ($player[$nkey]["nr"] == "14") {
                    $zauber[$i] = $player;
                    return $player;
                }
            }


            foreach ($player as $nkey => $cvalue) {
                if ($player[$nkey]["color"] == $trumpf->color) {
                    $color[] = $player;
                }

                if ($player[$nkey]["color"] == $firstPlayedCard["color"] && $player[$nkey]["nr"] == $firstPlayedCard["nr"]) {
                    continue;
                }

                if ($player[$nkey]["color"] == $firstPlayedCard["color"] && $player[$nkey]["nr"] > $firstPlayedCard["nr"]) {
                    $noTrumpfNoZaubererWinner = $player;
                }
            }

            //Highest Card Color

            print_r($zauber);
            print_r($color);
        }


        //Trumpf highest
        if (!empty($color)) {
            $highestTrumpfCard = null;
            $p = null;
            for ($i = 0; $i < count($color); $i++) {
                $player = $color[$i];
                $p = $color[$i];

                foreach ($player as $nkey => $cvalue) {
                    $highestTrumpfCard[$nkey] = max(array_column($player, "nr"));
                }
            }
            if (!empty($highestTrumpfCard)) {
                asort($highestTrumpfCard);
                print_r($highestTrumpfCard);
                end($highestTrumpfCard);
                $keyName = array_keys($highestTrumpfCard);
                $winner = $keyName[count($keyName) - 1];
                foreach ($pctr as $key => $va) {
                    $k = key($va);
                    if ($k == $winner) {
                        return $va;
                    }
                }
                /*for($i = 0; $i < count($pctr) ; $i++){
                    if($pctr[$i][{$winner}] == $winner){
                        return $pctr[$i];
                    }
                }*/
                //return $highestTrumpfCard[$winner];
            }
        }


        return $noTrumpfNoZaubererWinner;
    }
}
