<?php
#-------------------------------------------
include 'config.php';
include 'utils.php';

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




if(!isset($_SESSION['userid'])){
	input_register();
	input_login();
	
	$page = removeSection("<!-- logout section -->", $page);	
	$page = removeSection("<!-- new order section -->"	, $page);	
	$page = removeSection("<!-- finish order section -->", $page);
	
} else if($userid != getUserWhoIsOrdering()){
	$page = removeSection("<!-- finish order section -->", $page);
}
	
if(isset($_SESSION['userid'])){

	input_logout();

	eventOrderKill	  ();
	eventOrderAdd 	  ();
	eventOrderPaid	  ();
	eventOrderComment ();
	eventOrderFinished();
	eventOrderRestart ();

	$page = preg_replace("/\[\%loginName\%\]/" ,  getLogin(), $page);	
	# load userpanel
	// hide login / register sections
	$page = removeSection("<!-- login section -->", $page);
	$page = removeSection("<!-- register section -->", $page);
}


# load orders
switch (getOrderState()) {
case 0:
	#---------------------------------------------------------------------------
	#  START NEW ORDER 
	#---------------------------------------------------------------------------
	$page = removeSection("<!-- incoming orders section -->", $page);
	$page = removeSection("<!-- order items section -->"    , $page); 
	$page = removeSection("<!-- order finished section -->"	, $page);
	
	if($userid == -1){
//		$page = removeSection("<!-- new order section -->"	, $page);
	}
	else{
		//$page = removeSection("<!-- finish order section -->"   , $page);
	}
	
	
	showOrderStarted();
	//$page = preg_replace($template_ordersTxt   , $layout_startNewOrder, $page);	
	
	#----------------- create supplier list ------------------------------------
	$page = preg_replace("/\[\%supplierList\%\]/" ,  getSupplierList(), $page);
	
	#----------------- fill hours ----------------------------------------------
	$htmlTxt = '';
	$zero   = '';
	// write hours to countdown
	for ($hh = 0; $hh < 24; $hh++) {
		if($hh < 10){$zero   = '0';} else {$zero   = '';}
		$htmlTxt = $htmlTxt . "<option value='".$hh."'>".$zero .$hh." </option>";                                                                    
	}  
	$page = preg_replace("/\[\%countDwnHH\%\]/" , $htmlTxt, $page);
	
	#----------------- fill minutes --------------------------------------------
	$htmlTxt = '';
	$zero   = '';
	// write minutes to countdown
	for ($mm = 0; $mm < 60; $mm = $mm +5) {
		if($mm < 10){$zero   = '0';} else {$zero   = '';}
		$htmlTxt = $htmlTxt . "<option value='".$mm."'>".$zero .$mm." </option>";                                                                    
	}  
	$page = preg_replace("/\[\%countDwnMM\%\]/" , $htmlTxt, $page);
	break;
case 1:	
	#---------------------------------------------------------------------------
	#  ORDER RUNNING
	#---------------------------------------------------------------------------
	//orderRunning()
	$page = removeSection("<!-- new order section -->", $page);		
	$page = removeSection("<!-- order finished section -->"    , $page); 
	
	$table = createIncomingOrdersTable(extractSection("<!-- incoming orders section -->", $page));
	$page = replaceSection("<!-- incoming orders section -->", $table, $page);
	
	$table = createOrderTable(extractSection("<!-- order items section row -->", $page));	
	$page = replaceSection("<!-- order items section row -->", $table, $page); 

	break;
case 2:      
	#---------------------------------------------------------------------------
	#  ORDER FINISHED
	#---------------------------------------------------------------------------          	
	$page = removeSection("<!-- new order section -->"	    , $page);
	$page = removeSection("<!-- order items section -->", $page); 
	$page = removeSection("<!-- finish order section -->"	    , $page);
	
	$table = createIncomingOrdersTable(extractSection("<!-- incoming orders section -->", $page));
	$page = replaceSection("<!-- incoming orders section -->", $table, $page);
	
	break;
}

echo $page;
?>