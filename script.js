//#################### classes ##########################
function Me(n, rid) {
    this.status;
    this.name = n;
    this.rId = rId;
    this.isRdy = false;
    this.handCards = new Array();
}

function Cards(nr, c) {
    this.number = nr;
    this.color = c;
}

function Game() {
    this.players = new Array();
    this.round = 1;
    this.giver;
    this.giverName;
    this.stechenTurn;
    this.stechenTurnName;
    this.stechenAnz;
    this.gestochen;
    this.playersTurn;
    this.winnerLasTRound;
    this.trumpfCard;

}

function Player(n, rId) {
    this.status;
    this.name = n;
    this.rId = rId;
    this.isRdy = false;
    this.handCards = new Array();
}

function Lobby() {
    this.players = {};
    this.sittingPlayers = {};
    this.rdy = 0;
    this.game;
    this.pointsInEachRound = {};
    this.gestochen = {};

}

var conn = new WebSocket('ws://192.168.178.190:8080');


conn.onopen = function (e) {
    console.log("Connection established!");

    conn.send(JSON.stringify({ "newPlayer": document.getElementById("hUser").textContent }));
};

var lobby;
var me;
var minPlayer = 2;

conn.onmessage = function (e) {
    console.log(JSON.stringify(JSON.parse(e.data), null, 2));
    let data = JSON.parse(e.data);
    let command = Object.keys(data)[0];

    switch (command) {
        case "me":
            me = data.me;
            if (me.handCards && me.handCards.length > 1) {
                me.handCards.sort(function (a, b) {
                    return a.number - b.number;
                });
            }
            // showMyCards();

            break;
        case "lobby":

            lobby = new Lobby();
            let players = data.lobby.players;
            lobby.sittingPlayers = data.lobby.sittingPlayers;
            lobby.rdy = data.lobby.rdy;
            lobby.playedCardThisRounds = data.lobby.playedCardThisRounds;
            lobby.pointsInEachRound = data.lobby.pointsInEachRound;
            for (let i in players) {
                let p = new Player(players[i].name, players[i].rId);
                lobby.players[p.name] = p;
            }
            showOnlinePlayers();

            if (lobby.sittingPlayers) {
                showSittingPlayers();
            }

            break;
        case "leave":
            lobby = data.lobby;
            break;
        case "chat":
            pasteInChat(data);
            break;
        case "startGame":
            lobby = data.startGame;
            //if (lobby.game && lobby.game.trumpfCard) {

            showSittingPlayers();
            setTimeout(() => {
                giveCardsAnim();
            }, 1000);

            showTrumpfCard();
            //}

            initPointsTable();
            //if (lobby.game && lobby.game.playedCardThisRounds) {
            //}

            for (let i = 0; i < lobby.game.players.length; i++) {
                let n = lobby.game.players[i]["name"];  //player name color white = default
                document.getElementById("sit" + n).style.color = "white";
            }

            break;
        case "stechen":
            lobby = data.stechen;
            if (lobby.game && lobby.game.stechenTurnName != null) {
                let p = document.getElementById("sit" + lobby.game.stechenTurnName);
                let sitParent = p.parentElement;
                sitParent.style.border = "1px solid pink";
            } else {
                for (let i = 0; i < lobby.game.players.length; i++) {
                    let p = document.getElementById("sit" + lobby.game.players[i].name);
                    let sitParent = p.parentElement;
                    sitParent.style.border = "1px solid black";
                }
            }
            break;
        case "stechenErg":
            lobby = data.stechenErg;
            //if (lobby.game && lobby.gestochen) {
            showPlayerStich();
            for (let i = 0; i < lobby.game.players.length; i++) {
                let p = document.getElementById("sit" + lobby.game.players[i].name);
                let sitParent = p.parentElement;
                sitParent.style.border = "1px solid black";
            }
            //}
            break;

        case "loop":
            console.log("ok bin drin");

            if (data.loop == "") {
                console.log("erste if");
                conn.send(JSON.stringify({ "stechen": "" }));
            }

            if (data.loop == "stechen") {
                console.log("ok i bims mit stechen dran!");
                showStechen();
            }
            break;
        case "playSeq":
            console.log("gogogogogo");
            if (lobby.game && lobby.game) {
                if (data.playSeq.showPlayerTurn) {
                    lobby = data.playSeq.showPlayerTurn;
                    showPlayerTurnBorder();
                }
            }
            if (data.playSeq == "") {
                playCard();
            }
            break;
        case "playCard":
            lobby = data.playCard;
            showPlayedCards();
            playThisCardAnim();
            break;
        case "nextRound":
            setTimeout(() => {
                
                console.log("nextRound");
                /*lobby = new Lobby();
                lobby.players = data.nextRound.players;
                lobby.sittingPlayers = data.nextRound.sittingPlayers;
                lobby.rdy = data.nextRound.rdy;
                lobby.game = data.nextRound.game;
                lobby.pointsInEachRound = data.nextRound.pointsInEachRound;
                showOnlinePlayers();
                //lobby = data.nextRound;
    
                /*document.getElementById("trumpfCard").remove();
                if (lobby.game && lobby.game.trumpfCard) {
                   showTrumpfCard();
               }*/
                lobby = new Lobby();
                let playerss = data.nextRound.players;
                lobby.sittingPlayers = data.nextRound.sittingPlayers;
                lobby.rdy = data.nextRound.rdy;
                lobby.playedCardThisRounds = data.nextRound.playedCardThisRounds;
                lobby.pointsInEachRound = data.nextRound.pointsInEachRound;
                lobby.game = data.nextRound.game;
                for (let i in playerss) {
                    let p = new Player(playerss[i].name, playerss[i].rId);
                    lobby.players[p.name] = p;
                }

                conn.send(JSON.stringify({ "startGame": lobby.game }));
            }, 5000);
            break;
        case "deletePlayedCards":
            lobby.game.playedCardThisRounds = [];
            if (lobby.game.playedCardThisRounds.length <= 0) {
                setTimeout(function () {
                    const myNode = document.getElementById("playedCard");
                    while (myNode.lastElementChild) {
                        myNode.removeChild(myNode.lastElementChild);
                    }
                }, 5000);

            }
            break;
        case "deletePlayedCardInDrawPhase":
            lobby = data.deletePlayedCardInDrawPhase.lobby;
            let winnerName = data.deletePlayedCardInDrawPhase.winner;
            let pctr = lobby.game.playedCardThisRounds;
            giveWinnerCardsPoints(winnerName, pctr);

            break;
        case "deleteCardInDrawPhase":
            lobby.game.playedCardThisRounds = [];
            if (lobby.game.playedCardThisRounds.length <= 0) {
                setTimeout(function () {
                    const myNode = document.getElementById("playedCard");
                    while (myNode.lastElementChild) {
                        myNode.removeChild(myNode.lastElementChild);
                    }
                }, 5000);

            }
            break;
        case "showPlayerPoints":
            lobby = data.showPlayerPoints;
            setTimeout(function () {
                showPlayerPoints();
            }, 3000);
            break;
        case "userAlreadyLogged":
            window.location.replace("http://192.168.178.190/wzrd/login.php");
            alert("User already online!");
            break;

    }



};

