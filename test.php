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
    
    // Run parse.php
    exec("php7.4 " . $parse_script . " < " . $directory . ".src" . " > temp.out" , $parseOut, $parseRC);

    // Run java comparator
    exec("java -jar " . $jexamlxml . " " . $directory. ".out" . " temp.out" , $parseOut, $parseRC);
/*    if($parseRC == 0){
        echo("\r\ntest passed\r\n");
    }
    else{
        echo("\r\ntest failed\r\n");
    }*/
    generatHTML();

    function generatHTML(){


        $doc = new DOMDocument('1.0');

        $doc->formatOutput = true;
        
        $root = $doc->createElement('html');
        $root = $doc->appendChild($root);
        
        $head = $doc->createElement('head');
        $head = $root->appendChild($head);

        $style = $doc->createElement("style", '
        body {
            font-family: "Arial", sans-serif;
            color: #2c3e50;
            background: #ecf0f1;
        }
        table, td {
            border: 2px solid #2c3e50;
        }
        table {
            border-collapse: collapse;
            margin-bottom: 15px;
            width: 100%;
        }
        td {
            padding-left: 50px;
        }
        ');
        $head = $head->appendChild($style);
        
        $title = $doc->createElement('title');
        $title = $head->appendChild($title);
        
        $text = $doc->createTextNode('This is the title');
        $text = $title->appendChild($text);
       
        $body = $doc->createElement('body');
        $body = $root->appendChild($body);
        
        $table = $doc->createElement('table');
        $table = $body->appendChild($table);

        //first row
        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);

        $table_row_el = $doc->createElement('th',"id");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"test");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"passed");
        $table_row_el = $table_row->appendChild($table_row_el);
        $doc->saveHTMLFile("php://stdout");

       
    }
?>