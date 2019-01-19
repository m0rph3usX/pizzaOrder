<?php
$utilsIncluded = 1;

session_start();


function getLogin()
{
    if(!isset($database)){
        include 'config.php';
    }

	
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $database);    
    $sql = "SELECT login FROM user WHERE id = " . $userid;

    foreach ($db->query($sql) as $row) {        
      $login = $row['login'];           
    } 
    
    return $login;
}

function input_login()
{
    if(!isset($database)){
        include 'config.php';
    }
    
    $pdo = new PDO('sqlite:' . $database); 
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

		  header("Location: index.php");		  
        }
        else
        {
          $loginSucceeded = false;
          echo "Login oder Passwort ungültig.";                      
        }			
    }
}

function input_register()
{	
    if(!isset($database)){
        include 'config.php';
    }
	
	if(isset($_GET['register'])) {

	
	 $error     = false;
	 $login     = $_POST['login'];
	 $password  = $_POST['password'];
	 $password2 = $_POST['password2'];
	 
	 $db = new PDO('sqlite:' . $database);
	 
	 if(strlen($password) == 0) {
			echo 'Bitte ein Passwort angeben<br>';
			$error = true;
		 }
		 if($password != $password2) {
			echo 'Die Passwörter müssen übereinstimmen<br>';
			$error = true;
		 }
		 
		 //check if login is already used
		 if(!$error) { 
			$statement = $db->prepare("SELECT * FROM user WHERE login = :login");
			$result = $statement->execute(array('login' => $login));
			$user = $statement->fetch();
		 
			if($user !== false) {
				echo 'Login ist bereits vergeben<br>';
				$error = true;
			} 
		 }
		 
		 //no errors, register user
		 if(!$error) { 
			$password_hash = password_hash($password, PASSWORD_DEFAULT);	 
			$isAdmin = 0;
		 
			foreach ($db->query("SELECT COUNT(*) FROM user") as $row) {   
				$count = $row[0];
			}
		 
			if($count < 1){  $isAdmin = 1; }
		 
			$statement = $db->prepare("INSERT INTO user (login, password, isAdmin) VALUES (:login, :password, $isAdmin)");
			$result = $statement->execute(array('login' => $login, 'password' => $password_hash));
		 
			if($result) { 
				echo 'Du wurdest erfolgreich registriert. <a href="login.php">Zum Login</a>';
				header("Location: index.php");		  
			} else {
				echo 'Beim Abspeichern ist leider ein Fehler aufgetreten<br>';
			}
		 } 
	}
}

function input_logout()
{
	if(isset($_GET['logout'])) {        
		if(isset($_SESSION['userid'])){
			session_destroy();            
			header("Location: index.php");		  
		}
	}
	
}


function replaceSection($splitter, $replacement, $page)
{
	$splitted = explode($splitter,$page);
	
	if(count($splitted) == 3){
		$page = $splitted[0] . $replacement . $splitted[2];	
	}
	return $page;
}

function removeSection($splitter, $page)
{
	$splitted = explode($splitter,$page);
	
	if(count($splitted) == 3){
		$page = $splitted[0] . $splitted[2];
	}
	return $page;
}

function extractSection($splitter, $page)
{
	$splitted = explode($splitter,$page);
	$page = $splitted[1];
	return $page;
}

function getCurrentSupplierId()
{    
    if(!isset($database)){
        include 'config.php';
    }
    $db = new PDO('sqlite:' . $database);    
    $sql = "SELECT supplier_ID FROM orders WHERE state < 3";
    foreach ($db->query($sql) as $row) {       
        $supplier_ID = $row['supplier_ID'];       
    }    
    return $supplier_ID;
}


