#!/usr/bin/env php
<?php
    //path to parse script default
    $parse_script = "parse.php";
    //path to interpret script default
    $int_script = "interpret.php";
    //path to tests default
    $directory = "";
    //recursive search througth repositories
    $recursive = false;
    //only parser script will be tested
    $parse_only = false;
    //only interpret script will be tested
    $int_only = false;
    //path to XML differencing tool
    $jexamlxml = "/pub/courses/ipp/jexamxml/jexamxml.jar";


    checkArguments();

    // Run parse.php
    exec("php7.4 " . $parseScript . " < " . $src, $parseOut, $parseRC);
    $parseOut = shell_exec("php7.4 " . $parseScript . " < " . $src);

    function checkArguments(){

    }
?>