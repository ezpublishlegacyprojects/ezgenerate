<?php

/**
 * Functions used to read the source XML file and create the content tree
 *
 * $LastChangedDate$
 * $Revision$
 * $Author$
 */
class HelperXml
{
	//recursive function
	public static function checkClasses($simpleXmlNode) {
		
		$result = true;
		
		//if this is not the root
		if(!$simpleXmlNode['node_id']) {
			//get the class from XML, and check if the class exists in eZ database
			$className = (string)$simpleXmlNode['class'];
			$contentClass = eZContentClass::fetchByIdentifier( $className );
			if ( !is_object( $contentClass ) )
			{
				return false;
			}
		}
		
		//for each child, check if the class exists
		foreach($simpleXmlNode->children() as $child) {
			$result = $result && self::checkClasses($child);
		}
		
		return $result;
	}
	
	public static function generate($simpleXmlNode, $parentNodeId = null ) {
		
		$result = true;
		
		//general case : if this is not the root
		if(!$simpleXmlNode['node_id']) {
			
			//get the class from XML, and check if the class exists in eZ database
			$className	= (string)$simpleXmlNode['class'];
			$qty		= (string)$simpleXmlNode['qty'];
			
			//quantity is not mandatory : default = 1
			if( !is_numeric($qty) ) {
				$qty = 1;
			}
			
			eZDebug::writeNotice( "Creating $qty node(s) of $className in node $parentNodeId", 'HelperXml::generate' );
			
			//define params for creating nodes
			$params['parent_node_id'] = $parentNodeId;
       		$params['class_identifier'] = $className;
       		
       		//creating all nodes
       		for($i = 0; $i < $qty; $i++) {
				$object = eZExtendedContentFunctions::createAndPublishObjectWithRandomData($params);
								
				if($object) {
					//try to create all children in this node
					$parentNodeId = $object->mainNodeID();
					foreach($simpleXmlNode->children() as $child) {
						$result = $result && self::generate($child, $parentNodeId);
					}
				}
				else {
					$result = false;
					eZDebug::writeError( "No node created in $parentNodeId", 'HelperXml::generate' );
				}
       		}
		}
		else {
			$parentNodeId = $simpleXmlNode['node_id'];
			//for each child, check if the class exists
			foreach($simpleXmlNode->children() as $child) {
				$result = $result && self::generate($child, $parentNodeId);
			}
		}
		
		
		return $result;
	}
	
}
?>