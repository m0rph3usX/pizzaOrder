<!--function move() {
    var elem = document.getElementById("myBar");
    var width = 10;
    var id = setInterval(frame, 10);
    
    var dateTime = new Date;
    var dateTimeEnd = new Date;
    
    dateTime = dateTime - dateTime.getHours() - dateTime.getMinutes() - dateTime.getSeconds() - dateTime.getMilliseconds();    
    dateTime.getMonth() = new Date('Jul 15 01:30:00 2001');

    this.setTime(this.getTime() + (h*60*60*1000)); 
    
    function frame() {
        datetime = Date.now();
        if (width >= 100) {
            clearInterval(id);
        } else {
            width++;
            elem.style.width = width + '%';
            elem.innerHTML   = width * 0.1 + '%';
//            elem.innerHTML   = datetime;
        }
    }
} 
</script>-->

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
    $sql = "SELECT supplier_ID FROM orders WHERE state < 3";
    foreach ($db->query($sql) as $row) {       
        $supplier_ID = $row['supplier_ID'];       
    }    
    return $supplier_ID;
}

function getUserWhoIsOrdering()
{
    include 'config.php';
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT user_ID FROM orders WHERE state < 3";
    foreach ($db->query($sql) as $row) {       
        $userId = $row['user_ID'];       
    }    
    return $userId;
}


function getCurrentSupplierName()
{
    include 'config.php';
    
    $supplier_Name = '';
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT supplier.name FROM supplier, orders WHERE supplier.id = orders.supplier_ID";

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
    $sql = "SELECT state FROM orders WHERE state < 3";
    

    $orderState = 0;
    
    if(is_array($db->query($sql)) || is_object($db->query($sql))){
        foreach ($db->query($sql) as $row) {
            $orderState = $row['state'];
        }                
    }
    
    return $orderState;
}

function getCurrentOrderId()
{
    include 'config.php';
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT id, state FROM orders WHERE state < 3";
    
    $orderId = 0;
    if(is_array($db->query($sql)) || is_object($db->query($sql))){
        foreach ($db->query($sql) as $row) {
            $orderId = $row['id'];
        }
    }
    
    return $orderId;
}

