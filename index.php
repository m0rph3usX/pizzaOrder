<?php
#-------------------------------------------

include_once 'config.php';
include_once 'utils.php';

$config = new ConfigStruct();


#check for existing database
if (!file_exists($database)) {
 header("Location: setup.php");
 die;
}   

$config->db 	      = new PDO('sqlite:' . $database);
$config->orderid      = getCurrentOrderId();
$config->messageCount = 0;

#check database version
updateDatabase();

$config->userid = -1;
if(isset($_SESSION['userid'])){
	$config->userid = $_SESSION['userid'];  
}

$config->login   = getLogin($config->userid);
$config->isHistory = false;


$template_userpanelTxt = "/\[\%userpanel\%\]/";
$template_ordersTxt    = "/\[\%orders\%\]/";

# define templates:
$template 	    = "template/index.html";

#-------------------------------------------
# load template:
$page        = file_get_contents($template);
#-------------------------------------------


if($config->userid == -1){
	$page = removeSection("<!-- order finished section -->"	, $page);
}
if(!isset($_SESSION['userid'])){
	input_register();
	input_login();

	$page = removeSection("<!-- logout section -->", $page);	
	$page = removeSection("<!-- new order section -->"	, $page);	
	$page = removeSection("<!-- finish order section -->", $page);	
	$page = removeSection("<!-- order arrival info -->"	    , $page);
} else if($config->userid != getUserWhoIsOrdering()){
	$page = removeSection("<!-- finish order section -->", $page);
}
	
eventUpdateArrivalInfo();
if($config->userid > -1){

	eventVirtualPay();
	input_logout();

	eventOrderKill	  ();
	eventOrderAdd 	  ();
	eventOrderPaid	  ();
	eventOrderComment ();
	eventOrderFinished();
	eventOrderRestart ();
	

	$page = preg_replace("/\[\%loginName\%\]/" ,  $config->login, $page);	
	$page = preg_replace("/\[\%money\%\]/" 	   ,  countMoney(), $page);	
	$page = preg_replace("/\[\%moneySpent\%\]/",  getOverallSpentMoneyFromLogin($config->userid), $page);	
	# load userpanel
	// hide login / register sections
	$page = removeSection("<!-- login section -->", $page);
	$page = removeSection("<!-- register section -->", $page);
}

$timestamp = getTimeStampFreezingOrder();;

			//$timestampNow = time();
			//$timestampFreeze = $timestampNow + 3600 * $timeHH + $timeMM * 60;
			
if($timestamp <= time()){
	$timestamp = "ABGELAUFEN!!!";
	$page = preg_replace("/\[\%orderDeadline\%\]/" , $timestamp , $page);
}
else{
	$page = preg_replace("/\[\%orderDeadline\%\]/" , date('d.m.o -  H:i:s' ,$timestamp). "h", $page);
}
$page = preg_replace("/\[\%version\%\]/" , getVersion(), $page);


eventButtonStartNewOrder();	
# load orders
switch (getOrderState()) {
case 0:
	#---------------------------------------------------------------------------
	#  START NEW ORDER 
	#---------------------------------------------------------------------------
	$page = removeSection("<!-- incoming orders section -->", $page);
	$page = removeSection("<!-- order items section -->"    , $page); 
	$page = removeSection("<!-- order finished section -->"	, $page);
	$page = removeSection("<!-- order deadline -->"	        , $page);
	$page = removeSection("<!-- order arrival info -->"	    , $page);
	$page = removeSection("<!-- order arrival control -->"	, $page);
	
	//$page = preg_replace($template_ordersTxt   , $layout_startNewOrder, $page);	
	
	#----------------- create supplier list ------------------------------------
	$page = preg_replace("/\[\%supplierList\%\]/" ,  getSupplierList(), $page);
	
	#----------------- fill hours ----------------------------------------------
	$page = preg_replace("/\[\%countDwnHH\%\]/" , getComboboxHH(11), $page);
	#----------------- fill minutes --------------------------------------------
	$page = preg_replace("/\[\%countDwnMM\%\]/" , getComboboxMM(0), $page);
	
	break;
case 1:	
	#---------------------------------------------------------------------------
	#  ORDER RUNNING
	#---------------------------------------------------------------------------
	
	if(getTimeStampFreezingOrder() < time()){
		closeCurrentOrder();
		header("Location: index.php");
	}else{
		$page = removeSection("<!-- new order section -->", $page);		
		$page = removeSection("<!-- order finished section -->"    , $page); 
		$page = removeSection("<!-- order arrival info -->"	    , $page);
		$page = removeSection("<!-- order arrival info storno-->"	, $page);
		$page = removeSection("<!-- order arrival control -->"	    , $page);
		$page = removeSection("<!-- order arrived -->"	, $page);
		
		
		
		$table = createIncomingOrdersTable(extractSection("<!-- incoming orders section -->", $page));
		$page = replaceSection("<!-- incoming orders section -->", $table, $page);
		
		$table = createOrderTable(extractSection("<!-- order items section row -->", $page));	
		$page  = replaceSection("<!-- order items section row -->", $table, $page); 
	}



	break;
case 2:      
	#---------------------------------------------------------------------------
	#  ORDER FINISHED
	#---------------------------------------------------------------------------          	
	$page = removeSection("<!-- new order section -->"	    , $page);
	$page = removeSection("<!-- order items section -->", $page); 
	$page = removeSection("<!-- finish order section -->"	    , $page);
	$page = removeSection("<!-- order deadline -->"	    , $page);	
	$page = preg_replace("/\[\%phoneNumber\%\]/" , getCurrentSupplierPhoneNr(), $page);
	
	if(getUserWhoIsOrdering() == $config->userid){
		//$page = removeSection("<!-- order arrival info -->"	    , $page);
		$page = preg_replace("/\[\%timeArrivalHH\%\]/" , getComboboxHH(12), $page);
		$page = preg_replace("/\[\%timeArrivalMM\%\]/" , getComboboxMM(15), $page);
	}
	else{
		$page = removeSection("<!-- order arrival control -->"	    , $page);
	}
	
	$arrivalInfo = getArrivalInfo();
	
	if($arrivalInfo[0] > 0){
		$page = removeSection("<!-- order arrival info -->"	    , $page);
		$page = removeSection("<!-- order arrival control -->"	, $page);

		$page = preg_replace("/\[\%orderArrivalTime\%\]/" , date("d.m.Y - H:i", $arrivalInfo[0]), $page);
		$page = preg_replace("/\[\%orderArrivalLogin\%\]/" , $arrivalInfo[1], $page);
		
		if($config->userid == $arrivalInfo[2]){
			eventButtonOrderArrivedStorno();
		}
		else{
			$page = removeSection("<!-- order arrival info storno-->"	, $page);
		}
	}
	else{
		eventButtonOrderArrived();
		$page = removeSection("<!-- order arrived -->"	, $page);
		$page = removeSection("<!-- order arrival info storno-->"	, $page);
	}
	
	$table = createIncomingOrdersTable(extractSection("<!-- incoming orders section -->", $page));
	$page = replaceSection("<!-- incoming orders section -->", $table, $page);
	
	$page = preg_replace("/\[\%orderArrival\%\]/" , date("d.m.Y - H:i", getTimeStampArrivalOrder()), $page);
	
	break;
}
echo $page;

showMessages();

?>