function startTimer() {
    timer.start();
    setTimeout(stopTimer, 5000);
}

function stopTimer() {
    timer.stop();
    console.log("stop");
}

//#################### GAME ############################
function gameLogic(data) {
    let game = data.lobby.game;
    console.log("Round: " + game.round);
    var players = new Array();
    for (const key in game.players) {
        let player = new Player();
        let p = game.players[key];
        player = p;

        for (let i = 0; i < game.round; i++) {
            player.handCards.push(game.cards.pop());
        }
        game.players[key] = player;
    }
}


//#################### functions ########################

function showOnlinePlayers() {
    let divOnlinePlayers = document.getElementById("showOnlinePlayers");
    while (divOnlinePlayers.firstChild) {
        divOnlinePlayers.firstChild.remove();
    }

    for (let i in lobby.players) {
        let p = document.createElement("p");
        p.setAttribute("class", "onlinePlayersTxt");
        let ptxt = document.createTextNode(lobby.players[i].name);
        p.appendChild(ptxt);
        divOnlinePlayers.appendChild(p);
    }
    let user = document.getElementById("hUser").textContent;
    btn = document.getElementById("join");
    if (user in lobby.sittingPlayers) {

        if (lobby.game && lobby.game.players.length) {
            btn.disabled = "disabled";
            btn.textContent = "Spiel läuft";
        } else {
            if (lobby.sittingPlayers[user].isRdy) {
                btn.setAttribute("onclick", "notRdyGame(this)");
                btn.textContent = "or nah?"
            } else {
                btn.setAttribute("onclick", "rdyGame(this)");
                btn.textContent = "rdy?"
            }
        }


    } else {
        if (lobby.game && lobby.game.players.length) {
            btn.disabled = "disabled";
            btn.textContent = "Spiel läuft";
        }
    }




}

