<?php
$utilsIncluded = 1;

class ConfigStruct {
	public $db;
	public $orderid;
	public $orderSum;
	public $orderDiscountThreshold;
	public $orderDiscountPercent;
	public $orderDiscountPaid;
	public $userid;
	public $login;
	public $isHistory;
	public $messageList = array();
	public $messageCount;
}

session_start();
include 'config.php';

function tooLateMessage() {
	addMessage( 'Leider zu spät die Deadline wurde überschritten!' );
}

function addMessage( $message ) {
	global $config;
	array_push( $config->messageList, $message );
}

function showMessages() {
	global $config;
	$messageTxt = '';

	for ( $i = 0; $i < count( $config->messageList ); $i++ ) {
		$messageTxt = $messageTxt . $config->messageList[ $i ] . '<br>';
	}

	?>
	<script>
		var js_messageTxt = '<?php echo $messageTxt;?>';
		console.log( document.getElementById( "messagetext" ).innerHTML = js_messageTxt )
	</script>
	<?php
}




function getLogin( $userid ) {
	global $config;

	$sql = "SELECT login FROM user WHERE id = " . $userid;

	$login = "";
	foreach ( $config->db->query( $sql ) as $row ) {
		$login = $row[ 'login' ];
	}
	return $login;
}



	
function input_login() {
	global $config;

	if ( isset( $_POST[ 'eventButtonLogin' ] ) ) {
		$login = $_POST[ 'login' ];
		$password = $_POST[ 'password' ];

		$statement = $config->db->prepare( "SELECT * FROM user WHERE login = :login" );
		$result = $statement->execute( array( 'login' => $login ) );
		$user = $statement->fetch();


		//Überprüfung des Passworts
		if ( $user !== false && password_verify( $password, $user[ 'password' ] ) ) {
			$_SESSION[ 'userid' ] = $user[ 'id' ];
			$loginSucceeded = true;

			addMessage( 'Login erfolgreich' );
			header( "Location: index.php" );
		} else {
			$loginSucceeded = false;
			addMessage( 'Login fehlgeschlagen. Falscher Benutzername oder falsches Passwort?!' );
		}
	}
}



function input_discountPayout(){
		
	global $config;

	if ( isset( $_POST[ 'eventButtonDiscountPayout' ] ) ) {
		
		class userInfo {
			public $id;
			public $price;
			public $discount;
		}	
		
		
		$orderInfo = getOrderDiscountInfo($config->orderid);
		
		$oderDiscountPercent = $orderInfo[1];
		$oderDiscountPaid = $orderInfo[2];
		
		if($oderDiscountPaid == 0){
			$timestamp = time();
		
			$sql = "UPDATE orders SET discountPaid = '". $timestamp  . "' WHERE id = '" . $config->orderid . "'";
			$config->db->exec( $sql );			
			
			
			$sql = "SELECT orderDetail.price, orderDetail.user_ID, orders.id
					FROM orders
       				INNER JOIN orderDetail ON orderDetail.order_ID = orders.id
					WHERE  orders.id = " . $config->orderid;

			
			$userList = array();
			
			
			foreach ( $config->db->query( $sql ) as $row ) {
				
				$user = new userInfo();
				
				$user->id       = $row[ 'user_ID' ];
				$user->price    = $row[ 'price' ];
				$user->discount = round($user->price * $oderDiscountPercent);
				$user->discount = $user->discount / 100;
				
				array_push( $userList, $user);	
			}
			
			
			for ($i=0; $i< count($userList); $i++){
				$sql = "INSERT INTO bank (`user_id_transactor`,  `user_id_customer`,     `amount`, `timeStamp`)
			    VALUES 		 (" . $config->userid . " , " . $userList[$i]->id . " , " . $userList[$i]->discount . "," . $timestamp . " )";
				
				$config->db->exec( $sql );
			}
			
			
		}				
	}
}
function input_reopenOrder() {
	global $config;

	if ( isset( $_POST[ 'eventButtonReopenOrder' ] ) ) {
		
		$timestamp = time() + 60*5;
		//$timestampFreeze = $timestampNow + 3600 * $timeHH + $timeMM * 60;
		
		$sql = "UPDATE orders SET timeStampFreezing = '". $timestamp  . "' WHERE id = '" . $config->orderid . "'";
		$config->db->exec( $sql );
		
		$sql = "UPDATE orders SET state = 1 WHERE id = '" . $config->orderid . "'";
		$config->db->exec( $sql );
				
	}
}
		
		
	
	
function input_register() {
	global $config;

	if ( isset( $_POST[ 'eventButtonRegister' ] ) ) {

		$error = false;
		$login = $_POST[ 'login' ];
		$password = $_POST[ 'password' ];
		$password2 = $_POST[ 'password2' ];


		$error = !isLoginNameValid( $login );

		if ( strlen( $password ) == 0 ) {
			addMessage( "Bitte ein Passwort angeben" );
			$error = true;
		}
		if ( $password != $password2 ) {
			addMessage( "Die Passwörter müssen übereinstimmen" );
			$error = true;
		}

		//check if login is already used
		if ( !$error ) {
			$statement = $config->db->prepare( "SELECT * FROM user WHERE login = :login" );
			$result = $statement->execute( array( 'login' => $login ) );
			$user = $statement->fetch();

			if ( $user !== false ) {
				addMessage( "Dieser Login ist bereits vergeben" );
				$error = true;
			}
		}

		//no errors, register user
		if ( !$error ) {
			$password_hash = password_hash( $password, PASSWORD_DEFAULT );
			$isAdmin = 0;

			foreach ( $config->db->query( "SELECT COUNT(*) FROM user" ) as $row ) {
				$count = $row[ 0 ];
			}

			if ( $count < 1 ) {
				$isAdmin = 1;
			}

			$statement = $config->db->prepare( "INSERT INTO user (login, password, isAdmin) VALUES (:login, :password, $isAdmin)" );
			$result = $statement->execute( array( 'login' => $login, 'password' => $password_hash ) );

			if ( $result ) {
				addMessage( "Login erfolgreich registriert" );
				//header("Location: index.php");		  
			} else {
				addMessage( "Beim Abspeichern ist leider ein Fehler aufgetreten" );
			}
		}
	}
}


function input_change_pw() {
	global $config;

	if ( isset( $_POST[ 'eventButtonChangePw' ] ) ) {
		$resetcode = $_GET[ 'resetcode' ];

		$error = false;
		$login = $_POST[ 'login' ];
		$password = $_POST[ 'password' ];
		$password2 = $_POST[ 'password2' ];



		$error = !isLoginNameValid( $login );

		//check if login is already used
		if ( !$error ) {
			$sql = "SELECT id FROM user WHERE (resetcode = '" . $resetcode . "')";

			$userid = -1;
			foreach ( $config->db->query( $sql ) as $row ) {
				$userid = $row[ 'id' ];
			}


			//$sql = "SELECT id, login FROM user WHERE login = '" . $login . "'";
			$sql = "SELECT id, login FROM user WHERE login LIKE '" . $login . "'";

			$userid_db = -1;
			foreach ( $config->db->query( $sql ) as $row ) {
				$userid_db = $row[ 'id' ];
			}

			if ( ( $userid_db != $userid ) && ( $userid_db > -1 ) ) {
				addMessage( "Dieser Login ist bereits vergeben" );
				$error = true;
			}

		}

		if ( strlen( $password ) == 0 ) {
			addMessage( "Bitte ein Passwort angeben" );
			$error = true;
		}
		if ( $password != $password2 ) {
			addMessage( "Die Passwörter müssen übereinstimmen" );
			$error = true;
		}

		//no errors, register user
		if ( !$error ) {
			$password_hash = password_hash( $password, PASSWORD_DEFAULT );

			$sql = "UPDATE user SET password = '" . $password_hash . "' WHERE resetcode = '" . $resetcode . "'";
			$config->db->exec( $sql );

			$sql = "UPDATE user SET login = '" . $login . "' WHERE resetcode = '" . $resetcode . "'";
			$config->db->exec( $sql );

			$sql = "UPDATE user SET resetcode = null WHERE resetcode = '" . $resetcode . "'";
			$config->db->exec( $sql );

			header( "Location: index.php" );
		}
	}
}


