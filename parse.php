#!/usr/bin/env php
<?php

checkArguments();

//load first line
if (!$line = fgets(STDIN))
    error(11, "PARSER ERROR: No input");

//get rid of whitespace chars
$line = preg_replace('/\s/', "", $line);

//check first line
if ($line != ".IPPcode20")
    error(21, "PARSER ERROR: Invalid header");


/**
 * brief: checks arguments and call exit if:
 *          -wrong number of parameters
 *          -parameter "--help" was entered  
 */
function checkArguments(){
    global $argc;
    global $argv;
    if($argc == 1){
        return;
    }
    elseif($argc == 2 && $argv[1] == "--help" )
    {
        echo "to do help";
        exit(0);
    }
    else
        error(10, "PARSER ERROR: Wrong parameters of the script parse.php");
}

/**
 * brief:   function which exits program and 
 *          returns error code and error message
 * @param $err_val = error code
 * @param $err_msg = what should be written to the console
 */
function error($err_val, $err_msg){
    fputs(STDERR, "$err_msg\n");
    exit($err_val);
}

?>