onkeydown = function (e) {

    if (document.getElementById("stichArea").style.display != "block") {
        document.getElementById("writeText").focus();
        if (document.getElementById("writeText").value != "") {
            if (e.keyCode == 13) {
                document.getElementById("sendChatBtn").click();
                return false;
            }
            return true;
        }
    }
}

function writeChat() {
    let i = 0;
    $j = {
        "who": document.getElementById("hUser").textContent,
        "msg": document.getElementById("writeText").value
    }
    conn.send(JSON.stringify({ "chat": $j }));
    document.getElementById("writeText").value = "";
}


function pasteInChat(data) {
    let li = document.createElement("li");
    let txt = document.createTextNode(data.chat.who + ": " + data.chat.msg);
    li.appendChild(txt);
    //if me then green bg
    if (data.chat.who == document.getElementById("hUser").textContent) {
        li.setAttribute("class", "chatMe");
    }
    if (data.chat.who == "admin") {
        li.setAttribute("class", "chatAdmin");
    }

    document.getElementById("messages").appendChild(li);

    var msg = document.getElementById("messages");
    msg.scrollTop = msg.scrollHeight;


}

function joinGame(data) {
    lobby.sittingPlayers[me.name] = me;
    console.log(lobby.sittingPlayers);
    conn.send(JSON.stringify({ "sit": me }));

    data.setAttribute("onclick", "rdyGame(this)");
    data.textContent = "rdy?";

}

function rdyGame(data) {
    lobby.rdy++;
    lobby.sittingPlayers[document.getElementById("hUser").textContent].isRdy = true;
    me = lobby.sittingPlayers[document.getElementById("hUser").textContent];
    console.log(lobby.rdy + " ? lobby.players.length " + Object.keys(lobby.players).length);
    if (lobby.rdy == Object.keys(lobby.players).length && Object.keys(lobby.players).length >= minPlayer) {
        data.disabled = "disabled";
        btn.textContent = "Spiel läuft";
        lobby.game = new Game();
        // lobby.game.players = lobby.sittingPlayers;
        for (const key in lobby.sittingPlayers) {
            lobby.game.players.push(lobby.sittingPlayers[key]);
        }
        console.log("game start");
        conn.send(JSON.stringify({ "startGame": lobby.game }));
    }
    data.setAttribute("onclick", "notRdyGame(this)");
    data.setAttribute("class", "rdyGame");
    data.textContent = "or nah?";
    conn.send(JSON.stringify({ "rdy": { "rdyCount": lobby.rdy, "who": me } }));
}

function notRdyGame(data) {
    lobby.rdy--;
    lobby.sittingPlayers[document.getElementById("hUser").textContent].isRdy = false;
    me = lobby.sittingPlayers[document.getElementById("hUser").textContent];
    console.log(lobby.rdy);
    data.setAttribute("onclick", "rdyGame(this)");
    data.setAttribute("class", "notRdyGame");
    data.textContent = "rdy?";
    conn.send(JSON.stringify({ "rdy": { "rdyCount": lobby.rdy, "who": me } }));
}

function leaveSit(data) {
    document.getElementById("sit" + me.name).parentElement.style.backgroundColor = "rgba(255, 255, 255, 0.102)";
    document.getElementById("sit" + me.name).remove();
    conn.send(JSON.stringify({ "leave": me }));
    document.getElementById("join").setAttribute("onclick", "joinGame(this)");
    document.getElementById("join").textContent = "join";
    sitzPlatz -= 1;
}


