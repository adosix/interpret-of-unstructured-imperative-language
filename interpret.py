"""
Interpreter of XML representation of IPPcode20.

Author: Andrej Ježík
School login: xjezik03
"""
import re
import sys
import xml.etree.ElementTree as ET
DEBUG = False
types = [ 
          'int',
          'bool',
          'string',
          'type',
          'var',
          'nil',
          'label'
           ];
opcodes = [  
               'MOVE',
               'CREATEFRAME',
               'PUSHFRAME',
               'POPFRAME',
               'DEFVAR',
               'CALL',
               'RETURN',
               'PUSHS',
               'POPS',
               'ADD',
               'SUB',
               'MUL',
               'IDIV',
               'LT',
               'GT',
               'EQ',
               'AND',
               'OR',
               'NOT',
               'INT2CHAR',
               'STRI2INT',
               'READ',
               'WRITE',
               'CONCAT',
               'STRLEN',
               'GETCHAR',
               'SETCHAR',
               'TYPE',
               'LABEL',
               'JUMP',
               'JUMPIFEQ',
               'JUMPIFNEQ',
               'EXIT',
               'DPRINT',
               'BREAK'
              ];
# --------  main definition  --------
def main():
     #----ARGUMENT proccessig
     file_path = processArguments()
     #----XML FILE parsing
     xml_tree = parse_file(file_path)
     #----GENERAL SYNTAX OF ROOT checked 
     root = check_syntax(xml_tree)
     #----LABELS check + Instruction general structure check
     labels_check(root)
     #----INSTRUCTION proccessing
     process_instructions(root)

def process_instructions(root):
     global labels, global_frame, local_frame, temp_frame, stack, frame_stack, call_stack, order, instr_total

     for inst in root:
          order += 1
          instr_total += 1
          values = []
          opcode = inst.get('opcode')
          if DEBUG:
               print("====CURRENT INSTRUCTION===========")
               print(opcode)
               print("==================================")
          #-----MOVE----
          #-----⟨var⟩ ⟨symb⟩
          if opcode == "MOVE":
               if not correct_n_of_arg(inst,2):
                    error(32, "invalid_n_of_args", inst)
          #-----CREATEFRAME----
          #-----PUSHFRAME----
          #-----POPFRAME----
          elif opcode == "CREATEFRAME" or opcode == "PUSHFRAME" or opcode == "POPFRAME":
               if not correct_n_of_arg(inst,0):
                    error(32, "invalid_n_of_args", inst)
          #-----DEFVAR----
          #-----⟨var⟩
          elif opcode == "DEFVAR":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid_n_of_args", inst)
               values.append(check_val(inst[0], "var", labels))
               def_var(values[0][:2], values[0][3:], global_frame, local_frame, temp_frame)
          #-----CALL----
          #-----⟨label⟩
          elif opcode == "CALL":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid_n_of_args", inst)
          #-----RETURN----
          elif opcode == "RETURN":
               if not correct_n_of_arg(inst,0):
                    error(32, "invalid_n_of_args", inst)
          #-----PUSHS----
          #-----⟨symb⟩
          elif opcode == "PUSHS":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid_n_of_args", inst)
          #-----POPS----
          #-----⟨var⟩
          elif opcode == "POPS":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid_n_of_args", inst)
          #-----ADD----
          #-----SUB----
          #-----MUL----
          #-----IDIV----
          #-----⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
          elif opcode == "ADD" or opcode == "SUB" or opcode == "MUL" or opcode == "IDIV":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid_n_of_args", inst)
               if DEBUG:
                    print("----arguments of arithm inst------")
                    print(inst[0].text + ", " + inst[1].text + ", " + inst[2].text)
                    print("----------------------------------")
               values = aritmetic_op(inst, values, global_frame, local_frame, temp_frame, labels)
               result = values[1] + values[2]
               if DEBUG:
                    print("----result of operatio------------")
                    print(result)
                    print("----------------------------------")
               set_val_to_var(values[0][:2], values[0][3:], result, global_frame, local_frame, temp_frame)
          #-----LT----
          #-----GT----
          #-----EQ----
          #-----AND----
          #-----OR----
          #-----NOT----
          #-----⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
          elif opcode == "LT" or opcode == "GT" or opcode == "EQ" or  opcode == "AND" or opcode == "OR" or opcode == "NOT":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid n of args", inst)
          elif opcode == "WRITE":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid n of args", inst)
               write(inst, values,)
               
          else:
               print("error")
