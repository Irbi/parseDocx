# parseDocx
PHP parser for docx files 
For now parses <bold>bold</bold> elements, CLI output

Will be extended for other tags

Usage:
$boldElementsAsString = (new ParserDocx($inputFile)) -> getBold();
