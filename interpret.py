"""
Interpreter of XML representation of IPPcode20.

Author: Andrej Ježík
School login: xjezik03
"""
import sys
import xml.etree.ElementTree as ET

# --------  main definition  --------
def main():
     #----ARGUMENT proccessig
     file_path = processArguments()
     #----XML FILE parsing
     xml_tree = parse_file(file_path)
     #----GENERAL SYNTAX OF ROOT checked 
     root = check_syntax(xml_tree)
     #----INSTRUCTION proccessing
     process_instructions(root)

def process_instructions(root):
     for inst in root:
          print(inst)

def check_syntax(xml_tree):
     root = xml_tree.getroot()
     if(root.tag != "program") or (root.attrib['language']  !=  "IPPcode20"):
          error(32)
     for atr in root.attrib:
          if (atr != "language") and (atr != "name") and (atr != "description"):
               error(32)  
     return root   

def parse_file(file_path):
     try:
          tree = ET.parse(file_path)
          return tree
     except IOError:
          error(11)
     except:
          error(31)

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
          sys.exit(0)
     else:
          error(10)

def error(err_val):
     switch = {
          10: "Wrong combination of parameters",
          11: "Error while opening input file/s",
          12: "Error while opening output file/s",
          31: "Wrong XML format fo input file/s",
          32: "unexpected structure of XML file, lexical of syntax error",
          99: "internal error"
     }
     print(sys.stderr,str(err_val) + ": " + switch.get(err_val,": Invalid error code"))
     sys.exit(err_val)

#---- global variables

instr_total = 0
local_frame = None
temp_frame = None
stack = []
frame_stack = []
call_stack = []
order = 1
labels = {}
global_frame = {}
main()