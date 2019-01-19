<?php
#-------------------------------------------
include 'config.php';
include 'utils.php';

# define templates:
$template 	    = "template/setup.html";

#-------------------------------------------
# load template:
$page        = file_get_contents($template);
#-------------------------------------------

#check for existing database
if (!file_exists($database)) {
 echo "no database found! - run setup.php";       
 die;
}
else{
	$page = removeSection("<!-- create db section -->", $page);
	
	#check database version
	updateDatabase();
}   



$userid = -1;
if(isset($_SESSION['userid'])){
	$userid = $_SESSION['userid'];
	
	if(!isAdmin()){die;}
}
else{die;}

// user is admin --->
eventSetUserIsAdmin();
eventSetUserIsBank();
eventButtonSetCurrentOrderer();

$page = preg_replace("/\[\%version\%\]/" , getVersion(), $page);
$page = adminSetCurrentOrderer($page);
$page = adminShowUserData($page);


$template_userpanelTxt = "/\[\%userpanel\%\]/";
$template_ordersTxt    = "/\[\%orders\%\]/";



echo $page;
?>