function showMyCards() {
    let dhandCards = document.getElementById("handCards");
    let fourteen = 0;
    let zero = 0;
    for (let i = 0; i < me.handCards.length; i++) {
        let newDivCard = document.createElement("div");
        let txtNr;
        if (me.handCards[i].number == "14") {
            txtNr = document.createTextNode("Z");
        } else {
            txtNr = document.createTextNode(me.handCards[i].number);
        }
        if (!(document.getElementById(me.handCards[i].number + me.handCards[i].color))) {
            let txtDiv = document.createElement("div");
            txtDiv.setAttribute("class", "handCardTxt");
            txtDiv.appendChild(txtNr);
            newDivCard.appendChild(txtDiv);

            if (me.handCards[i].number == "14") {
                newDivCard.setAttribute("id", me.handCards[i].number + me.handCards[i].color + fourteen);
                fourteen++;
            } else if (me.handCards[i].number == "0") {
                newDivCard.setAttribute("id", me.handCards[i].number + me.handCards[i].color + zero);
                zero++;
            } else {
                newDivCard.setAttribute("id", me.handCards[i].number + me.handCards[i].color);
            }

            newDivCard.setAttribute("class", "handCard");
            //newDivCard.style.opacity = 0;
            setTimeout(() => {
                //newDivCard.style.opacity = 1;
            }, 1000);
            newDivCard.setAttribute("data-cardnr", me.handCards[i].number);
            newDivCard.setAttribute("data-cardcolor", me.handCards[i].color);


            if (me.handCards[i].number == "0") {
                newDivCard.style.backgroundColor = "grey";
            } else if (me.handCards[i].number == "14") {
                newDivCard.style.backgroundColor = "purple";

            } else {
                newDivCard.style.backgroundColor = me.handCards[i].color;
            }
            dhandCards.appendChild(newDivCard);
        }
    }
}

function showStechen() {
    //var stechen = prompt("Gebe eine Zahl ein: ");
    document.getElementById("stichArea").style.display = "block";

    //siehe oben onkeydown function ^^^^^^

}

function sendStechen(data) {
    conn.send(JSON.stringify({ "stechenErg": { "who": me.name, "nr": data.value } }));
    document.getElementById("stichArea").style.display = "none";
}

function showTrumpfCard() {
    if (document.getElementById("trumpfCard")) {
        document.getElementById("trumpfCard").remove();
    }

    if (!document.getElementById("trumpfCard")) {
        let divTrumpfCard = document.getElementById("trumpfCardDiv");

        let newDivCard = document.createElement("div");
        newDivCard.style.opacity = 0;

        let txtNr = document.createTextNode(lobby.game.trumpfCard.number);
        let txtDiv = document.createElement("div");


        txtDiv.appendChild(txtNr);
        newDivCard.appendChild(txtDiv);
        newDivCard.setAttribute("id", "trumpfCard");
        newDivCard.style.backgroundColor = lobby.game.trumpfCard.color;
        divTrumpfCard.appendChild(newDivCard);

        setTimeout(() => {
            document.getElementById("trumpfCard").style.opacity = 1;
        }, 1000);
    }


}

function showPlayedCards() {
    let playedCardDiv = document.getElementById("playedCard");

    for (let i = 0; i < lobby.game.playedCardThisRounds.length; i++) {
        let arr = lobby.game.playedCardThisRounds[i];
        let k = Object.keys(arr);
        console.log(k + " - " + arr[k].nr + " - " + arr[k].color);

        if (!document.getElementById(arr[k].nr + arr[k].color + "played") && !document.getElementById(arr[k].nr + k + "played")) {
            let newDivCard = document.createElement("div");
            if (arr[k].nr == "0") {

                newDivCard.setAttribute("id", arr[k].nr + k + "played");
            } else {
                newDivCard.setAttribute("id", arr[k].nr + arr[k].color + "played");
            }
            newDivCard.setAttribute("class", "playedCards");


            newDivCardAndName = document.createElement("div");
            newDivCardAndName.setAttribute("class", "cardAndName");
            let divTxt = document.createElement("div");
            divTxt.setAttribute("class", "cardAndNameName");
            nameTxt = document.createTextNode(k);
            divTxt.appendChild(nameTxt);
            newDivCardAndName.appendChild(divTxt);

            let txtNr;
            if (arr[k].nr == "14") {
                txtNr = document.createTextNode("Z");
            } else {
                txtNr = document.createTextNode(arr[k].nr);
            }

            let txtDiv = document.createElement("div");


            txtDiv.appendChild(txtNr);
            newDivCard.appendChild(txtDiv);
            if (arr[k].nr == "0") {
                newDivCard.style.backgroundColor = "grey";
            } else if (arr[k].nr == "14") {
                newDivCard.style.backgroundColor = "purple";

            } else {
                newDivCard.style.backgroundColor = arr[k].color;
            }
            newDivCardAndName.appendChild(newDivCard);
            playedCardDiv.appendChild(newDivCardAndName);


        }
    }




}

