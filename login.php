<?php 
include 'config.php';
session_start();



//$pdo = new PDO('mysql:host=localhost;dbname=php-einfach', 'root', '');
//$pdo = new PDO('sqlite:host=localhost;dbname=data.db', 'root', ''); 
$pdo = new PDO('sqlite:' . $datenbank); 
if(isset($_GET['login'])) {
 $login = $_POST['login'];
 $password = $_POST['password'];
 
 $statement = $pdo->prepare("SELECT * FROM user WHERE login = :login");
 $result = $statement->execute(array('login' => $login));
 $user = $statement->fetch();
 
 //Überprüfung des Passworts
 if ($user !== false && password_verify($password, $user['password'])) {
 $_SESSION['userid'] = $user['id'];
 die('Login erfolgreich. Weiter zu <a href="index.php">Pizza Bestellung</a>');
 } else {
 $errorMessage = "Login oder Passwort war ungültig<br>";
 }
 
}
?>
<!DOCTYPE html> 
<html> 
<head>
  <title>Login</title> 
</head> 
<body>
 
<?php 
if(isset($errorMessage)) {
 echo $errorMessage;
}
?>
 
<form action="?login=1" method="post">
Benutzer:<br>
<input type="text" size="40" maxlength="250" name="login"><br><br>
 
Dein Passwort:<br>
<input type="password" size="40"  maxlength="250" name="password"><br>
 
<input type="submit" value="Abschicken">
</form> 
</body>
</html>