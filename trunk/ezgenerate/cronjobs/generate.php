<?php

/**
 * Cronjob that reads the source Xml file and creates the contents with random data
 *
 * $LastChangedDate$
 * $Revision$
 * $Author$
 */

include_once('extension/ezgenerate/classes/helperXml.class.php');

//initialize INI
$generateIni 	= eZINI::instance( 'generate.ini' );

$logFile = $generateIni->variable('Configuration', 'LogFile');

//initialize logs
$logs = new eZLog();
$logs->write("Begin generation", $logFile);

//initialize cli
//$cli = eZCLI::instance();
$cli->setUseStyles( true );
$cli->output( $cli->stylize( 'cyan', "Begin generation\n" ), false );

//check parameters
$fileName = '';
foreach( $_SERVER['argv'] as $param )  {
	
	//find the param with the source file
	if( strstr($param,'--file') ) {
		$arguments = explode('=',$param);
		$fileName = 'extension/ezgenerate/'.$generateIni->variable('Configuration', 'SourcePath').$arguments[1];	
	}
}

if( $fileName == '' ) {
	$cli->output( $cli->stylize( 'red', "No parameter for file\n Use --file=source.xml" ), false );
	return;
} 


//fetch the user to create objects
$user = eZUser::fetch($generateIni->variable('User', 'ObjectId'));

if( !$user ) {
	$cli->output( $cli->stylize( 'red', "The user <".$generateIni->variable('User', 'ObjectId')."> doesn't exist.\n" ), false );
	$logs->write("The user <".$generateIni->variable('User', 'ObjectId')."> doesn't exist.", $logFile);
	return;
}
$user->loginCurrent();

//begin the generation
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
			$result = HelperXml::generate($xml);
			
			if( $result ) {
				$cli->output( $cli->stylize( 'green', "Generation succeed !\n" ), false );
			}
			else {
				$cli->output( $cli->stylize( 'red', "An error occured during the creation.\n" ), false );
			}
			
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

$cli->output( $cli->stylize( 'cyan', "End\n" ), false );
$logs->write("End", $logFile);
$logs->write("-------------", $logFile);


?>