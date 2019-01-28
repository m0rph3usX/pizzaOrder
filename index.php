<?php
#-------------------------------------------
include_once 'config.php';
include_once 'utils.php';

#check for existing database
if (!file_exists($database)) {
 echo "no database found! - run setup.php";       
 die;
}   

#check database version
updateDatabase();

$userid = -1;
if(isset($_SESSION['userid'])){
	$userid = $_SESSION['userid'];
}

$template_userpanelTxt = "/\[\%userpanel\%\]/";
$template_ordersTxt    = "/\[\%orders\%\]/";

# define templates:
$template 	    = "template/index.html";

#-------------------------------------------
# load template:
$page        = file_get_contents($template);
#-------------------------------------------


//if(isBankTransactor() == 0){
//  $page = removeSection("<!-- bank section -->", $page);
//}
//else{
//  eventBankInput();
//  $combobox = addUserIdLogin(extractSection("<!-- bank section customer bankInput-->", $page));
//  $page  = replaceSection("<!-- bank section customer bankInput-->", $combobox , $page); 
//}


if($userid == -1){
	$page = removeSection("<!-- order finished section -->"	, $page);
}
if(!isset($_SESSION['userid'])){
	input_register();
	input_login();

	$page = removeSection("<!-- logout section -->", $page);	
	$page = removeSection("<!-- new order section -->"	, $page);	
	$page = removeSection("<!-- finish order section -->", $page);	
} else if($userid != getUserWhoIsOrdering()){
	$page = removeSection("<!-- finish order section -->", $page);
}
	
eventUpdateArrivalInfo();
if(isset($_SESSION['userid'])){

	eventVirtualPay();
	input_logout();

	eventOrderKill	  ();
	eventOrderAdd 	  ();
	eventOrderPaid	  ();
	eventOrderComment ();
	eventOrderFinished();
	eventOrderRestart ();
	

	$page = preg_replace("/\[\%loginName\%\]/" ,  getLogin(), $page);	
	$page = preg_replace("/\[\%money\%\]/" ,  countMoney(), $page);	
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



# load orders
switch (getOrderState()) {
case 0:
	#---------------------------------------------------------------------------
	#  START NEW ORDER 
	#---------------------------------------------------------------------------
	$page = removeSection("<!-- incoming orders section -->", $page);
	$page = removeSection("<!-- order items section -->"    , $page); 
	$page = removeSection("<!-- order finished section -->"	, $page);
	$page = removeSection("<!-- order deadline -->"	    , $page);
	$page = removeSection("<!-- order arrival info -->"	    , $page);
	$page = removeSection("<!-- order arrival control -->"	    , $page);
	showOrderStarted();
	//$page = preg_replace($template_ordersTxt   , $layout_startNewOrder, $page);	
	
	#----------------- create supplier list ------------------------------------
	$page = preg_replace("/\[\%supplierList\%\]/" ,  getSupplierList(), $page);
	
	#----------------- fill hours ----------------------------------------------
	//$htmlTxt = '';
	//$zero   = '';
	// write hours to countdown
	//for ($hh = 0; $hh < 24; $hh++) {
	//	if($hh < 10){$zero   = '0';} else {$zero   = '';}
	//	$htmlTxt = $htmlTxt . "<option value='".$hh."'>".$zero .$hh." </option>";                                                                    
	//}  
	//$page = preg_replace("/\[\%countDwnHH\%\]/" , $htmlTxt, $page);
	$page = preg_replace("/\[\%countDwnHH\%\]/" , getComboboxHH(), $page);
	#----------------- fill minutes --------------------------------------------
	//$htmlTxt = '';
	//$zero   = '';
	// write minutes to countdown
	//for ($mm = 0; $mm < 60; $mm = $mm +5) {
	//	if($mm < 10){$zero   = '0';} else {$zero   = '';}
	//	$htmlTxt = $htmlTxt . "<option value='".$mm."'>".$zero .$mm." </option>";                                                                    
	//}  
	
	
	//$page = preg_replace("/\[\%countDwnMM\%\]/" , $htmlTxt, $page);
	$page = preg_replace("/\[\%countDwnMM\%\]/" , getComboboxMM(), $page);
	
	break;
case 1:	
	#---------------------------------------------------------------------------
	#  ORDER RUNNING
	#---------------------------------------------------------------------------
	//orderRunning()
	$page = removeSection("<!-- new order section -->", $page);		
	$page = removeSection("<!-- order finished section -->"    , $page); 
	$page = removeSection("<!-- order arrival info -->"	    , $page);
	$page = removeSection("<!-- order arrival control -->"	    , $page);
	$table = createIncomingOrdersTable(extractSection("<!-- incoming orders section -->", $page));
	$page = replaceSection("<!-- incoming orders section -->", $table, $page);
	
	$table = createOrderTable(extractSection("<!-- order items section row -->", $page));	
	$page  = replaceSection("<!-- order items section row -->", $table, $page); 


	break;
case 2:      
	#---------------------------------------------------------------------------
	#  ORDER FINISHED
	#---------------------------------------------------------------------------          	
	$page = removeSection("<!-- new order section -->"	    , $page);
	$page = removeSection("<!-- order items section -->", $page); 
	$page = removeSection("<!-- finish order section -->"	    , $page);
	$page = removeSection("<!-- order deadline -->"	    , $page);	
	
	
	if(getUserWhoIsOrdering() == $userid){
		//$page = removeSection("<!-- order arrival info -->"	    , $page);
		$page = preg_replace("/\[\%timeArrivalHH\%\]/" , getComboboxHH(), $page);
		$page = preg_replace("/\[\%timeArrivalMM\%\]/" , getComboboxMM(), $page);
	}
	else{
		$page = removeSection("<!-- order arrival control -->"	    , $page);
	}
	
	$table = createIncomingOrdersTable(extractSection("<!-- incoming orders section -->", $page));
	$page = replaceSection("<!-- incoming orders section -->", $table, $page);
	
	$page = preg_replace("/\[\%orderArrival\%\]/" , date("d.m.Y - H:i", getTimeStampArrivalOrder()), $page);
	
	break;
}

echo $page;

	script_countdown(getTimeStampFreezingOrder());
?>