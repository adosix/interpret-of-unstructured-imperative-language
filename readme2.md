# Documentation of the parser.php
### name and surname: Andrej Ježík
### login: xjezik03
## Interpret.py
### executing script
  example:   

    python3.8 interpret.py --source=parse.out

  * argument **--source=program.xml** where program.xml is xml file which represents program which will be interpreted
  * argument **--input=input.txt** which is optional. Input.txt stands for program with inputs of the processed program, if it's not given then program expects input from stdin
  * argument **--help** which is optional.
  This argument displays basic execution rules for this script.
  * Reads and interprets xml representation of the program.
### main parts
Flow of the application
1. main()
    controls flow of the program:
    1. Function processArguments() is called to check and process arguments.
    2. Function parse_file(src) parses xml file and returns xml_tree for work with xml files I used library xml.etree.ElementTree.
    3. Function check_syntax(xml_tree) will check basic syntax of program in xml_tree. 
    4. Function labels_check(root) if label defined and saves them into the list.
    5. Function process_instructions(root, inp, 0) processes instructions.
2. process_instructions(root, inp, 0):
    1. This function contains basically one while(True) loop. Firstly it gets next instruction with function get_next_inst(root,order) which returns next instruction according to attribute order.
    2. Then there are if statements which tell us according opcode of the instruction what the instruction should do. For each instruction there is a function which interprets it. <br>

We have three main frames: <br>
  They serve us for organizing and storing variables in different scopes.
  1. Global frame: is created empty before processing any instruction.
  2. Local frame: is actual frame we are working with (we store local variables in int).
  3. Temp frame: is created when instruction CREATEFRAME is encountered.

Working with values while processing instruction
- If the instruction need certain type of a value we have alway conditions if the value is that type or if it is variable, if it's variable we use function get_var() to which we send frame of wanted variable and name of that variable, this function returns us variable and then we can check if it has correct type. <br>

Error handling 
- Error handling is done by function error() which takes error value which is integer and also closer description of the error. There are default prints for that error which are printed according error value but we send to that function more specific quote.
## Test.php
### executing script
  example:   

    php7.4 test.php --directory=tests/ --recursive >../index.html

  * argument **--help** which is optional.
  This argument displays basic execution rules for this script.                    
  * argument **directory** path to directory in which are the tests default: ./
  * argument **parse-script** path to parse script, default ./parse.php
  * argument **int-script** path to interpret script, default ./interpret.py
  * argument **jexamxml** path to jexamxml.jar, default /pub/courses/ipp/jexamxml/jexamxml.jar
  * argument **parse-only** test just parse script
  * argument **int-only** test just interpret script

Test.php is a php script that testes programs parse.php and interpret.py with given data and then outputs .html results on stdout.

### overview
Firstly get arguments with function **getopt()** and then we check if they are correct.
Next we will check if  directory with tests exists if yes we store all test files into variables($files_src, $files_out, $files_rc). 
Then we call function **generateHTML()** which holds the main functionality which is to generate .html file we do that with **DOM Document library**. Each html tag is represented by DOMDocument element. For each test file we generate one row of html table. Style of html file is stored as string and then saved into the .html file
