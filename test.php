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
    $jexamlxml = "../jexamxml/jexamxml.jar";

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
    /*
    if (!is_dir($directory)) {
        fwrite(STDERR, "TEST SCRIPT ERROR: Tests directory (" . $directory . ") does not exist\r\n");
        exit(11);
    } */
    
    // var_dump($options);
    // Run parse.php
     // exec("php7.4 " . $parse_script . " < " . $directory . ".src" . " > temp.out" , $parseOut, $parseRC);
     // echo $parseRC;

     
      echo "\r\n------------\r\n";
      exec("java -jar " . $jexamlxml . " " . $directory. ".out" . " temp.out" , $parseOut, $parseRC);
      //exec("rm temp.out");
      echo $parseRC;
   // $parseOut = shell_exec("php7.4 " . $parseScript . " < " . $src);
?>