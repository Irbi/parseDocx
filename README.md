# parseDocx
PHP parser for docx files. <br> 
For now parses <b>bold</b> elements, CLI output

Will be extended for other tags

Usage:<br>
$boldElementsAsString = (new ParserDocx($inputFile)) -> getBold();
