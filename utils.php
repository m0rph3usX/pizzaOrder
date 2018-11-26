<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$utilsIncluded = 1;

session_start();

function getCurrentSupplierId()
{    
    if(!isset($datenbank)){
        include 'config.php';
    }
    $db = new PDO('sqlite:' . $datenbank);    
    $sql = "SELECT supplier_ID FROM orders WHERE state < 3";
    foreach ($db->query($sql) as $row) {       
        $supplier_ID = $row['supplier_ID'];       
    }    
    return $supplier_ID;
}


function updateDatabaseToV0_06()
{
 if(!isset($datenbank)){
    include 'config.php';
 }
 
 

 $db = new PDO('sqlite:' . $datenbank);


 // update cntrl table
 $db-> exec("INSERT INTO `cntrl` (type, value) VALUES (
              'version', 0.6)");
 
// update supplier table 
 $db-> exec("ALTER TABLE supplier ADD phoneNumber char(255);");
 $db-> exec("ALTER TABLE supplier ADD minAmount DOUBLE;");
 $db-> exec("ALTER TABLE supplier ADD discountThreshold DOUBLE;");
 $db-> exec("ALTER TABLE supplier ADD discountPercent DOUBLE;");
 
 // update orderDetail table 
 $db-> exec("ALTER TABLE orders RENAME TO orderDetail");
 $db-> exec("ALTER TABLE orderDetail ADD supplierCard_ID INTEGER;");
 $db-> exec("ALTER TABLE orderDetail ADD comment char(255);");
 $db-> exec("ALTER TABLE orderDetail ADD isPaid INTEGER;");
 $db-> exec("ALTER TABLE orderDetail ADD price DOUBLE;");
 $db-> exec("UPDATE orderDetail SET supplierCard_ID = order_ID;"); 
 
 
 // copy price to new table
 $sql = "SELECT 
                [main].[orderDetail].[id], 
                [main].[supplierCard].[price] AS [price1]
                FROM   [main].[orderDetail]
                INNER JOIN [main].[supplierCard] ON [main].[orderDetail].[supplierCard_ID] = [main].[supplierCard].[id];";

 $db2 = new PDO('sqlite:' . $datenbank);    
 foreach ($db->query($sql) as $row) {          
    $sql = "UPDATE orderDetail SET price = ". $row['price1'] . " WHERE id = " .$row['id'];    
    $db-> exec($sql);
 }    
    
 
 
  // update orderDetail table 
  $db-> exec("CREATE TABLE `orders` (      
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `supplier_ID` INTEGER,
      `user_ID` INTEGER,
      `state` INTEGER,
      `timeStampStarted` INTEGER,
      `timeStampFreezing` INTEGER,
      `timeStampReceive` INTEGER,
      FOREIGN KEY(supplier_ID) REFERENCES supplier(id),
      FOREIGN KEY(user_ID) REFERENCES user(id))");  
    
 $sql = "SELECT value FROM cntrl WHERE type = 'userWhoIsOrdering'";

 $userId = -1;
 foreach ($db->query($sql) as $row) {
     $userId = $row['value'];
 }
 $sql = "SELECT value FROM cntrl WHERE type = 'orderState'";
 $state = -1;
 
 if(is_array($db->query($sql)) || is_object($db->query($sql))){
     foreach ($db->query($sql) as $row) {
         $state = $row['value'];
    }
 }
 
 
 $sql = "SELECT id FROM supplier WHERE active = 1";
 $supplierId = -1;
 if(is_array($db->query($sql)) || is_object($db->query($sql))){
     foreach ($db->query($sql) as $row) {
        $supplierId = $row['id'];
    }
 }
 
 $db-> exec("UPDATE orderDetail SET `order_ID`= 1");  
 
 if(($supplierId != -1) && ($state != -1)){
  
    $db-> exec("INSERT INTO `orders` (supplier_ID, user_ID, state) VALUES (".
             $supplierId . " , " . $userId . " , " .  $state . ")");    
 }
  
}

