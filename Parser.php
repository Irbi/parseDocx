<?php

/**
* Base class for the different types of file parsers
*/
class Parser
{
	/**
	* Source file
	*
	* @var string
	* @access public
	*/
	public		$sourceFile;

	/**
	* Source extension
	*
	* @var string
	* @access protected
	*/
	protected 	$fileExtension;

	/**
	* Result format. By default each element in the new string
	*
	* @var string
	* @access protected
	*/
	protected $resultFormat		= 'sep_string';

	/**
	* Result of parsing
	*
	* @var array
	* @access protected
	*/	
	protected $searchList 		= [];



	/**
	* Construct
	*
	* @param string source
	* @throws \Exception File doesn't exists
	* @throws \Exception Incorrect file extension
	* @return object
	*/
	public function __construct($source)
	{
		if (file_exists($source)) {
			$this -> sourceFile = $source;
			if (!$this -> checkFileExtension()) {
				throw new \Exception('Incorrect file extension, .' . $this -> fileExtension . ' expected');	
			}
		} else {
			throw new \Exception('Can\'t open file ' . $source);
		}

		return $this;
	}

	/**
	* Whether the file extension matches the called parser type
	*
	* @return boolean. False if extension doesn't match
	*/
	protected function checkFileExtension()
	{
		$fileInfo = pathinfo($this -> sourceFile);
		return $fileInfo['extension'] == $this -> fileExtension;
	}

	/**
	* Format the result
	*
	* @return mixed
	*/
	protected function formatResult()
	{
		switch($this -> resultFormat) 
		{
			case 'sep_string':
					$this -> searchList = "\n\r" . implode("\n\r", $this -> searchList) . "\n\r";
				break;
			default;
		}

		return $this -> searchList;
	}
}

/**
* Unpacks archives. Could be extended with other archive types
*/
class ArchiveManager
{
	/**
	* Source file
	*
	* @var string
	* @access public
	*/	
	public $sourceFile;

	/**
	* File to search in archive 
	*
	* @var string
	* @access public
	*/	
	public $dataFile 	= false;


	/**
	* Open zip archive, return extracted data file if it was set
	*
	* <code>
	* $archivedFile = new ArchiveManager();
	* $archivedFile -> setSourceFile('many_files.zip')
	*				-> setDataFile('particular_file.txt');
	* $extractedFile = $archivedFile -> unpackZip();
	* </code>
	*
	* Could be extended for manipulations with the extracted directories
	*
	* @throws \Exception Can't open archive
	* @throws \Exception Can't locate dataFile 
	* @return mixed
	*/
	public function unpackZip()
	{
		$zipReader = new ZipArchive();
		$result = false;

		if ($zipReader -> open($this -> getSourceFile()) === true) {
			if ($dataFile = $this -> getDataFile()) {
				$searchFile = $zipReader -> locateName($dataFile);

				if ($searchFile) {
					$result = $zipReader -> getFromIndex($searchFile);
				} else {
					throw new \Exception('Unable to locate sought-for file ' . $this -> getDataFile() . ' in archive');
				}
			} else {
				// do something with all archive files
			}

			$zipReader -> close();
		} else {
			throw new \Exception('Unable to open archive file ' . $this -> getSourceFile());
		}

		return $result;
	}

	/**
	* Set source file
	*
	* @param string sourceFile 
	* @return object
	*/
	public function setSourceFile($sourceFile)
	{
		$this -> sourceFile = $sourceFile;
		return $this;
	}

	/**
	* Set target data file
	*
	* @param string dataFile 
	* @return object
	*/
	public function setDataFile($dataFile)
	{
		$this -> dataFile = $dataFile;
		return $this;
	}

	/**
	* Get source file
	*
	* @return string
	*/
	public function getSourceFile()
	{
		return $this -> sourceFile;
	}

	/**
	* Get data file
	*
	* @return string. False if wasn't set
	*/
	public function getDataFile()
	{
		return $this -> dataFile;
	}
}

/**
* Parse .docx files. Allows to pick the certain elements by attribute
*/
class ParserDocx extends Parser
{
	/**
	* Source extension
	*
	* @var string
	* @access protected
	*/
	protected $fileExtension	= 'docx';

	/**
	* File to extract from the .docx tree
	*
	* @var string
	* @access protected
	*/
	protected $dataFile			= 'word/document.xml';

	/**
	* Document in XML tree
	*
	* @var string
	* @access protected
	*/	
	protected $xmlSource;


	/**
	* Extract <b>bold</b> elements from the source .docx
	*
	* <code>
	* $boldElementsAsString = (new ParserDocx($inputFile)) -> getBold();
	* </code>
	*
	* @return mixed  
	*/
	public function getBold()
	{
		$this -> loadXmlFromSource();
		//$this -> searchBoldTags($this -> xmlSource, 't');
		$this -> searchBoldRTagsRegex($this -> xmlSource);

		return $this -> formatResult();
	}


	/**
	* Search for <b>bold</b> using recutsion
	*
	* @var object DOMDocument
	* @var string
	* @return void 
	*/
	protected function searchBoldTags($xml, $tag, $textContent = false)
	{
		foreach($xml -> getElementsByTagName($tag) as $element) {
			if ($element -> localName == 't') {
				$this -> searchBoldTags($element -> parentNode, 'rPr', $element -> parentNode -> textContent);

			} else if ($element -> localName == 'rPr') {
				$this -> searchBoldTags($element, 'b', $textContent);	

			} else if ($element -> localName == 'b' && !$element-> getAttribute('w:val')) {
				$this -> searchList[] = trim($textContent);
			}
		}
	}


	/**
	* Alternative search for <b>bold</b> using regex. For regex fans
	*
	* @var object DOMDocument
	* @return string 
	*/
	protected function searchBoldRTagsRegex($xml)
	{
		$regexp = '/\<w:b\/\>((?!\<w:b(\s+w:val="false")?\/\>)(?!\<\/w:r\>).)*?\<w:t(?:\s+.*?)*?\>(?P<boldText>.+?)\<\/w:t\>/';

		$xmlToString = $xml -> saveXML();
		preg_match_all($regexp, $xmlToString, $matches);

		$this -> searchList = $matches['boldText'];
		return;
	}


	/**
	* Convert extracted file to XML
	*
	* @return void
	*/
	protected function loadXmlFromSource()
	{
		$rawSource = (new ArchiveManager()) -> setSourceFile($this -> sourceFile)
											-> setDataFile($this -> dataFile)
											-> unpackZip();
		$this -> xmlSource = new DOMDocument();
		$this -> xmlSource -> loadXML($rawSource);

		return;
	}
}


/* path to the .docx file */
$inputFile = 'docx_sample.docx';
$result = (new ParserDocx($inputFile)) -> getBold();
echo($result);
exit();