function input_logout() {
	if ( isset( $_GET[ 'logout' ] ) ) {
		if ( isset( $_SESSION[ 'userid' ] ) ) {
			session_destroy();
			header( "Location: index.php" );
		}
	}

}


function replaceSection( $splitter, $replacement, $page ) {
	$splitted = explode( $splitter, $page );

	if ( count( $splitted ) == 3 ) {
		$page = $splitted[ 0 ] . $replacement . $splitted[ 2 ];
	}
	return $page;
}

function removeSection( $splitter, $page ) {
	$splitted = explode( $splitter, $page );

	if ( count( $splitted ) == 3 ) {
		$page = $splitted[ 0 ] . $splitted[ 2 ];
	}
	return $page;
}

function extractSection( $splitter, $page ) {
	$splitted = explode( $splitter, $page );
	$page = $splitted[ 1 ];
	return $page;
}

function getCurrentSupplierId() {
	global $config;
	$sql = "SELECT supplier_ID FROM orders WHERE id = " . $config->orderid;


	foreach ( $config->db->query( $sql ) as $row ) {
		$supplier_ID = $row[ 'supplier_ID' ];
	}
	return $supplier_ID;
}


function getCurrentSupplierPhoneNr() {
	global $config;

	$supplier_ID = getCurrentSupplierId();

	$phoneNumber = "";
	$sql = "SELECT phoneNumber FROM supplier WHERE id = " . $supplier_ID;
	foreach ( $config->db->query( $sql ) as $row ) {
		$phoneNumber = $row[ 'phoneNumber' ];
	}
	return $phoneNumber;
}


function updateDatabaseToV0_6() {
	if ( !isset( $database ) ) {
		include 'config.php';
	}



	$db = new PDO( 'sqlite:' . $database );


	// update cntrl table
	$db->exec( "INSERT INTO `cntrl` (type, value) VALUES (
              'version', 0.6)" );

	// update supplier table 
	$db->exec( "ALTER TABLE supplier ADD phoneNumber char(255);" );
	$db->exec( "ALTER TABLE supplier ADD minAmount DOUBLE;" );
	$db->exec( "ALTER TABLE supplier ADD discountThreshold DOUBLE;" );
	$db->exec( "ALTER TABLE supplier ADD discountPercent DOUBLE;" );

	// update orderDetail table 
	$db->exec( "ALTER TABLE orders RENAME TO orderDetail" );
	$db->exec( "ALTER TABLE orderDetail ADD supplierCard_ID INTEGER;" );
	$db->exec( "ALTER TABLE orderDetail ADD comment char(255);" );
	$db->exec( "ALTER TABLE orderDetail ADD isPaid INTEGER;" );
	$db->exec( "ALTER TABLE orderDetail ADD price DOUBLE;" );
	$db->exec( "UPDATE orderDetail SET supplierCard_ID = order_ID;" );


	// copy price to new table
	$sql = "SELECT 
                [main].[orderDetail].[id], 
                [main].[supplierCard].[price] AS [price1]
                FROM   [main].[orderDetail]
                INNER JOIN [main].[supplierCard] ON [main].[orderDetail].[supplierCard_ID] = [main].[supplierCard].[id];";

	$db2 = new PDO( 'sqlite:' . $database );
	foreach ( $db->query( $sql ) as $row ) {
		$sql = "UPDATE orderDetail SET price = " . $row[ 'price1' ] . " WHERE id = " . $row[ 'id' ];
		$db->exec( $sql );
	}

	// update orderDetail table 
	$db->exec( "CREATE TABLE `orders` (      
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `supplier_ID` INTEGER,
      `user_ID` INTEGER,
      `state` INTEGER,
      `timeStampStarted` INTEGER,
      `timeStampFreezing` INTEGER,
      `timeStampReceive` INTEGER,
      FOREIGN KEY(supplier_ID) REFERENCES supplier(id),
      FOREIGN KEY(user_ID) REFERENCES user(id))" );

	$sql = "SELECT value FROM cntrl WHERE type = 'userWhoIsOrdering'";

	$userId = -1;
	foreach ( $db->query( $sql ) as $row ) {
		$userId = $row[ 'value' ];
	}
	$sql = "SELECT value FROM cntrl WHERE type = 'orderState'";
	$state = -1;

	if ( is_array( $db->query( $sql ) ) || is_object( $db->query( $sql ) ) ) {
		foreach ( $db->query( $sql ) as $row ) {
			$state = $row[ 'value' ];
		}
	}


	$sql = "SELECT id FROM supplier WHERE active = 1";
	$supplierId = -1;
	if ( is_array( $db->query( $sql ) ) || is_object( $db->query( $sql ) ) ) {
		foreach ( $db->query( $sql ) as $row ) {
			$supplierId = $row[ 'id' ];
		}
	}

	$db->exec( "UPDATE orderDetail SET `order_ID`= 1" );

	if ( ( $supplierId != -1 ) && ( $state != -1 ) ) {

		$db->exec( "INSERT INTO `orders` (supplier_ID, user_ID, state) VALUES (" . $supplierId . " , " . $userId . " , " . $state . ")" );
	}
}

function updateDatabaseToV0_7() {

	if ( !isset( $database ) ) {
		include 'config.php';
	}

	$db = new PDO( 'sqlite:' . $database );
	$sql = "CREATE TABLE `bank` (
		`id`	INTEGER PRIMARY KEY AUTOINCREMENT,
		`user_id_transactor`	INTEGER,
		`user_id_customer`	INTEGER,
		`amount`		DOUBLE,
		`orderDetail_id`	INTEGER,
		`timestamp`		INTEGER
		);";

	// update cntrl table
	$db->exec( $sql );

	$db->exec( "ALTER TABLE user ADD isBankTransactor INTEGER;" );

	$db->exec( "UPDATE cntrl SET value = 0.7 WHERE type = 'version';" );
}

function updateDatabaseToV0_7_5() {

	if ( !isset( $database ) ) {
		include 'config.php';
	}

	$db = new PDO( 'sqlite:' . $database );
	$db->exec( "ALTER TABLE orders ADD timeStampArrival INTEGER;" );
	$db->exec( "ALTER TABLE orders ADD user_ID_arrival INTEGER;" );
	$db->exec( "UPDATE cntrl SET value = 0.75 WHERE type = 'version';" );
}


function updateDatabaseToV0_8_0() {

	if ( !isset( $database ) ) {
		include 'config.php';
	}

	$db = new PDO( 'sqlite:' . $database );
	$db->exec( "ALTER TABLE user ADD resetcode varchar(255)" );

	$db->exec( "UPDATE cntrl SET value = 0.8 WHERE type = 'version';" );
}

function updateDatabaseToV0_8_5() {

	if ( !isset( $database ) ) {
		include 'config.php';
	}

	$db = new PDO( 'sqlite:' . $database );
	$db->exec( "ALTER TABLE orders ADD discountPaid integer" );

	$db->exec( "UPDATE cntrl SET value = 0.85 WHERE type = 'version';" );
}


function dbInitialized() {
	global $config;

	$sql = "SELECT count(*) FROM sqlite_master WHERE type = 'table'";

	$tableCount = 0;
	foreach ( $config->db->query( $sql ) as $row ) {
		$tableCount = $row[ 0 ];
	}

	if ( $tableCount > 0 ) {
		return 1;
	}

	return 0;
}

function getVersion() {
	global $config;

	$sql = "SELECT value FROM cntrl WHERE type = 'version'";

	$version = 0;
	foreach ( $config->db->query( $sql ) as $row ) {
		$version = $row[ 'value' ];
	}

	return $version;
}

