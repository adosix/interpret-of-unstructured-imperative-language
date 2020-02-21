
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
        $doc;
        $body;
        
        //flags arguments
        $recursive = -1;

        $previous = " ";
        $dir_pass = 0;
        $dir_fail = 0;

        $shortopts  = "";
        $shortopts .= "h"; // short --help

        $last_dir = "";

        $longopts  = array(
            "directory:",   
            "parse-script:",   
            "int-script:",
            "jexamxml:",
            "help", 
            "recursive",
            "parse-only",
            "int-only",
        );

        
        $opts = getopt($shortopts, $longopts);
        foreach (array_keys($opts) as $opt) switch ($opt) {
            case 'directory':
                $directory = $opts["directory"];
                break;
            case 'recursive':
                $recursive = 0;
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
            global $recursive ;
            $files = scandir($dir);
            foreach($files as $key => $value){
                $path = realpath($dir.DIRECTORY_SEPARATOR.$value); 
        
                if(!is_dir($path)) {
                    if(empty($filter) || preg_match($filter, $path)) $results[] = $path;
                }
                else if($value != "." && $value != ".." && $recursive != -1) {
                    test_files($path, $filter, $results);
                }
            }
        
            return $results;
        }

        function generatHTML($parse_script, $files_src, $files_out, $files_rc, $jexamlxml){
            global $doc;
            global $body;

            $doc = new DOMDocument('1.0');

            $doc->formatOutput = true;
            $script = returnScript();
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
            $class_attribute = $doc->createAttribute("class");
            $class_attribute->value = "first_col";
            $table_row_el->appendChild($class_attribute);

            $table_row_el = $doc->createElement('th',"test");
            $table_row_el = $table_row->appendChild($table_row_el);
            $class_attribute = $doc->createAttribute("class");
            $class_attribute->value = "second_col";
            $table_row_el->appendChild($class_attribute);

            $table_row_el = $doc->createElement('th',"result");
            $table_row_el = $table_row->appendChild($table_row_el);
            $class_attribute = $doc->createAttribute("class");
            $class_attribute->value = "third_col";
            $table_row_el->appendChild($class_attribute);

            $table_row_el = $doc->createElement('th',"detail");
            $table_row_el = $table_row->appendChild($table_row_el);
            $class_attribute = $doc->createAttribute("class");
            $class_attribute->value = "fourth_col";
            $table_row_el->appendChild($class_attribute);

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
            global $dir_fail;
            global $dir_pass;
            exec("php7.4 " . $parse_script . " < " . $file_src . " > temp.out" , $parseOut, $parseRC);
            if($parseRC == 0){

                // Run java comparator
                //for merlin add as the last argument /pub/courses/ipp/jexamxml/options
                $difference = "" ;
                exec("java -jar " . $jexamlxml . " " . $file_loc. ".out " . " temp.out " . " difference.txt", $parseOut, $xmlRC);
                if($xmlRC == 0){
                    $dir_pass = $dir_pass + 1;
                    $last_flag =1;
                    $result = "passed";
                    $details = "xml files are identical";
                    $passed_counter = $passed_counter + 1;
                }
                else{
                    $dir_fail = $dir_fail + 1;
                    $last_flag=-1;
                    $result = "failed";
                    $details = "xml files are different";
                    $failed_counter = $failed_counter + 1;
                    $difference = trim(file_get_contents("temp.out"));
                }
            }
            else{

                $number = trim(file_get_contents($file_loc. ".rc"));
                if($number == $parseRC){
                    $dir_pass = $dir_pass + 1;
                    $last_flag=1;
                    $result = "passed";
                    $details = "rc is: " .$parseRC ;
                    $passed_counter = $passed_counter + 1;
                }
                else{
                    $dir_fail = $dir_fail + 1;
                    $last_flag=-1;
                    $result = "failed ";
                    $details = "rc should be: " . $number ."\r\n".
                            "rc is: " .$parseRC;
                    $failed_counter = $failed_counter + 1;
                }
                
            }
            $file_loc = explode('/', $file_loc);
            $position = count($file_loc) - 2;
            global $last_dir;

            if(strcmp($file_loc[$position],$last_dir) != 0){
            //add statistic to previous rep
                global $previous;
                if($previous != " "){
                    if($last_flag == 1){
                        $dir_pass= $dir_pass-1;
                    }
                    else{
                        $dir_fail= $dir_fail-1;
                    }
                    $last_dir = $file_loc[$position];
                    generateTableStat($dir_fail,$dir_pass) ;
                    
                    $dir_pass = 0;
                    $dir_fail = 0;
                    if($last_flag == 1){
                        $dir_pass= 1;
                    }
                    else{
                        $dir_fail= 1;
                    }

                }
                else{
                    $last_dir = $file_loc[$position];
                }
                $table = $doc->createElement('table');
                $table = $body->appendChild($table);

                $table_row = $doc->createElement('tr');
                $table_row = $table->appendChild($table_row);

                $table_row_el = $doc->createElement('th',$last_dir);
                $table_row_el = $table_row->appendChild($table_row_el);

                $class_attribute = $doc->createAttribute("colspan");
                $class_attribute->value = "4";
                $table_row_el->appendChild($class_attribute);

                $class_attribute= $doc->createAttribute("class");
                $class_attribute->value = "dir";
                $table_row_el->appendChild($class_attribute);

                $previous = $table_row_el;
            }
            $last_flag = 0;
            generateTestRow($table, $id_counter, $filename,$result,$details,$difference);
            }
            //last partial table 
            generateTableStat($dir_fail,$dir_pass) ;

            $table = $doc->createElement('h1', "Overall statistics");
            $table = $body->appendChild($table);

            generateTableStat($failed_counter,$passed_counter);
            

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
    h1{
        text-align:center;
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
    .first_col{
        width:10%;
        max_width:10%;
        min_width:10%;
    }
    .second_col{
        width:40%;
        max_width:40%;
        min_width:40%;
    }
    .third_col{
        width:20%;
        max_width:20%;
        min_width:20%;
    }
    .fourth_col{
        width:30%;
        max_width:30%;
        min_width:30%;
    }

    .dir{
        font-size:15px;
        color:white;
        background-color:black;
        text-align:center;
    }
    .dir_prob{
        font-size:15px;
        text-align:center;
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

    function generateTableStat($n_failed, $n_passed){
        global $doc;
        global $body;

        $table = $doc->createElement('table');
        $table = $body->appendChild($table);

        //first row
        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);
        
        $table_row_el = $doc->createElement('th',"n of passed");
        $table_row_el = $table_row->appendChild($table_row_el);
        $class_attribute = $doc->createAttribute("class");


        $table_row_el = $doc->createElement('th',"n of failed");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"passed percentage");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row_el = $doc->createElement('th',"failed percentage");
        $table_row_el = $table_row->appendChild($table_row_el);

        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);

        $table_row_el = $doc->createElement('th',"$n_passed");
        $table_row_el = $table_row->appendChild($table_row_el);
        
        $table_row_el = $doc->createElement('th',"$n_failed");
        $table_row_el = $table_row->appendChild($table_row_el);
        $passed_percentage = $n_passed/ (($n_passed +$n_failed)/100) ;
        $table_row_el = $doc->createElement('th',$passed_percentage." %");
        $table_row_el = $table_row->appendChild($table_row_el);
        $failed_percentage = $n_failed/ (($n_passed +$n_failed)/100);
        $table_row_el = $doc->createElement('th',$failed_percentage ." %");
        $table_row_el = $table_row->appendChild($table_row_el);
    }
    function generateTestRow($table, $id_counter, $filename,$result,$details,$difference){
        global $doc;
        global $body;
        $table_row = $doc->createElement('tr');
        $table_row = $table->appendChild($table_row);


        $class_attribute = $doc->createAttribute("onClick");
        $class_attribute->value = "show(".$id_counter.");";
        $table_row->appendChild($class_attribute);
        //-------------------------------------

        $table_row_el = $doc->createElement('th',$id_counter);
        $table_row_el = $table_row->appendChild($table_row_el);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value = "first_col";
        $table_row_el->appendChild($class_attribute);

        $table_row_el = $doc->createElement('th',$filename);
        $table_row_el = $table_row->appendChild($table_row_el);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value = "second_col";
        $table_row_el->appendChild($class_attribute);

        $table_row_el = $doc->createElement('th',$result);
        $table_row_el = $table_row->appendChild($table_row_el);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value = $result." third_col";
        $table_row_el->appendChild($class_attribute);

        $table_row_el = $doc->createElement('th',$details);
        $table_row_el = $table_row->appendChild($table_row_el);
        $class_attribute = $doc->createAttribute("class");
        $class_attribute->value = "fourth_col";
        $table_row_el->appendChild($class_attribute);


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
    function returnScript(){
        return " 
            function show(id) {
                var x = document.getElementById(id);
                if (x.style.display === \"none\") {
                x.style.display = \"block\";
                } else {
                x.style.display = \"none\";
                }
            } ";
    }
    ?>
    