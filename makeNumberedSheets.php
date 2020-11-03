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
			"numberOfSheets",
            "outputDirectory"
		)
	);


echo "setting up temp directory... \n";
/*set up temporary directories, creating them if needed.*/
// $outputDirectory = dirname(__FILE__) . "/generated";
$outputDirectory = $options['outputDirectory'];
// $tempDirectory = dirname(__FILE__) . "/temp";
$tempDirectory = tempnam(sys_get_temp_dir(), basename(__FILE__));
unlink($tempDirectory);
if(!file_exists($tempDirectory)) {mkdir($tempDirectory);};

//realpath ensures there is no trailing directory delimeter (i.e. "/") and ensures that the directory delimeters are appropriate for
//the operating system.
$tempDirectory = realpath($tempDirectory); 
echo "temp directory: " . $tempDirectory . " \n";

if(!file_exists($outputDirectory)) {mkdir($outputDirectory);};
$outputDirectory = realpath($outputDirectory);

$templateFile = realpath($options['templateFile']);


echo "reading template file...\n";
//read in template file
$template = file_get_contents($templateFile);

$replacement = 
    "\\def\\initialSheetNumber{" . $options['initialNumber'] . "}" . "%\n" .
    "\\def\\numberOfSheets{" . $options['numberOfSheets'] . "}" . "%\n" .
    "\\def\\sheetNumberPrefix{" . $options['prefix'] . "}" . "%\n";

//this regex matches the strin between the "%BEGIN REPLACEMENT\n" and "\n%END REPLACEMENT" tags.  Note that each tag has te be immediately preceded and followed by a newline.
//$pattern="/(?<=\\n%BEGIN REPLACEMENT\\n)([\\s\\S]*)(?=\\n%END REPLACEMENT\\n)/";
$pattern="/(?<=(\\r|\\n)%BEGIN REPLACEMENT(\\r|\\n))[\\s\\S]*(?=(\\r|\\n)%END REPLACEMENT[\\s]*(\\r|\\n))/";
//in making this regex, I struggled with the cr/lf distinction, as always.
//echo $pattern . "\n";
//to be thorough, we ought to escape $labelText for TeX, but I am not  bothering with that at the moment because, in my intended use for this script, labeltext will contain only  alphabetic symbols and digits, none of whic need to be escaped for TeX.


//write the xetex code to a file, $tex_file
$uniqeFilenameRoot = uniqid(); //microtime(); //might use GUID instead of microtime
$texFile = $tempDirectory . "/" . $uniqeFilenameRoot . ".tex" ;
$pdfFile = $tempDirectory . "/" . $uniqeFilenameRoot . ".pdf" ;


echo "creating the temporary tex file...\n";
//$label_xetex is a a string containing xetex to generate the code.
$labelXetex = preg_replace($pattern,$replacement,$template,-1,$count);
//echo $count . " replcements made\n";
file_put_contents($texFile, $labelXetex);

//$uniqueRootName = uniqid();
date_default_timezone_set('America/Los_Angeles');
$uniqueRootName = date("Y-m-d-H-i") . "_" . "numberedSheets_" . $options['initialNumber']. "_to_". ($options['initialNumber'] +$options['numberOfSheets']-1)  ;
$outputPdfFile = $outputDirectory . "/" . $uniqueRootName . ".pdf";

echo "generating the pdf file...\n";
//call xetex to create the pdf file
// for($i=1;$i<=2;$i++)
// {
    // //something about the tikzpicture latex package requires to be run twice in order for the rule lines to appear in the right positions.
    // //if you only run the latex file once, the rule line pattern ends too far up and to the right.  I do not understand why this is,
    // //but running it twice fixes it.
    // exec( "miktex-xetex --quiet --undump=xelatex --interaction=nonstopmode --output-directory=\"$tempDirectory\" --aux-directory=\"$tempDirectory\"" ." ". "\"$texFile\" 2>&1");
// }
// The "2>&1" in the command string causes stderr to be redirected to stdout, which is absorbed by php and not printed to the console.  This prevents error messages from the runing of xetex appearing on
//the console when this php script is run at the command line.


// Rather than run miktex-xetex twice explicitly, I will use latexmk to handle any repeated running that might be necessary:
// exec("latexmk -xelatex -gg --interaction=nonstopmode --output-directory=\"$tempDirectory\" --aux-directory=\"$tempDirectory\" -jobname=\"$uniqeFilenameRoot\" \"$texFile\" 2>&1");
// exec("latexmk -xelatex -gg --interaction=nonstopmode --output-directory=\"$tempDirectory\" --aux-directory=\"$tempDirectory\" -jobname=\"$uniqeFilenameRoot\" \"$texFile\"");

//2019/09/13 - latexmk is complainng about path names containing backslashes.  Therefore, I will run latexmk with the temp directory being the current working directory
chdir(dirname($texFile));
// exec("latexmk -xelatex -gg --interaction=nonstopmode --output-directory=\".\" --aux-directory=\".\" -jobname=\"$uniqeFilenameRoot\" \"$texFile\"");
exec("latexmk -xelatex -gg --interaction=nonstopmode --output-directory=\"$tempDirectory\" --aux-directory=\"$tempDirectory\" -jobname=\"$uniqeFilenameRoot\" \"" . basename($texFile) . "\"");


echo "Moving the generated pdf to the final output folder ...\n";
rename($pdfFile, $outputPdfFile); //the rename command can be used to move a file to a different directory.

echo "Cleaning up temp files...\n";
rrmdir($tempDirectory);

echo "Opening newly generated pdf file for viewing and printing.";
// exec("cmd /c start \"\"  Acrobat \"$outputPdfFile\"");
exec("cmd /c start \"\"  \"$outputPdfFile\"");


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