function playCard() {
    let cards = document.getElementsByClassName("handCard");

    for (let i = 0; i < cards.length; i++) {
        let cn = cards[i].className;
        cards[i].setAttribute("onclick", "playThisCard(this)");
        cards[i].setAttribute("class", cn + " playCard");

    }

}

function playThisCard(data) {
    console.log(data.dataset.cardnr + " - " + data.dataset.cardcolor);
    conn.send(JSON.stringify({ "playSeq": { "who": me, "card": { "nr": data.dataset.cardnr, "color": data.dataset.cardcolor } } }));
    data.remove();
    let cards = document.getElementsByClassName("handCard");
    for (let i = 0; i < cards.length; i++) {
        cards[i].disabled = true;
        cards[i].removeAttribute("onclick");
        cards[i].classList.remove("playCard");
    }

}

function playThisCardAnim() {
    //let id = setInterval(anim, 500);
    let counter = 0;
    let lastPos = lobby.game.playedCardThisRounds.length - 1;
    let name = Object.keys(lobby.game.playedCardThisRounds[lastPos]);
    let gc = document.getElementById("sit" + name);

    let cloneGc = document.createElement("div");
    let txt = document.createTextNode("W");
    cloneGc.appendChild(txt);
    cloneGc.style.zIndex = "0";
    cloneGc.style.width = "20px";
    cloneGc.style.height = "32px";
    cloneGc.style.backgroundColor = "rgba(63, 33, 112, 0.561)";
    cloneGc.style.left = gc.offsetLeft + "px";
    cloneGc.style.top = gc.offsetTop + "px";
    cloneGc.style.opacity = 1;
    cloneGc.setAttribute("class", "playTestAnim");
    setTimeout(() => {
        cloneGc.style.opacity = 0;

    }, 1000);

    document.getElementById("gameArea").appendChild(cloneGc);

    let cardNr = lobby.game.playedCardThisRounds[lastPos][name]["nr"];
    let cardColor = lobby.game.playedCardThisRounds[lastPos][name]["color"];

    let target = document.getElementById(cardNr + cardColor + "played");
    let xT = target.offsetLeft + ((target.offsetWidth / 2) - (cloneGc.offsetWidth / 2));
    let yT = target.offsetTop; + ((target.offsetHeight / 2) - (cloneGc.offsetHeight / 2));

    let xE = cloneGc.offsetLeft;
    let yE = cloneGc.offsetTop;

    cloneGc.style.left = xE + "px";
    cloneGc.style.top = yE + "px";

    cloneGc.style.left = xT + "px";
    cloneGc.style.top = yT + "px";

    if (cloneGc.style.left == xT + "px" && cloneGc.style.top == yT + "px") {
        setTimeout(() => {
            cloneGc.remove();
        }, 2000);
    }


}



var sitzPlatz = 1;
function showSittingPlayers() {
    for (let i = 1; i <= 6; i++) {
        let s = document.getElementById("sitz" + i);
        if (s.childNodes.length > 0) {
            let name = s.childNodes[0].id.substr(3);
            if (!lobby.sittingPlayers[name]) {
                s.childNodes[0].remove();
            }


        }
    }

    for (let key in lobby.sittingPlayers) {
        for (let i = 1; i <= 6; i++) {
            let s = document.getElementById("sitz" + i);
            if (s.childNodes.length <= 0) {
                if (!document.getElementById("sit" + key)) {
                    let divPlayer = document.createElement("div");
                    divPlayer.setAttribute("id", "sit" + key);
                    divPlayer.setAttribute("class", "sitPlayer");
                    let divSit = document.getElementById("sitz" + i);

                    let txt = document.createTextNode(lobby.sittingPlayers[key].name);
                    divPlayer.appendChild(txt);
                    divSit.appendChild(divPlayer);
                    if (me.name == key) {
                        divSit.style.backgroundColor = "rgba(255, 255, 255, 0.274)";
                    }

                }
            }

        }



    }



    if (lobby.game && lobby.game.giverName) {
        if (!document.getElementById("sitCard" + lobby.game.giverName)) {
            let p = document.getElementById("sit" + lobby.game.giverName);
            let sitParent = p.parentElement;

            let newDivCard = document.createElement("div");
            newDivCard.setAttribute("class", "giverCard");
            newDivCard.setAttribute("id", "sitCard" + lobby.game.giverName);

            let txt = document.createTextNode("W");
            newDivCard.appendChild(txt);

            sitParent.appendChild(newDivCard);



        }
        if (document.getElementsByClassName("giverCard").length > 1) {
            let sitCards = document.getElementsByClassName("giverCard");
            for (let i = 0; i < sitCards.length; i++) {
                let idName = sitCards[i].id;
                let name = idName.substring(7);
                if (name != lobby.game.giverName) {
                    sitCards[i].remove();
                }
            }
        }

    }


}

