<?php
session_start();
$u;
if (isset($_POST["user"])) {
    $u = $_POST["user"];
    $_SESSION["user"] = $u;
    header("Location: http://192.168.178.190/wzrd/lobby.php");
}
if (isset($_POST["offline"])) {
    session_destroy();
    header("Location: http://192.168.178.190/wzrd/login.php");
}
$u = $_SESSION["user"];
echo <<<html
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="styles.css" type="text/css" />
<meta content="width=device-width, initial-scale=1" name="viewport" />
</head>
<body>
<div class="wrapper">
<div id="onlinePlayers">
<h1 id="hUser">$u</h1>
<form action="lobby.php" method="post"><input type="submit" name="offline" value="logout" id="logoutBtn"></form>
<p id="onlineTxt">Online:</p>
<div id="showOnlinePlayers">
</div>
</div>
<div id="chat">
<p id="titelChat">Chat</p>
<ul id="messages"></ul>
<input type="text" id="writeText"><button id="sendChatBtn" onclick="writeChat()">Senden</button>
<form action="lobby.php" method="post">
</form>
</div>
<div id="topMid"><button id="join" onclick="joinGame(this)">Join</button>
<button id="leave" onclick="leaveSit(this)">leave</button>
<h1>wzrd</h1>
</div>
<div id="topRight"></div>
<div id="gameArea">
<div onclick="showPointsMobile" id="pointsBtn"></div>
<div id="stichArea">
<p class="stichTxt">Stich</p>
<div class="stichGrpBtn">
<button class="stichBtn" onclick="sendStechen(this)" value=0>0</button>
<button class="stichBtn"  onclick="sendStechen(this)" value=1>1</button>
<button class="stichBtn" onclick="sendStechen(this)" value=2>2</button>
<button class="stichBtn" onclick="sendStechen(this)" value=3>3</button>
<button class="stichBtn" onclick="sendStechen(this)" value=4>4</button>
</div>
<div class="stichGrpBtn"> 
<button class="stichBtn" onclick="sendStechen(this)" value=5>5</button>
<button class="stichBtn" onclick="sendStechen(this)" value=6>6</button>
<button class="stichBtn" onclick="sendStechen(this)" value=7>7</button>
<button class="stichBtn" onclick="sendStechen(this)" value=8>8</button>
<button class="stichBtn" onclick="sendStechen(this)" value=9>9</button>
</div>
</div>
<div id="trumpfCardDiv"></div>
<div id="sitz1" class="sitze"></div>
<div id="sitz2" class="sitze"></div>
<div id="sitz3" class="sitze"></div>
<div id="sitz4" class="sitze"></div>
<div id="sitz5" class="sitze"></div>
<div id="sitz6" class="sitze"></div>
<div id="playedCard"></div>
</div>
<div id="handCards"></div>
<div id="points">
<p id="pointsTxt">Points</p>
<div id="pointsRound">Round</div>
<div id="pp1"></div>
<div id="pp2"></div>
<div id="pp3"></div>
<div id="pp4"></div>
<div id="pp5"></div>
<div id="pp6"></div>
</div>
</div>
<script src="script.js"></script>
</body>
</html>
html;

/*echo "<div id='playerChair1'</div>";
echo "<div id='playerChair2'</div>";
echo "<div id='playerChair3'</div>";
echo "<div id='playerChair4'</div>";
echo "<div id='playerChair5'</div>";
echo "<div id='playerChair6'</div>";*/
?>
