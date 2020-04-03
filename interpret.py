"""
Interpreter of XML representation of IPPcode20.

Author: Andrej Ježík
School login: xjezik03
"""
import re
import sys
import xml.etree.ElementTree as ET
DEBUG = False
DEBUG_FRAME = False
types = [ 
          'int',
          'bool',
          'string',
          'type',
          'var',
          'nil',
          'label'
           ]
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
     process_instructions(root,1) # 1 stands for first instruction

def process_instructions(root, order):
     global labels, local_frame, temp_frame, stack, frame_stack, call_stack
     global_frame = {}
     i_done = -1
     for inst in root:
          values = []
          opcode = inst.get('opcode')
          order = inst.get('order')
          if opcode == None or order == None:
               error(23,"Opcode or order is missing")
          if DEBUG:
               print("===========CURRENT INSTRUCTION===========")
               print(opcode)
               print("=========================================")
          #-----MOVE----
          #-----⟨var⟩ ⟨symb⟩
          if opcode == "MOVE":
               if not correct_n_of_arg(inst,2):
                    error(32, "invalid_n_of_args", inst)
               if DEBUG:
                    print("----arguments of MOVE instruction------")
                    print(inst[0].text + ", " + inst[1].text)
                    print("----------------------------------")
               move(inst, values, global_frame, local_frame, temp_frame, labels)     
          #-----CREATEFRAME----
          #-----PUSHFRAME----
          #-----POPFRAME---- 
          elif opcode == "CREATEFRAME":
               if not correct_n_of_arg(inst,0):
                    error(32, "invalid_n_of_args", inst)
               temp_frame = {}
          #-----PUSHFRAME----
          elif opcode == "PUSHFRAME":
               if not correct_n_of_arg(inst,0):
                    error(32, "invalid_n_of_args", inst)
               if(temp_frame == None):
                    error(55, "Frame does't exist")
               frame_stack.append(temp_frame)
               local_frame = frame_stack[len(frame_stack)-1]
               temp_frame = None
          #-----POPFRAME---- 
          elif opcode == "POPFRAME":
               if not correct_n_of_arg(inst,0):
                    error(32, "invalid_n_of_args", inst)
               if(local_frame == None):
                    error(55, "Frame does't exist")
               temp_frame = frame_stack[0]
               frame_stack.pop(0)
               if len(frame_stack) != 0:
                    local_frame = frame_stack[len(frame_stack)-1]
               else:
                    local_frame = None
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

          #-----LT----
          #-----GT----
          #-----EQ----

          #-----AND----
          #-----OR----
          #-----⟨var⟩ ⟨symb 1 ⟩ ⟨symb 2 ⟩
          elif opcode == "ADD" or opcode == "SUB" or opcode == "MUL" or opcode == "IDIV" or opcode == "LT" or opcode == "GT" or opcode == "EQ" or opcode == "AND" or opcode == "OR":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid_n_of_args", inst)
               if DEBUG:
                    print("----arguments of arithm inst------")
                    print(str(inst[0].text) + ", " + str(inst[1].text) + ", " + str(inst[2].text))
                    print("----------------------------------")
               op_eq = False
               op_type = "int"
               if(opcode == "EQ"):
                    op_eq = True
               elif(opcode == "AND" or opcode == "OR"):
                    op_type = "bool"

               values = aritmetic_op(inst, values, global_frame, local_frame, temp_frame, labels, op_type, op_eq)
               if(opcode == "ADD"):
                    result = values[1] + values[2]
               elif(opcode == "SUB"):
                    result = values[1] - values[2]
               elif(opcode == "MUL"):
                    result = values[1] * values[2]
               elif(opcode == "IDIV"):
                    if(values[2] == 0):
                         error(57,"Division by zero: instruction IDIV, order: " + str(order))
                    result = values[1] // values[2]
               elif(opcode == "LT"):
                    result = values[1] < values[2]
               elif(opcode == "GT"):
                    result = values[1] > values[2]
               elif(opcode == "EQ"):
                    result = values[1] == values[2]
               if opcode == "AND":
                    result = 'true' if values[1] == values[2] == 'true' else 'false'
               elif opcode == "OR":
                    result = 'true' if values[1] == 'true' or values[2] == 'true' else 'false'
               if DEBUG:
                    print("----result of operatio------------")
                    print(result)
                    print("----------------------------------")
               set_value(values[0][:2], values[0][3:], result, global_frame, local_frame, temp_frame)
          #-----NOT----
          #-----⟨var⟩ ⟨symb1⟩ 
          elif opcode == "NOT":
               if not correct_n_of_arg(inst,2):
                    error(32, "invalid_n_of_args", inst)
               not_i(inst, values, global_frame, local_frame, temp_frame, labels)
          #-----INT2CHAR----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "INT2CHAR":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid_n_of_args", inst)
          #-----STRI2INT----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "STRI2INT":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid_n_of_args", inst)
          #-----READ----
          #-----⟨var⟩ ⟨type⟩
          elif opcode == "READ":
               if not correct_n_of_arg(inst,2):
                    error(32, "invalid_n_of_args", inst)
          #-----WRITE----
          #-----⟨symb⟩
          elif opcode == "WRITE":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid_n_of_args", inst)
               if DEBUG:
                    print("----arguments of arithm inst------")
                    print(inst[0].text)
                    print("----------------------------------")
               write(inst, values,global_frame, local_frame, temp_frame)
          #-----CONCAT----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "CONCAT":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid_n_of_args", inst)
          #-----STRLEN----
          #-----⟨var⟩ ⟨symb⟩
          elif opcode == "STRLEN":
               if not correct_n_of_arg(inst,2):
                    error(32, "invalid_n_of_args", inst)
          #-----GETCHAR----
          #-----SETCHAR----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "GETCHAR" or opcode == "SETCHAR":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid_n_of_args", inst)
          #-----TYPE----
          #-----⟨var⟩ ⟨symb⟩
          elif opcode == "TYPE":
               if not correct_n_of_arg(inst,2):
                    error(32, "invalid_n_of_args", inst)
               if DEBUG:
                    print("----arguments of TYPE instruction------")
                    print(inst[0].text + ", " + inst[1].text)
                    print("----------------------------------")
               type_i(inst, values, global_frame, local_frame, temp_frame, labels)
          #-----JUMP----
          #-----LABEL----
          #-----⟨label⟩
          elif opcode == "JUMP" or opcode == "LABEL":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid_n_of_args", inst)
          #-----JUMPIFEQ----
          #-----JUMPIFNEQ----
          #-----⟨label⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "JUMPIFEQ" or opcode == "JUMPIFNEQ":
               if not correct_n_of_arg(inst,3):
                    error(32, "invalid_n_of_args", inst)
          #-----EXIT----
          #-----⟨symb⟩
          elif opcode == "EXIT":
               if not correct_n_of_arg(inst,1):
                    error(32, "invalid_n_of_args", inst)
               if DEBUG:
                    print("----arguments of arithm inst------")
                    print(inst[0].text)
                    print("----------------------------------")
          #-----BREAK----
          #-----
          elif opcode == "BREAK":
               if not correct_n_of_arg(inst,0):
                    error(32, "invalid_n_of_args", inst)
               if DEBUG:
                    print("----arguments of arithm inst------")
                    print(inst[0].text)
                    print("----------------------------------")
               break_i(order, global_frame, local_frame, temp_frame,labels, i_done)
          else:
               error(32,"Instruction with invalid order opcode: " +  str(opcode) + " order: " + str(order))
