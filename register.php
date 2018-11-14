<?php 
include 'config.php';
session_start();
//$pdo = new PDO('mysql:host=localhost;dbname=test', 'root', '');
//$pdo = new PDO('mysql:host=localhost;dbname=php-einfach', 'root', '');
//$pdo = new PDO('sqlite:host=localhost;dbname=data.db', 'root', ''); 
$pdo = new PDO('sqlite:' . $datenbank); 


?>
<!DOCTYPE html> 
<html> 
<head>
  <title>Registrierung</title> 
</head> 
<body>
 
<?php
$showFormular = true; //Variable ob das Registrierungsformular anezeigt werden soll
 
if(isset($_GET['register'])) {
 $error = false;
 $login = $_POST['login'];
 $password = $_POST['password'];
 $password2 = $_POST['password2'];
  
 //if(!filter_var($login, FILTER_VALIDATE_EMAIL)) {
 //echo 'Bitte eine gültige E-Mail-Adresse eingeben<br>';
 //$error = true;
 //} 
 if(strlen($password) == 0) {
 echo 'Bitte ein Passwort angeben<br>';
 $error = true;
 }
 if($password != $password2) {
 echo 'Die Passwörter müssen übereinstimmen<br>';
 $error = true;
 }
 
 //Überprüfe, ob Login bereits registriert wurde
 if(!$error) { 
 $statement = $pdo->prepare("SELECT * FROM user WHERE login = :login");
 $result = $statement->execute(array('login' => $login));
 $user = $statement->fetch();
 
 if($user !== false) {
 echo 'Login ist bereits vergeben<br>';
 $error = true;
 } 
 }
 
 //Keine Fehler, wir können den Nutzer registrieren
 if(!$error) { 
 $password_hash = password_hash($password, PASSWORD_DEFAULT);
 
 $isAdmin = 0;
 
 foreach ($pdo->query("SELECT COUNT(*) FROM user") as $row) {   
    $count = $row[0];
 }
 
 if($count < 1){  $isAdmin = 1; }
 
 $statement = $pdo->prepare("INSERT INTO user (login, password, isAdmin) VALUES (:login, :password, $isAdmin)");
 $result = $statement->execute(array('login' => $login, 'password' => $password_hash));
 
 if($result) { 
 echo 'Du wurdest erfolgreich registriert. <a href="login.php">Zum Login</a>';
 $showFormular = false;
 } else {
 echo 'Beim Abspeichern ist leider ein Fehler aufgetreten<br>';
 }
 } 
}
 
$db = new PDO('sqlite:' . $datenbank);    
$sql = "SELECT value FROM cntrl WHERE type = 'regIsAllowed'";

foreach ($db->query($sql) as $row) {
    $regActive = $row['value'];
}
    
if(($showFormular) and ($regActive == '1')) {
?>
 
<form action="?register=1" method="post">
Benutzer:<br>
<input type="text" size="40" maxlength="250" name="login"><br><br>
 
Dein Passwort:<br>
<input type="password" size="40"  maxlength="250" name="password"><br>
 
Passwort wiederholen:<br>
<input type="password" size="40" maxlength="250" name="password2"><br><br>
 
<input type="submit" value="Abschicken">
</form>
 
<?php
} //Ende von if($showFormular)
?>
 
</body>
</html>