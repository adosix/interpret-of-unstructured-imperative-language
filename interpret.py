"""
Interpreter of XML representation of IPPcode20.

Author: Andrej Ježík
School login: xjezik03
"""

from xml.dom.minidom import parse, parseString
import sys

# --------  main definition  --------
def main():
     file_path = processArguments()
     print(file_path)
     # --- Open input file ---
     try:
          doc = parse(file=file_path)
     except IOError:
          error(11)
     except ET.ParseError:
          error(31)
     except:
          error(99)

     # --- Check root node ---
     root = tree.getroot()

     # --- Process instructions ---
     Interpret.loadInstructions(root)

     # --- Successful end ---
     sys.exit(0)

def processArguments():
     # check correct number of arguments
     if len(sys.argv) != 2:
          error(10)

     if sys.argv[1][:9] == "--source=":
          return sys.argv[1][9:] 
     elif sys.argv[1] == "--help":
          h = """
     Convert xml document which represents code in unstructured imperative language IPP20 which is based on python3
     usage: python3.8 interpret.py option
     options:
     --help        -> prints help message and exits program.
     --source=file -> input file with XML representation of the source code
     --input=file  -> file with input XML files 
          """ 
          print(h)
     else:
          return "bullshit"

def error(err_val):
     switch = {
          10: "Wrong combination of parameters",
          11: "Error while opening input file/s",
          12: "Error while opening output file/s",
          99: "internal error"
     }
     print(sys.stderr, switch.get(err_val, "Invalid error code"))
     exit(err_val)


main()
