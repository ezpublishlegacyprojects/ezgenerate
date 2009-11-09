<?php


class eZExtendedContentFunctions extends eZContentFunctions
{
	public static function createAndPublishObjectWithRandomData( $params )
	{
		$parentNodeID = $params['parent_node_id'];
		$classIdentifier = $params['class_identifier'];
		$creatorID = isset( $params['creator_id'] ) ? $params['creator_id'] : false;
		$storageDir = isset( $params['storage_dir'] ) ? $params['storage_dir'] : '';
		$contentObject = false;
		
		$generateINI = eZINI::instance( 'generate.ini' );
		srand();	//initialize all the randoms

		$parentNode = eZContentObjectTreeNode::fetch( $parentNodeID, false, false );
		
		print " - Creation of $classIdentifier in $parentNodeID";

		if ( is_array( $parentNode ) )
		{
			$contentClass = eZContentClass::fetchByIdentifier( $classIdentifier );
			if ( is_object( $contentClass ) )
			{
				$db = eZDB::instance();
				$db->begin();

				$contentObject = $contentClass->instantiate( $creatorID );
				$contentObject->store();

				$nodeAssignment = eZNodeAssignment::create( array( 'contentobject_id' => $contentObject->attribute( 'id' ),
                                                                   'contentobject_version' => $contentObject->attribute( 'current_version' ),
                                                                   'parent_node' => $parentNodeID,
                                                                   'is_main' => 1,
                                                                   'sort_field' => $contentClass->attribute( 'sort_field' ),
                                                                   'sort_order' => $contentClass->attribute( 'sort_order' ) ) );
				$nodeAssignment->store();

				$version = $contentObject->version( 1 );
				$version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
				$version->setAttribute( 'status', eZContentObjectVersion::STATUS_DRAFT );
				$version->store();

				$attributes = $contentObject->attribute( 'contentobject_attributes' );
				
				//load the source of the text
				//TODO: mettre la source en parametre
				$generateIni 	= eZINI::instance( 'generate.ini' );
				$extensionDir	= eZExtension::baseDirectory().'/ezgenerate/';
				$sourceFile		= $extensionDir.$generateIni->variable('Configuration', 'TextSource');
				
				$textGenerator	= TextGenerator::getInstance($sourceFile);
				
				if(!$textGenerator) {
					return false;
				}

				//for each attribute, fill with random data
				foreach( $attributes as $attribute )
				{
					$attributeIdentifier = $attribute->attribute( 'contentclass_attribute_identifier' );
					$attributeId = $attribute->attribute( 'contentclassattribute_id' );
					$classAttribute = eZContentClassAttribute::fetch($attributeId);
					
					$dataString = '';
					
					//time to fill with random data depending of the datatype
					switch ( $datatypeString = $attribute->attribute( 'data_type_string' ) )
					{
						case 'ezstring':
							$maxLen = $classAttribute->attribute(eZStringType::MAX_LEN_FIELD);
							//try to get a realistic length
							if( $maxLen < 15 && $maxLen > 0 ) {
								$stringLength = rand(1, $maxLen);
							}
							else {
								$stringLength = rand(15, $maxLen);
							}
							$dataString = substr( $textGenerator->getRandomText(1), 0, $stringLength-1);
							break;
							
						case 'eztext':
							$dataString = $textGenerator->getRandomText(rand(1, 5));
							break;
							
						case 'ezxmltext':
							$parser = new eZSimplifiedXMLInputParser( $contentObject->attribute( 'id' ) );
							$parser->setParseLineBreaks( true );
							$document = $parser->process( nl2br($textGenerator->getRandomText(rand(1, 5))) );

			                if ( !is_object( $document ) ) {
			                	eZDebug::writeError( "No dom document returned by xml parser for object id = ".$contentObject->attribute( 'id' ), 'eZExtendedContentFunctions::createAndPublishObjectWithRandomData' );
			                    $errors = $parser->getMessages();
			                    foreach ( $errors as $error )  {
			                    	eZDebug::writeError( "* $error", 'eZExtendedContentFunctions::createAndPublishObjectWithRandomData' );
			                    }
			                }

               				$dataString = eZXMLTextType::domString( $document );
							break;
						
						case 'ezdatetime':
						case 'ezdate':
							$dataString = time();
							break;
							
						case 'ezkeyword':
							$str = substr( $textGenerator->getRandomText(1), 0, 30);
							$dataString = str_replace(' ', ',', $str);
							break;
							
						case 'ezboolean':
							$dataString = (rand(0,1) == 0) ? false : true;
							break;
								
						case 'ezbinaryfile':
						case 'ezmedia':
							//get the files
							$files = scandir($extensionDir. 'data/files/');
							$files = array_slice($files, 2);
							
							//pick up a random file from the directory
							$dataString = $extensionDir. 'data/files/'.$files[rand(0, count($files) - 1)];
							break;
							
						case 'ezimage':
								//get the files
								$images = scandir($extensionDir. 'data/images/');
								$images = array_slice($images, 2);
								
								//pick up a random image from the directory
								$dataString = $extensionDir. 'data/images/'.$images[rand(0, count($images) - 1)];
								break;
						default:
					}

					$attribute->fromString( $dataString );
					$attribute->store();
				}


				$operationResult = eZOperationHandler::execute( 'content', 'publish', array( 'object_id' => $contentObject->attribute( 'id' ),
                                                                                             'version' => 1 ) );
				//commit if all went fine
				if( $operationResult['status'] ) {
					$db->commit();
				}
				else {
					$db->rollback();
					eZDebug::writeError( "Can not publish node : status '".$operationResult['status']."'", 'eZExtendedContentFunctions::createAndPublishObjectWithRandomData' );
				}
			}
			else
			{
				eZDebug::writeError( "Content class with identifier '$classIdentifier' doesn't exist.", 'eZExtendedContentFunctions::createAndPublishObjectWithRandomData' );
			}
		}
		else
		{
			eZDebug::writeError( "Node with id '$parentNodeID' doesn't exist.", 'eZExtendedContentFunctions::createAndPublishObjectWithRandomData' );
		}
		
		print " -> node ".$nodeAssignment->attribute('id')." created. \n";
		return $contentObject;
	}
}

?>