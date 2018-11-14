<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

session_start();

function getCurrentSupplierId()
{
    include 'config.php';
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT id, name, active FROM supplier WHERE active = 1";

    foreach ($db->query($sql) as $row) {       
        $supplier_ID = $row['id'];       
    }    
    return $supplier_ID;
}

function getUserWhoIsOrdering()
{
    include 'config.php';
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT value FROM cntrl WHERE cntrl.type = 'userWhoIsOrdering'";

    foreach ($db->query($sql) as $row) {       
        $userId = $row['value'];       
    }    
    return $userId;
}


function getCurrentSupplierName()
{
    include 'config.php';
    
    $supplier_Name = '';
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT name FROM supplier WHERE active = 1";

    foreach ($db->query($sql) as $row) {       
        $supplier_Name = $row['name'];       
    }    
    return $supplier_Name;
}


function showUserLogin()
{
    include 'config.php';
    

    echo "<div class='userCntrl'>";   
    
    if(!isset($_SESSION['userid'])) {
          
      $loginSucceeded = false;
      echo "Bitte zuerst einloggen oder <a href='register.php'>registrieren</a> <br>";
      echo "<span class ='userCntrlInfo'>";        
      echo "<form action='?login=1' method='post'>";
      echo "Login: ";
      echo "<input type='text' size='25' maxlength='250' name='login'>";
      echo "    Passwort: ";
      echo "<input type='password' size='25'  maxlength='250' name='password'>";
      echo "    ";
      echo "<input type='submit' value='einloggen'>";            
         
         
      $pdo = new PDO('sqlite:' . $datenbank); 
      if(isset($_GET['login']))
      {        
        $login = $_POST['login'];
        $password = $_POST['password'];

        $statement = $pdo->prepare("SELECT * FROM user WHERE login = :login");
        $result = $statement->execute(array('login' => $login));
        $user = $statement->fetch();

        echo "<br>";
        //Überprüfung des Passworts
        if ($user !== false && password_verify($password, $user['password'])) {
          $_SESSION['userid'] = $user['id'];          
            $loginSucceeded = true;
            echo "Login erfolgreich.";
          }
          else
          {
            $loginSucceeded = false;
            echo "Login oder Passwort ungültig.";                      
          }
      }
      
      echo "</span>";
    
      echo "<span class ='userCntrlOptions'>";
      echo "</span>";

      if (!$loginSucceeded)
      {
        die;
      }
    }
    else
    {
      $userid = $_SESSION['userid'];
      $db = new PDO('sqlite:' . $datenbank);    
      $sql = "SELECT login FROM user WHERE id = " . $userid;

      
      foreach ($db->query($sql) as $row) {        
        $login = $row['login'];           
      }
    
      echo "<form action='?logout=1' method='post'>";
            
      echo "<span class ='userCntrlInfo'>";
         echo "Angemeldet als: " . $login;
      echo "</span>";
  
      echo "<span class ='userCntrlOptions'>";       
         echo "<input type='submit' value='ausloggen'>";
         echo "</form>";
      echo "</span>";
    }                     
echo "</div>";
}

function getOrderState()
{
    include 'config.php';
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT value FROM cntrl WHERE type = 'orderState'";

    $orderState = 0;
    foreach ($db->query($sql) as $row) {
        $orderState = $row['value'];
    }
    return $orderState;
}