function initPointsTable() {
    let i = 1;
    let points = document.getElementById("points");
    for (let key in lobby.sittingPlayers) {
        let pp = document.getElementById("pp" + i);
        if (pp.textContent == "") {
            let nameTxt = document.createTextNode(key);
            pp.appendChild(nameTxt);

        }
        for (let j = 3; j <= 22; j++) {
            let divPointsStich = document.createElement("div");
            divPointsStich.setAttribute("class", "pointsStich");

            let divPoints = document.createElement("div");
            divPoints.style.borderRight = "1px solid rgb(67, 93, 92)";
            divPoints.style.display = "inline-block";
            divPoints.style.width = "70%";
            divPoints.style.height = "100%";
            divPoints.setAttribute("id", "pointsRound" + (j - 2) + key);
            let divStich = document.createElement("div");
            divStich.style.display = "inline-block";
            divStich.style.width = "30%";
            divStich.style.height = "100%";
            divStich.style.borderRight = "1px solid rgb(67, 93, 92)";
            divStich.setAttribute("id", "stichRound" + (j - 2) + key);
            divStich.setAttribute("class", "stichPoints");


            divPointsStich.appendChild(divPoints);
            divPointsStich.appendChild(divStich);

            divPointsStich.style.borderBottom = "1px solid rgb(67, 93, 92)";
            divPointsStich.style.gridColumnStart = i + 1;
            divPointsStich.style.gridColumnEnd = i + 1;
            divPointsStich.style.gridRowStart = j;
            divPointsStich.style.gridRowEnd = j;

            points.appendChild(divPointsStich);
        }


        i++;
    }

    for (let i = 3; i <= 22; i++) {
        let divRound = document.createElement("div");
        divRound.style.borderRight = "1px solid rgb(67, 93, 92)";
        divRound.style.borderBottom = "1px solid rgb(67, 93, 92)";

        let txt = document.createTextNode(i - 2);
        divRound.appendChild(txt);

        divRound.style.gridColumnStart = "1";
        divRound.style.gridColumnEnd = "1";
        divRound.style.gridRowStart = i;
        divRound.style.gridRowEnd = i;

        points.appendChild(divRound);

    }



}

function showPlayerStich() {
    if (lobby && lobby.gestochen) {
        for (let key in lobby.gestochen) {
            let round = key;
            for (let name in lobby.gestochen[key]) {
                let n = name;
                if (document.getElementById("stichRound" + round + n).textContent == "") {
                    let divStich = document.getElementById("stichRound" + round + n);
                    let txtStich = document.createTextNode(lobby.gestochen[key][name]);
                    divStich.appendChild(txtStich);
                }
            }
        }
    }


}

function showPlayerPoints() {
    let count = 1;
    if (lobby && lobby.pointsInEachRound) {
        for (let i = 0; i < lobby.pointsInEachRound.length; i++) {
            for (let r in lobby.pointsInEachRound[i]) {
                let round = r;
                for (let n in lobby.pointsInEachRound[i][round]) {

                    let name = n;
                    if (document.getElementById("pointsRound" + round + name).textContent == "") {
                        setTimeout(() => {
                            let divPoints = document.getElementById("pointsRound" + round + n);
                            let txtPoints = document.createTextNode(lobby.pointsInEachRound[i][r][name]);
                            divPoints.appendChild(txtPoints);
                        }, count * 800);
                    }
                    count++;
                }


            }
        }
    }
}

