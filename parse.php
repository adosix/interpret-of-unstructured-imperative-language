#!/usr/bin/env php
<?php

checkArguments();

//load first line
if (!$line = fgets(STDIN))
    error(11, "PARSER ERROR: No input");

//get rid of whitespace chars and comments
$line = preg_replace("/#.*$/", "", $line);
$line = preg_replace('/\s/', "", $line);

echo $line;
//check first line
if ($line != ".IPPcode20")
    error(21, "PARSER ERROR: Invalid header");

//counter for instructions
$order = 1;

//create basic XML document 
$xml_doc = createDoc();
$program = $xml_doc ->createElement("program");
$xml_doc->appendChild($program);

//parse input doc and create xml doc
while($line = fgets(STDIN)){     
    
    $parsed_line = parse($line);
     if($parsed_line != 1){ 
        
    $instruction = $xml_doc->createElement("instruction");
    $program->appendChild($instruction);

    $op_code = $xml_doc->createElement("opcode",$parsed_line[0]);
    $instruction->appendChild($op_code);

    $order_write = $xml_doc->createElement("order",$order);
    $instruction->appendChild($order_write);
    $order =  $order + 1;

    $xml_doc = checkSyntaxI($parsed_line, $instruction, $xml_doc);
     }
}
//get final document
SaveDocExit($xml_doc);

function parse($line){
    $line = preg_replace("/#.*$/", "", $line);
    $line = preg_replace( "/\r|\n|\x1a/", "", $line);
    $parsed_line = explode(' ', $line);
    
    $i = 0;     //to know on which element am I
    //deletes an elemet which contains nothing a = ""
    foreach ($parsed_line as $word) {
        if($word == ""){
            unset($parsed_line[$i]);
        }
        $i = $i + 1;
    } 
    if(count($parsed_line) == 0){
        return 1;
    }
    return $parsed_line;
    
}

function addArg($doc, $instruction, $type, $parsed_line, $argn){
    if($type == "var" || $type == "symb")
        $arg_parts =  checkSyntaxVar($parsed_line[$argn], $type);
    else if($type == "label")
        $arg_parts =  checkSyntaxLabel($parsed_line[$argn]);     
    else if($type == "type")
        $arg_parts =  checkSyntaxLabel($parsed_line[$argn]);    
    else 
        error(99, "PARSER ERROR: Internal error, type not recognized");
    $arg = $doc->createElement("arg" . $argn);
    $instruction->appendChild($arg);
    if(count($arg_parts)==2){
        $arg_child = $doc->createElement($arg_parts[0], $arg_parts[1]);
    }
    else if(count($arg_parts)==1){
        $arg_child = $doc->createElement($type, $arg_parts[0]);
    }
    else{
        error(99, "PARSER ERROR: Wrong number of argument parts: ". count($arg_parts));
    }
    
    $arg->appendChild($arg_child);
    return $doc;
}
function checkSyntaxLabel($arg){
    if(!preg_match("/^[[:alpha:]_\-$&%*][[:alnum:]_\-$&%*]*$/", $arg))
        error(23, "PARSER ERROR: Invalid characters in argument \r\n" . $arg . " var / label");
    $arg_parts = explode("@", $arg);
    return $arg_parts;
}

function checkSyntaxVar($arg){
    
    $arg_parts = explode("@", $arg, 2);
    if(count($arg_parts) != 2){
        print($arg . " var");
        error(23, "PARSER ERROR: \'@\' has to be present in argument \r\n" . $arg . " var");
    }
    switch($arg_parts[0]){
        case "int":
            if(!preg_match("/^[-[:alpha:]\_$&%*][-[:alnum:]\_$&%*]*$/", $arg_parts[1]));
            break;
        case "string":
            
            break;
        case "bool":
            if($arg_parts[1] != "true" || $arg_parts[1] != "false")
                error(23, "PARSER ERROR: bool value can be only \"true\" or \"false\" \r\n" . $arg . " var");
            break;
        case "LF":
        case "GF":
        case "TF":
            checkSyntaxLabel($arg_parts[1]);
            break;
        default:
            error(23, "PARSER ERROR: Invalid type of argument \r\n" . $arg_parts[0] . " @ " . $arg_parts[1]);
        }
    return $arg_parts;
}

function checkSyntaxI($parsed_line, $instruction, $doc){
    $parsed_line[0] = strtoupper($parsed_line[0]);
    $arg_count = count($parsed_line) - 1;
    $inv_n_params = "invalid number of parameter (" . $arg_count . ") of the function "; 
    switch($parsed_line[0]){
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
            if($arg_count != 0)
            {
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
            if($arg_count != 1)
            {
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
            if($arg_count != 1)
            {
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
            if($arg_count != 1)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'symb', $parsed_line,1);
            break;
        //---------------------------------------------------
        //-----------------param(s) : var, type--------------
        //---------------------------------------------------
        // READ ⟨var⟩ ⟨type⟩
        case "READ":
            if($arg_count != 2)
            {
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
            if($arg_count != 2)
            {
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

            if($arg_count != 3)
            {
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
            if($arg_count != 3)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'label', $parsed_line, 1);
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 2);
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 3);
            break;
        default:
            error(21, "PARSER ERROR: Invalid opcode \"" . $parsed_line[0] . "\" ");
    }
    return $doc;
}

function createDoc(){
    $doc = new DomDocument("1.0", "UTF-8");

    $doc->formatOutput = true;

    return $doc;
}

/**
 * brief:   saves file and exits program
 * @param $doc = file to save
 */

function SaveDocExit($doc){
    $doc->save("php://stdout");
	exit(0);
}

/**
 * brief:   checks arguments and call exit if:
 *          -wrong number of parameters
 *          -parameter "--help" was entered  
 */
function checkArguments(){
    global $argc;
    global $argv;
    if($argc == 1){
        return;
    } 
    elseif($argc == 2 && $argv[1] == "--help" ){
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