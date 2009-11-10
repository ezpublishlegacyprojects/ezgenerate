<?php
/**
 * Generate some random text
 *
 * $LastChangedDate$
 * $Revision$
 * $Author$
 */
class TextGenerator 
{
	/**
	 * Create a generator
	 * @param string $file XML source file
	 * @return TextGenerator or null
	 */
	public static function getInstance($file) {
		if( is_readable($file) ) {
			return new TextGenerator($file);
		}
		else {
			return null;
		}
	}
	
	/**
	 * Get a random text
	 * @param integer $nbParagraphs Number of paragraphs desired
	 * @return Random text
	 */
	public function getRandomText($nbParagraphs = 3) {
		
		if( $nbParagraphs < 0 ) {
			return false;
		}
		
		$text = array();
		for($i = 0; $i < $nbParagraphs; $i++) {
			$paragraphNumber = rand(1,$this->nbParagraphs);
			$randomParagraph = $this->data->xpath("/document/paragraph[$paragraphNumber]");
			$text[] = (string)$randomParagraph[0];
		}
		return implode($text, "\n");
	}
	
	
	
	private function __construct($fileName) {
		$this->data = simplexml_load_file($fileName);
		if( $this->data ) {
			$this->nbParagraphs = count($this->data->children());
		}
		else {
			$this->nbParagraphs = 0;
		}
	}
	
	private $data;
	private $nbParagraphs;
}

?>