function giveCardsAnim() {
    for (let i = 0; i < lobby.game.players.length; i++) {
        let id = setInterval(anim, 500);
        let counter = 0;
        let gc = document.getElementsByClassName("giverCard")[0];
        let gcName = gc.id.substr(7);

        function anim() {
            showMyCardsTest(counter);
            counter++;
            if (counter == lobby.game.round) {
                clearInterval(id);

            }
            if (gcName != lobby.game.players[i].name) {
                let cloneGc = document.createElement("div");
                let txt = document.createTextNode("W");
                cloneGc.appendChild(txt);
                cloneGc.style.width = "20px";
                cloneGc.style.height = "32px";
                cloneGc.style.backgroundColor = "rgba(63, 33, 112, 0.561)";
                cloneGc.style.left = gc.offsetLeft + "px";
                cloneGc.style.top = gc.offsetTop + "px";
                cloneGc.setAttribute("class", "testAnim");

                document.getElementById("gameArea").appendChild(cloneGc);

                let playersit = document.getElementById("sit" + lobby.game.players[i].name);
                let xT = playersit.offsetLeft + ((playersit.offsetWidth / 2) - (cloneGc.offsetWidth / 2));
                let yT = playersit.offsetTop + 20;

                let xE = cloneGc.offsetLeft;
                let yE = cloneGc.offsetTop;

                cloneGc.style.left = xE + "px";
                cloneGc.style.top = yE + "px";

                cloneGc.style.left = xT + "px";
                cloneGc.style.top = yT + "px";

                if (cloneGc.style.left == xT + "px" && cloneGc.style.top == yT + "px") {
                    setTimeout(() => {
                        cloneGc.remove();
                    }, 2000);
                }
            }
        }

    }

}

var fourteen = 0;
var zero = 0;
function showMyCardsTest(pos) {
    let dhandCards = document.getElementById("handCards");

    let newDivCard = document.createElement("div");
    let txtNr;
    if (me.handCards[pos].number == "14") {
        txtNr = document.createTextNode("Z");
    } else {
        txtNr = document.createTextNode(me.handCards[pos].number);
    }
    if (!(document.getElementById(me.handCards[pos].number + me.handCards[pos].color))) {
        let txtDiv = document.createElement("div");
        txtDiv.setAttribute("class", "handCardTxt");
        txtDiv.appendChild(txtNr);
        newDivCard.appendChild(txtDiv);

        /* if (me.handCards[pos].number == "14") {
             newDivCard.setAttribute("id", me.handCards[pos].number + me.handCards[pos].color + fourteen);
             fourteen++;
         } else if (me.handCards[pos].number == "0") {
             newDivCard.setAttribute("id", me.handCards[pos].number + me.handCards[pos].color + zero);
             zero++;
         } else {
         }*/
        newDivCard.setAttribute("id", me.handCards[pos].number + me.handCards[pos].color);

        newDivCard.setAttribute("class", "handCard");
        newDivCard.style.opacity = 0;
        setTimeout(() => {
            newDivCard.style.opacity = 1;
        }, 1000);
        newDivCard.setAttribute("data-cardnr", me.handCards[pos].number);
        newDivCard.setAttribute("data-cardcolor", me.handCards[pos].color);


        if (me.handCards[pos].number == "0") {
            newDivCard.style.backgroundColor = "grey";
        } else if (me.handCards[pos].number == "14") {
            newDivCard.style.backgroundColor = "purple";

        } else {
            newDivCard.style.backgroundColor = me.handCards[pos].color;
        }
        dhandCards.appendChild(newDivCard);
    }

}

function giveWinnerCardsPoints(n, pctr) {
    let cards = document.getElementById("handCards");
    cards.style.pointerEvents = "none";

    setTimeout(() => {
        for (let i = 0; i < pctr.length; i++) {
            let player = pctr[i];
            for (let n in player) {
                let card = player[n];
                if (card.nr == "0") {
                    document.getElementById(card.nr + n + "played").parentElement.remove();
                } else {
                    document.getElementById(card.nr + card.color + "played").parentElement.remove();
                }
            }
        }

        cards.style.pointerEvents = "auto";

    }, 2000);

}

function showPlayerTurnBorder() {
    for (let i = 0; i < lobby.game.players.length; i++) {
        let name = lobby.game.players[i]["name"];
        if (name != lobby.game.playersTurn) {
            document.getElementById("sit" + name).style.color = "white";
        }
    }

    let c = document.getElementById("sit" + lobby.game.playersTurn);
    c.style.color = "green";
}