function showOrderRefreshed()
{
    include 'config.php';
    
    $userid = $_SESSION['userid'];
    
    echo "<div class='orderRefreshed'>";
    if(isset($_GET['ordering'])) { 
        
       // Bestellung entgegen nehmen 
        if(isset($_POST['supplierCard_ID']))
        {
            include 'config.php';

            $order_ID = $_POST['supplierCard_ID'];

            $db = new PDO('sqlite:' . $datenbank);    
            $sql = "SELECT * FROM orderDetail WHERE user_ID = " . $userid;

            $counter = 0;
            foreach ($db->query($sql) as $row) {
                $counter++;
            }
            
            
            $orderId = getCurrentOrderId();
            $price   = $_POST['supplierCard_price'];
            
            $supplierID = getCurrentSupplierId();
            $db-> exec("INSERT INTO orderDetail 
                        (order_ID, supplierCard_ID, supplier_ID, user_ID, price)                         
                       VALUES ( " .
                       $orderId . ",". $order_ID . ",". $supplierID ." , " .$userid . "," . $price . ")");
            echo "<center>Die Bestellung wurde angenommen<br></center>";
        }
    }

    if(isset($_POST['finish']))
    { 
        if($userid == getUserWhoIsOrdering())
        {
            $db = new PDO('sqlite:' . $datenbank);         
            
            $sql = "UPDATE cntrl SET `value`= 12.15 WHERE `type` ='arrivalInfo'";
            $db-> exec($sql);
                    
            $orderId = getCurrentOrderId();
//            $db-> exec("UPDATE cntrl SET `value` = 2 WHERE `type` = 'orderState'");
            $db-> exec("UPDATE orders SET `state` = 2 WHERE `id` = " . $orderId );
            echo "Bestellung wurde geschlossen! <br>";
        }

    }
    if(isset($_POST['orderKill']))
    {            
        include 'config.php';

        $order_ID = $_POST['orderKill'];
        $db = new PDO('sqlite:' . $datenbank);    
        $sql = "DELETE FROM orderDetail WHERE orderDetail.id = " . $order_ID;      
        
        $db-> exec($sql);        
        echo "<center>Die Bestellung wurde storniert<br></center>";
    }
    
    if(isset($_POST['orderUpdateCommentID']))
    {            
        include 'config.php';
              

        $order_ID = $_POST['orderUpdateCommentID'];
        $comment  = $_POST['orderUpdateCommentTxt'];
        $db = new PDO('sqlite:' . $datenbank);              
        
        $comment = str_replace(' ', '#$#', $comment);
        
        $sql = "UPDATE orderDetail SET comment = '". $comment ."' WHERE ( orderDetail.id = ". $order_ID . " )";
        $db-> exec($sql);
         echo "<center>Kommentar wurde gespeichert<br></center>";
    }
    
    if(isset($_POST['restart']))
    { 
        
        $db = new PDO('sqlite:' . $datenbank);         
  
        $db-> exec("INSERT INTO orders
                      (supplier_ID, user_ID, state)                         
                       VALUES ( " .
                       "0, " . $userid . ", 1 )");
        
        echo "Bestellung wurde neu gestartet! <br>";
    }
    
    if(isset($_POST['orderPaidStorno']))
    { 
      include 'config.php';

      $order_ID = $_POST['orderId'];
      $db = new PDO('sqlite:' . $datenbank);              
        
      
        
      $sql = "UPDATE orderDetail SET isPaid = 0 WHERE ( orderDetail.id = ". $order_ID . " )";
      $db-> exec($sql);
      echo "<center>Bezahlstatus storniert<br></center>";  
    }
    
    if(isset($_POST['orderPaid']))
    { 
      include 'config.php';

      $order_ID = $_POST['orderId'];
      $db = new PDO('sqlite:' . $datenbank);              
        
      
        
      $sql = "UPDATE orderDetail SET isPaid = 1 WHERE ( orderDetail.id = ". $order_ID . " )";
      $db-> exec($sql);
      echo "<center>Bestellung als bezahlt markiert <br></center>";  
    }    
                           
    echo "</div>";
}

function showOrderStarted()
{
    include 'config.php';
    $userid = $_SESSION['userid'];
    
//    if(isset($_GET['pollSubmit'])) { 
//        // neue Bestellung anlegen
//        $db = new PDO('sqlite:' . $datenbank);         
//        $supplierID = $_POST['supplier'];
//
//        $db-> exec("UPDATE cntrl SET `value` = 1 WHERE `type` = 'orderState'");
//
//        $db-> exec("UPDATE supplier SET `active` = 0");
//        $db-> exec("UPDATE supplier SET `active` = 1 WHERE id = " . $supplierID);
//
//        $db-> exec( "UPDATE cntrl SET `value`= ". $userid ." WHERE `type` ='userWhoIsOrdering'");
//
//        echo "Bestellung wurde gestartet! <br>";
//    }    
    

    
        if(isset($_GET['pollSubmit'])) { 
            // neue Bestellung anlegen
            $db = new PDO('sqlite:' . $datenbank);         
            $supplierID = $_POST['supplier'];
            $orderId = getCurrentOrderId();

//            $db-> exec("UPDATE orders SET `supplier_ID` = " .$supplierID ." WHERE `id` = ". $orderId);
            
            $db-> exec("INSERT INTO orders
                        (supplier_ID, user_ID, state)                         
                       VALUES ( " .
                       $supplierID . ",". $userid . ", 1 )");           
            echo "Bestellung wurde gestartet! <br>";
        }    
}

function orderNotStarted()
{
    include 'config.php';
    $orderState = getOrderState();
    if($orderState == 0){                                   
        // select supplier
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
           echo "<div class='orderList'>";
           
           $orderId = getCurrentOrderId();
           
           $sql = "SELECT supplier.id, supplier.name FROM supplier, orders WHERE orders.id = " .$orderId;

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
                  echo "Preis";
               echo "</span>"; 
           echo "</div>";

           foreach ($db->query($sql) as $row) {               
               echo "<div class='orderItem'>";   
                   echo "<form action='?ordering=1' method='post'>";
                       echo "<span class='orderItemButton'>";                        
                          echo "<input type='submit' value='bestellen'    name='supplierCard_ID'/>";
                          echo "<input type='hidden' value=".$row['id']." name='supplierCard_ID'/>";
                          echo "<input type='hidden' value=".$row['price']." name='supplierCard_price'/>";
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
                          echo number_format($row['price'] , 2) . " €";
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
    
//    $sql = "SELECT value FROM  cntrl WHERE cntrl.type = 'userWhoIsOrdering'";
    $sql = "SELECT user_ID FROM  orders WHERE orders.state < 3";

    foreach ($db->query($sql) as $row) {
//        $oderUserId = $row['value'];
        $oderUserId = $row['user_ID'];
    }

    $sql = "SELECT login FROM user WHERE id = " . $oderUserId;

    foreach ($db->query($sql) as $row) {
        $oderUserName = $row['login'];
    }

    $orderCounter = 0;
    $priceCounter = 0;

    $orderId = getCurrentOrderId();

    $sql = "SELECT user.id AS user_ID, orderDetail.isPaid, user.login, orderDetail.id AS order_ID, orderDetail.supplierCard_ID, orderDetail.comment, supplierCard.nr, supplierCard.name, orderDetail.price FROM orders, ((orderDetail INNER JOIN user ON orderDetail.user_ID = user.id) INNER JOIN supplierCard ON orderDetail.supplierCard_ID = supplierCard.ID) WHERE orders.id = orderDetail.order_ID AND orders.id = " .$orderId ;

    echo "<div class='currentOrder'>";            
          echo "<div class='currentOrderRow'>";                   
          echo "<center>Bestellung bei ' ". $supplierName . " ' wurde von ". $oderUserName . " gestartet.</center><br>";

          echo "<center>aktuelle Bestellungen: </center><br>";                  
          echo "</div>";

          echo "<div class='currentOrderRow'>";                                       
              echo "<span class='currentOrderBlank'>";                       
              echo "</span>";

              echo "<span class='orderIsPaid'>";
                  echo "Status";
              echo "</span>";
                
              echo "<span class='currentOrderNr'>";
                  echo "Nr.";
              echo "</span>";

              echo "<span class='currentOrderBlank'>";                       
              echo "</span>";
              
              echo "<span class='currentOrderName'>";
                echo "Beschreibung";
              echo "</span>";  

              echo "<span class='currentOrderLogin'>";
                echo "Besteller";
              echo "</span>";

              echo "<span class='currentOrderComment'>";
                echo "Kommentar";
              echo "</span>";  
                
              echo "<span class='currentOrderPrice'>";
                  echo "Preis";
              echo "</span>";  

          echo "</div>";

        



        class supplierNr {
            public $nr;
            public $count;
            public $name;
            public $comment;            
        }
        
        $nrList  = array();      
        $iCounter = 0;

        $nrObject = new supplierNr();
        $nrObject->nr    =  0;
        $nrObject->count =  0;
        array_push($nrList, $nrObject);
                        
        
        foreach ($db->query($sql) as $row) {     
            
            
//            var_dump($row);
            $orderCounter = $orderCounter + 1;
            $priceCounter = $priceCounter + doubleval(str_replace(',','.', $row['price']));            
            $isPaid = $row['isPaid'];
            $supplierCardNr =  $row['nr'];
            
            
            $comment = $row['comment'];
            $comment = str_replace('#$#', ' ', $comment);
                  
            // count all same supplier-nr together

            $max = sizeof($nrList);

            for ($i = 0; $i < $max; $i++) {
                if(($nrList[$i]->nr      === $supplierCardNr) && 
                   ($nrList[$i]->comment === $comment )){
                    $nrList[$i]->count = $nrList[$i]->count +1;
                    break;
                }

                if($i === ($max -1)){                        
                    $nrObject = new supplierNr();

                    $nrObject->nr      =  $supplierCardNr;
                    $nrObject->count   =  1;
                    $nrObject->comment =  $comment;
                    $nrObject->name  =  $row['name'];;
                    array_push($nrList, $nrObject);
                }
            }
        
            echo "<div class='currentOrderRow'>";                                       
                echo "<span class='currentOrderBlank'>"; 
                  if(($userid == $row['user_ID']) && ($oderstate == 1))
                  {
                     echo "<form action='' method='post'>";
                        echo "<input type='submit' value='stornieren'name='orderKill'/>";
                        echo "<input type='hidden' value=".$row['order_ID']." name='orderKill'/>";                         
                     echo "</form>";
                  }                    
                echo "</span>";

                echo "<span class='orderIsPaid'>";                                           
                    if($userid == getUserWhoIsOrdering()){
                        if($isPaid == 1){
                           echo "<form action='' method='post'>";
                           echo "<input type='submit' value='OFFEN'name='orderPaidStorno'/>";
                           echo "<input type='hidden' value=".$row['order_ID']." name='orderId'/>";                         
                           echo "</form>";
                        }
                        else {
                           echo "<form action='' method='post'>";
                           echo "<input type='submit' value='BEZAHLT'name='orderPaid'/>";
                           echo "<input type='hidden' value=".$row['order_ID']." name='orderId'/>";                         
                           echo "</form>";
                        }                        
                    }
                    else{
                        if($isPaid == 1){
                            echo "BEZAHLT";
                        }
                        else{
                            echo "OFFEN";
                        }
                            
                    }
                echo "</span>";
              
                echo "<span class='currentOrderNr'>";
                    echo $supplierCardNr;
                echo "</span>";

                echo "<span class='currentOrderBlank'>";                       
                echo "</span>";
              
                echo "<span class='currentOrderName'>";
                    echo $row['name'];
                echo "</span>";  
                
                echo "<span class='currentOrderLogin'>";
                    echo $row['login'];                        
                echo "</span>";

                echo "<span class='currentOrderComment'>";                  
//                  echo $comment;
                  if(($userid == $row['user_ID']) && ($oderstate == 1))
                  {
                     echo "<form action='' method='post'>";                     
                        echo "<input type='text' name='orderUpdateCommentTxt' value='" . $comment . "' >";
                        echo "<input type='submit' value='speichern'name='updateComment'/>";                       
                        echo "<input type='hidden' value=".$row['order_ID'] ." name='orderUpdateCommentID'/>";
                     echo "</form>";                     
                  }
                  else   
                  {
                    echo $comment;
                  }
                echo "</span>";  
                
                echo "<span class='currentOrderPrice'>";
                    echo number_format($row['price'] , 2) . " €";
                echo "</span>";             

            echo "</div>";
       
            
            $iCounter = $iCounter +1;                        
            
        }


        // show sum 
        echo "<br>";
        echo "<div class='currentOrder'>";            
            echo "<span class='sumUpTxt'>";                
                echo $orderCounter . " Bestellung(en), Summe gesamt = "        ;
            echo "</span>";
            
            echo "<span class='sumUpPrice'>";
                echo number_format($priceCounter , 2) . " €"; 
            echo "</span>";
        echo "</div>";
        
        
        
        // show discount
        
        
        // comprimized order        
        $max = sizeof($nrList);
        
        echo "<center>";
        sort($nrList);
        echo "<br> Zusammenfassung der Bestellung: <br>";
                       
        for ($i = 01; $i < $max; $i++) {
            if($nrList[$i]->count > 0){
                echo "<div class='currentOrder'>";
                    echo "<span class='sumUpOrder'>";                
                        echo $nrList[$i]->count  ."x Nr. " .   $nrList[$i]->nr . "   -   " . $nrList[$i]->name . " " . $nrList[$i]->comment;
                    echo "</span>";
                echo "</div>";
            
                
            }
        }
        
        echo "</center>";    
                
//        echo "<div id='myProgress'>";
//            echo "<div id='myBar'></div>";
//        echo "</div>";
    
//        ?> <script type="text/javascript">move()</script> <?php
        
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
            echo "<input type='hidden' value='' name='supplierCard_ID'/>";
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
?>

