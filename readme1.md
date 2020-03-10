# Documentation of the parser.php
### name and surname: Andrej Ježík
### login: xjezik03
## Parser.php
### executing script
  * Takes only one argument **--help** which is optional.
  This argument displays basic execution rules for this script.
  * Reads and processes standard input (three adress code) and then prints output xml file on standard output.
### lexical analysis
 1. Reads first line and checks header of the file which should define language and prints header of output xml file.
 

 2. Processes input file line by line and creates output xml file. Line is parsed and then opcode and number of arguments of the instraction is checked in the function **checkSyntaxI()**.

###syntactic analysis
 1. From the funtion checkSyntax we call function **addArg()** for every argument and it calls function **checkSyntaxVar()** or **checkSyntaxLabel()** , if check passed then the argument is added to the xml file using DOM (Document Object Model) library.
 

 2. In the function **checkSyntaxVar()** or **checkSyntaxLabel()** is checked if syntax of the argument is correct. Syntax is controlled by regular expressions.

### error handling
Function **error()** is called and it takes two arguments  first error code and second error message which will be printed. Error code will be returned as the application will be terminated.

### statistics
I have unokenebted also extension which provide statistics of the code. There are several options/arguments you can choose from: 

<br>
filename is the name of the file where the statistics will be printed.

    --stats=filename 

<br>
prints number of instructions

    --loc
<br>
prints number of comments

    --comments
<br>
prints number of uniqe labels

    --labels
<br>
number of jumps

    --jumps