function showOrderRefreshed()
{
    include 'config.php';
    
    $userid = $_SESSION['userid'];
    
    echo "<div class='orderRefreshed'>";
    if(isset($_GET['ordering'])) { 
       // Bestellung entgegen nehmen 
        if(isset($_POST['orderId']))
        {
            include 'config.php';

            $orderID = $_POST['orderId'];

            $db = new PDO('sqlite:' . $datenbank);    
            $sql = "SELECT * FROM orders WHERE user_ID = " . $userid;

            $counter = 0;
            foreach ($db->query($sql) as $row) {
                $counter++;
            }
            $supplierID = getCurrentSupplierId();
            if($counter < 1)
            {        
                $db-> exec("INSERT INTO orders 
                          (order_ID, supplier_ID, user_ID)
                           VALUES ( "
                          . $orderID . ",". $supplierID ." , " .$userid . " )");
                echo "<center>Die Bestellung wurde gespeichert<br></center>";
            }
            else
            {
                $sqlCmd = "UPDATE orders SET order_ID = " . $orderID . " WHERE user_ID = ".$userid;
                $db-> exec($sqlCmd);    
                echo "<center>Die Bestellung wurde aktualisiert<br></center>";
            }
        }
    }

    if(isset($_POST['finish']))
    { 
        if($userid == getUserWhoIsOrdering())
        {
            $db = new PDO('sqlite:' . $datenbank);         
            
            $sql = "UPDATE cntrl SET `value`= 12.15 WHERE `type` ='arrivalInfo'";
            $db-> exec($sql);
                        
            $db-> exec("UPDATE cntrl SET `value` = 2 WHERE `type` = 'orderState'");
            echo "Bestellung wurde geschlossen! <br>";
        }

    }
    if(isset($_POST['orderKill']))
    {            
        include 'config.php';

        $orderID = $_POST['orderKill'];
        $db = new PDO('sqlite:' . $datenbank);    

        $sql = "DELETE FROM orders WHERE orders.order_ID = " . $orderID . ' AND '.
                'orders.user_ID = ' . $userid ;
        
        
//        echo $sql;
        $db-> exec($sql);        
         echo "<center>Die Bestellung wurde storniert<br></center>";
    }
    
    if(isset($_POST['restart']))
    { 
//        if($userid == getUserWhoIsOrdering())
//        {
        $db = new PDO('sqlite:' . $datenbank);         

        $db-> exec("UPDATE cntrl SET `value` = 0 WHERE `type` = 'orderState'");
        $db-> exec("UPDATE cntrl SET `value` = '.$userid.' WHERE `type` = 'userWhoIsOrdering'");
        $db-> exec("DELETE FROM orders");
              
        echo "Bestellung wurde neu gestartet! <br>";
//        }

    }
//    else if(isset($_POST['orderComment']))
//    {
//        echo 'submit';
//    }
    echo "</div>";
}

function showOrderStarted()
{
    include 'config.php';
    $userid = $_SESSION['userid'];
    
    if(isset($_GET['pollSubmit'])) { 
        // neue Bestellung anlegen
        $db = new PDO('sqlite:' . $datenbank);         
        $supplierID = $_POST['supplier'];

        $db-> exec("UPDATE cntrl SET `value` = 1 WHERE `type` = 'orderState'");

        $db-> exec("UPDATE supplier SET `active` = 0");
        $db-> exec("UPDATE supplier SET `active` = 1 WHERE id = " . $supplierID);

        $db-> exec("UPDATE cntrl SET `value`= ". $userid ." WHERE `type` ='userWhoIsOrdering'");

        echo "Bestellung wurde gestartet! <br>";
    }    
}


function orderNotStarted()
{
    include 'config.php';
    $orderState = getOrderState();
    if($orderState == 0){                                   
        // Umfrage, wo soll bestellt werden
        echo "<div class'pollQuery'";                                                                                                                                
            echo "Keine aktiven Bestellungen.<br>";

            echo "Neue Bestellung starten?<br>";
            echo "Lieferant wählen:<br>";

            $db = new PDO('sqlite:' . $datenbank);    
            $sql = "SELECT id, name FROM supplier";

            echo "<form action='?pollSubmit=1' method='post'>";

                foreach ($db->query($sql) as $row) {
                    echo "<input type='radio' name='supplier' value='".$row['id']."'/>". $row["name"] ."<br>";                            
                }                            

                echo "<input type='submit' value='Bestellung starten'>"; 

            echo "</form>";                                                                              
        echo "</div";                                                                 
    }    
}


function showOrderItems()
{
    include 'config.php';
    $db = new PDO('sqlite:' . $datenbank);    
    $userid = $_SESSION['userid'];
    
    echo "<div class='orderItem'>";
           // Bestellformular, wer will was bestellen?                                                
           echo "<div class='orderList'">        
           $sql = "SELECT id, name, active FROM supplier WHERE `active`= 1";

           foreach ($db->query($sql) as $row) {

               $supplier_ID = $row['id'];

               $supplierName = $row['name'];
               $supplierSelect = $supplier_ID;                                
           }

           echo "<br><center> Speisekarte: </center><br>";

           $sql = "SELECT id, nr, name, ingredients, price FROM supplierCard WHERE supplier_ID = " .$supplierSelect;

           echo "<div class='orderItem'>";
               echo "<span class='orderItemButton'>";               
               echo "</span>";

               echo "<span class='orderItemNr'>";
                  echo "Nr";
               echo "</span>";                     

               echo "<span class='orderItemName'>";
                  echo "Beschreibung";
               echo "</span>";    

               echo "<span class='orderItemIngredients'>";
                  echo "Zutaten";
               echo "</span>";                     

               echo "<span class='orderItemPrice'>";
                  echo "Preis / €";
               echo "</span>"; 
           echo "</div>";

           foreach ($db->query($sql) as $row) {               
               echo "<div class='orderItem'>";   
                   echo "<form action='?ordering=1' method='post'>";
                       echo "<span class='orderItemButton'>";                        
                          echo "<input type='submit' value='bestellen'    name='orderId'/>";
                          echo "<input type='hidden' value=".$row['id']." name='orderId'/>";
                       echo "</span>";

                       echo "<span class='orderItemNr'>";
                          echo $row['nr'];
                       echo "</span>";                     

                       echo "<span class='orderItemName'>";
                          echo $row['name'];
                       echo "</span>";    

                       echo "<span class='orderItemIngredients'>";
                          echo $row['ingredients'];
                       echo "</span>";                     

                       echo "<span class='orderItemPrice'>";
//                          echo $row['price'];
//                          $price = str_replace($row['price'], ",", ".");
                          echo number_format($row['price'] , 2);
//                          echo number_format(str_replace($row['price'], ",", ".") , 2);
                       echo "</span>";
                   echo "</form>";
              echo "</div>";               
           }                    
           echo "</div>";      
}
function showIncomingOrders()
{
    include 'config.php';
    
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $datenbank);   
    $supplierName = getCurrentSupplierName();

    $oderstate = getOrderState();
    
    $sql = "SELECT value FROM  cntrl WHERE cntrl.type = 'userWhoIsOrdering'";

    foreach ($db->query($sql) as $row) {
        $oderUserId = $row['value'];
    }

    $sql = "SELECT login FROM user WHERE id = " . $oderUserId;

    foreach ($db->query($sql) as $row) {
        $oderUserName = $row['login'];
    }

    $orderCounter = 0;
    $priceCounter = 0;

    $sql = "SELECT user.id, user.login, orders.order_ID , supplierCard.nr, supplierCard.name, supplierCard.price FROM ((orders                     
                                INNER JOIN user ON orders.user_ID = user.id)
                                INNER JOIN supplierCard ON orders.order_ID = supplierCard.ID)";

    echo "<div class='currentOrder'>";            
          echo "<div class='currentOrderRow'>";                   
          echo "<center>Bestellung bei ' ". $supplierName . " ' wurde von ". $oderUserName . " gestartet.</center><br>";

          echo "<center>aktuelle Bestellungen: </center><br>";                  
          echo "</div>";

          echo "<div class='currentOrderRow'>";                                       
              echo "<span class='currentOrderBlank'>";                       
              echo "</span>";

              echo "<span class='currentOrderNr'>";
                  echo "Nr.";
              echo "</span>";

              echo "<span class='currentOrderName'>";
                echo "Beschreibung";
              echo "</span>";  

              echo "<span class='currentOrderLogin'>";
                echo "Besteller";
              echo "</span>";

              echo "<span class='currentOrderPrice'>";
                  echo "Preis / €";
              echo "</span>";  

          echo "</div>";


        foreach ($db->query($sql) as $row) {     
            $orderCounter = $orderCounter + 1;
            $priceCounter = $priceCounter + doubleval(str_replace(',','.', $row['price']));
            echo "<div class='currentOrderRow'>";                                       
                echo "<span class='currentOrderBlank'>"; 
                  if(($userid == $row['id']) && ($oderstate == 1))
                  {
                     echo "<form action='' method='post'>";
                        echo "<input type='submit' value='stornieren'name='orderKill'/>";
                        echo "<input type='hidden' value=".$row['order_ID']." name='orderKill'/>";                         
                     echo "</form>";
                  }                    
                echo "</span>";

                echo "<span class='currentOrderNr'>";
                    echo $row['nr'];
                echo "</span>";

                echo "<span class='currentOrderName'>";
                    echo $row['name'];
                echo "</span>";  

                echo "<span class='currentOrderLogin'>";
                    echo $row['login'];                        
                echo "</span>";

                echo "<span class='currentOrderPrice'>";
                    echo number_format($row['price'] , 2);
                echo "</span>";  

            echo "</div>";
        }


        echo "<center>";
        echo $orderCounter . " Bestellung(en) <br>"; 

        echo "Summe gesamt = " . number_format($priceCounter , 2) . " €<br>"; 
        echo "</center>";                
    echo "</div>";    
}

