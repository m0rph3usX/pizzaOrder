<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css">
</head> 

<?php

function createNewDB($user, $passwordHash)
{
 include 'config.php';
 include 'utils.php';

 $db = new PDO('sqlite:' . $datenbank);
 
 $db->beginTransaction();
 
 $db-> exec("CREATE TABLE `user` (      
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `login` varchar(255) NOT NULL,
      `password` varchar(255) NOT NULL,      
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL,
      `isAdmin`	INTEGER DEFAULT 0,
       UNIQUE (`login`))");  
   
  $db-> exec("CREATE TABLE `supplier` (      
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `name` varchar(255) NOT NULL,
      `active` INTEGER)");
  
  $db-> exec("CREATE TABLE `orders` (      
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `order_ID` INTEGER,
      `supplier_ID` INTEGER,
      `user_ID` INTEGER,
      FOREIGN KEY(supplier_ID) REFERENCES supplier(id),
      FOREIGN KEY(user_ID) REFERENCES user(id))"); 
  
  $db-> exec("CREATE TABLE `supplierCard` (      
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `supplier_ID` INTEGER,            
      `nr` INTEGER,
      `name` varchar(255),
      `ingredients` varchar(255),
      `price` DOUBLE,
       FOREIGN KEY(supplier_ID) REFERENCES supplier(id))"); 
             
  $db-> exec("CREATE TABLE `cntrl` (      
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `type` varchar(255),            
      `value` TEXT)"); 
  
   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
              'orderState',0)"); 
  
   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
              'regIsAllowed',1)");
        
   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
              'userWhoIsOrdering',0)");
   
   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
              'arrivalInfo',' ')");
   
   
   $db->exec("INSERT INTO user (login, password, isAdmin) VALUES ('$user', '$passwordHash', 1)");
// $result = $statement->execute(array('login' => $login, 'password' => $password_hash));
 
   $supplierID = 1;
    // öffnen des Verzeichnisses
    if ( $handle = opendir('./src/') )
    {                
        // einlesen der Verzeichnisses
        while (($file = readdir($handle)) !== false)
        {    
//            sleep(1);
            flush();
            usleep(1);
            
            // Nur Dateien lesen
            if($file != "." AND $file != ".."){
                
                echo "<br> lese Datei <br>" . $file;                        
                 
                // Supplier erzeugen
                $supplier = str_replace(".txt", "", $file);
                $db-> exec("INSERT INTO `supplier` (name, active)
                            VALUES ('".$supplier."', 0)");                              
  
                // Speisekarte anlegen
                $handleFile = fopen("src/" . $file, "r");
                if ($handleFile) {
                    while (($line = fgets($handleFile)) !== false) {
                        
                        echo "#";
                        flush();
                        usleep(1);                                
                        // process the line read.
                        $line = utf8_encode($line);                         
                        $splitted = explode(";", $line);
                            
                        $db-> exec("INSERT INTO `supplierCard` (      
                            supplier_ID,
                            nr,
                            name,
                            ingredients,
                            price) 
                            VALUES(" . $supplierID.  "," . $splitted[0]. ",'" . $splitted[1]. "','" . $splitted[2]. "','" . $splitted[3]. "')");                                                                                                 
                    }

                    fclose($handleFile);
                    $supplierID++;
                } 
            }
        }
        closedir($handle);
    }
    
    
    $db->commit();    
   
 
 // Schreibrechte überprüfen
 if (!is_writable($datenbank)) {
  // Schreibrechte setzen
  chmod($datenbank, 0777);
 }
 
 echo 'Datenbank erstellt!';
}


function showFirstSetup()
{
    
}

function showControlGrid()
{
    include 'config.php';
//    include 'utils.php';

    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $datenbank);
    
    $sql = "SELECT * FROM cntrl";

    foreach ($db->query($sql) as $row) {
        $currentType = $row['type'];
        $currentValue = $row['value'];

        switch ($currentType)
        {
            case 'orderState':
                $dbOrderState = $currentValue; 
                break;
            case 'regIsAllowed':
                $dbRegIsAllowed = $currentValue;
                break;            
            case 'userWhoIsOrdering':
                $dbUserWhoIsOrdering = $currentValue;
                break;   
            default:
                break;
        }
    }
        
    echo "<div class='adminCntrlRow'>";                                       
        echo "<span class='adminCntrlItem'>";                       
            echo 'Status der aktuellen Bestellung';
        echo "</span>";

        echo "<span class='adminCntrlItem'>";
            echo 'Registrierung erlaubt';
        echo "</span>";

        echo "<span class='adminCntrlItem'>";
          echo "Aktueller Besteller";
        echo "</span>";  
    echo "</div>";    
    
    echo "<div class='adminCntrlRow'>";                                       
        echo "<span class='adminCntrlItem'>";                       
            echo $dbOrderState;
        echo "</span>";

        echo "<span class='adminCntrlItem'>";
            echo $dbRegIsAllowed;
        echo "</span>";

        echo "<span class='adminCntrlItem'>";
          echo $dbUserWhoIsOrdering;
        echo "</span>";  
    echo "</div>";  
    
}

