# Documentation of the parser.php
### name and surname: Andrej Ježík
### login: xjezik03
## Interpret.py
### executing script
  * argument **--source=program.xml** where program.xml is xml file which represents program which will be interpreted
  * argument **--input=input.txt** which is optional. Input.txt stands for program with inputs of the processed program, if it's not given then program expects input from stdin
  * argument **--help** which is optional.
  This argument displays basic execution rules for this script.
  
  * Reads and interprets xml representation of the program
### main parts
Flow of the application
1. main()
    controls flow of the program:
    1. function processArguments() is called to check and process arguments
    2. function parse_file(src) parses xml file and returns xml_tree for work with xml files I used library xml.etree.ElementTree
    3. function check_syntax(xml_tree) will check basic syntax of program in xml_tree 
    4. function labels_check(root) if label defined and saves them into the list
    5. function process_instructions(root, inp, 0) processes instructions
2. process_instructions(root, inp, 0):
    1. This function contains basically one while(True) loop. Firstly it gets next instruction with function get_next_inst(root,order) which returns next instruction according to attribute order.
    2. Then there are if statements which tell us according opcode of the instruction what the instruction should do. For each instruction there is a function which interprets it.
## Test.php
### executing script
  * argument **--help** which is optional.
  This argument displays basic execution rules for this script.                    
  * argument **directory** path to directory in which are the tests default: ./
  * argument **parse-script** path to parse script, default ./parse.php
  * argument **int-script** path to interpret script, default ./interpret.py
  * argument **jexamxml** path to jexamxml.jar, default /pub/courses/ipp/jexamxml/jexamxml.jar
  * argument **parse-only** test just parse script
  * argument **int-only** test just interpret script

Tests scripts parse.php and interpret.py with given data and outputs .html results on stdout.
