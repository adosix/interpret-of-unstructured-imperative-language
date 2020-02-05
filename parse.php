#!/usr/bin/env php
<?php

//load first line
if (!$line = fgets(STDIN))
    error(11, "PARSER ERROR: No input");

//get rid of whitespace chars
$line = preg_replace('/\s/', "", $line);

//check first line
if ($line != ".IPPcode20")
    error(21, "PARSER ERROR: Invalid header");



function error($err_val, $err_msg)
{
    fputs(STDERR, "$err_msg\n");
    exit($err_val);
}

?>