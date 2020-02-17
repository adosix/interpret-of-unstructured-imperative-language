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

    $shortopts  = "";
    $shortopts .= "h"; // short --help

    $longopts  = array(
        "directory:",      // Required value
        "optional::",       // Optional value   --delete
        "help", 
    );

    
    $opts = getopt($shortopts, $longopts);
    foreach (array_keys($opts) as $opt) switch ($opt) {
        case 'directory':
            $directory = $opts["directory"];
    
    }
    $output ="";
   // var_dump($options);
    // Run parse.php
      exec("php7.4 " . $parse_script . " < " . $directory, $parseOut, $parseRC);
    print_r($parseOut);
      echo $parseRC;
   // $parseOut = shell_exec("php7.4 " . $parseScript . " < " . $src);
    function checkArguments(){

    }
?>