function updateDatabase() {
	if ( !isset( $database ) ) {
		include 'config.php';
	}

	if ( file_exists( $database ) ) {
		$version = getVersion();
		if ( $version == 0 ) {
			updateDatabaseToV0_6();
			$version = getVersion();
		}

		if ( $version == 0.6 ) {
			updateDatabaseToV0_7();
			$version = getVersion();
		}

		if ( $version == 0.7 ) {
			updateDatabaseToV0_7_5();
			$version = getVersion();
		}

		if ( $version == 0.75 ) {
			updateDatabaseToV0_8_0();
			$version = getVersion();
		}
		
		if ( $version == 0.8 ) {
			updateDatabaseToV0_8_5();
			$version = getVersion();
		}
	}

}


function getArrivalInfo() {
	global $config;

	$timestampArrival = 0;
	$login = "";
	$userId = -1;

	$sql = "SELECT orders.timestampArrival, user.id, user.login FROM user INNER JOIN orders ON user.id = orders.user_ID_arrival WHERE orders.id = " . $config->orderid . " ;";

	foreach ( $config->db->query( $sql ) as $row ) {
		$timestampArrival = $row[ 'timeStampArrival' ];
		$login = $row[ 'login' ];
		$userId = $row[ 'id' ];
	}

	return array( $timestampArrival, $login, $userId );

}

function getUserWhoIsOrdering() {
	global $config;

	$userId = -1;
	$sql = "SELECT user_ID FROM orders WHERE id = " . $config->orderid;
	foreach ( $config->db->query( $sql ) as $row ) {
		$userId = $row[ 'user_ID' ];
	}
	return $userId;
}

function getCurrentSupplierName() {
	global $config;


	$supplier_Name = '';
	$sql = "SELECT supplier.name, orders.id FROM supplier, orders WHERE supplier.id = orders.supplier_ID AND orders.id = " . $config->orderid;

	foreach ( $config->db->query( $sql ) as $row ) {
		$supplier_Name = $row[ 'name' ];
	}
	return $supplier_Name;
}

function getOrderState() {
	global $config;

	$sql = "SELECT state, id FROM orders WHERE id = " . $config->orderid;

	$orderState = 0;

	if ( is_array( $config->db->query( $sql ) ) || is_object( $config->db->query( $sql ) ) ) {
		foreach ( $config->db->query( $sql ) as $row ) {
			$orderState = $row[ 'state' ];
		}
	}

	return $orderState;
}

function getOrderDiscountInfo($orderId) {
	global $config;
	
	$sql = "SELECT orders.id, discountThreshold, discountPercent, discountPaid
			FROM orders INNER JOIN supplier ON supplier.id = orders.supplier_ID
			WHERE orders.id = " . $orderId;
	
	$discountPercent   = 0;
	$discountThreshold = 0;
	$discountPaid      = 0;
	if ( is_array( $config->db->query( $sql ) ) || is_object( $config->db->query( $sql ) ) ) {
		foreach ( $config->db->query( $sql ) as $row ) {
			
			$discountThreshold = $row[ 'discountThreshold' ];
			$discountPercent   = $row[ 'discountPercent' ];
			$discountPaid      = $row[ 'discountPaid' ];
		}
	}
	
	return array($discountThreshold, $discountPercent, $discountPaid);
}

	
function getCurrentOrderId() {
	global $config;

	$sql = "SELECT id, state FROM orders WHERE state < 3";
	
	$orderId = 0;
	if ( is_array( $config->db->query( $sql ) ) || is_object( $config->db->query( $sql ) ) ) {
		foreach ( $config->db->query( $sql ) as $row ) {
			$orderId = $row[ 'id' ];			
		}
	}
	return $orderId;
}



function eventButtonSetCurrentOrderer() {
	global $config;

	if ( isset( $_POST[ 'eventButtonSetCurrentOrderer' ] ) ) {
		$newOrdererId = $_POST[ 'newOrderer' ];

		$sql = "UPDATE orders SET user_ID = " . $newOrdererId . " WHERE ( id = " . $config->orderid . " )";
		$config->db->exec( $sql );

		//header("Location: setup.php");	
	}
}

function eventButtonOrderArrived() {
	global $config;

	if ( isset( $_SESSION[ 'userid' ] ) ) {
		if ( isset( $_POST[ 'eventButtonOrderArrived' ] ) ) {
			$timestamp = time();

			$sql = "UPDATE orders SET timestampArrival = " . $timestamp . ", user_ID_arrival = " . $config->userid . " WHERE ( id = " . $config->orderid . " )";
			$config->db->exec( $sql );

			header( "Location: index.php" );
		}

	}
}

function eventButtonOrderArrivedStorno() {
	global $config;

	if ( isset( $_SESSION[ 'userid' ] ) ) {

		if ( isset( $_POST[ 'eventButtonOrderArrivedStorno' ] ) ) {
			$sql = "UPDATE orders SET timestampArrival = 0, user_ID_arrival = -1 WHERE ( id = " . $config->orderid . " )";
			$config->db->exec( $sql );

			addMessage( "Bestellung storniert" );
			header( "Location: index.php" );
		}
	}
}

function eventSetUserIsBank() {
	global $config;

	if ( isset( $_POST[ 'eventSetUserIsBank' ] ) ) {
		$loginId = $_POST[ 'userId' ];
		$value = $_POST[ 'isBank' ];
		$value = 1 - intval( $value );

		$sql = "UPDATE user SET isBankTransactor = " . $value . " WHERE ( id = " . $loginId . " )";
		$config->db->exec( $sql );

		header( "Location: setup.php" );
	}
}

function eventSetUserIsAdmin() {
	global $config;


	if ( isset( $_POST[ 'eventSetUserIsAdmin' ] ) ) {
		$loginId = $_POST[ 'userId' ];
		$value = $_POST[ 'isAdmin' ];

		$value = 1 - intval( $value );
		$sql = "UPDATE user SET isAdmin = " . $value . " WHERE ( id = " . $loginId . " )";
		$config->db->exec( $sql );

		header( "Location: setup.php" );
	}
}


function eventCreateResetCode() {
	global $config;


	if ( isset( $_POST[ 'eventCreateResetCode' ] ) ) {
		$userId = $_POST[ 'userId' ];
		$bytes = random_bytes( 10 );
		$resetcode = bin2hex( $bytes );

		//$value = 1 - intval($value);
		$sql = "UPDATE user SET resetcode = '" . $resetcode . "' WHERE ( id = " . $userId . " )";
		$config->db->exec( $sql );

		header( "Location: setup.php" );
	}
}

function eventDeleteResetCode() {
	global $config;


	if ( isset( $_POST[ 'eventDeleteResetCode' ] ) ) {
		$userId = $_POST[ 'userId' ];

		$sql = "UPDATE user SET resetcode = null WHERE ( id = " . $userId . " )";
		$config->db->exec( $sql );

		header( "Location: setup.php" );
	}
}

function checkDeadLine() {
	if ( getTimeStampFreezingOrder() < time() ) {
		closeCurrentOrder();
	}
}

function eventOrderKill() {
	global $config;

	if ( isset( $_POST[ 'orderKill' ] ) ) {
		checkDeadLine();

		if ( getOrderState() >= 2 ) {
			tooLateMessage();
		} else {
			$order_ID = $_POST[ 'orderKill' ];
			$sql = "DELETE FROM orderDetail WHERE orderDetail.id = " . $order_ID;

			$config->db->exec( $sql );

			$sql = "UPDATE bank SET amount = 0 WHERE orderDetail_id = " . $order_ID;

			$config->db->exec( $sql );

			addMessage( "Bestellung storniert" );
			usleep(500000);
			header( "Location: index.php" );
		}
	}
}

function eventOrderPaid() {
	global $config;

	if ( isset( $_POST[ 'eventButtonPayOrder' ] ) ) {
		$order_ID = $_POST[ 'orderId' ];

		$sql = "UPDATE orderDetail SET isPaid = 1 WHERE ( orderDetail.id = " . $order_ID . " )";
		$config->db->exec( $sql );

		addMessage( "Bestellung als bezahlt markiert" );
		usleep(500000);
		header( "Location: index.php" );
	}

	if ( isset( $_POST[ 'eventButtonOrderStorno' ] ) ) {

		$order_ID = $_POST[ 'orderId' ];
		$sql = "UPDATE orderDetail SET isPaid = 0 WHERE ( orderDetail.id = " . $order_ID . " )";
		$config->db->exec( $sql );

		addMessage( "Bestellung als offen markiert" );
		usleep(500000);
		header( "Location: index.php" );
	}
}