function showUsers()
{
    include 'config.php';
//    include 'utils.php';

    class MyStruct {
        public $userId;
        public $isAdmin;
    }
    
    
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $datenbank);
    $dbExec = new PDO('sqlite:' . $datenbank);
    
    $sql = "SELECT * FROM user";
    

    
    echo "<div class='adminCntrlRow'>"; 
    echo "<form action='?storeDb' method='post'>";
   
    
    $idx = 0;
    foreach ($db->query($sql) as $row) {
        $userId   = $row['id'];
        $userName = $row['login'];    
        $userAdminRight = $row['isAdmin'];

        $obj = new MyStruct();
        $obj->userId = $userId;
        $obj->isAdmin= $userAdminRight;

        $array[$idx] = $obj;
            
        echo "<div class='currentOrderRow'>"; 
        echo "<span class='adminCntrlUserId'>";                       
            echo $userId;
        echo "</span>";
        
        echo "<span class='adminCntrlUserName'>";                       
            echo $userName;
        echo "</span>";
        
        echo "<span class='adminCntrlUserIsAdmin'>";                                   
            echo "<span class='orderItemButton'>";                        
                if($userAdminRight == 1)
                {
                    echo "<input type='checkbox' value=$userId name='user[]' checked='checked'/>";
                }
                else
                {
                    echo "<input type='checkbox' value=$userId name='user[]'/>";
                }           
            echo "</span>";
                       
        echo "</span>";
       
        echo "</div>"; 
        
        $idx++;
    }
    echo "</div>"; 


        
    echo "<input type='submit' value='speichern' name='storeDb' />";       


        
    echo "</form>"; 
}

function showAdminPanel()
{
//    echo "BLA5";
    include 'config.php';
    include 'utils.php'; 
    
//    echo "BLA6";
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $datenbank);
    
    if (isset ($_POST['storeDb']))
    {
        if (isset ($_POST['user']))
        {            
            $sql = "UPDATE user SET `isAdmin` = 0";
            
            $db-> exec($sql);
             foreach ($_POST['user'] as $value) {
                $sql = "UPDATE user SET `isAdmin` = 1 WHERE `id` = $value";
                $db-> exec($sql);
             }   
        }
        
        echo "<div class=''>";         
            echo "Daten gespeichert";
        echo "</div>";        
    }
//    echo "BLA7";
    if(isAdmin() == 1)
    {

//    echo "BLA8";
       showControlGrid();
       
//       echo "BLA9";
       showUsers();
//       echo "BLA10";
    }
     
// 
//    $db-> exec("CREATE TABLE `user` (     
}








function create_progress() {
  // First create our basic CSS that will control
  // the look of this bar:
  echo "
<style>
#text {
  position: absolute;
  top: 100px;
  left: 50%;
  margin: 0px 0px 0px -150px;
  font-size: 18px;
  text-align: center;
  width: 300px;
}
  #barbox_a {
  position: absolute;
  top: 130px;
  left: 50%;
  margin: 0px 0px 0px -160px;
  width: 304px;
  height: 24px;
  background-color: black;
}
.per {
  position: absolute;
  top: 130px;
  font-size: 18px;
  left: 50%;
  margin: 1px 0px 0px 150px;
  background-color: #FFFFFF;
}

.bar {
  position: absolute;
  top: 132px;
  left: 50%;
  margin: 0px 0px 0px -158px;
  width: 0px;
  height: 20px;
  background-color: #0099FF;
}

.blank {
  background-color: white;
  width: 300px;
}
</style>
";

  // Now output the basic, initial, XHTML that
  // will be overwritten later:
  echo "
<div id='text'>Script Progress</div>
<div id='barbox_a'></div>
<div class='bar blank'></div>
<div class='per'>0%</div>
";

  // Ensure that this gets to the screen
  // immediately:
  flush();
}

// A function that you can pass a percentage as
// a whole number and it will generate the
// appropriate new div's to overlay the
// current ones:

function update_progress($percent) {
  // First let's recreate the percent with
  // the new one:
  echo "<div class='per'>{$percent}
    %</div>\n";

  // Now, output a new 'bar', forcing its width
  // to 3 times the percent, since we have
  // defined the percent bar to be at
  // 300 pixels wide.
  echo "<div class='bar' style='width: ",
    $percent * 3, "px'></div>\n";

  // Now, again, force this to be
  // immediately displayed:
  flush();
}

function showAdminUserData()
{
    include 'config.php'; 
    if(isset($_GET['createNewDb'])) {
        
    
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
    


    //Keine Fehler, wir können den Nutzer registrieren
    if(!$error) { 
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

//    echo "CREATENEWDB";
    createNewDB($login,$password_hash);
    
    $db = new PDO('sqlite:' . $datenbank);
    $statement = $db->prepare("INSERT INTO user (login, password, isAdmin) VALUES (:login, :password, 1)");
    $result = $statement->execute(array('login' => $login, 'password' => $password_hash));
    } 
   }


    echo "<div class=''>";                                       
        echo "<span class=''>";                       
            echo "Neue Datenbank erstellen";
            
            echo "<form action='?createNewDb=1' method='post'>";
                echo "Admin Login Name:<br>";
                    echo "<input type='text' size='40' maxlength='250' name='login'><br><br>"; 
                echo "Passwort:<br>";
                    echo "<input type='password' size='40'  maxlength='250' name='password'><br>"; 
                echo "Passwort wiederholen:<br>";
                    echo "<input type='password' size='40' maxlength='250' name='password2'><br><br>";
 
                echo "<input type='submit' value='Datenbank erstellen'>";
            echo "</form>";                                        
        echo "</span>";
    echo "</div>";                                               
}



//function main()
//{
include 'config.php';
//include 'utils.php';
// Datenbank-Datei erstellen
if (!file_exists($datenbank)) {
 //echo "BLA1";
 showAdminUserData();       
 //echo "BLA2";
     

}
else {
 // Verbindung
// $db = new PDO('sqlite:' . $datenbank);
// echo 'Datenbank existiert bereits!';

// showUserLogin();
 //echo "BLA3";
 showAdminPanel();    
 //echo "BLA4";
}



?>