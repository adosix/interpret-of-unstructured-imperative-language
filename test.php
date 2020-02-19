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
    
    if (!is_dir($directory)) {
        fwrite(STDERR, "TEST SCRIPT ERROR: Tests directory (" . $directory . ") does not exist\r\n");
        exit(11);
    } 
    
    $files_src = test_files($directory, '/\.src$/');
    $files_out = test_files($directory, '/\.out$/');
    $files_rc = test_files($directory, '/\.rc$/');

    generatHTML($parse_script, $files_src, $files_out,$files_rc, $jexamlxml);

    function test_files($dir, $filter = '', &$results = array()) {
        $files = scandir($dir);
        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value); 
    
            if(!is_dir($path)) {
                if(empty($filter) || preg_match($filter, $path)) $results[] = $path;
            } elseif($value != "." && $value != "..") {
                test_files($path, $filter, $results);
            }
        }
    
        return $results;
    }

    function generatHTML($parse_script, $files_src, $files_out, $files_rc, $jexamlxml){


        $doc = new DOMDocument('1.0');

        $doc->formatOutput = true;
        $script =" 
          function show(id) {
            var x = document.getElementById(id);
            if (x.style.display === \"none\") {
              x.style.display = \"table-cell\";
            } else {
              x.style.display = \"none\";
            }
          } ";
        $js = $doc->createElement("script",$script);
        $js = $doc->appendChild($js);

        $root = $doc->createElement('html');
        $root = $doc->appendChild($root);
        
        $head = $doc->createElement('head');
        $head = $root->appendChild($head);
        
        $title = $doc->createElement('title');
        $title = $head->appendChild($title);
        $text = $doc->createTextNode('Test result');
        $text = $title->appendChild($text);

        $style = get_style();
        $style = $doc->createElement("style", $style);
        $head = $head->appendChild($style);

        

       
        $body = $doc->createElement('body');
        $body = $root->appendChild($body);
        
        $table = $doc->createElement('table');
        $table = $body->appendChild($table);

        //first row
        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value ="first_row";
        $table_row ->appendChild($class_attribute);

        $table_row_el = $doc->createElement('th',"id");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"test");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"result");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"detail");
        $table_row_el = $table_row->appendChild($table_row_el);

        //conters
        $id_counter = 0;
        $passed_counter = 0;
        $failed_counter = 0;
        //tests each file
       foreach ($files_src as $file_src) {
        $id_counter = $id_counter+1;
        $file_loc = explode('.', $file_src);
        $file_loc = $file_loc[0];

        $file_to_find =$file_loc.".out";
        if (!in_array($file_to_find, $files_out)){
            $new_file = fopen($file_to_find, "w");
            fclose($new_file);
        }
        
        $file_to_find =$file_loc.".rc";
        if (!in_array($file_to_find, $files_rc)){
            $new_file = fopen($file_to_find, "w");
            fwrite($new_file, "0");
            fclose($new_file);
        }

        $filename = explode('/', $file_loc);
        $filename = end($filename);

        //echo "-------------------- " . $filename . " -------------------- \r\n";
        $difference_xml = "";
        //fputs(STDERR, "$file_src\n");
        // Run parse.php
        exec("php7.4 " . $parse_script . " < " . $file_src . " > temp.out" , $parseOut, $parseRC);
        if($parseRC == 0){

            // Run java comparator
            //for merlin add as the last argument /pub/courses/ipp/jexamxml/options
            $difference = "" ;
            exec("java -jar " . $jexamlxml . " " . $file_loc. ".out " . " temp.out " . " difference.txt", $parseOut, $xmlRC);
            if($xmlRC == 0){
                $result = "passed";
                $details = "xml files are identical";
                $passed_counter = $passed_counter + 1;
            }
            else{
                $result = "failed";
                $details = "xml files are different";
                $failed_counter = $failed_counter + 1;
                $difference = trim(file_get_contents("temp.out"));
            }
        }
        else{

            $number = trim(file_get_contents($file_loc. ".rc"));
            if($number == $parseRC){
                $result = "passed";
                $details = "rc is: " .$parseRC ;
                $passed_counter = $passed_counter + 1;
            }
            else{
                $result = "failed ";
                $details = "rc should be: " . $number ."\r\n".
                        "rc is: " .$parseRC;
                $failed_counter = $failed_counter + 1;
            }
            
        }

        //set table row
        //-------------------------------------
        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);


        $class_attribute = $doc->createAttribute("onClick");
        $class_attribute->value = "show(".$id_counter.");";
        $table_row->appendChild($class_attribute);
        //-------------------------------------

        $table_row_el = $doc->createElement('th',$id_counter);
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',$filename);
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',$result);
        $table_row_el = $table_row->appendChild($table_row_el);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value = $result;
        $table_row_el->appendChild($class_attribute);

        $table_row_el = $doc->createElement('th',$details);
        $table_row_el = $table_row->appendChild($table_row_el);


        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);


        $table_row_el = $doc->createElement('th',$difference);
        $table_row_el = $table_row->appendChild($table_row_el);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value = "difference";
        $table_row_el->appendChild($class_attribute);

        $class_attribute = $doc->createAttribute("colspan");
        $class_attribute->value = "4";
        $table_row_el->appendChild($class_attribute);
        if($details == "xml files are different"){
            $class_attribute = $doc->createAttribute("id");
            $class_attribute->value = $id_counter;
            $table_row_el->appendChild($class_attribute);
        }



        }


        $table = $doc->createElement('table');
        $table = $body->appendChild($table);

        //first row
        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value ="first_row";
        $table_row ->appendChild($class_attribute);
        
        $table_row_el = $doc->createElement('th',"n of passed");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"n of failed");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"passed percentage");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"failed percentage");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);

        $table_row_el = $doc->createElement('th',"$passed_counter");
        $table_row_el = $table_row->appendChild($table_row_el);
        $table_row_el = $doc->createElement('th',"$failed_counter");
        $table_row_el = $table_row->appendChild($table_row_el);
        $passed_percentage = $passed_counter/ ($id_counter/100) ;
        $table_row_el = $doc->createElement('th',$passed_percentage." %");
        $table_row_el = $table_row->appendChild($table_row_el);
        $failed_percentage = $failed_counter/ ($id_counter/100);
        $table_row_el = $doc->createElement('th',$failed_percentage ." %");
        $table_row_el = $table_row->appendChild($table_row_el);

        $doc->saveHTMLFile("php://stdout");
        exec("rm temp.out difference.txt");
    }

function error($err_val, $err_msg)
{
    fputs(STDERR, "$err_msg\n");
    exit($err_val);
}

function get_style()
{
 return '
 body {
     font-family: "Arial", sans-serif;
     color: #26A102;
     background: #333333;
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
 .passed{
     background-color: green;
     color:white;
 }
 .failed{
     background-color: #E33E33;
     color:white;
 }
 .difference{
     background-color: #676767;
     color: black;
     display: none;
     margin:auto;
     
 }
 table {
    border: 4px solid black;
  }
 th, td{
    border: 1px solid black;
  }
  .first_row{
    font-size: 23px;
    border: 4px solid black;
  }
 ';
}
?>