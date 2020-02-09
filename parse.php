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


    $xml_doc = checkSyntax($parsed_line, $instruction, $xml_doc);
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
    $arg = $doc->createElement($argn);
    $instruction->appendChild($arg);
    $var = $doc->createElement($type, $parsed_line[1]);
    $arg->appendChild($var);
    return $doc;
}

function checkSyntax($parsed_line, $instruction, $doc){
    $parsed_line[0] = strtoupper($parsed_line[0]);
    $arg_count = count($parsed_line) - 1;
    $inv_n_params = "invalid number of parameter (" . $arg_count . ") of the function "; 
    switch($parsed_line[0]){
        // MOVE ⟨var⟩ ⟨symb⟩
        case "MOVE":
            if($arg_count != 2)
            {
                error(23, "$inv_n_params" . "MOVE");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 'arg1');
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg2');
            
            break;
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
        // DEFVAR ⟨var⟩
        case "DEFVAR":
            if($arg_count != 1)
            {
                error(23, "$inv_n_params" . "DEFVAR");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 'arg1');
            break;
        // CALL ⟨label⟩
        case "CALL":
            if($arg_count != 1)
            {
                error(23, "$inv_n_params" . "CALL");
            }
            $doc = addArg($doc, $instruction, 'label', $parsed_line, 'arg1');
            break;
        // PUSHS ⟨symb⟩
        case "PUSHS":
            if($arg_count != 1)
            {
                error(23, "$inv_n_params" . "PUSHS");
            }
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg1');
            break;
        // POPS ⟨var⟩
        case "POPS":
            if($arg_count != 1)
            {
                error(23, "$inv_n_params" . "POPS");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 'arg1');
            break;
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
        // NOT      ⟨var⟩⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "NOT":
            if($arg_count != 3)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 'arg1');
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg2');
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg3');
            break;
        // INT2CHAR ⟨var⟩ ⟨symb⟩
        case "INT2CHAR":
            if($arg_count != 2)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 'arg1');
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg2');
            break;

        // READ ⟨var⟩ ⟨type⟩
        case "READ":
            if($arg_count != 2)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 'arg1');
            $doc = addArg($doc, $instruction, 'type', $parsed_line, 'arg2');
            break;
        // WRITE ⟨symb⟩
        case "WRITE":
            if($arg_count != 1)
            {
                error(23, "$inv_n_params" . "WRITE");
            }
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg1');
            break;
        // STRLEN ⟨var⟩ ⟨symb⟩
        case "STRLEN":
        // TYPE ⟨var⟩ ⟨symb⟩
        case "TYPE":
            if($arg_count != 2)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'var', $parsed_line, 'arg1');
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg2');
            break;
        // LABEL ⟨label⟩
        case "LABEL":
        // JUMP ⟨label⟩
        case "JUMP":
            if($arg_count != 1)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'label', $parsed_line, 'arg1');
            break;
        // JUMPIFEQ     ⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "JUMPIFEQ":
        // JUMPIFNEQ    ⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "JUMPIFNEQ":
            if($arg_count != 3)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'label', $parsed_line, 'arg1');
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg2');
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg3');
            break;
        // EXIT ⟨symb⟩
        case "EXIT":
        // DPRINT ⟨symb⟩
        case "DPRINT":
            if($arg_count != 1)
            {
                error(23, "$inv_n_params");
            }
            $doc = addArg($doc, $instruction, 'symb', $parsed_line, 'arg1');
            break;
        // BREAK
        case "BREAK":
            if($arg_count != 0)
            {
                error(23, "$inv_n_params");
            }
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