def write(inst, values,):
     typ = get_atrib_type(inst[0].attrib["type"])
     if DEBUG:
          print(typ)
     values.append(check_val(inst[0], typ, labels))
     if typ == "var":
          var_control(values[0][:2], values[0][3:], global_frame, local_frame, temp_frame)
          values[0] = get_var(values[0][3:], global_frame, local_frame, temp_frame)
          typ = var_type_control(values[0])
     elif typ == 'int':
          is_int(values[0])
     elif typ == 'string':
          values[0] = is_string(values[0])
     elif typ == 'bool':
          values[0] = str(values[0]).lower()
          is_bool(values[0])
     else:
          values.append(check_val(inst[0], typ, labels))
     values[0] = str(values[0])
     if values[0] == None:
          error(56,"missing value")
     print(values[0])

def aritmetic_op(val, values, global_frame, local_frame, temp_frame, labels):
     values.append(check_val(val[0], "var", labels))
     var_control(values[0][:2], values[0][3:], global_frame, local_frame, temp_frame)
     typ1 = get_atrib_type(val[1].attrib["type"])
     typ2 = get_atrib_type(val[2].attrib["type"])
     if typ1 == "int" or typ1 == "var":
          values.append(check_val(val[1], typ1, labels))
          if typ1 == "var":
               var_control(values[1][:2], values[1][3:], global_frame, local_frame, temp_frame)
               values[1] = get_var(values[1][3:], global_frame, local_frame, temp_frame)
               is_int(values[1])
     else:
          print("53: Nesprávný typ operandů.")
          sys.exit(53)
          error(53, )
     if typ2 == "int" or typ2 == "var":
          values.append(check_val(val[2], typ2, labels))
          if typ2 == "var":
               var_control(values[2][:2], values[2][3:], global_frame, local_frame, temp_frame)
               values[2] = get_var(values[2][3:], global_frame, local_frame, temp_frame)
               is_int(values[2])
     else:
          print("53: Nesprávný typ operandů.")
          sys.exit(53)
     var_control(values[0][:2], values[0][3:], global_frame, local_frame, temp_frame)
     if DEBUG:
          print("----values (of arithmetic op)-----")
          print(values)
          print("----------------------------------")
     return values

def is_var(val):
     pass
def is_int(val):
     return re.match(r"^[+,-]?[0-9]+$", val) is not None
def is_bool(val):
     return val == "true" or val == "false"
def is_string(val):
     pass
def is_type(val):
     pass
def is_label(val):
     pass
def check_atrib_type(type, atrib_type):
     if(type != atrib_type):
          error(32, "inv a type")

def get_var(var_name, global_frame, local_frame, temp_frame):
     if var_name in global_frame:
          return global_frame[var_name]
     elif local_frame is not None and var_name in local_frame:
          return local_frame[var_name]
     elif temp_frame is not None and var_name in temp_frame:
          return temp_frame[var_name]
     else:
          error(54, "Variable doesn't exist")

def set_val_to_var(frame_name, var_name, val, global_frame, local_frame, temp_frame):
     if frame_name == "GF":
          global_frame[var_name] = val
     elif frame_name == "TF":
          temp_frame[var_name] = val
     elif frame_name == "LF":
          local_frame[var_name] = val

def get_atrib_type(atrib_type):
     if(atrib_type == "var"):
          return atrib_type
     elif(atrib_type == "int"):
          return atrib_type
     elif(atrib_type == "bool"):
          return atrib_type
     elif(atrib_type == "string"):
          return atrib_type
     elif(atrib_type == "label"):
          return atrib_type
     elif(atrib_type == "type"):
          return atrib_type
     else:
          error(32, "Argument has an invalid type")

def var_control(frame_name, var_name, global_frame, local_frame, temp_frame):
     if frame_name == "GF":
          if var_name not in global_frame:
               error(54,"Variable doesn't exist")

     elif frame_name == "TF":
          if temp_frame == None:
               error(55,"Frame does't exist")
          if var_name not in temp_frame:
               error(54,"Variable doesn't exist")

     elif frame_name == "LF":
          if local_frame == None:
               error(55,"Frame does't exist")
          if var_name not in local_frame:
               error(54,"Variable doesn't exist")

