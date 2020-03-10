#!/usr/bin/env php
<?php
$stats = " ";
$loc=-1;
$comments=-1;
$labels=-1;
$jumps=-1;
$labels_arr =  (array) null; ;
checkArguments();

//load first line
if (!$line = fgets(STDIN))
    error(21, "PARSER ERROR: Invalid header");

if($comments != -1){
    if (strpos($line, "#")) {
        $comments = $comments +1;
    }
}

//get rid of whitespace chars and comments
$line = preg_replace("/#.*$/", "", $line);
$line = preg_replace('/\s/', "", $line);

//echo $line;
//check first line
$line = strtolower($line);
if ($line != ".ippcode20")
    error(21, "PARSER ERROR: Invalid header");

//counter for instructions
$order = 1;

//create basic XML document 
$xml_doc = createDoc();
$program = $xml_doc->createElement("program");
$xml_doc->appendChild($program);
$lang = $xml_doc->createAttribute("language");
$lang->value = substr(".IPPcode20", 1) ;
$program->appendChild($lang);


//parse input doc and create xml doc
while ($line = fgets(STDIN)) {
    if($comments != -1){
        if (strpos($line, "#")!== false) {
            $comments = $comments +1;
        }
    }
    $parsed_line = parse($line);
    if ($parsed_line != 1) {
        $ins_doc = addInstructionSkeleton($xml_doc, $program, $order, $parsed_line);
        $xml_doc = $ins_doc[0];
        $instruction =$ins_doc[1]; 
        $xml_doc = checkSyntaxI($parsed_line, $instruction, $xml_doc);
        $order =  $order + 1;
    }
}
//create stat file
if($stats != " "){
    CreateStatFile();
}
//get final document
SaveDocExit($xml_doc);


//------------------------------------------------------
//---------------------FUNCTIONS------------------------
//------------------------------------------------------

function CreateStatFile(){
    global $stats;
    global $loc;
    global $comments;
    global $labels;
    global $jumps;
    global $argv;
    $n_of_args = count($argv) -1;
    if (($stats != " ") && ($statsFile = fopen($stats, "w")) == false){
        error(12, "PARSER ERROR: file: " . $stats . " couldn't be opened.");
    }
    for($i = 1; $i <= $n_of_args; $i=$i+1){
        if($argv[$i] == "--loc"){
            fwrite($statsFile, $loc . "\n");
        }
        if($argv[$i] == "--comments"){
            fwrite($statsFile, $comments . "\n");
        }
        if($argv[$i] == "--labels"){
            fwrite($statsFile, $labels . "\n");
        }
        if($argv[$i] == "--jumps"){
            fwrite($statsFile, $jumps . "\n");
        }
    }
    fclose($statsFile);

}
/**
 * brief:   Adds skeleton of instruction to xml (output file)
 * @param $doc = xml document (output of the program)
 * @param $program = defines main element of xml file
 * @param $order = number of instruction which will be added
 * @param $parsed_line = parsed line from standard input (three adress code)
 */
function addInstructionSkeleton($doc, $program, $order, $parsed_line){
    $instruction = $doc->createElement("instruction");
    $program->appendChild($instruction);

    $order_write = $doc->createAttribute("order");
    $order_write->value = $order;
    $instruction->appendChild($order_write);

    $op_code = $doc->createAttribute("opcode");
    if(!array_key_exists(0, $parsed_line))
    {
        error(21, "PARSER ERROR: Opcode missing ");
    }
    $op_code->value = strtoupper($parsed_line[0]);
    $instruction->appendChild($op_code);

    $ret_vals = array($doc, $instruction);
    return $ret_vals;
}
/**
 * brief:   will parse the input line (three adress code)
 * @param $line = line to be parsed
 */
function parse($line)
{    
    $line = trim(preg_replace("/#.*$/", "", $line));
    $line = preg_replace('/\s+/', ' ', $line);
    $parsed_line = explode(' ', $line);
    $parsed_line = array_map('trim', $parsed_line); //applies trim to all elements of array
    $i = 0;     //to know on which element am I
    //deletes an elemet which contains nothing a = ""
    foreach ($parsed_line as $word) {
        if ($word == "") {
            unset($parsed_line[$i]);
        }
        $i = $i + 1;
    }
    if (count($parsed_line) == 0) {
        return 1;
    }
    return $parsed_line;
}

