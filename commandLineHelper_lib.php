<?php


/*==============================================
 * The function getRequiredOptions takes, as an argument, an array of strings,
 * each of which is an option that we want to be required on the command line, with a  value.
 * The function looks at the command line parameters that were passed.  If all the
 * required parameters were present, with a value for each one (i.e. each parameter x
 * was passed on the command line as "...  --x=<<valueOfX>>  ...", not merely as "... --x  ..."),
 * then this function returns an array of the form
 * Array(
 * 	optionName => optionValue,
 *  ...
 * ).
 * Otherwise, the function echos an error message stating which options are required and which options were actually passed
 * , and then exits with an exit code of 1 (whcih indicates an error).
 * 
 * Note: If a parameter is passed wrapped in double quotes
 * (i.e.  --parameter="value of parameter"  ) ,
 * the double quotes are stripped automatically.
 * (i.e.  $options['parameter'] evaluates to "value of parameter" NOT "\"value of parameter\"") 
 *   
 * ======================================================
 */

function getRequiredOptions($requiredOptions)
{
	$options = getopt("",array_map(function($x){return $x.":";},$requiredOptions));
	
	$allRequiredOptionsArePresent = true;
	foreach($requiredOptions as $requiredOption)
	{
		if(!array_key_exists($requiredOption,$options)){$allRequiredOptionsArePresent=false; break;}
	}
	
	if( ! $allRequiredOptionsArePresent ) //if one or more of the required options was not passed
	{
		echo 
			"\nError from " . $_SERVER['PHP_SELF'] . ": " . "This script was not passed one or more of its required parameters.".
			"\nThe required parameters are " . implode(", ",$requiredOptions) . ".  " . 
			"\nThis script was passed the following parameters: " . implode(", ", array_keys($options)) . "\n" .
			"example usage: php " . $_SERVER['PHP_SELF'] ." --stateMapInputFile=myFile1 --statemachineSubroutinesOutputFile=myFile2 --stateEnumerationOutputFile=myFile3 \n\n";
		print("exiting...");
		exit(1);
	}
	return $options;
}

/*
	Strips c-style comments (i.e. comments of the form  //...(EOL)     OR      /* ... * /) from the string argument.
 * 
 */
function stripCStyleComments($x)
{
	$y = $x;
	$y = preg_replace('!/\*.*?\*/!s', '', $y);  //strips block comments of the form /*...*/
	$y = preg_replace('/\/\/.*$/m', "", $y); //strips single line comments of the form //...
	return $y;
};


?>
