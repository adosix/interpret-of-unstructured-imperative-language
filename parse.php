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

    $n_of_args = count($parsed_line) - 1;
    if($n_of_args >= 1){
        $arg = $xml_doc->createElement('arg1',$parsed_line[1]);
        $instruction->appendChild($arg);
    }
    if($n_of_args >= 2){
        $arg = $xml_doc->createElement('arg2',$parsed_line[2]);
        $instruction->appendChild($arg);
    }
    if($n_of_args >= 3){
        $arg = $xml_doc->createElement('arg3',$parsed_line[3]);
        $instruction->appendChild($arg);
    }
    if($n_of_args >= 4){
        $arg = $xml_doc->createElement('arg4',$parsed_line[4]);
        $instruction->appendChild($arg);
    }

    $order = $order + 1;
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
    $parsed_line = checkSyntax($parsed_line);

    if($parsed_line == 10){
        return 1;
    }
    else{
        return $parsed_line;
    } 
    
}

function checkSyntax($parsed_line){
    $parsed_line[0] = strtoupper($parsed_line[0]);
    switch($parsed_line[0]){
        // MOVE ⟨var⟩ ⟨symb⟩
        case "MOVE":
            break;
        // CREATEFRAME
        case "CREATEFRAME":
            break;
        // PUSHFRAME
        case "PUSHFRAME":
            break;
        // POPFRAME
        case "POPFRAME":
            break;
        // DEFVAR ⟨var⟩
        case "DEFVAR":
            break;
        // CALL ⟨label⟩
        case "CALL":
            break;
        // RETURN
        case "RETURN":
            break;
        // PUSHS ⟨symb⟩
        case "PUSHS":
            break;
        // POPS ⟨var⟩
        case "POPS":
            break;
        // ADD ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "ADD":
            break;
        // SUB ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "SUB":
            break;
        // MUL ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "MUL":
            break;
        // IDIV ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "IDIV":
            break;
        // LT ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "LT":
            break;
        // GT ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "GT":
            break;
        // EQ ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "EQ":
            break;
        // AND ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "AND":
            break;
        // OR ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "OR":
            break;
        // NOT ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "NOT":
            break;
        // INT2CHAR ⟨var⟩ ⟨symb⟩
        case "INT2CHAR":
            break;
        // STRI2INT ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "STRI2INT":
            break;
        // READ ⟨var⟩ ⟨type⟩
        case "READ":
            break;
        // WRITE ⟨symb⟩
        case "WRITE":
            break;
        // CONCAT ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "CONCAT":
            break;
        // STRLEN ⟨var⟩ ⟨symb⟩
        case "STRLEN":
            break;
        // GETCHAR ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "GETCHAR":
            break;
        // SETCHAR ⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "SETCHAR":
            break;
        // TYPE ⟨var⟩ ⟨symb⟩
        case "TYPE":
            break;
        // LABEL ⟨label⟩
        case "LABEL":
            break;
        // JUMP ⟨label⟩
        case "JUMP":
            break;
        // JUMPIFEQ ⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "JUMPIFEQ":
            break;
        // JUMPIFNEQ ⟨label⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
        case "JUMPIFNEQ":
            break;
        // EXIT ⟨symb⟩
        case "EXIT":
            break;
        // DPRINT ⟨symb⟩
        case "DPRINT":
            break;
        // BREAK
        case "BREAK":
            break;
        default:
            error(21, "PARSER ERROR: Invalid opcode \"" . $parsed_line[0] . "\" ");
    }
    return $parsed_line;
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