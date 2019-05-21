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


if ( !isset( $_GET[ 'id' ] ) ) {
	die;
}

$config->db = new PDO( 'sqlite:' . $database );
$config->orderid = $_GET[ 'id' ];


#check database version
updateDatabase();

$config->userid = -1;
if ( isset( $_SESSION[ 'userid' ] ) ) {
	$config->userid = $_SESSION[ 'userid' ];
}

$config->login = getLogin( $config->userid );
$config->isHistory = true;



# define templates:
$template = "template/historyDetails.html";

#-------------------------------------------
# load template:
$page = file_get_contents( $template );
#-------------------------------------------

//$page = getHistoryOrderList($page);

//eventShowHistoryDetails();

$table = createIncomingOrdersTable( extractSection( "<!-- incoming orders section -->", $page ) );
$page = replaceSection( "<!-- incoming orders section -->", $table, $page );


if ( $config->userid > -1 ) {
	input_logout();

	$page = preg_replace( "/\[\%loginName\%\]/", $config->login, $page );
	$page = preg_replace( "/\[\%money\%\]/", countMoney(), $page );
} else {
	$page = removeSection( "<!-- login section -->", $page );
}

$page = preg_replace( "/\[\%version\%\]/", getVersion(), $page );

echo $page;

?>