/**
 * brief:   Adds the argument to the function to xml file 
 *          Exits with code '99' if there were internal error
 * @param $doc = xml document (output of the program)
 * @param $argn = number of argument which is going to be added
 * @param $instruction = element of xml file to which we are going to add argument
 * @param $type = type of argument which is going to be added 
 * @param $parsed_line = parsed line which contains whole instruction 
 */
function addArg($doc, $instruction, $type, $parsed_line, $argn)
{
    
    if ( $type == "symb") {
        $type = "var";
        
        //fputs(STDERR, "$parsed_line[0]\n");
        $parsed_arg = explode('@', $parsed_line[$argn], 2);
        checkSyntaxVar($parsed_arg, "var");

        if($parsed_arg[0] == "LF" || $parsed_arg[0] == "GF" || $parsed_arg[0] == "TF"){
            $parsed_arg[1] = $parsed_arg[0] . "@" . $parsed_arg[1];
            $parsed_arg[0] = "var";
        }
        //substitute special characters for escape sequences
        //$parsed_arg[1] = htmlentities($parsed_arg[1],ENT_QUOTES,'UTF-8');
        
        $arg = $doc->createElement("arg" . $argn, htmlspecialchars($parsed_arg[1]));

        $type = $parsed_arg[0];
    } 
    else if ( $type == "var"){
        ;
        $parsed_arg = explode('@', $parsed_line[$argn], 2);
        checkSyntaxVar($parsed_arg, "var");
        if($parsed_arg[0] == "LF" || $parsed_arg[0] == "GF" || $parsed_arg[0] == "TF"){
            $parsed_arg[1] = $parsed_arg[0] . "@" . $parsed_arg[1];
            $parsed_arg[0] = "var";
        }
        else{
            error(23, "PARSER ERROR: Variable has to start with frame (LF/GF/TF) ");
        }
        $parsed_arg[1] = htmlentities($parsed_arg[1],ENT_QUOTES,'UTF-8');

        $arg = $doc->createElement("arg" . $argn, $parsed_arg[1]);

        $type = $parsed_arg[0];
    }
    else if ($type == "label") {
        global $labels;
        global $labels_arr;
        if($labels != -1 && !(in_array($parsed_line[$argn], $labels_arr))){
            $new_label = $parsed_line[$argn];
            array_push($labels_arr, $new_label );
            $labels =$labels+1;
        }
        checkSyntaxLabel($parsed_line[$argn]);
        $arg = $doc->createElement("arg" . $argn, $parsed_line[$argn]);
    } 
    else if ($type == "type") {
        if($parsed_line[$argn] == "int" || $parsed_line[$argn] == "string" ||$parsed_line[$argn] == "bool") //nemusi byt case sensitive  a potom previest na lowercase pismena
        {   
             $arg = $doc->createElement("arg" . $argn, $parsed_line[$argn]);
        }
        else{
             error(23, "PARSER ERROR: Type can be only \"bool\",\"int\",\"string\" ");
        }
    } 
    else
        error(99, "PARSER ERROR: Internal error, type not recognized");


    
    $instruction->appendChild($arg);

    $arg_attribute = $doc->createAttribute("type");
    $arg_attribute->value = $type;

    $arg->appendChild($arg_attribute);
    return $doc;
}

/**
 * brief:   Checks syntax of argument (label/second part of var)
 *          exits if there is syntactic error
 * @param $arg = it's label/second part of var which is going to be checked in this function
 */
function checkSyntaxLabel($arg)
{  
    if (!preg_match("/^[[:alpha:]_\-$&%!?*][[:alnum:]_\-$&%!?*]*$/", $arg))
        error(23, "PARSER ERROR: Invalid characters in argument " . $arg . " var / label");
    return $arg;
}

/**
 * brief:   Checks syntax of argument (var)
 *          decide of which type the variable is 
 *          and if needed divide variable into two parts
 *          firstpart@secondpart
 *          exits if there is syntactic error
 * @param $arg = it's variable which is going to be checked in this funtion
 */
