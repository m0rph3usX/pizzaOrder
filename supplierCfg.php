<?php
#-------------------------------------------
include_once 'config.php';
include_once 'utils.php';

$config = new ConfigStruct();


#check for existing database
if ( !file_exists( $database ) ) {
	echo "no database found! - run setup.php";
	die;
}


$config->supplierId = -1;

if(isset($_GET[ 'id' ])){
	$config->supplierId = $_GET[ 'id' ];	
}
	


$config->db = new PDO( 'sqlite:' . $database );
$config->orderid = getCurrentOrderId();
$config->isHistory = true;

#check database version
updateDatabase();

$config->userid = -1;
if ( isset( $_SESSION[ 'userid' ] ) ) {
	$config->userid = $_SESSION[ 'userid' ];
}


$config->login = getLogin( $config->userid );




# define templates:
$template = "template/supplierCfg.html";

#-------------------------------------------
# load template:
$page = file_get_contents( $template );
#-------------------------------------------

if ( $config->userid > -1 ) {
	input_logout();

	$page = preg_replace( "/\[\%loginName\%\]/", $config->login, $page );
	$page = preg_replace( "/\[\%money\%\]/", countMoney(), $page );
} else {
	$page = removeSection( "<!-- login section -->", $page );
}


$page = showSupplierCfgList($page);
$page = showSuppliersCfg($page);



	
$page = preg_replace( "/\[\%version\%\]/", getVersion(), $page );


eventSaveSupplierCfgList();
eventSaveSupplierCfg();

echo $page;

?>