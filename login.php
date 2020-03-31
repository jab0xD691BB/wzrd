<?php
session_start();

echo<<<html
<!DOCTYPE html>
<html lang="de">
<head>
<title>Login</title>
</head>
<body>
<h1>wzrd</h1>
<form action="lobby.php" method="POST">
<link rel="stylesheet" href="login.css" type="text/css" />
<input type="text" name="user" placeholder="Name">
</form>
</body>
</html>


html;



?>