function checkSyntaxVar($arg)
{
    if (count($arg) != 2) {
        $n_of_at = count($arg) -1;
        error(23, "PARSER ERROR: '@' has to be present once in argument: " . $arg[0] . "but it's: ".  $n_of_at . " time(s).");
    }
    switch ($arg[0]) {
        case "int":
            if (!preg_match('/^[-+]?\d+([Ee][+-]?\d+)?$/', $arg[1]))
                error(23, "PARSER ERROR: wrong int value".$arg[1]);
            break;
        case "nil":
            if (strcmp("nil", $arg[1]))
                error(23, "PARSER ERROR: can be only nil@nil but was found nil@".$arg[1]);
            break;
        case "string":
            if($arg[1] != ""){
                if (!preg_match('/^(\\\\[0-9]{3}|[^\\\\])*$/',  $arg[1]))         //escape sequence can be only \\000 -\\999 or \\\\
                    error(23, "PARSER ERROR: string can have only escape sequences from \\000 to \\999 " . $arg[1] . " var");
            }
            break;
        case "bool":
            if ($arg[1] != "true" && $arg[1] != "false")
                error(23, "PARSER ERROR: bool value can be only \"true\" or \"false\" " . $arg[1] . " var");
            break;
        case "LF":
        case "GF":
        case "TF":
            checkSyntaxLabel($arg[1]);
            break;
        default:
            error(23, "PARSER ERROR: Invalid type of argument " . $arg[0] . " @ " . $arg[1]);
    }
    return $arg;
}

/**
 * brief:   Checks syntax of instruction / one row
 *          defines which instruction it's processing and calls other
 *          function to check arguments 
 *          returns $doc which could be edited
 * @param $parsed_line = parsed line from standard input
 * @param $instruction = element of xml file
 * @param $doc = whole xml document (output of the program)
 */
function checkSyntaxI($parsed_line, $instruction, $doc)
{   
    global $loc;
    global $stats;
    if($loc != -1){
        $loc=$loc+1;
    }
    $parsed_line[0] = strtoupper($parsed_line[0]);
    $arg_count = count($parsed_line) - 1;
    $inv_n_params = "PARSER ERROR: invalid number of parameter (" . $arg_count . ") of the function ";
    if( (strcmp($parsed_line[0],"JUMP") == 0)       ||
        (strcmp($parsed_line[0],"JUMPIFNEQ") == 0)  ||
        (strcmp($parsed_line[0],"JUMPIFEQ") == 0)   ||
        (strcmp($parsed_line[0],"RETURN") == 0)     ||
        (strcmp($parsed_line[0],"CALL") == 0)
    ){
        statsAddJump();
    }

    switch ($parsed_line[0]) {
            //---------------------------------------------------
            //-----------------param(s) :       -----------------
            //---------------------------------------------------
            // BREAK
        case "BREAK":
            // CREATEFRAME
        case "CREATEFRAME":
            // PUSHFRAME
        case "PUSHFRAME":
            // POPFRAME
        case "POPFRAME":
            // RETURN
        case "RETURN":
            if ($arg_count != 0) {
                error(23, "$inv_n_params" . "POPFRAME");
            }
            break;
            //---------------------------------------------------
            //-----------------param(s) : label -----------------
            //---------------------------------------------------
            // LABEL ⟨label⟩
        case "LABEL":
            // JUMP ⟨label⟩
        case "JUMP":
            // CALL ⟨label⟩
        case "CALL":
            if ($arg_count != 1) {
                error(23, "$inv_n_params" . "CALL");
            }
            $doc = addArg($doc, $instruction, 'label', $parsed_line, 1);
            break;
            //---------------------------------------------------
            //-----------------param(s) : var   -----------------
            //---------------------------------------------------
            // DEFVAR ⟨var⟩
        case "DEFVAR":
            // POPS ⟨var⟩
        case "POPS":
            if ($arg_count != 1) {
                error(23, "$inv_n_params" . "POPS");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 1);
            break;
            //---------------------------------------------------
            //-----------------param(s) : symb ------------------
            //---------------------------------------------------
            // PUSHS ⟨symb⟩
        case "PUSHS":
            // WRITE ⟨symb⟩
        case "WRITE":
            // EXIT ⟨symb⟩
        case "EXIT":
            // DPRINT ⟨symb⟩
        case "DPRINT":
            if ($arg_count != 1) {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 1);
            break;
            //---------------------------------------------------
            //-----------------param(s) : var, type--------------
            //---------------------------------------------------
            // READ ⟨var⟩ ⟨type⟩
        case "READ":
            if ($arg_count != 2) {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 1);
            $doc = addArg($doc, $instruction, 'type', $parsed_line, 2);
            break;
            //---------------------------------------------------
            //-----------------param(s) : symb , var-------------
            //---------------------------------------------------
            // MOVE     ⟨var⟩ ⟨symb⟩
        case "MOVE":
            // INT2CHAR ⟨var⟩ ⟨symb⟩
        case "INT2CHAR":
            // STRLEN   ⟨var⟩ ⟨symb⟩
        case "STRLEN":
            // NOT      ⟨var⟩⟨symb⟩
        case "NOT":
            // TYPE ⟨var⟩ ⟨symb⟩
        case "TYPE":
            if ($arg_count != 2) {
                error(23, "$inv_n_params" . "MOVE");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 1);
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 2);

            break;
            //---------------------------------------------------
            //-----------------param(s) : var, symb1, symb2------
            //---------------------------------------------------
            // ADD      ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "ADD":
            // SUB      ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "SUB":
            // MUL      ⟨var⟩(symb 1 ⟩ ⟨symb 2 ⟩
        case "MUL":
            // IDIV     ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "IDIV":
            // LT       ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "LT":
            // GT       ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "GT":
            // EQ       ⟨var⟩symb 1 ⟩ ⟨symb 2 ⟩
        case "EQ":
            // AND      ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "AND":
            // OR       ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "OR":
            // STRI2INT ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "STRI2INT":
            // CONCAT   ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "CONCAT":
            // GETCHAR  ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "GETCHAR":
            // SETCHAR  ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "SETCHAR":

            if ($arg_count != 3) {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 1);
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 2);
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 3);
            break;
            //---------------------------------------------------
            //-----------------param(s) : label, symb1, symb2----
            //---------------------------------------------------
            // JUMPIFEQ     ⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "JUMPIFEQ":
            // JUMPIFNEQ    ⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "JUMPIFNEQ":
            if ($arg_count != 3) {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'label', $parsed_line, 1);
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 2);
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 3);
            break;
        default:
            error(22, "PARSER ERROR: Invalid opcode \"" . $parsed_line[0] . "\" ");
    }
    return $doc;
}
/**
 * brief:   Creates document and returns it
 */