function updateDatabase()
{
    if(!isset($datenbank)){
       include 'config.php';
    }
    
    $db = new PDO('sqlite:' . $datenbank);


    $sql = "SELECT value FROM cntrl WHERE type = 'version'";

    $version = 0;
    foreach ($db->query($sql) as $row) {
        $version = $row['value'];
    }

   // echo $version;
    if($version == 0){    
       updateDatabaseToV0_06();
    }
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

     
    
    if(!isset($_SESSION['userid'])) {
      
      echo "<div class='userCntrl'>";      
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
                       "0, " . $userid . ", 0 )");
        
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
  

    
    if(isset($_GET['pollSubmit'])) { 

        $timeHH = $_POST['timeFreezeHH'];
        $timeMM = $_POST['timeFreezeMM'];

                             
        $timestampNow = time();
        $timestampFreeze = $timestampNow + 3600 * $timeHH + $timeMM * 60;
                
        // neue Bestellung anlegen
        $db = new PDO('sqlite:' . $datenbank);         
        $supplierID = $_POST['supplier'];
        $orderId = getCurrentOrderId();
    
        
        $sql = "INSERT INTO orders
                    (supplier_ID, user_ID, state, timeStampStarted, timeStampFreezing)                         
                   VALUES ( " .
                   $supplierID . ",". $userid . ", 1, ". $timestampNow ." , " . $timestampFreeze . " )";
        
        $db-> exec($sql);
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
                
                echo "Countdown bis keine weiteren Bestellungen mehr angenommen werden:<br>";                
                echo "<select name='timeFreezeHH' width='100'>";

                    for ($hh = 0; $hh < 24; $hh++) {
                        if($hh < 10){
                            echo "<option value='".$hh."'>0".$hh." </option> ";                                                
                        }
                        else {
                            echo "<option value='".$hh."'>".$hh." </option> ";                                                
                        }
                        
                    }   
                echo "</select>"; 
                echo "Stunden ";
                echo "<select name='timeFreezeMM' width='100'>";
                    for ($mm = 0; $mm < 60; $mm = $mm + 5) {
                        if($mm < 10){
                            echo "<option value='".$mm."'>0".$mm." </option> ";                                              
                        }
                        else{
                                echo "<option value='".$mm."' selected='selected'>".$mm." </option> ";                                              
                        }
                    }
                echo "</select>";               
                echo "Minuten ";
                echo "<br>";
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
           // who wants what ?
           echo "<div class='orderList'>";
           
           $orderId = getCurrentOrderId();
           
           $sql = "SELECT supplier_ID, supplier.name FROM orders INNER JOIN supplier ON orders.supplier_ID = supplier.id WHERE (orders.id = " . $orderId .")";
           
           
           foreach ($db->query($sql) as $row) {

               $supplier_ID = $row['supplier_ID'];

               $supplierName = $row['name'];               
           }
                      
           
           $sql = "SELECT id, nr, name, ingredients, price FROM supplierCard WHERE supplier_ID = " .$supplier_ID;

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
                          echo number_format($row['price'] , 2) . " €";
                       echo "</span>";
                   echo "</form>";
              echo "</div>";               
           }                    
        echo "</div>";   
}

function showCountDown($timeEnd)
{
    $timeEnd =  $timeEnd * 1000; // convert seconds to milliseconds
  ?>
    <script>
    var countDownDate = '<?php echo $timeEnd ?>';

    // Update the count down every 1 second
    var x = setInterval(function() {

      // Get todays date and time
      var now = new Date().getTime();

      // Find the distance between now and the count down date
      var distance = countDownDate - now;

      // Time calculations for days, hours, minutes and seconds
      var days = Math.floor(distance / (1000 * 60 * 60 * 24));
      var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      var seconds = Math.floor((distance % (1000 * 60)) / 1000);

      // Display the result in the element with id="demo"
//      document.getElementById("countdownFreeze").innerHTML = days + "d " + hours + "h "
//      + minutes + "m " + seconds + "s ";
      document.getElementById("countdownFreeze").innerHTML = "Countdown bis bestellt wird: " + hours + "h "
      + minutes + "m " + seconds + "s ";
      
      // If the count down is finished, write some text
      if (distance < 0) {
        clearInterval(x);
        document.getElementById("countdownFreeze").innerHTML = ""; 
      }
    }, 1000);
    </script>
    <?php
}

function showIncomingOrders()
{
    include 'config.php';
    
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $datenbank);   
    $supplierName = getCurrentSupplierName();

    $oderstate = getOrderState();
    
    $sql = "SELECT user_ID FROM  orders WHERE orders.state < 3";    
            
    foreach ($db->query($sql) as $row) {
        $oderUserId = $row['user_ID'];
    }

    $sql = "SELECT login FROM user WHERE id = " . $oderUserId;

    foreach ($db->query($sql) as $row) {
        $oderUserName = $row['login'];
    }

    $orderCounter = 0;
    $priceCounter = 0;

    $orderId = getCurrentOrderId();
    
    $timeStampStarted  = 0;
    $timeStampFreezing = 0;
                
    $sql = "SELECT timeStampStarted, timeStampFreezing FROM orders WHERE id = " . $orderId;
    foreach ($db->query($sql) as $row) {
        $timeStampStarted = $row['timeStampStarted'];
        $timeStampFreezing = $row['timeStampFreezing'];
    }
    
    $sql = "SELECT user.id AS user_ID, orderDetail.isPaid, user.login, orderDetail.id AS order_ID, orderDetail.supplierCard_ID, orderDetail.comment, supplierCard.nr, supplierCard.name, orderDetail.price FROM orders, ((orderDetail INNER JOIN user ON orderDetail.user_ID = user.id) INNER JOIN supplierCard ON orderDetail.supplierCard_ID = supplierCard.ID) WHERE orders.id = orderDetail.order_ID AND orders.id = " .$orderId ;
    
    
    echo "<div class='currentOrder'>";            
          echo "<div class='currentOrderRow'>";                   
          echo "<center>Bestellung bei ' ". $supplierName . " ' wurde von ". $oderUserName . " am ". date("d.m.Y - H:i", $timeStampStarted) . " Uhr gestartet.</center><br>";
//          echo "<center>Eingangsfrist der Bestellungen: ".date("d.m.Y - H:i", $timeStampFreezing) ." Uhr</center><br>";
          echo "<center>" ?> <div id="countdownFreeze"></div>  <?php echo"</center><br>";
          
          showCountDown($timeStampFreezing);
          
//         //<!-- Progress bar holder -->
//         echo "<div id='progress' style='width:500px;border:10px solid #ccc;'></div> <br>";
//         //<!-- Progress information -->
//         echo "<div id='information' style='width'></div><br>";                           
         
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
                           echo "<input type='submit' value='BEZAHLT'name='orderPaidStorno'/>";
                           echo "<input type='hidden' value=".$row['order_ID']." name='orderId'/>";                         
                           echo "</form>";
                        }
                        else {
                           echo "<form action='' method='post'>";
                           echo "<input type='submit' value='OFFEN'name='orderPaid'/>";
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