function orderRunning()
{
    include 'config.php';
       
    
    $userid = $_SESSION['userid'];

    if($userid == getUserWhoIsOrdering())
    {
        echo "<div class=''>";
            echo "<form action='?finish' method='post'>";
            echo "<input type='submit' value='Bestellung abschließen'  name='finish'/>";
            echo "<input type='hidden' value='' name='orderId'/>";
            echo "</form>";
        echo "</div>";
    }
    
    $orderState = getOrderState();
    
    showIncomingOrders();
    showOrderItems();                
}

function showInformationOfArrival()
{
    include 'config.php';
    
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $datenbank);    
    
    $sql = "SELECT value FROM cntrl WHERE `type` ='arrivalInfo'";
    
    echo "<div class='orderArrival'>";
    
    foreach ($db->query($sql) as $row) {
        $arrivalTxt = $row['value'];        
    }
    
    if(getUserWhoIsOrdering() == $userid)
    {      
      
        if(isset($_POST['saveArrival']))
        {                       
            $txt = $_POST['txtArrival'];            
            
            $sql = "UPDATE cntrl SET `value`= '".$txt. "' WHERE `type` ='arrivalInfo'";
            $db-> exec($sql);
            
            $arrivalTxt = $txt;
        }
        
        echo "<form action='?updateArrivalInfo' method='post'>";
            echo 'Bestellung kommt vorraussichtlich um:';
            echo "<input type='text' value=$arrivalTxt  name='txtArrival'/>";
            echo ' Uhr      ';
            echo "<input type='submit' value='speichern'  name='saveArrival'/>";
            echo "<input type='hidden' value=$arrivalTxt name='saveArrival'/>";
        echo "</form>";

    }
    else
    {
        echo 'Bestellung kommt vorraussichtlich um: ' . $arrivalTxt . ' Uhr';   
    }
    
    
    echo "</div>";
}
function orderFinished()
{
    include 'config.php';
    showIncomingOrders();
    showInformationOfArrival();
    
    echo "<div class=''>";
        echo "<form action='?restart' method='post'>";
        echo "<input type='submit' value='neue Bestellung starten'  name='restart'/>";
        echo "<input type='hidden' value='' name='restart'/>";
        echo "</form>";
    echo "</div>";    
}