function createDoc()
{
    $doc = new DomDocument('1.0', 'UTF-8');

    $doc->formatOutput = true;

    return $doc;
}

/**
 * brief:   saves file and exits program
 * @param $doc = file to save
 */
function SaveDocExit($doc)
{
    echo $doc->saveXML();
    //$doc->save("php://stdout");
    exit(0);
}

/**
 * brief:   checks arguments and call exit if:
 *          -wrong number of parameters
 *          -parameter "--help" was entered  
 */
function checkArguments()
{
    global $stats;
    global $loc;
    global $comments;
    global $labels;
    global $jumps;

    $shortopts  = "";
    $shortopts .= "h"; // short --help

    $longopts  = array(
        "stats:",      // Required value
        "loc", 
        "comments", 
        "labels",
        "jumps", 
        "help",
    );

    $opts = getopt($shortopts, $longopts);
    foreach (array_keys($opts) as $opt) switch ($opt) {
        case 'stats':
            $stats = $opts['stats'];
            break;
        case 'loc':
            $loc = 0;
            break;
        case 'comments':
            $comments = 0;
            break;
        case 'labels':
            $labels = 0;
            break;
        case 'jumps':
            $jumps = 0;
            break;
        case 'h':
        case 'help':
            echo "
            reads source file .src and generate html file .out according rules of unstructured imperative language IPP20 \r\n
            Usage: php7.4 parse.php --help/[std-in] \n\r
            --help  ->  prints help message and exits program. \n\r
            [std-in]->  is standard input, which should be three-address code
            which will be processed and transformed into xml file \n\r
            statistics of the code can be printed in the file:  \n\r
            --stats=filename -> filename is name of file where statistics will be printed \n\r
            --loc -> prints number of instructions  \n\r
            --comments -> prints number of comments \n\r
            --labels -> prints number of uniqe labels  \n\r
            --jumps -> number of jumps \n\r
            ";
            exit(0);
            break;
        default:      
            error(10, "PARSER ERROR: unknown argument");
    }
    if(($loc ==0 || $comments == 0 || $labels == 0 || $jumps == 0) && ($stats == " ")    ){
        error(10, "PARSER ERROR: to generate statistics, you have specify folder.");
    }
}

function statsAddJump(){
    global $jumps;
    if($jumps != -1){
        $jumps = $jumps + 1;
    }
}

/**
 * brief:   exits program and returns
 *          error code and error message
 * @param $err_val = error code
 * @param $err_msg = what should be written to the console
 */
function error($err_val, $err_msg)
{
    fputs(STDERR, "$err_msg\n");
    exit($err_val);
}
?>