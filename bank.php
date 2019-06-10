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

$config->db = new PDO( 'sqlite:' . $database );
$config->orderid = getCurrentOrderId();


#check database version
updateDatabase();

$config->userid = -1;
if ( isset( $_SESSION[ 'userid' ] ) ) {
	$config->userid = $_SESSION[ 'userid' ];
}

$config->login = getLogin( $config->userid );




$template_userpanelTxt = "/\[\%userpanel\%\]/";
$template_ordersTxt = "/\[\%orders\%\]/";

# define templates:
$template = "template/bank.html";

#-------------------------------------------
# load template:
$page = file_get_contents( $template );
#-------------------------------------------


$combobox = addUserIdLogin( extractSection( "<!-- customer section transfer -->", $page ),1 );
$page = replaceSection( "<!-- customer section transfer -->", $combobox, $page );
eventBankTransfer();

if ( isBankTransactor() == 0 ) {
	$page = removeSection( "<!-- bank section -->", $page );
	
} else {
	eventBankInput();
	$combobox = addUserIdLogin( extractSection( "<!-- bank section customer bankInput-->", $page ),0 );
	$page = replaceSection( "<!-- bank section customer bankInput-->", $combobox, $page );
}

if ( $config->userid > -1 ) {
	input_logout();

	$page = preg_replace( "/\[\%loginName\%\]/", $config->login, $page );
	$page = preg_replace( "/\[\%money\%\]/", countMoney(), $page );
} else {
	$page = removeSection( "<!-- login section -->", $page );
}

$page = showBankInfo( $page );

$page = preg_replace( "/\[\%version\%\]/", getVersion(), $page );

echo $page;
?>