def var_type_control(var):
	if type(var) is int:
		return "int";
	else:
		if isinstance(var, str) and var not in ['true', 'false', 'True', 'False'] or var == None:
			return "string";
		elif (isinstance(var, bool)) or (isinstance(var, str) and var in ['true', 'false', 'True', 'False']):
			return "bool";
		elif var.isdigit():
			return "int";

def check_val(value, type, lab):
     if type == "var":
          is_var(value.text)
          check_atrib_type("var", value.attrib["type"])
          return value.text;
     elif type == "int":
          is_int(value.text)
          check_atrib_type("int", value.attrib["type"])
          return int(value.text);
     elif type == "bool":
          is_bool(value.text)
          check_atrib_type("bool", value.attrib["type"])
          return value.text;
     elif type == "string":
          if value.text == None:
               return ""
          is_string(value.text)
          check_atrib_type("string", value.attrib["type"])
          return value.text;
     elif type == "type":
          if value.text not in ['int', 'bool', 'string']:
               print("52: Argument type má nesprávnou hodnotu.")
               sys.exit(52)
     elif type == "label":
          is_label(value.text, lab)
          return value.text

def def_var(frame_name, var_name, global_frame, local_frame, temp_frame):
     if DEBUG:
          print(frame_name)
     if frame_name == "GF":
          global_frame[var_name] = None
     elif frame_name == "TF":
          if(temp_frame == None):
               error(32,"frame doesn t exist")
          else:
               temp_frame[var_name] = None

     elif frame_name == "LF":
          if(local_frame == None):
               error(32,"frame doesn t exist")
          else:
               local_frame[var_name] = None
     else:
          error(32,"wrong frame name")

def check_instruction(inst):
     arg_n = 1
     if inst.get('opcode') == None or inst.get('order') == None:
          print(sys.stderr,"Instruction without opcode or order ")
          error(32)
     if  (inst.attrib["opcode"] not in opcodes) or int(inst.get('order')) < 0 :
          print(sys.stderr,"Instruction with invalid opcode or order opcode: " +  str(inst.get('opcode')) + " order: " + str(inst.get('order')))
          error(32)
     for arg in inst:
          if(arg.tag != "arg"+str(arg_n)) or (arg.attrib["type"] not in types):
               print(sys.stderr,"wrong name of argument of the instruction " + str(inst[0]))
               error(32)
          arg_n += 1

def labels_check(root):
     for inst in root:
          check_instruction(inst)
          if(inst.attrib["opcode"] == "LABEL"):
               if (inst[0].text in labels) or not correct_n_of_arg(inst,1):
                    print(sys.stderr,"Instruction LABEL has wrong number of arguments or the label is redefined")
                    error(32)
               labels[inst[0].text] = int(inst.attrib["order"])

def correct_n_of_arg(inst, n_of_arg):
     return (len(inst) == n_of_arg)

def check_syntax(xml_tree):
     root = xml_tree.getroot()
     if(root.tag != "program") or (root.attrib['language']  !=  "IPPcode20"):
          error(32, "root_el")
     for atr in root.attrib:
          if (atr != "language") and (atr != "name") and (atr != "description"):
               print(sys.stderr,"root element has arguments")
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

def error(err_val, desc="", inst = None):
     switch = {
          10: "Wrong combination of parameters",
          11: "Error while opening input file/s",
          12: "Error while opening output file/s",
          31: "Wrong XML format fo input file/s",
          32: "unexpected structure of XML file, lexical of syntax error",
          99: "internal error"
     }
     if desc == "invalid n of args" and inst != None:
          print(sys.stderr,"Instruction with invalid n of arguments opcode: " +  str(inst.get('opcode')) + " order: " + str(inst.get('order')))
     elif desc == "inv a type":
          print(sys.stderr,"Argument has an invalid type")
     elif desc == "wrong frame name":
          print(sys.stderr,"invalid frame name, only GF, LF and TF are allowed")
     elif desc == "frame doesn t exist":
          print(sys.stderr,"Frame doesn't exists")
     elif desc == "root_el":
          print(sys.stderr,"root element has wrong tag or attrinute language")
     elif desc != "":
          print(sys.stderr,desc)
     print(sys.stderr,str(err_val) + ": " + switch.get(err_val,": Invalid error code"))
     sys.exit(err_val)

if __name__ == "__main__":
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