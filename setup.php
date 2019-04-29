<?php
#-------------------------------------------

include_once 'config.php';
include_once 'utils.php';

$config = new ConfigStruct();

$config->db 	      = new PDO('sqlite:' . $database);
$config->messageCount = 0;

# define templates:
$template 	    = "template/setup.html";

#-------------------------------------------
# load template:
$page        = file_get_contents($template);
#-------------------------------------------

#check for existing database
if (!dbInitialized()) {
	$page = removeSection("<!-- admin order section -->", $page);
	$page = removeSection("<!-- admin user section -->" , $page);
	$page = preg_replace("/\[\%version\%\]/" , '', $page);
	
	eventButtonCreateDatabase();
	
	echo $page;
	showMessages();
}
else{
	$page = removeSection("<!-- create db section -->", $page);
	
	$config->orderid      = getCurrentOrderId();
	
	#check database version
	updateDatabase();
	
	$config->userid = -1;
	if(isset($_SESSION['userid'])){
		$config->userid = $_SESSION['userid'];  
	}

	$config->login   = getLogin($config->userid);
	
	$page = preg_replace("/\[\%version\%\]/" , getVersion(), $page);
	
	if(isAdmin()){

		// user is admin --->
		eventSetUserIsAdmin();
		eventSetUserIsBank();
		eventButtonSetCurrentOrderer();
		eventCreateResetCode();
		eventDeleteResetCode();
		
		$page = adminSetCurrentOrderer($page);
		$page = adminShowUserData($page);
		
		
		echo $page;
		showMessages();
	}
	else{	
		header("Location: index.php");
	}
}   

?>