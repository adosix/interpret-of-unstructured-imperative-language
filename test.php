
    <?php
        //path to parse script default
        $parse_script = "./parse.php";
        //path to interpret script default
        $int_script = "./interpret.php";
        //path to tests default
        $directory = "./";
        //recursive search throught repositories
        $recursive = false;
        //only parser script will be tested
        $parse_only = false;
        //only interpret script will be tested
        $int_only = false;
        //path to XML differencing tool
        $jexamxml = "/pub/courses/ipp/jexamxml/jexamxml.jar";

        $doc;
        $body;

        checkArguments();

        if (!file_exists($parse_script)){ 
            error(11,"TEST ERROR:parse script doesn't exists at path " . $parse_script);
        }  
        
        //flags arguments
        //$recursive = -1;
        $previous = " ";
        $dir_pass = 0;
        $dir_fail = 0;
        $last_dir = "";

        

        
        if (!is_dir($directory)) {
            fwrite(STDERR, "TEST SCRIPT ERROR: Tests directory (" . $directory . ") does not exist\r\n");
            exit(11);
        } 
        
        $files_src = test_files($directory, '/\.src$/');
        $files_out = test_files($directory, '/\.out$/');
        $files_rc = test_files($directory, '/\.rc$/');

        generatHTML($parse_script, $files_src, $files_out,$files_rc);

//------------------------------------------------------
//---------------------FUNCTIONS------------------------
//------------------------------------------------------

        
    /**
    * brief:   Searches trought directories and returns array of files
    * @param $dir -> directory from which script starts
    * @param $filter -> filter which files will be added to array
    */
        function test_files($dir, $filter = '', &$results = array()) {
            global $recursive ;
            $files = scandir($dir);
            foreach($files as $key => $value){
                $path = realpath($dir.DIRECTORY_SEPARATOR.$value); 
        
                if(!is_dir($path)) {
                    if(empty($filter) || preg_match($filter, $path)) $results[] = $path;
                }
                else if($value != "." && $value != ".." && $recursive == true) {
                    test_files($path, $filter, $results);
                }
            }
            return $results;
        }
        
    /**
    * brief:  Logic of the program and calling other functions
    * @param $parse_script -> path to parse script
    * @param $files_src -> source files
    * @param $files_out -> files with three adress code
    * @param $files_rc -> files with return codes
    */
        function generatHTML($parse_script, $files_src, $files_out, $files_rc){
            global $doc;
            global $body;

            global $directory;
            global $recursive;
            global $parse_script;
            global $int_script;
            global $parse_only;
            global $int_only;
            global $jexamxml;

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
            global $dir_fail;
            global $dir_pass;
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

            //fputs(STDERR, "$file_src\n");
            // Run parse.php
            $difference = "" ;
            exec("php7.4 " . $parse_script . " < " . $file_src . " > temp.out" , $parseOut, $parseRC);
            if($parseRC == 0 &&  $parse_only == true){
                if (!file_exists($jexamxml)){ 
                    error(11, "TEST ERROR: file jexamxml doesn't exist at path " . $jexamxml);
                } 
                // Run java comparator
                exec("java -jar " . $jexamxml . " " . $file_loc. ".out " . " temp.out " . " difference.txt", $parseOut, $xmlRC);
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
            else if ($parse_only == false && $parseRC == 0 ){
                if (!file_exists($int_script)){ 
                    error(11,"TEST ERROR:interpret script doesn't exists at path " . $int_script);
                }  
                exit(0);
                exec("python3.8 " . $int_script . " < " . "temp.out" . " > temp.out" , $intOut, $intRC);
                
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
            if($parse_only == true || $parseRC != 0){
                if($parse_only != true){
                    $difference = "";
                }
                generateTestRow($table, $id_counter, $filename,$result,$details,$difference);
            }
            
            }
            generateTableStat($dir_fail,$dir_pass) ;

            $table = $doc->createElement('h1', "Overall statistics");
            $table = $body->appendChild($table);

            generateTableStat($failed_counter,$passed_counter);
            

            $doc->saveHTMLFile("php://stdout");
            if (file_exists("temp.out")){ 
                exec("rm temp.out");
            }            
            if (file_exists("difference.txt")){ 
                exec("rm difference.txt");
            } 
           
        }
/**
    * brief:   prints error and returns error code
    * @param $err_val -> error code
    * @param $err_msgr -> error message
    */
    function error($err_val, $err_msg)
    {
        fputs(STDERR, "$err_msg\n");
        exit($err_val);
    }
    /**
    * brief:   Returns string with style (css)
    */
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
        
        background-color: grey;
        font-size: 25px;
        color: #01311E;
        border: 4px solid black;
    }
    ';
    }
    /**
    * brief:   Adds table with results to our html document
    * @param $doc -> html document (output of the program)
    * @param $n_failed -> number of tests that passed
    * @param $n_passed -> number of tests that passed
    */
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
        if($n_failed == 0 && $n_passed == 0){
            $passed_percentage = 0;
            $failed_percentage = 0;
        }
        else{
            $passed_percentage = $n_passed/ (($n_passed +$n_failed)/100) ;
            $failed_percentage = $n_failed/ (($n_passed +$n_failed)/100);
        }
        
        $table_row_el = $doc->createElement('th',$passed_percentage." %");
        $table_row_el = $table_row->appendChild($table_row_el);
        
        $table_row_el = $doc->createElement('th',$failed_percentage ." %");
        $table_row_el = $table_row->appendChild($table_row_el);
    }
        /**
    * brief:   Adds table row to our html document
    * @param $table -> to which table we are going to add row
    * @param $id_counter -> number of test
    * @param $result -> if the test was succesfull or not
    * @param $details -> more detailed output of the test
    * @param $difference -> difference between xml files
    */
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
    /**
    * brief:   return string with javascript
    */
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
    /**
    * brief:  Checks arguments of the program
    */
    function checkArguments(){
        global $directory;
        global $recursive;
        global $parse_script;
        global $int_script;
        global $parse_only;
        global $int_only;
        global $jexamxml;
        $shortopts  = "";
        $shortopts .= "h"; // short --help

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
        foreach (array_keys($opts) as $opt){ 
            switch ($opt) {
                case 'directory':
                    $directory = $opts['directory'];
                    if(substr($directory, -1) != "/"){
                        $directory = $directory ."/";
                    }
                    if($directory[0] != '/'){
                        $directory = "./" . $directory;
                    }
                    break;
                case 'recursive':
                    $recursive = true;
                    break;
                case 'jexamxml':
                    $jexamxml = $opts['jexamxml'];
                    break;
                case 'parse-only':
                    $parse_only = true;
                    break;
                case 'parse-script':
                    $parse_script = $opts['parse-script'];
                    break;
                case 'int-script':
                    $int_script = $opts['int-script'];
                    break;

                case 'int-only':
                    $int_only = true;
                    break;
                case 'h':
                case 'help':
                    echo "help todo";
                    exit(0);

                default:
            }
        }

    }
    ?>
    