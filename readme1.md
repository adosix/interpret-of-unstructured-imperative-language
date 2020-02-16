# Documentation of the parser.php
### name and surname: Andrej Ježík
### login: xjezik03
## Parser.php
### executing script
  * Takes only one argument **--help** which is optional and it displays basic execution rules for this script.
  * Reads and processes standard input (three adress code) and then prints output xml file on standard output.
### lexical analysis
 1. Reads first line and checks header of the file which should define language and prints header of output xml file.
 

 2. Processes input file line by line and creates output xml file. Line is parsed and then opcode and number of arguments of the instraction is checked in the function **checkSyntaxI()**.

###syntactic analysis
 1. From the funtion checkSyntax we call function **addArg()** for every argument and it calls function **checkSyntaxVar()** or **checkSyntaxLabel()** , if check passed then the argument is added to the xml file using DOM (Document Object Model) library.
 

 2. In the function **checkSyntaxVar()** or **checkSyntaxLabel()** is checked if syntax of the argument is correct. Syntax is controlled by regular expressions.

### error handling
function **error()** is called and it takes two arguments  first error code and second error message which will be printed. Error code will be returned as the application will be terminated.

