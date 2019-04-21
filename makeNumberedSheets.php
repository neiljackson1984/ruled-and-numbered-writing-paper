<?php

require_once("commandLineHelper_lib.php");
$options = 
	getRequiredOptions
	(
		array
		(
			"templateFile",
			"prefix",
			"initialNumber",
			"numberOfSheets"
		)
	);


echo "setting up temp directory...\n";
/*set up temporary directories, creating them if needed.*/
$outputDirectory = dirname(__FILE__) . "/generated";
$tempDirectory = dirname(__FILE__) . "/temp";
if(!file_exists($outputDirectory)) {mkdir($outputDirectory);};


if(!file_exists($tempDirectory)) {mkdir($tempDirectory);};
$ghostscriptExecutable="gswin64";
$ghostviewExecutable="gsview64";

//realpath ensures there is no trailing directory delimeter (i.e. "/") and ensures that the directory delimeters are appropriate for
//the operating system.
$tempDirectory = realpath($tempDirectory); 
$outputDirectory = realpath($outputDirectory);
//$templateFile = dirname(__FILE__) . "/template.tex";
$templateFile = realpath($options['templateFile']);


echo "reading template file...\n";
//read in template file
$template = file_get_contents($templateFile);





$labelTexts = array();
for ($sheetNumber=$options['initialNumber'];$sheetNumber<=$options['initialNumber']+$options['numberOfSheets']-1;$sheetNumber++)
{
	array_push($labelTexts, $options['prefix'].$sheetNumber);
};

//delete everything from temp directory here?

echo "creating a pdf file for each label text...\n";
$pdfFiles = array();
foreach($labelTexts as $labelText)
{
	makeSheet($labelText);
}

//print_r($pdfFiles);

echo "Merging the per-label pdf files into one pdf file with multiple pages...\n";
//merge the pdf files
//$uniqueRootName = uniqid();
date_default_timezone_set('America/Los_Angeles');
$uniqueRootName = date("Y-m-d-H-i") . "_" . "numberedSheets_" . $options['initialNumber']. "_to_". ($options['initialNumber'] +$options['numberOfSheets']-1)  ;
$mergedPdfFile = $tempDirectory . "/" . $uniqueRootName . ".pdf";
$outputPdfFile = $outputDirectory . "/" . $uniqueRootName . ".pdf";
$shellCommandForMerging = $ghostscriptExecutable . " -q -dSAFER -dNOPAUSE -dBATCH -sOutputFile=\"$mergedPdfFile\" -sDEVICE=pdfwrite -c .setpdfwrite -f ";
foreach ($pdfFiles as $pdfFile)
{
	$shellCommandForMerging.= " \"$pdfFile\" ";
}

exec($shellCommandForMerging);

echo "Moving merged pdf to final output folder ...\n";
rename($mergedPdfFile, $outputPdfFile); //the rename command can be used to move a file to a different directory.

echo "Cleaning up temp files...\n";
rrmdir($tempDirectory);


echo "Opening newly generated pdf file for viewing and printing.";
exec("cmd /c start \"\"  Acrobat \"$outputPdfFile\"");



function makeSheet($labelText)
 {
	global $template;
	global $pdfFiles;
	global $tempDirectory;
	
	echo "Now processing label text \"$labelText\". (" . (strlen($labelText)) . " characters long)" . "\n";
	
	// $replacement is a string that will be inserted into the template file in place of the 
	// replaceable block to form the source that gets run through xetex to make the label.
	$replacement = "\\def\\sheetId{".$labelText."}"; 
	
	//this regex matches the strin between the "%BEGIN REPLACEMENT\n" and "\n%END REPLACEMENT" tags.  Note that each tag has te be immediately preceded and followed by a newline.
	//$pattern="/(?<=\\n%BEGIN REPLACEMENT\\n)([\\s\\S]*)(?=\\n%END REPLACEMENT\\n)/";
	$pattern="/(?<=(\\r|\\n)%BEGIN REPLACEMENT(\\r|\\n))[\\s\\S]*(?=(\\r|\\n)%END REPLACEMENT[\\s]*(\\r|\\n))/";
	//in making this regex, I struggled with the cr/lf distinction, as always.
	//echo $pattern . "\n";
	//to be thorough, we ought to escape $labelText for TeX, but I am not  bothering with that at the moment because, in my intended use for this script, labeltext will contain only  alphabetic symbols and digits, none of whic need to be escaped for TeX.
	
	//$label_xetex is a a string containing xetex to generate the code.
	$labelXetex = preg_replace($pattern,$replacement,$template,-1,$count);
	//echo $count . " replcements made\n";
	
	//write the xetex code to a file, $tex_file
	$uniqeFilenameRoot = uniqid(); //microtime(); //might use GUID instead of microtime
	$texFile = $tempDirectory . "/" . $uniqeFilenameRoot . ".tex" ;
	$pdfFile = $tempDirectory . "/" . $uniqeFilenameRoot . ".pdf" ;
	
	file_put_contents($texFile, $labelXetex);
	
	//call xetex to create the pdf file
	for($i=1;$i<=2;$i++)
	{
		//something about the tikzpicture latex package requires to be run twice in order for the rule lines to appear in the right positions.
		//if you only run the latex file once, the rule line pattern ends too far up and to the right.  I do not understand why this is,
		//but running it twice fixes it.
		exec
		(
			"miktex-xetex --quiet --undump=xelatex --interaction=nonstopmode --output-directory=\"$tempDirectory\" --aux-directory=\"$tempDirectory\"" ." ". "\"$texFile\" 2>&1"
		);
	}
	// The "2>&1" in the command string causes stderr to be redirected to stdout, which is absorbed by php and not printed to the console.  This prevents error messages from the runing of xetex appearing on
	//the console when this php script is run at the command line.
	array_push($pdfFiles,realpath($pdfFile)); //add $pdfFile to the list of $pdfFiles, to be used when we generate the tiled output page.
 }

 
/**
 * Recursively removes a folder along with all its files and directories
	*
 * @param String $path
 * 
 * COPIED FROM http://ben.lobaugh.net/blog/910/php-recursively-remove-a-directory-and-all-files-and-folder-contained-within
 */
function rrmdir($path) {
	// Open the source directory to read in files
	$i = new DirectoryIterator($path);
	foreach($i as $f) {
		if($f->isFile()) {
			unlink($f->getRealPath());
		} else if(!$f->isDot() && $f->isDir()) {
			rrmdir($f->getRealPath());
		}
	}
	rmdir($path);
}
 
?>