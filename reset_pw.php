<?php
#-------------------------------------------
include_once 'config.php';
include_once 'utils.php';

$resetcode = -1;
if(isset($_GET['resetcode'])){
	$resetcode = $_GET['resetcode'];
}




$config = new ConfigStruct();

#check for existing database
if (!file_exists($database)) {
 echo "no database found! - run setup.php";       
 die;
}   

$config->db 	 = new PDO('sqlite:' . $database);
$config->orderid = -1;


input_change_pw();


#check database version
updateDatabase();

$config->userid = -1;
if(isset($_SESSION['userid'])){
	$config->userid = $_SESSION['userid'];  
}

$config->login   = getLogin($config->userid);

# define templates:
$template 	    = "template/reset_pw.html";

#-------------------------------------------
# load template:
$page        = file_get_contents($template);
#-------------------------------------------


$userId = checkResetCode($resetcode);

if($userId > -1) {
	$page = preg_replace("/\[\%login\%\]/" ,  getLogin($userId), $page);
}
else{
	die;	
}
		

$page = preg_replace("/\[\%version\%\]/" , getVersion(), $page);

echo $page;

?>