function updateDatabaseToV0_6()
{
 if(!isset($database)){
    include 'config.php';
 }
 
 

 $db = new PDO('sqlite:' . $database);


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

 $db2 = new PDO('sqlite:' . $database);    
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

function updateDatabaseToV0_7(){

	 if(!isset($database)){
	    include 'config.php';
	 }
 
	$db = new PDO('sqlite:' . $database);
	$sql = "CREATE TABLE `bank` (
		`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
		`user_id_transactor`	INTEGER,
		`user_id_customer`	INTEGER,
		`amount`		DOUBLE,
		`orderDetail_id`	INTEGER,
		`timestamp`		INTEGER
		);";

	// update cntrl table
	$db-> exec($sql);
	
	$db-> exec("ALTER TABLE user ADD isBankTransactor INTEGER;");
	
	$db-> exec("UPDATE cntrl SET value = 0.7 WHERE type = 'version';");		 
}


function getVersion(){
    if(!isset($database)){
       include 'config.php';
    }
    
    $db = new PDO('sqlite:' . $database);

    $sql = "SELECT value FROM cntrl WHERE type = 'version'";

    $version = 0;
    foreach ($db->query($sql) as $row) {
        $version = $row['value'];
    }
    
    return $version;
}
function updateDatabase()
{
    $version = getVersion();
    if($version == 0){    
       updateDatabaseToV0_6();
       $version = getVersion();
    }
    
    if($version == 0.6){
	updateDatabaseToV0_7();
    }
}

function getUserWhoIsOrdering()
{
   if(!isset($database)){
        include 'config.php';
    }
    
	$userId = -1;
    $db = new PDO('sqlite:' . $database);    
    $sql = "SELECT user_ID FROM orders WHERE state < 3";
    foreach ($db->query($sql) as $row) {       
        $userId = $row['user_ID'];       
    }    
    return $userId;
}

function getCurrentSupplierName()
{
   if(!isset($database)){
        include 'config.php';
   }
    
    
    $supplier_Name = '';
    $db = new PDO('sqlite:' . $database);    
    $sql = "SELECT supplier.name FROM supplier, orders WHERE supplier.id = orders.supplier_ID";

    foreach ($db->query($sql) as $row) {       
        $supplier_Name = $row['name'];       
    }    
    return $supplier_Name;
}

function getOrderState()
{
   if(!isset($database)){
        include 'config.php';
   }
   
    $db = new PDO('sqlite:' . $database);    
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
   if(!isset($database)){
        include 'config.php';
   }
   
    $db = new PDO('sqlite:' . $database);    
    $sql = "SELECT id, state FROM orders WHERE state < 3";
    
    $orderId = 0;
    if(is_array($db->query($sql)) || is_object($db->query($sql))){
        foreach ($db->query($sql) as $row) {
            $orderId = $row['id'];
        }
    }
    
    return $orderId;
}

function eventButtonSetCurrentOrderer(){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
	
    if(isset($_POST['eventButtonSetCurrentOrderer']))
    {            
        include 'config.php';
		
		$newOrdererId = $_POST['newOrderer'];
			
 
        $db = new PDO('sqlite:' . $database);    
        $sql = "UPDATE orders SET user_ID = ".$newOrdererId ." WHERE ( id = ". getCurrentOrderId() . " )";
        $db-> exec($sql); 		
			
		header("Location: setup2.php");	
    }	
}

function eventSetUserIsBank(){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
	
    if(isset($_POST['eventSetUserIsBank']))
    {            
        include 'config.php';
		
		$loginId = $_POST['userId'];
		$value   = $_POST['isBank'];		
		$value = 1 - intval($value);
		
 
        $db = new PDO('sqlite:' . $database);    
        $sql = "UPDATE user SET isBankTransactor = ".$value ." WHERE ( id = ". $loginId . " )";
        $db-> exec($sql); 		
			
		header("Location: setup2.php");	
    }	
}

function eventSetUserIsAdmin(){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
	
    if(isset($_POST['eventSetUserIsAdmin']))
    {            
        include 'config.php';

        $loginId = $_POST['userId'];
		$value   = $_POST['isAdmin'];		
		
		$value = 1 - intval($value);
        $db = new PDO('sqlite:' . $database);    
        $sql = "UPDATE user SET isAdmin = ".$value ." WHERE ( id = ". $loginId . " )";
        $db-> exec($sql); 	
		
		header("Location: setup2.php");			
    }	
}

function eventOrderKill()
{
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
    if(isset($_POST['orderKill']))
    {            
        include 'config.php';

        $order_ID = $_POST['orderKill'];
        $db = new PDO('sqlite:' . $database);    
        $sql = "DELETE FROM orderDetail WHERE orderDetail.id = " . $order_ID;      
        
        $db-> exec($sql);     

        $sql = "DELETE FROM bank WHERE orderDetail_id = " . $order_ID;      
        
        $db-> exec($sql); 	
		
		header("Location: index.php");
    }	
	
}

function eventOrderPaid(){
   if(!isset($database)){
        include 'config.php';
   }
    if(isset($_POST['eventButtonPayOrder']))
    { 
      $order_ID = $_POST['orderId'];
      $db = new PDO('sqlite:' . $database);              

      $sql = "UPDATE orderDetail SET isPaid = 1 WHERE ( orderDetail.id = ". $order_ID . " )";
      $db-> exec($sql);   

	  header("Location: index.php");	  
    } 
	
    if(isset($_POST['eventButtonOrderStorno']))
    { 

      $order_ID = $_POST['orderId'];
      $db = new PDO('sqlite:' . $database);              
      $sql = "UPDATE orderDetail SET isPaid = 0 WHERE ( orderDetail.id = ". $order_ID . " )";
      $db-> exec($sql);
	  header("Location: index.php");
    }	
}
function eventOrderAdd(){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
        
	if(isset($_POST['eventButtonAddOrder']))
	{
		include 'config.php';

		$order_ID = $_POST['supplierCard_ID'];

		$db = new PDO('sqlite:' . $database);    
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
				   
	    header("Location: index.php");
	}
}
function eventOrderRestart(){
   if(!isset($database)){
        include 'config.php';
   }
    $userid = $_SESSION['userid'];
	
    if(isset($_POST['restart']))
    { 
		if(getOrderState() != 1){
			$db = new PDO('sqlite:' . $database);         
	  
			$db-> exec("INSERT INTO orders
						  (supplier_ID, user_ID, state)                         
						   VALUES ( " .
						   "0, " . $userid . ", 0 )");			   	    		 
	   }
	   header("Location: index.php");
    }
}

function eventOrderComment(){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
	
    if(isset($_POST['orderUpdateCommentID']))
    {            
        include 'config.php';
              

        $order_ID = $_POST['orderUpdateCommentID'];
        $comment  = $_POST['orderUpdateCommentTxt'];
        $db = new PDO('sqlite:' . $database);              
        
        $comment = str_replace(' ', '#$#', $comment);
        
        $sql = "UPDATE orderDetail SET comment = '". $comment ."' WHERE ( orderDetail.id = ". $order_ID . " )";
        $db-> exec($sql);
		
		header("Location: index.php");
    }
}

function eventOrderFinished(){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
    if(isset($_POST['finish']))
    { 
        if($userid == getUserWhoIsOrdering())
        {
            $db = new PDO('sqlite:' . $database);         
            
            $sql = "UPDATE cntrl SET `value`= 12.15 WHERE `type` ='arrivalInfo'";
            $db-> exec($sql);
                    
            $orderId = getCurrentOrderId();
            $db-> exec("UPDATE orders SET `state` = 2 WHERE `id` = " . $orderId );
        }
		header("Location: index.php");
    }
}

function eventBankInput()
{
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
    if(isset($_POST['eventButtonBankInput']))
    {       
        include 'config.php';
	
		$customer_id = $_POST['customer_id'];
		$amount      = $_POST['amount'];
	       
        $db = new PDO('sqlite:' . $database);    
        $sql = "INSERT INTO bank (`user_id_transactor`,  `user_id_customer`,     `amount`, `timeStamp`)
		VALUES 		 (" .$userid .      " , ". $customer_id ." , " . $amount  . "," . time() ." )";
        	
        $db-> exec($sql);  
		header("Location: index.php");		
    }	
}

function eventVirtualPay()
{
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];    						  
							   
    if(isset($_POST['eventVirtualPayButton']))
    {       
        include 'config.php';
	
		$orderDetail_id = $_POST['orderDetail_id'];
		$price          = $_POST['price'];
			   
			$db = new PDO('sqlite:' . $database);    
			$sql = "INSERT INTO bank (`user_id_transactor`,  `user_id_customer`,     `amount`, `timeStamp`, `orderDetail_id`)
			VALUES 		 (" .$userid .      " , ". $userid ." , " . -$price  . "," . time() ."," . $orderDetail_id .")";        	
			$db-> exec($sql);        
				
			$sql = "UPDATE orderDetail SET isPaid = 2 WHERE id = ".$orderDetail_id;
			$db-> exec($sql);        
		}	
		header("Location: index.php");
}



function showOrderStarted()
{
   if(!isset($database)){
        include 'config.php';
   }
	
	if(isset($_SESSION['userid'])){	
		$userid = $_SESSION['userid'];
		  
		if(isset($_GET['pollSubmit'])) { 
			
			$timeHH = $_POST['timeFreezeHH'];
			$timeMM = $_POST['timeFreezeMM'];
			
			$timestampNow = time();
			$timestampFreeze = $timestampNow + 3600 * $timeHH + $timeMM * 60;
					
			// create new order
			$db = new PDO('sqlite:' . $database);         
			$supplierID = $_POST['supplier'];
			$orderId = getCurrentOrderId();
		
			
			$sql = "INSERT INTO orders
						(supplier_ID, user_ID, state, timeStampStarted, timeStampFreezing)                         
					   VALUES ( " .
					   $supplierID . ",". $userid . ", 1, ". $timestampNow ." , " . $timestampFreeze . " )";
			
			$db-> exec($sql);
			echo "Bestellung wurde gestartet! <br>";
		
			header("Location: index.php");		  
		}    
	}
}

function getSupplierList()
{
   if(!isset($database)){
        include 'config.php';
   }
    
    $db = new PDO('sqlite:' . $database);    
    $sql = "SELECT id, name FROM supplier";

    $string = "<form action='?pollSubmit=1' method='post'>";

    foreach ($db->query($sql) as $row) {
        $string = $string . "<input type='radio' name='supplier' value='".$row['id']. "' CHECKED />". $row["name"] ."<br>";                            
    }
    
    return $string;
}

function createOrderTable($page)
{
   if(!isset($database)){
        include 'config.php';
   }
    $db = new PDO('sqlite:' . $database);        
	
	$userid = -1;
	if(isset($_SESSION['userid'])){
		$userid = $_SESSION['userid'];
	}
	
                   
    $orderId = getCurrentOrderId();           
    $sql = "SELECT supplier_ID, supplier.name FROM orders INNER JOIN supplier ON orders.supplier_ID = supplier.id WHERE (orders.id = " . $orderId .")";
      
    foreach ($db->query($sql) as $row) {
	    $supplier_ID = $row['supplier_ID'];
	    $supplierName = $row['name'];               
    }
                                
    $sql = "SELECT id, nr, name, ingredients, price FROM supplierCard WHERE supplier_ID = " .$supplier_ID;

	$templateRowOdd  = extractSection("<!-- order items section row odd -->", $page);	
	$templateRowEven = extractSection("<!-- order items section row even -->", $page);
	
	
	//$templateRow = $page;
	
	$page = "";
	$rowCount = 0;
	foreach ($db->query($sql) as $row) {
		if($userid > -1){
			$button = "<input type='submit' value='bestellen'       name='eventButtonAddOrder'/>
					   <input type='hidden' value=".$row['id']."    name='supplierCard_ID'/>
					   <input type='hidden' value=".$row['price']." name='supplierCard_price'/>";
	    }
		else{
			$button = "";
		}
		
		if(($rowCount % 2) > 0){
			$templateRow = $templateRowOdd;
		}
		else{
			$templateRow = $templateRowEven;
		}
		$newRow = preg_replace("/\[\%orderButton\%\]/"     ,  $button    		 			  , $templateRow);
		$newRow = preg_replace("/\[\%orderNr\%\]/"         ,  $row['nr']  		 			  , $newRow);
		$newRow = preg_replace("/\[\%orderName\%\]/"       ,  $row['name']		 			  , $newRow);
		$newRow = preg_replace("/\[\%orderIngredients\%\]/",  $row['ingredients']		      , $newRow);
		$newRow = preg_replace("/\[\%price\%\]/"		   ,  number_format($row['price'] , 2), $newRow);           
		
		$page = $page . $newRow;
		
		$rowCount = $rowCount + 1;
	}

	return $page;	
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

function createIncomingOrdersTable($page)
{
   if(!isset($database)){
        include 'config.php';
   }
    
	$userid = -1;
	if(isset($_SESSION['userid'])){
		$userid = $_SESSION['userid'];
	}
	
    $db = new PDO('sqlite:' . $database);   
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
              
    // insert countdown
    //script_countdown($timeStampFreezing);
    
    $sql = "SELECT user.id AS user_ID, orderDetail.isPaid, user.login, orderDetail.id AS order_ID, orderDetail.supplierCard_ID, orderDetail.comment, supplierCard.nr, supplierCard.name, orderDetail.price FROM orders, ((orderDetail INNER JOIN user ON orderDetail.user_ID = user.id) INNER JOIN supplierCard ON orderDetail.supplierCard_ID = supplierCard.ID) WHERE orders.id = orderDetail.order_ID AND orders.id = " .$orderId ;
    
	$page = preg_replace("/\[\%supplierName\%\]/" ,  $supplierName, $page);
	$page = preg_replace("/\[\%orderOwner\%\]/"   ,  $oderUserName, $page);
	$page = preg_replace("/\[\%timestamp\%\]/"    ,  date("d.m.Y - H:i", $timeStampStarted), $page);
    
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
                        
    
	$table = "";
	
	$templateRowOdd  = extractSection("<!-- incoming orders section row odd -->", $page);	
	$templateRowEven = extractSection("<!-- incoming orders section row even -->", $page);	
	
	$rowCount = 0;
	foreach ($db->query($sql) as $row) {     
		$orderCounter = $orderCounter + 1;
		$price = doubleval(str_replace(',','.', $row['price']));
		$priceCounter = $priceCounter + $price;            
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
	
		if(($rowCount % 2) > 0){ $newRow = $templateRowEven;}
		else{   		 $newRow = $templateRowOdd ;}
					
		//  --- show kill button if allowed ---------------------------------------------------------
		$killButton 	  = "";
		$virtualPayButton = "";
		
		if(($userid == $row['user_ID']) && ($oderstate == 1))
		{
			$killButton = "<form action='' method='post'>
						   <input type='submit' value='stornieren' name='orderKill'/>
						   <input type='hidden' value=".$row['order_ID']." name='orderKill'/>                        
						   </form>";						   				
		}
		if(($userid == $row['user_ID']) && ($isPaid < 1))
		{
			if(countMoney() >= $price){ 
				$virtualPayButton = "<form action='' method='post'>
							   <input type='submit' value='virtualPay'name='eventVirtualPayButton'/>
							   <input type='hidden' value=".$row['order_ID']." name='orderDetail_id'/>                        
							   <input type='hidden' value=".$price." name='price'/>                        
						     </form>";
			}
		}
		

			
		$newRow = preg_replace("/\[\%killOrder\%\]/"  ,  $killButton	  , $newRow);
		$newRow = preg_replace("/\[\%virtualPay\%\]/" ,  $virtualPayButton, $newRow);
		
		$rowCount = $rowCount +1;
	
		$payState = "";
		
		//  --- show order control button if allowed ---------------------------------------------------------		
		if(($userid == getUserWhoIsOrdering()) and ($isPaid < 2)){
			if($isPaid == 1){
			   $payState = "<form action='' method='post'>
							    <input type='submit' value='BEZAHLT'name='eventButtonOrderStorno'/>
							    <input type='hidden' value=".$row['order_ID']." name='orderId'/>                       
							    </form>";
			}
			else {			
			$payState = "<form action='' method='post'>
							  <input type='submit' value='OFFEN'name='eventButtonPayOrder'/>
							  <input type='hidden' value=".$row['order_ID']." name='orderId'/>
							  </form>";
			}                        
		}
		else{
			if($isPaid     ==  2){$payState = "VIRTUELL BEZAHLT";}
			else if($isPaid == 1){$payState = "BEZAHLT";}
			else		     {$payState = "OFFEN";}				
		}
		
		$newRow = preg_replace("/\[\%payState\%\]/" 	  ,  $payState	    , $newRow);					
		$newRow = preg_replace("/\[\%supplierCardNr\%\]/" ,  $supplierCardNr, $newRow);
		$newRow = preg_replace("/\[\%orderName\%\]/"      ,  $row['name']   , $newRow);
		$newRow = preg_replace("/\[\%login\%\]/"          ,  $row['login']  , $newRow);

		
		//  --- show comment control button if allowed ---------------------------------------------------------		
		if(($userid == $row['user_ID']) && ($oderstate == 1)){
				 $comment = "<form action='' method='post'>                   
								<input type='text' name='orderUpdateCommentTxt' value='" . $comment . "' >
								<input type='submit' value='speichern'name='updateComment'/>                     
								<input type='hidden' value=".$row['order_ID'] ." name='orderUpdateCommentID'/>
							 </form>";                     
	    }			  
		$newRow = preg_replace("/\[\%comment\%\]/"        ,  $comment  , $newRow);	
		$newRow = preg_replace("/\[\%price\%\]/"        ,  number_format($row['price'] , 2) . " €"  , $newRow);	
		
		$table = $table . $newRow;
		$iCounter = $iCounter +1;                        
		
	}

	
	$page   = replaceSection("<!-- incoming orders section row odd -->", $table, $page);	
	$page   = removeSection ("<!-- incoming orders section row even -->", $page);	

	$page = preg_replace("/\[\%orderCount\%\]/" ,  $orderCounter, $page);
	$page = preg_replace("/\[\%orderSum\%\]/" ,  number_format($priceCounter , 2), $page);
	 
	 
	// show discount
		   
	// comprimized order        
	$max = sizeof($nrList);
            
    sort($nrList);
               
	
	if($orderCounter > 0){
		$newRow = extractSection("<!-- incoming orders final -->", $page);	
		
		$newRowOdd  = extractSection("<!-- incoming orders final odd row-->", $page);	
		$newRowEven = extractSection("<!-- incoming orders final even row-->", $page);	
		
		$finalTable = "";
		$tableRow = "";
		for ($i = 01; $i < $max; $i++) {
			if($nrList[$i]->count > 0){
				if($i % 2 == 0) {
					$newRow = $newRowEven;
				}
				else{
					$newRow = $newRowOdd;
				}
				$tableRow = preg_replace("/\[\%finalCount\%\]/" ,  $nrList[$i]->count   , $newRow  );
				$tableRow = preg_replace("/\[\%finalNumber\%\]/" , $nrList[$i]->nr      , $tableRow);
				$tableRow = preg_replace("/\[\%finalName\%\]/"   , $nrList[$i]->name    , $tableRow);
				$tableRow = preg_replace("/\[\%finalComment\%\]/", $nrList[$i]->comment , $tableRow);
				
				$finalTable = $finalTable . $tableRow;                
			}
		}     

		$page  = replaceSection("<!-- incoming orders final -->", $finalTable, $page);	
	}
	else{
		$page  = removeSection("<!-- incoming orders sumup -->", $page);	
	}
	
	return $page;	
}


function showOrderFinish()
{
   if(!isset($database)){
        include 'config.php';
   }
       
    
    $userid = $_SESSION['userid'];

    $string = '';
    if($userid == getUserWhoIsOrdering())
    {
        $string = "<div class=''>
                   form action='?finish' method='post'>
                   <input type='submit' value='Bestellung abschließen'  name='finish'/>
                   <input type='hidden' value='' name='supplierCard_ID'/>
                   </form>
                   </div>";
    }    
    return $string;
}

function showInformationOfArrival()
{
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $database);    
    
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


function script_countdown(){

   if(!isset($database)){
        include 'config.php';
   }
	$orderId = getCurrentOrderId();
	$db = new PDO('sqlite:' . $database);   
	
    $sql = "SELECT timeStampStarted, timeStampFreezing FROM orders WHERE id = " . $orderId;
    foreach ($db->query($sql) as $row) {
        $timeEnd = $row['timeStampFreezing'];
    }
	
    $timeEnd =  $timeEnd * 1000; // convert seconds to milliseconds
    
	?>
    <script type="text/javascript">

    </script>
    <?php
}

function isAdmin()
{
   if(!isset($database)){
        include 'config.php';
   }
       
    $userid = $_SESSION['userid'];
    $db = new PDO('sqlite:' . $database);   
    
    $sql = "SELECT isAdmin FROM user WHERE user.id = $userid";

    $isAdmin = 0;
    foreach ($db->query($sql) as $row) {
        $isAdmin = $row['isAdmin'];
    }
    
    return $isAdmin;
}

function isBankTransactor()
{
   if(!isset($database)){
        include 'config.php';
   }
    
	$isBankTransactor = 0;    
    if(isset($_SESSION['userid'])){
      $userid = $_SESSION['userid'];
      $db = new PDO('sqlite:' . $database);   
	    
      $sql = "SELECT isBankTransactor FROM user WHERE user.id = $userid";

	    
      foreach ($db->query($sql) as $row) {
  	$isBankTransactor = $row['isBankTransactor'];
      }
    }
    return $isBankTransactor;
}

function addUserIdLogin($item){ 
   if(!isset($database)){
        include 'config.php';
   }
       
    $db = new PDO('sqlite:' . $database);   
    
    $sql = "SELECT id, login FROM user";

    $comboboxItems = "";
    foreach ($db->query($sql) as $row) {
	$newItem = preg_replace("/\[\%bank_customer_user_ID\%\]/" , $row['id']   , $item);
	$newItem = preg_replace("/\[\%bank_customer_login\%\]/"   , $row['login'], $newItem);
        $comboboxItems = $comboboxItems . $newItem;
    }
    
    return $comboboxItems;
}

function countMoney(){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];    
    $db = new PDO('sqlite:' . $database);   
    
    $sql = "SELECT amount FROM bank WHERE user_id_customer = " . $userid;

    $money = 0;
    foreach ($db->query($sql) as $row) {
	$money = $money + $row['amount'];	
    }
    
    return number_format($money , 2);
}
 
 
function adminSetCurrentOrderer($page){
   if(!isset($database)){
        include 'config.php';
   }
    
    $userid = $_SESSION['userid'];    
    $db = new PDO('sqlite:' . $database);   
    
    $sql = "SELECT id, login FROM user";
	
	$defaultItem = extractSection("<!-- admin current orderer item -->", $page);
	
	$currentUserIdOrdering = getUserWhoIsOrdering();
	
	$comboboxItems = "";
	$idx = 0;
	$newRow = "";
	
	$table = "";
	
    foreach ($db->query($sql) as $row) {	
			
		$selected = "";
		
		if(intval($currentUserIdOrdering) == intval($row['id'])) {
			$selected = "selected";
		}
		
		$newItem  = preg_replace("/\[\%current_orderer_user_ID\%\]/" , $row['id']   , $defaultItem);
		$newItem  = preg_replace("/\[\%current_orderer_login\%\]/"   , $row['login']   , $newItem);
		$newItem  = preg_replace("/\[\%selected\%\]/"   , $selected   , $newItem);	
		$comboboxItems = $comboboxItems . $newItem;
	   
		$idx++;
    }
	
	$page = replaceSection("<!-- admin current orderer -->", $comboboxItems, $page);
	return $page;
}

function adminShowUserData($page){
   if(!isset($database)){
        include 'config.php';
   }
    $userid = $_SESSION['userid'];    
    $db = new PDO('sqlite:' . $database);   
    
    $sql = "SELECT id, login, created_at, isAdmin, isBankTransactor FROM user";
	
	$rowOdd  = extractSection("<!-- admin user section row odd -->", $page);
	$rowEven = extractSection("<!-- admin user section row even -->", $page);
	
	$comboboxItems = "";
	$idx = 0;
	$newRow = "";
	
	$table = "";
    foreach ($db->query($sql) as $row) {
	
		if(($idx % 2) == 0){
			$newRow = $rowEven;
		}
		else{
			$newRow = $rowOdd;
		}
		
		$newRow  = preg_replace("/\[\%userLogin\%\]/" , $row['login']   , $newRow);
		
		if(intval($row['isAdmin']) == 1){$value = "TRUE" ; $setValue = 1;}
		else							{$value = "FALSE"; $setValue = 0;}
		
		if($userid != $row['id']) {
			$newRow  = preg_replace("/\[\%checkboxAdminrights\%\]/" , "<input type='submit' name='eventSetUserIsAdmin' value='".$value."' />																	
															   <input type='hidden'  value='".$row['id']."' name='userId'/>
															   <input type='hidden'  value='".$setValue."' name='isAdmin'/>"   , $newRow);
		}
		else{
			$newRow  = preg_replace("/\[\%checkboxAdminrights\%\]/" , $value    , $newRow);
		}
		

	
		
		if(intval($row['isBankTransactor']) == 1){$value = "TRUE" ; $setValue = 1;}
		else									 {$value = "FALSE"; $setValue = 0;}
		
		$newRow  = preg_replace("/\[\%checkboxIsBank\%\]/" , "<input type='submit' name='eventSetUserIsBank' value='".$value."' />
															  <input type='hidden'  value='".$row['id']."' name='userId'/>
															  <input type='hidden'  value='".$setValue."' name='isBank'/>"   , $newRow);
	
		$table = $table . $newRow; 				   
		$idx++;
    }
	
	$page = replaceSection("<!-- admin user section row -->", $table, $page);
	return $page;
}

?>



