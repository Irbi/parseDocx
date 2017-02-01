# parseDocx
PHP parser for docx files. <br> 
For now picks <b>bold</b> elements, CLI output

Will be extended for other tags

Usage:<br>
$boldElementsAsString = (new ParserDocx($inputFile)) -> getBold();