function isAdmin()
{
    include 'config.php';
       
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $datenbank);   
    
    $sql = "SELECT isAdmin FROM user WHERE user.id = $userid";

    $isAdmin = 0;
    foreach ($db->query($sql) as $row) {
        $isAdmin = $row['isAdmin'];
    }
    
    return $isAdmin;
}

//function createNewDB()
//{
//  include 'config.php';
//  include 'utils.php';
// $db = new PDO('sqlite:' . $datenbank);
// 
// $db->beginTransaction();
// 
// $db-> exec("CREATE TABLE `user` (      
//      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
//      `login` varchar(255) NOT NULL,
//      `password` varchar(255) NOT NULL,      
//      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
//      `updated_at` timestamp NULL DEFAULT NULL,
//      `isAdmin`	INTEGER DEFAULT 0,
//       UNIQUE (`login`))");  
//   
//  $db-> exec("CREATE TABLE `supplier` (      
//      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
//      `name` varchar(255) NOT NULL,
//      `active` INTEGER)");
//  
//  $db-> exec("CREATE TABLE `orders` (      
//      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
//      `order_ID` INTEGER,
//      `supplier_ID` INTEGER,
//      `user_ID` INTEGER,
//      FOREIGN KEY(supplier_ID) REFERENCES supplier(id),
//      FOREIGN KEY(user_ID) REFERENCES user(id))"); 
//  
//  $db-> exec("CREATE TABLE `supplierCard` (      
//      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
//      `supplier_ID` INTEGER,            
//      `nr` INTEGER,
//      `name` varchar(255),
//      `ingredients` varchar(255),
//      `price` DOUBLE,
//       FOREIGN KEY(supplier_ID) REFERENCES supplier(id))"); 
//             
//  $db-> exec("CREATE TABLE `cntrl` (      
//      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
//      `type` varchar(255),            
//      `value` TEXT)"); 
//  
//   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
//              'orderState',0)"); 
//  
//   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
//              'regIsAllowed',1)");
//        
//   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
//              'userWhoIsOrdering',0)");
//   
//   $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
//              'arrivalInfo',' ')");
//   
//
//   $supplierID = 1;
//    // öffnen des Verzeichnisses
//    if ( $handle = opendir('./src/') )
//    {                
//        // einlesen der Verzeichnisses
//        while (($file = readdir($handle)) !== false)
//        {    
////            sleep(1);
//            flush();
//            usleep(1);
//            
//            // Nur Dateien lesen
//            if($file != "." AND $file != ".."){
//                
//                echo "<br> lese Datei <br>" . $file;                        
//                 
//                // Supplier erzeugen
//                $supplier = str_replace(".txt", "", $file);
//                $db-> exec("INSERT INTO `supplier` (name, active)
//                            VALUES ('".$supplier."', 0)");                              
//  
//                // Speisekarte anlegen
//                $handleFile = fopen("src/" . $file, "r");
//                if ($handleFile) {
//                    while (($line = fgets($handleFile)) !== false) {
//                        
//                        echo "#";
//                        flush();
//                        usleep(1);                                
//                        // process the line read.
//                        $line = utf8_encode($line);                         
//                        $splitted = explode(";", $line);
//                            
//                        $db-> exec("INSERT INTO `supplierCard` (      
//                            supplier_ID,
//                            nr,
//                            name,
//                            ingredients,
//                            price) 
//                            VALUES(" . $supplierID.  "," . $splitted[0]. ",'" . $splitted[1]. "','" . $splitted[2]. "','" . $splitted[3]. "')");                                                                                                 
//                    }
//
//                    fclose($handleFile);
//                    $supplierID++;
//                } 
//            }
//        }
//        closedir($handle);
//    }
//    
//    
//    $db->commit();    
//}
?>