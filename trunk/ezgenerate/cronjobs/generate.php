<?php

include_once('extension/ezgenerate/classes/helperXml.class.php');

//initialize INI
$generateIni 	= eZINI::instance( 'generate.ini' );

$logFile = $generateIni->variable('Configuration', 'LogFile');

//initialize logs
$logs = new eZLog();
$logs->write("Begin generation", $logFile);

	
//initialize cli
$cli = eZCLI::instance();
$cli->setUseStyles( true );
$cli->output( $cli->stylize( 'cyan', "Begin generation\n" ), false );

//fetch the user to create objects
$user = eZUser::fetch($generateIni->variable('User', 'ObjectId'));

if( !$user ) {
	$cli->output( $cli->stylize( 'red', "The user <".$generateIni->variable('User', 'ObjectId')."> doesn't exist.\n" ), false );
	$logs->write("The user <".$generateIni->variable('User', 'ObjectId')."> doesn't exist.", $logFile);
	return;
}
$user->loginCurrent();


//file name without the first slash
$fileName = "extension/ezgenerate/data/sources/tree.xml";

if (is_readable($fileName)) {
    $xml = simplexml_load_file($fileName);
	$cli->output( $cli->stylize( 'green', "Loading $fileName\n" ), false );
    
	//get the root node and check the node exists
	$rootNodeId = (int)$xml['node_id'];
	$rootNode = eZFunctionHandler::execute('content','node', array('node_id' => $rootNodeId ) );
	
	if( $rootNode ) {
		
		//check all classes defined in xml
		if( HelperXml::checkClasses($xml) ) {
			$cli->output( $cli->stylize( 'green', "All classes defined are available.\n" ), false );
			
			//everything is fine, begin to generate
			HelperXml::generate($xml);
			
		}
		else {
			$cli->output( $cli->stylize( 'red', "A class from the XML structure doesn't exist in the database.\n" ), false );
		}
	}
	else {
		$cli->output( $cli->stylize( 'red', "The root node doesn't exist.\n" ), false );
	}
} 
else {
	$cli->output( $cli->stylize( 'red', "Loading $fileName failed\n" ), false );
}

//initialize BD
//$db = eZDB::instance ();

$cli->output( $cli->stylize( 'cyan', "End\n" ), false );
$logs->write("End", $logFile);
$logs->write("-------------", $logFile);


?>