function eventOrderAdd() {
	global $config;

	if ( isset( $_POST[ 'eventButtonAddOrder' ] ) ) {
		checkDeadLine();

		if ( getOrderState() >= 2 ) {
			tooLateMessage();

		} else {
			$supplierCard_ID = $_POST[ 'supplierCard_ID' ];

			$sql = "SELECT * FROM orderDetail WHERE user_ID = " . $config->userid;

			$counter = 0;
			foreach ( $config->db->query( $sql ) as $row ) {
				$counter++;
			}

			$price = $_POST[ 'supplierCard_price' ];
			$supplierID = getCurrentSupplierId();
			$config->db->exec( "INSERT INTO orderDetail 
						(order_ID, supplierCard_ID, supplier_ID, user_ID, price)                         
					   VALUES ( " . $config->orderid . "," . $supplierCard_ID . "," . $supplierID . " , " . $config->userid . "," . $price . ")" );

			addMessage( "Bestellung hinzugefügt" );
			usleep(500000);
			header( "Location: index.php" );
		}

	}
}


function createNewOrder() {
	global $config;

	if ( getOrderState() != 1 ) {
		$config->db->exec( "INSERT INTO orders
					  (supplier_ID, user_ID, state)                         
					   VALUES ( " .
			"0, " . $config->userid . ", 0 )" );
	}
	addMessage( "Neue Bestellung gestartet" );
	header( "Location: index.php" );

}

function eventOrderRestart() {
	if ( isset( $_POST[ 'eventButtonNewOrder' ] ) ) {
		createNewOrder();
	}
}

function eventOrderComment() {
	global $config;

	if ( isset( $_POST[ 'orderUpdateCommentID' ] ) ) {
		checkDeadLine();
		if ( getOrderState() >= 2 ) {
			tooLateMessage();
		} else {
			$order_ID = $_POST[ 'orderUpdateCommentID' ];
			$comment = $_POST[ 'orderUpdateCommentTxt' ];

			$comment = str_replace( ' ', '#$#', $comment );

			$sql = "UPDATE orderDetail SET comment = '" . $comment . "' WHERE ( orderDetail.id = " . $order_ID . " )";
			$config->db->exec( $sql );

			addMessage( "Kommentar gespeichert" );
			
			usleep(1000000);
			header( "Location: index.php" );
		}
	}
}


function closeCurrentOrder() {
	global $config;

	$timeStampArrival = mktime( 12, 15, 0, date( "m" ), date( "d" ), date( "Y" ) );

	$sql = "UPDATE orders SET `state` = 2, timeStampReceive = " . $timeStampArrival . " WHERE ( id = " . $config->orderid . " )";
	$config->db->exec( $sql );
}

function eventOrderFinished() {
	global $config;

	if ( isset( $_POST[ 'finish' ] ) ) {
		closeCurrentOrder();
		addMessage( "Bestellung abgeschlossen" );
		header( "Location: index.php" );
	}
}

function eventBankInput() {
	global $config;
	if ( isset( $_POST[ 'eventButtonBankInput' ] ) ) {
		$customer_id = $_POST[ 'customer_id' ];
		$amount = $_POST[ 'amount' ];

		$sql = "INSERT INTO bank (`user_id_transactor`,  `user_id_customer`,     `amount`, `timeStamp`)
			    VALUES 		 (" . $config->userid . " , " . $customer_id . " , " . $amount . "," . time() . " )";

		$config->db->exec( $sql );

		addMessage( "Betrag virtuell einbezahlt" );
		usleep(1000000);
		header( "Location: bank.php" );
	}
}

function eventVirtualPay() {
	global $config;

	if ( isset( $_POST[ 'eventVirtualPayButton' ] ) ) {
		$orderDetail_id = $_POST[ 'orderDetail_id' ];
		$price = $_POST[ 'price' ];

		$sql = "INSERT INTO bank (`user_id_transactor`,  `user_id_customer`,     `amount`, `timeStamp`, `orderDetail_id`)
				VALUES 		 (" . $config->userid . " , " . $config->userid . " , " . - $price . "," . time() . "," . $orderDetail_id . ")";
		$config->db->exec( $sql );

		$sql = "UPDATE orderDetail SET isPaid = 2 WHERE id = " . $orderDetail_id;
		$config->db->exec( $sql );

		addMessage( "Bestellung virtuell bezahlt" );
		usleep(500000);
		header( "Location: index.php" );
	}

}


function eventButtonStartNewOrder() {
	global $config;

	if ( isset( $_POST[ 'eventButtonStartNewOrder' ] ) ) {


		$timeHH = $_POST[ 'timeFreezeHH' ];
		$timeMM = $_POST[ 'timeFreezeMM' ];


		$timestampNow = time();
		//$timestampFreeze = $timestampNow + 3600 * $timeHH + $timeMM * 60;
		$timestampFreeze = mktime( $timeHH, $timeMM, 0, date( "m" ), date( "d" ), date( "Y" ) );

		// create new order      
		$supplierID = $_POST[ 'supplier' ];



		// check if already one order has been started (specially after first setup)

		$sql = 'SELECT COUNT(*) FROM orders';

		$counter = 0;
		foreach ( $config->db->query( $sql ) as $row ) {
			$counter = $row[ 0 ];
		}

		if ( $counter == 0 ) {
			createNewOrder();
			$config->orderid = getCurrentOrderId();
		}

		$orderId = $config->orderid;

		$sql = "UPDATE orders SET 
					supplier_ID = " . $supplierID . ",
					user_ID     = " . $config->userid . "   ,
					state       = 1,
					timeStampStarted = " . $timestampNow . ",
					timeStampFreezing = " . $timestampFreeze . " WHERE (id = " . $orderId . ")";

		$config->db->exec( $sql );
		//header("Location: index.php");		  
	}
	//}
}

function getSupplierList() {
	global $config;

	$sql = "SELECT id, name FROM supplier";

	$string = "<form action='?pollSubmit=1' method='post'>";

	foreach ( $config->db->query( $sql ) as $row ) {
		$string = $string . "<input type='radio' name='supplier' value='" . $row[ 'id' ] . "' CHECKED />" . $row[ "name" ] . "<br>";
	}
	return $string;
}


function createOrderTable( $page ) {
	global $config;

	$orderId = $config->orderid;
	$sql = "SELECT supplier_ID, supplier.name FROM orders INNER JOIN supplier ON orders.supplier_ID = supplier.id WHERE (orders.id = " . $orderId . ")";

	foreach ( $config->db->query( $sql ) as $row ) {
		$supplier_ID = $row[ 'supplier_ID' ];
		$supplierName = $row[ 'name' ];
	}

	$sql = "SELECT id, nr, name, ingredients, price FROM supplierCard WHERE supplier_ID = " . $supplier_ID;

	$templateRowOdd = extractSection( "<!-- order items section row odd -->", $page );
	$templateRowEven = extractSection( "<!-- order items section row even -->", $page );

	$page = "";
	$rowCount = 0;

	foreach ( $config->db->query( $sql ) as $row ) {
		if ( $config->userid > -1 ) {
			$button = "<button type='submit' class='btnBuy' name='eventButtonAddOrder' onclick='playAudio(`order.wav`)'></button>								   					   
					   <input type='hidden' value=" . $row[ 'id' ] . "    name='supplierCard_ID'/>
					   <input type='hidden' value=" . $row[ 'price' ] . " name='supplierCard_price'/>";
		} else {
			$button = "";
		}

		if ( ( $rowCount % 2 ) > 0 ) {
			$templateRow = $templateRowOdd;
		} else {
			$templateRow = $templateRowEven;
		}
		$newRow = preg_replace( "/\[\%orderButton\%\]/", $button, $templateRow );
		$newRow = preg_replace( "/\[\%orderNr\%\]/", $row[ 'nr' ], $newRow );
		$newRow = preg_replace( "/\[\%orderName\%\]/", $row[ 'name' ], $newRow );
		$newRow = preg_replace( "/\[\%orderIngredients\%\]/", $row[ 'ingredients' ], $newRow );
		$newRow = preg_replace( "/\[\%price\%\]/", number_format( $row[ 'price' ], 2 ), $newRow );

		$page = $page . $newRow;

		$rowCount = $rowCount + 1;
	}

	return $page;
}


function getTimeStampFreezingOrder() {
	global $config;

	$orderId = $config->orderid;
	$sql = "SELECT timeStampStarted, timeStampFreezing FROM orders WHERE id = " . $orderId;

	$timeStampFreezing = 0;
	foreach ( $config->db->query( $sql ) as $row ) {
		$timeStampFreezing = $row[ 'timeStampFreezing' ];
	}
	return $timeStampFreezing;
}

function getTimeStampArrivalOrder() {
	global $config;

	$orderId = $config->orderid;
	$sql = "SELECT timeStampReceive FROM orders WHERE id = " . $orderId;

	$timeStampReceive = 0;
	foreach ( $config->db->query( $sql ) as $row ) {
		$timeStampReceive = $row[ 'timeStampReceive' ];
	}

	return $timeStampReceive;
}

function getOrderTimestampStart() {
	global $config;

	$sql = "SELECT timeStampStarted, timeStampFreezing FROM orders WHERE id = " . $config->orderid;
	foreach ( $config->db->query( $sql ) as $row ) {
		$timeStampStarted = $row[ 'timeStampStarted' ];
	}

	return $timeStampStarted;
}

function createIncomingOrdersTable( $page ) {
	global $config;

	$supplierName = getCurrentSupplierName();

	$oderstate = getOrderState();
	$sql = "SELECT user_ID, id FROM  orders WHERE orders.state AND id = " . $config->orderid;

	foreach ( $config->db->query( $sql ) as $row ) {
		$oderUserId = $row[ 'user_ID' ];
	}

	$sql = "SELECT login FROM user WHERE id = " . $oderUserId;

	foreach ( $config->db->query( $sql ) as $row ) {
		$oderUserName = $row[ 'login' ];
	}

	$orderCounter = 0;
	$priceCounter = 0;

	$timeStampStarted = 0;
	$timeStampFreezing = 0;

	$sql = "SELECT timeStampStarted, timeStampFreezing FROM orders WHERE id = " . $config->orderid;
	foreach ( $config->db->query( $sql ) as $row ) {
		$timeStampStarted = $row[ 'timeStampStarted' ];
		$timeStampFreezing = $row[ 'timeStampFreezing' ];
	}

	$sql = "SELECT user.id AS user_ID, orderDetail.isPaid, user.login, orderDetail.id AS order_ID, orderDetail.supplierCard_ID, orderDetail.comment, supplierCard.nr, supplierCard.name, orderDetail.price FROM orders, ((orderDetail INNER JOIN user ON orderDetail.user_ID = user.id) INNER JOIN supplierCard ON orderDetail.supplierCard_ID = supplierCard.ID) WHERE orders.id = orderDetail.order_ID AND orders.id = " . $config->orderid;

	$page = preg_replace( "/\[\%supplierName\%\]/", $supplierName, $page );
	$page = preg_replace( "/\[\%orderOwner\%\]/", $oderUserName, $page );
	$page = preg_replace( "/\[\%timestamp\%\]/", date( "d.m.Y - H:i", $timeStampStarted ), $page );

	class supplierNr {
		public $nr;
		public $count;
		public $name;
		public $comment;
	}

	$nrList = array();
	$iCounter = 0;

	$nrObject = new supplierNr();
	$nrObject->nr = 0;
	$nrObject->count = 0;
	array_push( $nrList, $nrObject );


	$table = "";

	$templateRowOdd = extractSection( "<!-- incoming orders section row odd -->", $page );
	$templateRowEven = extractSection( "<!-- incoming orders section row even -->", $page );

	$rowCount = 0;

	$moneyVirtual = 0;
	$moneyReal = 0;

	foreach ( $config->db->query( $sql ) as $row ) {
		$orderCounter = $orderCounter + 1;
		$price = doubleval( str_replace( ',', '.', $row[ 'price' ] ) );
		$priceCounter = $priceCounter + $price;
		$isPaid = $row[ 'isPaid' ];
		$supplierCardNr = $row[ 'nr' ];
		$comment = $row[ 'comment' ];
		$comment = str_replace( '#$#', ' ', $comment );

		// count all same supplier-nr together

		$max = sizeof( $nrList );

		for ( $i = 0; $i < $max; $i++ ) {
			if ( ( $nrList[ $i ]->nr === $supplierCardNr ) &&
				( $nrList[ $i ]->comment === $comment ) ) {
				$nrList[ $i ]->count = $nrList[ $i ]->count + 1;
				break;
			}

			if ( $i === ( $max - 1 ) ) {
				$nrObject = new supplierNr();

				$nrObject->nr = $supplierCardNr;
				$nrObject->count = 1;
				$nrObject->comment = $comment;
				$nrObject->name = $row[ 'name' ];;
				array_push( $nrList, $nrObject );
			}
		}

		if ( ( $rowCount % 2 ) > 0 ) {
			$newRow = $templateRowEven;
		} else {
			$newRow = $templateRowOdd;
		}

		//  --- show kill button if allowed ---------------------------------------------------------
		$killButton = "";
		$virtualPayButton = "";

		if ( !$config->isHistory ) {
			if ( ( $config->userid == $row[ 'user_ID' ] ) && ( $oderstate == 1 ) ) {
				$killButton = "<form action='' method='post'>
							   <button type='submit' class='btnDelete' name='orderKill' onclick='playAudio(`storno.wav`)'></button>								   
							   <input type='hidden' value=" . $row[ 'order_ID' ] . " name='orderKill'/>                        
							   </form>";
			}
			if ( ( $config->userid == $row[ 'user_ID' ] ) && ( $isPaid < 1 ) ) {
				if ( countMoney() >= $price ) {
					$virtualPayButton = "<form action='' method='post'>
								   <button type='submit' class='btnvPay' name='eventVirtualPayButton' onclick='playAudio(`pay.wav`)'></button>								   								   
								   <input type='hidden' value=" . $row[ 'order_ID' ] . " name='orderDetail_id'/>                        
								   <input type='hidden' value=" . $price . " name='price'/>                        
								 </form>";
				}
			}
		}


		$newRow = preg_replace( "/\[\%killOrder\%\]/", $killButton, $newRow );
		$newRow = preg_replace( "/\[\%virtualPay\%\]/", $virtualPayButton, $newRow );

		$rowCount = $rowCount + 1;

		$payState = "";

		//  --- show order control button if allowed ---------------------------------------------------------		
		if ( ( $config->userid == getUserWhoIsOrdering() )and( $isPaid < 2 )and( !$config->isHistory ) ) {
			if ( $isPaid == 1 ) {
				$payState = "<form action='' method='post'>							    
								<button type='submit' class='btnPaid' name='eventButtonOrderStorno' onclick='playAudio(`storno.wav`)' ></button>
							    <input type='hidden' value=" . $row[ 'order_ID' ] . " name='orderId'/>                       
							    </form>";
				$moneyReal = $moneyReal + $price;

			} else {


				$payState = "<form action='' method='post'>							  
							  <button type='submit' class='btnPay' name='eventButtonPayOrder' onclick='playAudio(`pay.wav`)'></button>							  
							  <input type='hidden' value=" . $row[ 'order_ID' ] . " name='orderId'/>
							  </form>";

			}
		} else {
			if ( $isPaid == 2 ) {
				//$payState = "VIRTUELL BEZAHLT";
				$payState = "<button type='submit' class='btnvPaid'></button>";

				$moneyVirtual = $moneyVirtual + $price;
			} else if ( $isPaid == 1 ) {
				//$payState = "BEZAHLT";
				$payState = "<button class='btnPaidNfo'></button>";
					
				$moneyReal = $moneyReal + $price;
			} else {
				//$payState = "OFFEN";
				$payState = "<button class='btnPayNfo'></button>";
			}
		}

		$newRow = preg_replace( "/\[\%payState\%\]/", $payState, $newRow );
		$newRow = preg_replace( "/\[\%supplierCardNr\%\]/", $supplierCardNr, $newRow );
		$newRow = preg_replace( "/\[\%orderName\%\]/", $row[ 'name' ], $newRow );
		$newRow = preg_replace( "/\[\%login\%\]/", $row[ 'login' ], $newRow );


		//  --- show comment control button if allowed ---------------------------------------------------------		
		if ( !$config->isHistory ) {
			if ( ( $config->userid == $row[ 'user_ID' ] ) && ( $oderstate == 1 ) ) {
				$comment = "<form action='' method='post'>         
									<div class='currentOrderCommentTextfield'>									
										<input type='text' name='orderUpdateCommentTxt' value='" . $comment . "' >
									</div>
									<div class='currentOrderCommentButton'>  
										<button type='submit' class='btnSave' name='updateComment' onclick='playAudio(`save.wav`)'></button>										
										<input type='hidden' value=" . $row[ 'order_ID' ] . " name='orderUpdateCommentID'/>
									</div>
								 </form>";
			}
		}

		$newRow = preg_replace( "/\[\%comment\%\]/", $comment, $newRow );
		$newRow = preg_replace( "/\[\%price\%\]/", number_format( $row[ 'price' ], 2 ) . " €", $newRow );

		$table = $table . $newRow;
		$iCounter = $iCounter + 1;

	}


	$page = replaceSection( "<!-- incoming orders section row odd -->", $table, $page );
	$page = removeSection( "<!-- incoming orders section row even -->", $page );

	$page = preg_replace( "/\[\%orderCount\%\]/", $orderCounter, $page );
	$page = preg_replace( "/\[\%orderSum\%\]/", number_format( $priceCounter, 2 ), $page );

	$config->orderSum = $priceCounter;
	
	$page = preg_replace( "/\[\%orderSumRealMoney\%\]/", number_format( $moneyReal, 2 ), $page );
	$page = preg_replace( "/\[\%orderSumVirtualMoney\%\]/", number_format( $moneyVirtual, 2 ), $page );
	$page = preg_replace( "/\[\%orderSumToGet\%\]/", number_format( $priceCounter - ( $moneyVirtual + $moneyReal ), 2 ), $page );


	// show discount

	// comprimized order        
	$max = sizeof( $nrList );

	sort( $nrList );


	if ( $orderCounter > 0 ) {
		$newRow = extractSection( "<!-- incoming orders final -->", $page );

		$newRowOdd = extractSection( "<!-- incoming orders final odd row-->", $page );
		$newRowEven = extractSection( "<!-- incoming orders final even row-->", $page );

		$finalTable = "";
		$tableRow = "";
		for ( $i = 01; $i < $max; $i++ ) {
			if ( $nrList[ $i ]->count > 0 ) {
				if ( $i % 2 == 0 ) {
					$newRow = $newRowEven;
				} else {
					$newRow = $newRowOdd;
				}
				$tableRow = preg_replace( "/\[\%finalCount\%\]/", $nrList[ $i ]->count, $newRow );
				$tableRow = preg_replace( "/\[\%finalNumber\%\]/", $nrList[ $i ]->nr, $tableRow );
				$tableRow = preg_replace( "/\[\%finalName\%\]/", $nrList[ $i ]->name, $tableRow );
				$tableRow = preg_replace( "/\[\%finalComment\%\]/", $nrList[ $i ]->comment, $tableRow );

				$finalTable = $finalTable . $tableRow;
			}
		}

		$page = replaceSection( "<!-- incoming orders final -->", $finalTable, $page );
	} else {
		$page = removeSection( "<!-- incoming orders sumup -->", $page );
	}

	return $page;
}


function showOrderFinish() {
	global $config;

	$string = '';
	if ( $config->userid == getUserWhoIsOrdering() ) {
		$string = "<div class=''>
                   form action='?finish' method='post'>
                   <input type='submit' value='Bestellung abschließen'  name='finish'/>
                   <input type='hidden' value='' name='supplierCard_ID'/>
                   </form>
                   </div>";
	}
	return $string;
}



function isAdmin() {
	global $config;

	$sql = "SELECT isAdmin FROM user WHERE user.id = " . $config->userid;

	$isAdmin = 0;
	foreach ( $config->db->query( $sql ) as $row ) {
		$isAdmin = $row[ 'isAdmin' ];
	}
	return $isAdmin;
}

function isBankTransactor() {
	global $config;

	$isBankTransactor = 0;
	if ( $config->userid > -1 ) {

		$sql = "SELECT isBankTransactor FROM user WHERE user.id = " . $config->userid;

		$isBankTransactor = 0;
		foreach ( $config->db->query( $sql ) as $row ) {
			$isBankTransactor = $row[ 'isBankTransactor' ];
		}
	}
	return $isBankTransactor;
}

function addUserIdLogin( $item ) {
	global $config;

	$sql = "SELECT id, login FROM user ORDER BY UPPER(login) ASC";

	$comboboxItems = "";
	foreach ( $config->db->query( $sql ) as $row ) {
		$newItem = preg_replace( "/\[\%bank_customer_user_ID\%\]/", $row[ 'id' ], $item );
		$newItem = preg_replace( "/\[\%bank_customer_login\%\]/", $row[ 'login' ], $newItem );
		$comboboxItems = $comboboxItems . $newItem;
	}
	return $comboboxItems;
}

function countMoney() {
	global $config;

	$sql = "SELECT amount FROM bank WHERE user_id_customer = " . $config->userid;

	$money = 0;
	foreach ( $config->db->query( $sql ) as $row ) {
		$money = $money + $row[ 'amount' ];
	}

	return number_format( $money, 2 );
}


function adminSetCurrentOrderer( $page ) {
	global $config;

	$sql = "SELECT id, login FROM user ORDER BY UPPER(login) ASC";

	$defaultItem = extractSection( "<!-- admin current orderer item -->", $page );

	$currentUserIdOrdering = getUserWhoIsOrdering();

	$comboboxItems = "";
	$idx = 0;
	$newRow = "";

	$table = "";

	foreach ( $config->db->query( $sql ) as $row ) {

		$selected = "";

		if ( intval( $currentUserIdOrdering ) == intval( $row[ 'id' ] ) ) {
			$selected = "selected";
		}

		$newItem = preg_replace( "/\[\%current_orderer_user_ID\%\]/", $row[ 'id' ], $defaultItem );
		$newItem = preg_replace( "/\[\%current_orderer_login\%\]/", $row[ 'login' ], $newItem );
		$newItem = preg_replace( "/\[\%selected\%\]/", $selected, $newItem );
		$comboboxItems = $comboboxItems . $newItem;

		$idx++;
	}

	$page = replaceSection( "<!-- admin current orderer -->", $comboboxItems, $page );
	return $page;
}

function adminShowUserData( $page ) {
	global $config;

	$sql = "SELECT id, login, created_at, isAdmin, isBankTransactor, resetcode FROM user ORDER BY UPPER(login) ASC";

	$rowOdd = extractSection( "<!-- admin user section row odd -->", $page );
	$rowEven = extractSection( "<!-- admin user section row even -->", $page );

	$comboboxItems = "";
	$idx = 0;
	$newRow = "";

	$table = "";
	foreach ( $config->db->query( $sql ) as $row ) {

		if ( ( $idx % 2 ) == 0 ) {
			$newRow = $rowEven;
		} else {
			$newRow = $rowOdd;
		}


		$resetcode = $row[ 'resetcode' ];

		$newRow = preg_replace( "/\[\%userLogin\%\]/", $row[ 'login' ], $newRow );

		if ( intval( $row[ 'isAdmin' ] ) == 1 ) {
			$value = "TRUE";
			$setValue = 1;
		} else {
			$value = "FALSE";
			$setValue = 0;
		}

		if ( $config->userid != $row[ 'id' ] ) {
			$newRow = preg_replace( "/\[\%checkboxAdminrights\%\]/", "<input type='submit' name='eventSetUserIsAdmin' value='" . $value . "' />																	
																	   <input type='hidden'  value='" . $row[ 'id' ] . "' name='userId'/>
																	   <input type='hidden'  value='" . $setValue . "' name='isAdmin'/>", $newRow );
		} else {
			$newRow = preg_replace( "/\[\%checkboxAdminrights\%\]/", $value, $newRow );
		}





		if ( $resetcode == null ) {
			$resetcode = "<input type='submit' name='eventCreateResetCode' value='GENERATE'/>
						  <input type='hidden'  value='" . $row[ 'id' ] . "' name='userId'/>";
		} else {
			$resetcode = "<a href='reset_pw.php?resetcode=" . $resetcode . "'>resetlink</a>
						  <input type='submit' name='eventDeleteResetCode' value='X'/>
						  <input type='hidden'  value='" . $row[ 'id' ] . "' name='userId'/>";
		}

		$newRow = preg_replace( "/\[\%resetcode\%\]/", $resetcode, $newRow );



		if ( intval( $row[ 'isBankTransactor' ] ) == 1 ) {
			$value = "TRUE";
			$setValue = 1;
		} else {
			$value = "FALSE";
			$setValue = 0;
		}

		$newRow = preg_replace( "/\[\%checkboxIsBank\%\]/", "<input type='submit' name='eventSetUserIsBank' value='" . $value . "' />
															  <input type='hidden'  value='" . $row[ 'id' ] . "' name='userId'/>
															  <input type='hidden'  value='" . $setValue . "' name='isBank'/>", $newRow );

		$table = $table . $newRow;
		$idx++;
	}

	$page = replaceSection( "<!-- admin user section table -->", $table, $page );



	return $page;
}


function showBankInfo( $page ) {
	global $config;

	$rowOdd = extractSection( "<!-- bank items section row even -->", $page );
	$rowEven = extractSection( "<!-- bank items section row odd -->", $page );

	$sql = "SELECT  bank.*, user.login, user1.login AS login1 FROM
			bank 
			INNER JOIN user ON user.id = bank.user_id_transactor
			INNER JOIN user user1 ON user1.id = bank.user_id_customer ORDER BY id DESC;";

	$completeAmount = 0;

	$counter = 1;
	$table = "";
	foreach ( $config->db->query( $sql ) as $row ) {

		if ( ( $counter % 2 ) == 0 ) {
			$newRow = $rowEven;
		} else {
			$newRow = $rowOdd;
		}

		$timestamp = date( 'd.m.o -  H:i:s', $row[ 'timestamp' ] );

		$newRow = preg_replace( "/\[\%bankId\%\]/", $row[ 'id' ], $newRow );
		$newRow = preg_replace( "/\[\%bankTimestamp\%\]/", $timestamp, $newRow );
		$newRow = preg_replace( "/\[\%banker\%\]/", $row[ 'login' ], $newRow );
		$newRow = preg_replace( "/\[\%account\%\]/", $row[ 'login1' ], $newRow );
		$newRow = preg_replace( "/\[\%amount\%\]/", number_format( $row[ 'amount' ], 2 ), $newRow );


		$completeAmount = $completeAmount + $row[ 'amount' ];

		$table = $table . $newRow;
		$counter = $counter + 1;
	}

	$page = replaceSection( "<!-- bank items section row -->", $table, $page );


	$page = preg_replace( "/\[\%completeAmount\%\]/", number_format( $completeAmount, 2 ), $page );

	return $page;
}


function checkResetCode( $code ) {
	global $config;

	$sql = "SELECT  user.id FROM user WHERE resetcode = '" . $code . "'";

	$count = 0;
	$userId = -1;

	foreach ( $config->db->query( $sql ) as $row ) {
		$count = $count + 1;
		$userId = $row[ 'id' ];
	}

	if ( $count == 1 ) {
		return $userId;
	}

	return -1;
}

function getComboboxHH( $hour ) {
	$htmlTxt = '';
	$zero = '';
	// write hours
	for ( $hh = 0; $hh < 24; $hh++ ) {
		if ( $hh < 10 ) {
			$zero = '0';
		} else {
			$zero = '';
		}

		if ( $hour == $hh ) {
			$htmlTxt = $htmlTxt . "<option value='" . $hh . "' selected>" . $zero . $hh . " </option>";
		} else {
			$htmlTxt = $htmlTxt . "<option value='" . $hh . "'>" . $zero . $hh . " </option>";
		}

	}
	return $htmlTxt;
}

function getComboboxMM( $minute ) {
	$htmlTxt = '';
	$zero = '';
	// write minutes
	for ( $mm = 0; $mm < 60; $mm = $mm + 5 ) {
		if ( $mm < 10 ) {
			$zero = '0';
		} else {
			$zero = '';
		}

		if ( $minute == $mm ) {
			$htmlTxt = $htmlTxt . "<option value='" . $mm . "' selected>" . $zero . $mm . " </option>";
		} else {
			$htmlTxt = $htmlTxt . "<option value='" . $mm . "'>" . $zero . $mm . " </option>";
		}

	}
	return $htmlTxt;
}


function eventUpdateArrivalInfo() {
	global $config;

	if ( isset( $_POST[ 'eventButtonUpdateArrival' ] ) ) {

		$timeHH = $_POST[ 'timeArrivalHH' ];
		$timeMM = $_POST[ 'timeArrivalMM' ];

		$timeStampArrival = mktime( $timeHH, $timeMM, 0, date( "m" ), date( "d" ), date( "Y" ) );

		$sql = "UPDATE orders SET timeStampReceive = " . $timeStampArrival . " WHERE ( id = " . $config->orderid . " )";
		$config->db->exec( $sql );

		//header("Location: index.php");			
	}
}

function getHistoryOrderList( $page ) {
	global $config;


	$rowOdd = extractSection( "<!-- history items section row odd -->", $page );
	$rowEven = extractSection( "<!-- history items section row even -->", $page );


	$sql = "SELECT orders.id, user.login, supplier.name, orders.state, orders.timeStampStarted " .
	"FROM orders " .
	"INNER JOIN user ON user.id = orders.user_ID " .
	"INNER JOIN supplier ON supplier.id = orders.supplier_ID " .
	"ORDER  BY orders.timeStampStarted DESC";

	$table = "";

	$counter = 0;
	foreach ( $config->db->query( $sql ) as $row ) {

		if ( ( $counter % 2 ) == 0 ) {
			$newRow = $rowEven;
		} else {
			$newRow = $rowOdd;
		}

		$timestamp = date( 'd.m.o -  H:i:s', $row[ 'timeStampStarted' ] );

		if ( $row[ 'state' ] == '2' ) {
			$state = "abgeschlossen";
		} else if ( $row[ 'state' ] == '1' ) {
			$state = "offen";
		} else {
			$state = "neu erstellt";
		}

		$button = "<input value='>> Details' type='submit' name='eventButtonHistoryDetails'> " .
		"<input type='hidden' value=" . $row[ 'id' ] . "    name='order_ID'/>";


		$newRow = preg_replace( "/\[\%historyId\%\]/", $row[ 'id' ], $newRow );
		$newRow = preg_replace( "/\[\%historyTimestamp\%\]/", $timestamp, $newRow );
		$newRow = preg_replace( "/\[\%historyState\%\]/", $state, $newRow );
		$newRow = preg_replace( "/\[\%historySupplier\%\]/", $row[ 'name' ], $newRow );
		$newRow = preg_replace( "/\[\%userId\%\]/", $row[ 'login' ], $newRow );
		$newRow = preg_replace( "/\[\%details\%\]/", $button, $newRow );

		$table = $table . $newRow;
		$counter = $counter + 1;
	}

	$page = replaceSection( "<!-- history items section row -->", $table, $page );

	return $page;
}


function eventShowHistoryDetails() {
	global $config;

	if ( isset( $_POST[ 'eventButtonHistoryDetails' ] ) ) {
		$order_ID = $_POST[ 'order_ID' ];

		header( "Location: historyDetails.php?id=" . $order_ID );
	}
}

function isLoginNameValid( $login ) {

	if ( strlen( $login ) < 5 ) {
		addMessage( "Login zu kurz, mind. 5 Zeichen!" );
		return false;
	}

	if ( !preg_match( '/[^A-Za-z0-9.#\\-$]/', $login ) ) {
		return true;
	} else {
		addMessage( "Login enthält unerlaubte Zeichen! Gültige Zeichen: A-Z, 0-9" );
		return false;
	}

}


function getOverallSpentMoneyFromLogin( $userId ) {
	global $config;

	$sql = "SELECT SUM(price) FROM orderDetail WHERE user_ID = " . $userId;

	$sum = 0;
	foreach ( $config->db->query( $sql ) as $row ) {
		$sum = $row[ 0 ];
	}
	return $sum;
}



function eventButtonCreateDatabase() {
	global $config;

	if ( isset( $_POST[ 'eventButtonCreateDatabase' ] ) ) {

		$login = $_POST[ 'login' ];
		$password = $_POST[ 'password' ];
		$password2 = $_POST[ 'password2' ];


		$error = !isLoginNameValid( $login );



		if ( strlen( $password ) == 0 ) {
			addMessage( "Bitte ein Passwort angeben" );
			$error = true;
		}
		if ( $password != $password2 ) {
			addMessage( "Die Passwörter müssen übereinstimmen" );
			$error = true;
		}

		//no error
		if ( !$error ) {
			$password_hash = password_hash( $password, PASSWORD_DEFAULT );
			createNewDB( $login, $password_hash );
		}
	}

}



function createNewDB( $user, $passwordHash ) {
	if ( !isset( $database ) ) {
		include 'config.php';
	}

	global $config;
	$config->db->beginTransaction();

	$config->db->exec( "CREATE TABLE `user` (      
					  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
					  `login` varchar(255) NOT NULL,
					  `password` varchar(255) NOT NULL,      
					  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  `updated_at` timestamp NULL DEFAULT NULL,
					  `isAdmin`	INTEGER DEFAULT 0,
					   UNIQUE (`login`))" );

	$config->db->exec( "CREATE TABLE `supplier` (      
				       `id` INTEGER PRIMARY KEY AUTOINCREMENT,
					   `name` varchar(255) NOT NULL,
					   `active` INTEGER)" );

	$config->db->exec( "CREATE TABLE `orders` (      
					   `id` INTEGER PRIMARY KEY AUTOINCREMENT,
						`order_ID` INTEGER,
						`supplier_ID` INTEGER,
						`user_ID` INTEGER,
					    FOREIGN KEY(supplier_ID) REFERENCES supplier(id),
						FOREIGN KEY(user_ID) REFERENCES user(id))" );

	$config->db->exec( "CREATE TABLE `supplierCard` (      
						`id` INTEGER PRIMARY KEY AUTOINCREMENT,
					    `supplier_ID` INTEGER,            
					    `nr` INTEGER,
					    `name` varchar(255),
					    `ingredients` varchar(255),
					    `price` DOUBLE,
					    FOREIGN KEY(supplier_ID) REFERENCES supplier(id))" );

	$config->db->exec( "CREATE TABLE `cntrl` (      
						`id` INTEGER PRIMARY KEY AUTOINCREMENT,
						`type` varchar(255),            
						`value` TEXT)" );

	$config->db->exec( "INSERT INTO `cntrl` (type, value) VALUES (
					  'orderState',0)" );

	$config->db->exec( "INSERT INTO `cntrl` (type, value) VALUES (
					  'regIsAllowed',1)" );

	$config->db->exec( "INSERT INTO `cntrl` (type, value) VALUES (
					'userWhoIsOrdering',0)" );

	$config->db->exec( "INSERT INTO `cntrl` (type, value) VALUES (
              'arrivalInfo',' ')" );


	$config->db->exec( "INSERT INTO user (login, password, isAdmin) VALUES ('$user', '$passwordHash', 1)" );

	$supplierID = 1;
	// open folder "src"
	if ( $handle = opendir( './src/' ) ) {
		// read folder
		while ( ( $file = readdir( $handle ) ) !== false ) {
			flush();
			usleep( 1 );

			// read only files
			if ( $file != "."
				AND $file != ".." ) {

				// create supplier
				$supplier = str_replace( ".txt", "", $file );
				$config->db->exec( "INSERT INTO `supplier` (name, active)
                            VALUES ('" . $supplier . "', 0)" );

				// read meal(s)
				$handleFile = fopen( "src/" . $file, "r" );
				if ( $handleFile ) {
					while ( ( $line = fgets( $handleFile ) ) !== false ) {

						flush();
						usleep( 1 );
						// process the line read.
						$line = utf8_encode( $line );
						$splitted = explode( ";", $line );

						$config->db->exec( "INSERT INTO `supplierCard` (      
                            supplier_ID,
                            nr,
                            name,
                            ingredients,
                            price) 
                            VALUES(" . $supplierID . "," . $splitted[ 0 ] . ",'" . $splitted[ 1 ] . "','" . $splitted[ 2 ] . "','" . $splitted[ 3 ] . "')" );
					}

					fclose( $handleFile );
					$supplierID++;
				}
			}
		}
		closedir( $handle );
	}
	$config->db->commit();
	// check chmod 
	chmod( $database, 0777 );

	updateDatabase();

	addMessage( 'Datenbank erfolgreich erstellt <a href="index.php"> >>>weiter</a>' );

}


function showDeadline() {
	?>
	<script>
		var countDownDate = "<?php echo getTimeStampFreezingOrder() ?>" * 1000;
		var countDownStarted = "<?php echo getOrderTimestampStart() ?>" * 1000;

		var x = setInterval( function () {
			var now = new Date().getTime();
			var distance = countDownDate - now;

			var hours = Math.floor( ( distance % ( 1000 * 60 * 60 * 24 ) ) / ( 1000 * 60 * 60 ) );
			var minutes = Math.floor( ( distance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) );
			var seconds = Math.floor( ( distance % ( 1000 * 60 ) ) / 1000 );

			var deltaMax = countDownDate - countDownStarted;
			var deltaCurr = countDownDate - now;

			var position = deltaCurr / deltaMax * 100;
			// Display the result in the element with id="demo"
			document.getElementById( "progressbarPosition" ).innerHTML = hours + "h " + minutes + "m " + seconds + "s ";
			//document.getElementById("progressbarPosition").innerHTML = position;
			document.getElementById( "progressbarPosition" ).setAttribute( "style", "width:" + position + "%" );

			// If the count down is finished, write some text
			if ( distance < 0 ) {
				clearInterval( x );
				document.getElementById( "progressbarPosition" ).innerHTML = "EXPIRED";
			}
		}, 1000 );
	</script>
	<?php
}

function checkDiscount($page){
	
	global $config;
	
	$orderDetail = getOrderDiscountInfo($config->orderid);

	$config->orderDiscountThreshold = $orderDetail[0];
	$config->orderDiscountPercent 	= $orderDetail[1];
	$config->orderDiscountPaid   	= $orderDetail[2];
	
	if(($config->orderDiscountThreshold > 0) &&
	   ($config->orderDiscountThreshold <= $config->orderSum)){

		$page = preg_replace( "/\[\%discountThreshold\%\]/", $config->orderDiscountThreshold, $page );
		$page = preg_replace( "/\[\%discountPercent\%\]/", $config->orderDiscountPercent, $page );
		
		if($config->orderDiscountPaid == 0){
			$page = removeSection( "<!-- order discount paid -->", $page );
		}
	}
	else{
		$page = removeSection( "<!-- order discount info -->", $page );	
	}
	
	return $page;
}

function check4arrival() {
	//global $config;

	$arrival = getArrivalInfo()
	?>
	<script>
		var arrival = "<?php echo $arrival[0] ?>";

		// check for valid timestamp
		//no valid timestamp, start interval until valid timeStamp
		var x = setInterval( function () {
			if ( arrival == 1 ) {
				//document.getElementById('pizzaAlarm').play();
				clearInterval( x );
			}
			location.reload( true );

		}, 5000 );
	</script>
	<?php
}
?>
