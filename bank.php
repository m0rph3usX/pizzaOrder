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
$template 	    = "template/bank.html";

#-------------------------------------------
# load template:
$page        = file_get_contents($template);
#-------------------------------------------


if(isBankTransactor() == 0){
  $page = removeSection("<!-- bank section -->", $page);
}
else{
  eventBankInput();
  $combobox = addUserIdLogin(extractSection("<!-- bank section customer bankInput-->", $page));
  $page     = replaceSection("<!-- bank section customer bankInput-->", $combobox , $page); 
}

if($userid > -1){
//if(isset($_SESSION['userid'])){
	input_logout();

	$page = preg_replace("/\[\%loginName\%\]/" ,  getLogin(), $page);	
	$page = preg_replace("/\[\%money\%\]/"    ,  countMoney(), $page);	
}
else{
	$page = removeSection("<!-- login section -->" , $page);	
}



$page = showBankInfo($page);
	
//if(isset($_SESSION['userid'])){

//	eventVirtualPay();
	//input_logout();

	//eventOrderKill	  ();
	//eventOrderAdd 	  ();
	//eventOrderPaid	  ();
	//eventOrderComment ();
	//eventOrderFinished();
	//eventOrderRestart ();

	//$page = preg_replace("/\[\%loginName\%\]/" ,  getLogin(), $page);	
	//$page = preg_replace("/\[\%money\%\]/" ,  countMoney(), $page);	
	//# load userpanel
	// hide login / register sections
	//$page = removeSection("<!-- login section -->", $page);
	//$page = removeSection("<!-- register section -->", $page);
//}

$page = preg_replace("/\[\%version\%\]/" , getVersion(), $page);

echo $page;
?>