def move(val, values, global_frame, local_frame, temp_frame, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var", labels))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1, global_frame, local_frame, temp_frame)

     whole_value_2 = val[1]
     typ_2 = get_atrib_type(whole_value_2.attrib["type"])
     if typ_2 == "var":
          values.append(check_val(val[1], typ_2, labels))
          frame_2 = values[1][:2]
          var_name_2 = values[1][3:]
          var_check(frame_2, var_name_2, global_frame, local_frame, temp_frame)
          values[1] = get_var(values[1][3:], global_frame, local_frame, temp_frame)
     else:
          if DEBUG:
               print(typ_2)
               print(whole_value_2.text)
          values.append(check_val(whole_value_2, typ_2, labels))
     set_value(frame_1, var_name_1, values[1], global_frame, local_frame, temp_frame)

def break_i(order, global_frame, local_frame, temp_frame, labels,i_done):
     print(sys.stderr,"Instructions done: " + str(i_done))
     print(sys.stderr,"Order of instructions: " + str(order))
     print(sys.stderr,"global frame: " + str(global_frame))
     print(sys.stderr,"local frame: " + str(local_frame))
     print(sys.stderr,"temporary frame: " + str(temp_frame))



def not_i(val, values, global_frame, local_frame, temp_frame, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var", labels))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1, global_frame, local_frame, temp_frame)

     whole_value_2 = val[1]
     typ_2 = get_atrib_type(whole_value_2.attrib["type"])
     if typ_2 == "var" or typ_2 == "bool":
          values.append(check_val(val[1], typ_2, labels))
          if typ_2 == "var":
               values.append(check_val(val[1], typ_2, labels))
               frame_2 = values[1][:2]
               var_name_2 = values[1][3:]
               var_check(frame_2, var_name_2, global_frame, local_frame, temp_frame)
               values[1] = get_var(values[1][3:], global_frame, local_frame, temp_frame)
               
     else:
          error(53,"Wrong type of operand int: " + str(val))
     is_bool(values[1])
     if values[1] == "true":
          values[1] = "false"
     else:
          values[1] = "true"

     if DEBUG:
          print("----value of to be written into variable-----")
          print(values[1])
          print("----------------------------------")
     set_value(frame_1, var_name_1, values[1], global_frame, local_frame, temp_frame)

def type_i(val, values, global_frame, local_frame, temp_frame, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var", labels))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1, global_frame, local_frame, temp_frame)

     whole_value_2 = val[1]
     typ_2 = get_atrib_type(whole_value_2.attrib["type"])
     if typ_2 == "var":
          values.append(check_val(val[1], typ_2, labels))
          frame_2 = values[1][:2]
          var_name_2 = values[1][3:]
          var_check(frame_2, var_name_2, global_frame, local_frame, temp_frame)
          values[1] = get_var(values[1][3:], global_frame, local_frame, temp_frame)

          if values[1] == 'true' or values[1] == 'false':
               typ_2 = 'bool'
          elif values[1] == 'nil':
               typ_2 = 'nil'
          elif type(values[1]) == int:
               typ_2 = 'int'
          elif type(values[1]) == str:
               typ_2 = 'string'
          else:
               typ_2 = ''
     else:
          values.append(check_val(whole_value_2, typ_2, labels))
     if DEBUG:
          print(typ_2)
          print(whole_value_2.text)
     set_value(frame_1, var_name_1, typ_2, global_frame, local_frame, temp_frame)
     
def write(inst, values,global_frame, local_frame, temp_frame):
     typ = get_atrib_type(inst[0].attrib["type"])
     values.append(check_val(inst[0], typ, labels))
     
     if typ == 'var':
          var_check(values[0][:2], values[0][3:], global_frame, local_frame, temp_frame)
          values[0] = get_var(values[0][3:], global_frame, local_frame, temp_frame)
          typ = var_type_control(values[0])
     if typ == 'int':
          is_int(values[0])
     elif typ == 'string':
          is_string(values[0])
     elif typ == 'bool':
          values[0] = str(values[0]).lower()
          is_bool(values[0])
     else:
          values.append(check_val(inst[0], typ, labels))

     values[0] = str(values[0])
     if values[0] == None:
          error(56,"missing value")
     if DEBUG:
          print("----Type of value, printed value------")
          print(typ)
     if typ == "nil":
         return
     print(values[0], end='')

     if DEBUG:
          print("")
          print("----------------------------------")

def aritmetic_op(val, values, global_frame, local_frame, temp_frame, labels, op_type, op_eq):
     values.append(check_val(val[0], "var", labels))
     var_check(values[0][:2], values[0][3:], global_frame, local_frame, temp_frame)
     typ1 = get_atrib_type(val[1].attrib["type"])
     typ2 = get_atrib_type(val[2].attrib["type"])
     if typ1 == op_type or typ1 == "var" or op_eq :
          values.append(check_val(val[1], typ1, labels))
          if typ1 == "var":
               var_check(values[1][:2], values[1][3:], global_frame, local_frame, temp_frame)
               values[1] = get_var(values[1][3:], global_frame, local_frame, temp_frame)
               is_int(values[1])
     else:
          print(typ1)
          error(53,"Wrong type of operand int: " + str(val))
     if typ2 == op_type or typ2 == "var" or op_eq :
          values.append(check_val(val[2], typ2, labels))
          if typ2 == "var":
               var_check(values[2][:2], values[2][3:], global_frame, local_frame, temp_frame)
               values[2] = get_var(values[2][3:], global_frame, local_frame, temp_frame)
               is_int(values[2])       
     else:
          error(53,"Wrong type of operand " + str(op_type) + ": " + str(val))
     var_check(values[0][:2], values[0][3:], global_frame, local_frame, temp_frame)
     if DEBUG:
          print("----values (of arithmetic op)-----")
          print(values)
          print("----------------------------------")
     return values

def is_var(val):
     pass
def is_int(val):
     try: 
          int(val)
          return 
     except:
          if(val == None):
               error(56,"Variable is unset: " + str(val))
          error(53,"Wrong type of operand int: " + str(val))
def is_bool(val):
     return val == "true" or val == "false"
def is_string(val):
     res = isinstance(val, str) 
     if res == False:
          if(val == None):
               error(56,"Variable is unset: " + str(val))
          error(53,"Wrong type of operand String: " + str(val))
def is_type(val):
     pass
def is_nil(val):
     if val != "nil":
          if(val == None):
               error(56,"Variable is unset: " + str(val))
          error(53,"Wrong type of operand nil: " + str(val))
def is_label(val):
     pass
def check_atrib_type(type, atrib_type):
     if(type != atrib_type):
          error(32, "invalid attributed type")

def get_var(var_name, global_frame, local_frame, temp_frame):
     if var_name in global_frame:
          return global_frame[var_name]
     elif local_frame is not None and var_name in local_frame:
          return local_frame[var_name]
     elif temp_frame is not None and var_name in temp_frame:
          return temp_frame[var_name]
     else:
          error(54, "Variable doesn't exist")


def set_value(frame_name, var_name, val, global_frame, local_frame, temp_frame):
     if frame_name == "GF":
          global_frame[var_name] = val
          if DEBUG_FRAME:
               print("")
               print("---- GF: after changing val to var-----")
               for item in global_frame:
                    print(str(global_frame[item]) + ", " +str(item))
               print("----------------------------------")
     elif frame_name == "TF":
          temp_frame[var_name] = val
          if DEBUG_FRAME:
               print("")
               print("---- TF: after changing val to var-----")
               for item in temp_frame:
                    print(str(temp_frame[item]) + ", " +str(item))
               print("----------------------------------")
     elif frame_name == "LF":
          local_frame[var_name] = val
          if DEBUG_FRAME:
               print("")
               print("---- LF: after changing val to var-----")
               for item in local_frame:
                    print(str(local_frame[item]) + ", " +str(item))
               print("----------------------------------")

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
     elif(atrib_type == "nil"):
          return atrib_type
     else:
          error(53, "Argument has an invalid type")

def var_check(frame_name, var_name, global_frame, local_frame, temp_frame):
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
          return "int"
     else:
          if isinstance(var, str) and var not in ['true', 'false', 'True', 'False','nil'] or var == None:
               return "string"
          elif (isinstance(var, bool)) or (isinstance(var, str) and var in ['true', 'false', 'True', 'False']):
               return "bool"
          elif (isinstance(var, str) and var in ['nil']):
               return "nil"
          elif var.isdigit():
               return "int"

def check_val(value, type, lab):
     if type == "var":
          is_var(value.text)
          check_atrib_type("var", value.attrib["type"])
          return value.text
     elif type == "int":
          is_int(value.text)
          check_atrib_type("int", value.attrib["type"])
          return int(value.text)
     elif type == "bool":
          is_bool(value.text)
          check_atrib_type("bool", value.attrib["type"])
          return value.text
     elif type == "string":
          if value.text == None:
               return ""
          is_string(value.text)
          check_atrib_type("string", value.attrib["type"])
          return value.text
     elif type == "nil":
          check_atrib_type("nil", value.attrib["type"])
          is_nil(value.text)
          return value.text
     elif type == "type":
          if value.text not in ['int', 'bool', 'string']:
               print("52: Argument type má nesprávnou hodnotu.")
               sys.exit(52)
     elif type == "label":
          is_label(value.text)
          return value.text

def def_var(frame_name, var_name, global_frame, local_frame, temp_frame):
     if DEBUG:
          print("----Frame, var_name---------------")
          print(frame_name +", "+ var_name)
          print("----------------------------------")
     if frame_name == "GF":
          global_frame[var_name] = None
     elif frame_name == "TF":
          if(temp_frame == None):
               error(32,"Temporary frame doesn't exists")
          else:
               temp_frame[var_name] = None

     elif frame_name == "LF":
          if(local_frame == None):
               error(32,"Local frame doesn't exists")
          else:
               local_frame[var_name] = None
     else:
          error(32,"invalid frame name, only GF, LF and TF are allowed")

def check_instruction(inst):
     arg_n = 1
     if inst.get('opcode') == None or inst.get('order') == None:
          error(32,"Instruction without opcode or order ")
     if  (int(inst.get('order')) < 0 ):
          error(32,"Instruction with invalid order opcode: " +  str(inst.get('opcode')) + " order: " + str(inst.get('order')))
     for arg in inst:
          if(arg.tag != "arg"+str(arg_n)) or (arg.attrib["type"] not in types):
               error(32,"Wrong name of argument of the instruction "+ str(inst[0]))
          arg_n += 1

def labels_check(root):
     for inst in root:
          check_instruction(inst)
          if(inst.attrib["opcode"] == "LABEL"):
               if (inst[0].text in labels) or not correct_n_of_arg(inst,1):
                    error(32,"Instruction LABEL has wrong number of arguments or the label is redefined")
               labels[inst[0].text] = int(inst.attrib["order"])

def correct_n_of_arg(inst, n_of_arg):
     return (len(inst) == n_of_arg)

def check_syntax(xml_tree):
     root = xml_tree.getroot()
     if(root.tag != "program") or (root.attrib['language']  !=  "IPPcode20"):
          error(32, "root element has wrong tag or attrinute language")
     for atr in root.attrib:
          if (atr != "language") and (atr != "name") and (atr != "description"):
               error(32,"Root element has invalid arguments")  
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
          23: "lexical or syntacic error in code",
          31: "Wrong XML format fo input file/s",
          32: "unexpected structure of XML file, lexical of syntax error",
          53: "wrong operand/s",
          54: "Variable doesn't exit",
          55: "Frame doesn't exist",
          57: "Division by 0",
          99: "internal error"
     }
     if desc == "invalid_n_of_args" and inst != None:
          print(sys.stderr,"Instruction with invalid n of arguments opcode: " +  str(inst.get('opcode')) + " order: " + str(inst.get('order')))
     elif desc != "":
          print(sys.stderr,desc)
     print(sys.stderr,str(err_val) + ": " + switch.get(err_val,": Invalid error code"))
     sys.exit(err_val)

if __name__ == "__main__":
     local_frame = None
     temp_frame = None
     stack = []
     frame_stack = []
     call_stack = []
     labels = {}
     main()