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
     global src, inp
     #----ARGUMENT proccessig
     processArguments()
     #----XML FILE parsing
     xml_tree = parse_file(src)
     #----GENERAL SYNTAX OF ROOT checked 
     root = check_syntax(xml_tree)
     #----LABELS check + Instruction general structure check
     labels_check(root)
     #----INSTRUCTION proccessing
     process_instructions(root, inp, 0) # 1 stands for first instruction

def process_instructions(root, inp, order):
     global labels, local_frame, temp_frame, stack, frame_s, call_s, i_done,global_frame, call_s
     while True:
          inst = get_next_inst(root,order)
          if inst == None:
               exit(0)
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
               correct_n_of_arg(inst,2)
               if DEBUG:
                    inst_debug(inst,2, order, opcode)
               move(inst, values, labels)     
          #-----CREATEFRAME----
          #-----PUSHFRAME----
          #-----POPFRAME---- 
          elif opcode == "CREATEFRAME":
               correct_n_of_arg(inst,0)
               temp_frame = {}
          #-----PUSHFRAME----
          elif opcode == "PUSHFRAME":
               correct_n_of_arg(inst,0)
               if(temp_frame == None):
                    error(55, "Frame does't exist")
               frame_s.append(temp_frame)
               local_frame = temp_frame
               temp_frame = None
          #-----POPFRAME---- 
          elif opcode == "POPFRAME":
               correct_n_of_arg(inst,0)
               if(local_frame == None):
                    error(55, "Frame does't exist")
               temp_frame = frame_s.pop()
               if(len(frame_s) != 0): 
                    local_frame = frame_s[-1]
               else:
                    local_frame = None
          #-----DEFVAR----
          #-----⟨var⟩
          elif opcode == "DEFVAR":
               correct_n_of_arg(inst,1)
               if DEBUG:
                    inst_debug(inst,1, order, opcode)
               values.append(check_val(inst[0], "var"))
               frame = str(values[0][:2])
               var_name = str(values[0][3:])
               if (frame == "GF" and global_frame is None) or (frame == "LF" and local_frame is None) or (frame == "TF" and temp_frame is None):
                    error(55, "frame not defined")
               if frame == "GF" and var_name in global_frame: 
                    error(52, "redefined variable")
               elif frame == "LF" and var_name in local_frame: 
                    error(52, "redefined variable")
               elif frame == "TF" and var_name in temp_frame: #local_frame is None 
                    error(52, "redefined variable")
               #if local_frame is None or frame == "LF" and var_name in local_frame:
               def_var(frame, var_name     )
          #-----CALL----
          #-----⟨label⟩
          elif opcode == "CALL":
               correct_n_of_arg(inst,1)
               if DEBUG:
                    inst_debug(inst ,1, order, opcode)
               values.append(check_val(inst[0], "label"))
               call_s.append(int(inst.attrib["order"]))
               if inst[0].text in labels:
                    process_instructions(root, inp, int(labels[inst[0].text])-1)
               else:
                    error(52,"Label doesn't exist")
               break
          #-----RETURN----
          elif opcode == "RETURN":
               correct_n_of_arg(inst,0)
               if call_s == []:
                    error(56, "Stack is empty")
               process_instructions(root, inp, int(call_s.pop()))
               break
          #-----PUSHS----
          #-----⟨symb⟩
          elif opcode == "PUSHS":
               correct_n_of_arg(inst,1)
               if DEBUG:
                    inst_debug(inst ,1, order, opcode)
               pushs(inst, values, labels)
          #-----POPS----
          #-----⟨var⟩
          elif opcode == "POPS":
               correct_n_of_arg(inst,1)
               if DEBUG:
                    inst_debug(inst ,1, order, opcode)
               pops(inst, values, labels)
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
               correct_n_of_arg(inst,3)
               if DEBUG:
                    inst_debug(inst ,3, order, opcode)
               op_eq = False
               op_type = "int"
               if(opcode == "EQ"):
                    op_eq = True
               elif(opcode == "AND" or opcode == "OR" or opcode == "LT" or opcode == "GT"):
                    op_type = "bool"

               values = aritmetic_op(inst, values, labels, op_type, op_eq)
               if DEBUG:
                    print("----values which will be processed------")
                    print(str(values[1]) + ", " + str(values[2]))
                    print("----------------------------------")

               if(opcode == "ADD"):
                    result = int(values[1]) + int(values[2])
               elif(opcode == "SUB"):
                    result = int(values[1]) - int(values[2])
               elif(opcode == "MUL"):
                    result = int(values[1]) * int(values[2])
               elif(opcode == "IDIV"):
                    if(values[2] == 0):
                         error(57,"Division by zero: instruction IDIV, order: " + str(order))
                    result = int(values[1]) // int(values[2])
               elif(opcode == "LT"):
                    try:
                         result = int(values[1]) < int(values[2])
                    except:
                         result = values[1] < values[2]
               elif(opcode == "GT"):
                    try:
                         result = int(values[1]) > int(values[2])
                    except:
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
               set_value(values[0][:2], values[0][3:], result     )
          #-----NOT----
          #-----⟨var⟩ ⟨symb1⟩ 
          elif opcode == "NOT":
               correct_n_of_arg(inst,2)
               if DEBUG:
                    inst_debug(inst ,2, order, opcode)
               not_i(inst, values, labels)
          #-----INT2CHAR----
          #-----⟨var⟩ ⟨symb⟩
          elif opcode == "INT2CHAR":
               correct_n_of_arg(inst,2)
               if DEBUG:
                    inst_debug(inst ,2, order, opcode)
               if DEBUG:
                    print("----arguments of arithm inst------")
                    print(inst[0].text)
                    print("----------------------------------")
               int2char(inst, values, labels)
               
          #-----STRI2INT----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "STRI2INT":
               correct_n_of_arg(inst,3)
               if DEBUG:
                    inst_debug(inst ,3, order, opcode)
               character_processing(inst, values, labels, opcode) 
          #-----READ----
          #-----⟨var⟩ ⟨type⟩
          elif opcode == "READ":
               correct_n_of_arg(inst,2)
               if DEBUG:
                    inst_debug(inst ,2, order, opcode)
               #print("\"" + str(inp) + "\"")
               if inp == "":
                    try:
                         line = input()
                    except Exception:
                         usr_input = ''
               else:     
                    line = inp.readline()
                    if not line:
                         break
                    #print(line)
               line = "".join(line.splitlines())
               read(inst, values, labels, line)    
               
               
          #-----WRITE----
          #-----⟨symb⟩
          elif opcode == "WRITE":
               correct_n_of_arg(inst,1)
               if DEBUG:
                    inst_debug(inst ,1, order, opcode)
               if DEBUG_FRAME:
                    print("----FRAMES------")
                    print("GF: " + str(global_frame))
                    print("LF: " + str(local_frame))
                    print("TF: " + str(temp_frame))
                    print("----------------------------------")              
               
               write(inst, values,global_frame, local_frame, temp_frame)
          #-----CONCAT----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "CONCAT":
               correct_n_of_arg(inst,3)
               if DEBUG:
                    inst_debug(inst ,3, order, opcode)
               concat(inst, values, labels)
          #-----STRLEN----
          #-----⟨var⟩ ⟨symb⟩
          elif opcode == "STRLEN":
               correct_n_of_arg(inst,2)
               if DEBUG:
                    inst_debug(inst ,2, order, opcode)

               strlen(inst, values, labels)  
          #-----SETCHAR----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "SETCHAR":
               correct_n_of_arg(inst,3)
               if DEBUG:
                    inst_debug(inst ,3, order, opcode)

               set_char(inst, values, labels) 
          #-----GETCHAR----
          #-----⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "GETCHAR":
               correct_n_of_arg(inst,3)
               if DEBUG:
                    inst_debug(inst ,3, order, opcode)
               character_processing(inst, values, labels, opcode)  

          #-----TYPE----
          #-----⟨var⟩ ⟨symb⟩
          elif opcode == "TYPE":
               correct_n_of_arg(inst,2)
               if DEBUG:
                    inst_debug(inst ,2, order, opcode)
               type_i(inst, values, labels)
          #-----JUMP----
          #-----⟨label⟩
          elif opcode == "JUMP":
               correct_n_of_arg(inst,1)
               if DEBUG:
                    inst_debug(inst ,1, order, opcode)
               if inst[0].text in labels:
                    process_instructions(root, inp, int(labels[inst[0].text])-1)
               else:
                    error(52,"Label doesn't exist")
          #-----JUMPIFEQ----
          #-----JUMPIFNEQ----
          #-----⟨label⟩ ⟨symb1⟩ ⟨symb2⟩
          elif opcode == "JUMPIFEQ" or opcode == "JUMPIFNEQ":
               correct_n_of_arg(inst,3)
               if DEBUG:
                    inst_debug(inst , 3, order, opcode)
               op_eq = True
               op_type = "not_important"   
               values = aritmetic_op(inst, values, labels, op_type, op_eq)
               result = values[1] == values[2]
               if inst[0].text in labels:
                    if result == True and opcode == "JUMPIFEQ":
                              process_instructions(root, inp, int(labels[inst[0].text])-1)
                    elif result == False and opcode == "JUMPIFNEQ":
                              process_instructions(root, inp, int(labels[inst[0].text])-1)
               else:
                    error(52,"Label doesn't exist")
          #-----EXIT----
          #-----⟨symb⟩
          elif opcode == "EXIT":
               correct_n_of_arg(inst,1)
               if DEBUG:
                    inst_debug(inst ,1, order, opcode)
               ret_val = exit_i(inst, values, labels)
               if ret_val < 0 or ret_val > 49:
                    error(57,"Error value has to be from 0-49 but it is: " + str(ret_val) ) 
               exit(ret_val)
          #-----BREAK----
          #-----
          elif opcode == "BREAK":
               correct_n_of_arg(inst,0)
               break_i(order,labels, i_done)
          elif opcode == "LABEL" or opcode == "DPRINT":
               pass
          else:
               error(32,"Instruction with invalid order opcode: " +  str(opcode) + " order: " + str(order))

"""
brief:    prints arguments of current instruction if DEBUG is set to True
parameters:    arguments of instruction
n_of_arg: number of arguments
"""
def inst_debug(parameters, n_of_arg,order, opcode):
     print("----arguments of instruction: " + opcode +"------")
     print("----order: " + order)
     if n_of_arg > 0:
          print(parameters[0].text, end='')
     if n_of_arg > 1:
          print(", " + str(parameters[1].text), end='')
     if n_of_arg > 2:
          print(", " + str(parameters[2].text), end='')
     print("")
     print("---------------------------------------")

"""
brief:    pops value from stack and sets value to variable
"""
def pops(val, values, labels):
     global stack
     if stack == []:
          error(56,"Stack is empty")
     typ = get_atrib_type(val[0].attrib["type"])
     if typ == 'var':
          values.append(check_val(val[0], typ))
          frame_1 = values[0][:2]
          var_name_1 = values[0][3:]
          var_check(frame_1, var_name_1)
     else:
          error(53, "Wrong operand type")
     popped_val = stack.pop()
     set_value(frame_1, var_name_1, popped_val )

"""
brief:    pushs value to stack
"""
def pushs(val, values, labels):
     global stack
     typ = get_atrib_type(val[0].attrib["type"])
     values.append(check_val(val[0], typ))
     if typ == 'var':
          var_check(values[0][:2], values[0][3:])
          values[0] = get_var(values[0][:2],values[0][3:])
     elif typ in ['label', 'type']:
          error(53, "Wrong operand type")

     if values[0] == None:
          error(56, "Unset value")
     stack.append(values[0])
     if DEBUG:
          print("----Content of stack after push------")
          print("stack: " + str(stack))
          print("---------------------------------------")

"""
brief:    move value from the second position to the variable on the first
"""
def move(val, values, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     whole_value_2 = val[1]
     typ_2 = get_atrib_type(whole_value_2.attrib["type"])
     if typ_2 == "var":
          values.append(check_val(val[1], typ_2))
          frame_2 = values[1][:2]
          var_name_2 = values[1][3:]
          var_check(frame_2, var_name_2)
          values[1] = get_var(values[1][:2],values[1][3:])
          if values[1] == None:
               error(56, "Unset value")
     else:
          if DEBUG:
               print(typ_2)
               print(whole_value_2.text)
          values.append(check_val(whole_value_2, typ_2))

     set_value(frame_1, var_name_1, values[1]     )

"""
brief:    reads value from stdin or input file
"""
def read(val, values, labels, line):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     if (get_atrib_type(val[1].attrib["type"]) == 'type'):
          if (val[1].text == 'int'):
               is_int(line)
               line = int(line)
          elif (val[1].text == 'bool'):
               is_bool(line)
               line = bool(line)
          elif (val[1].text == 'nil'):
               line = is_nil(line)
          elif (val[1].text == 'string'):
               line = is_string(line)
          else:
               error(52,"Invalid value of type.")
     else:
          error(53,"invalid value of argument.")

     set_value(values[0][:2], values[0][3:], line)

"""
brief:    changes value from number to character according ASCII table
"""
def int2char(val, values, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     typ1 = get_atrib_type(val[1].attrib["type"])

     if typ1 == "int" or typ1 == "var":
          values.append(check_val(val[1], typ1))
          if typ1 == "var":
               var_check(values[1][:2], values[1][3:])
               values[1] = get_var(values[1][:2], values[1][3:])
               is_int(values[1])
     else:
          print(typ1)
          error(53,"Wrong type of operand int: " + str(val))

     var_check(values[0][:2], values[0][3:])

     if DEBUG:
          print("----values (of arithmetic op)-----")
          print(values)
          print("----------------------------------")
     
     if values[1] == None :
          error(56, "Unset value")
     
     if int(values[1]) > 127 or int(values[1]) < 0:
          error(56, "Invalid value: " + str(values[1]) + " for instrucion int2char")

     values[1] = str(chr(values[1]))

     if DEBUG:
          print("----result-----")
          print(values[1])
          print("----------------------------------")
     
     set_value(frame_1, var_name_1, values[1])

"""
brief:    saves to variable length of string
"""
def strlen(val, values, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     whole_value_2 = val[1]

     typ_2 = get_atrib_type(whole_value_2.attrib["type"])

     if typ_2 == "var" or typ_2 == "string":
          values.append(check_val(val[1], typ_2))
          if typ_2 == "var":
               var_check(values[1][:2], values[1][3:])
               values[1] = get_var(values[1][:2], values[1][3:])
          is_string(values[1])
     else:
          error(53,"Wrong type of operand string: " + str(val))

     if values == None:
          error(56,"Value is not assign to variable")
     values[1] = len(values[1])
     if DEBUG:
          print("----arguments of arithm inst------")
          print(str(values[1]) + ", " + str(typ_2))
          print("----------------------------------")
     set_value(frame_1, var_name_1, values[1])

"""
brief:    exits program with given retrun code
"""
def exit_i(val, values, labels):
     typ1 = get_atrib_type(val[0].attrib["type"])
     
     if typ1 == "int" or typ1 == "var":
          values.append(check_val(val[0], typ1))
          if typ1 == "var":
               var_check(values[0][:2], values[0][3:])
               values[0] = get_var(values[0][:2], values[0][3:])
               is_int(values[0])
     else:
          error(53,"Wrong type of operand int: " + str(val))
     if DEBUG:
          print(typ1)
          print(values[0])
     return int(values[0])

"""
brief:    return next instruction according attribute order
"""
def get_next_inst(root,order):
     best_so_far_o = None
     best_so_far_i = None
     for inst in root:
          order_act = int(inst.get('order'))
          if order_act > int(order):
               if best_so_far_o == None:
                    best_so_far_o = order_act
                    best_so_far_i = inst

               elif best_so_far_o > order_act:
                    best_so_far_o = order_act
                    best_so_far_i = inst

     return best_so_far_i

"""
brief:    instruction which prints statistics of the program in the given momment
"""
def break_i(order, labels,i_done):
     print(sys.stderr,"Instructions done: " + str(i_done))
     print(sys.stderr,"Order of instructions: " + str(order))
     print(sys.stderr,"global frame: " + str(global_frame))
     print(sys.stderr,"local frame: " + str(local_frame))
     print(sys.stderr,"temporary frame: " + str(temp_frame))


"""
brief:    instruction NOT, negates value
"""
def not_i(val, values, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     whole_value_2 = val[1]
     typ_2 = get_atrib_type(whole_value_2.attrib["type"])
     if typ_2 == "var" or typ_2 == "bool":
          values.append(check_val(val[1], typ_2))
          if typ_2 == "var":
               values.append(check_val(val[1], "var"))
               frame_2 = values[1][:2]
               var_name_2 = values[1][3:]
               var_check(frame_2, var_name_2)
               values[1] = get_var(values[1][:2], values[1][3:])   
     else:
          error(53,"Wrong type of operand int: " + str(val))
     is_bool(values[1])
     if values[1] == "true":
          values[1] = "false"
     elif values[1] == "false":
          values[1] = "true"
     

     if DEBUG:
          print("----value of to be written into variable-----")
          print(values[1])
          print("----------------------------------")
     set_value(frame_1, var_name_1, values[1])

"""
brief:    set type of value to the string variable
"""
def type_i(val, values, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     whole_value_2 = val[1]
     typ_2 = get_atrib_type(whole_value_2.attrib["type"])
     if typ_2 == "var":
          values.append(check_val(val[1], typ_2))
          frame_2 = values[1][:2]
          var_name_2 = values[1][3:]
          var_check(frame_2, var_name_2)
          values[1] = get_var(values[1][:2], values[1][3:])
          if str(values[1]) == 'true' or str(values[1]) == 'false' or str(values[1]) == 'True' or str(values[1]) == 'False':
               typ_2 = 'bool'
          elif str(values[1]) == 'nil':
               typ_2 = 'nil'
          elif type(values[1]) == int:
               typ_2 = 'int'
          elif type(values[1]) == str:
               typ_2 = 'string'
          else:
               typ_2 = ''
     else:
          values.append(check_val(whole_value_2, typ_2))
     if DEBUG:
          print(typ_2)
          print(whole_value_2.text)
     set_value(frame_1, var_name_1, typ_2)
     
"""
brief:    instruction write, prints value
"""
def write(inst, values, global_frame, local_frame, temp_frame):
     typ = get_atrib_type(inst[0].attrib["type"])
     values.append(check_val(inst[0], typ))
     
     if typ == 'var':
          var_check(values[0][:2], values[0][3:])
          values[0] = get_var(values[0][:2], values[0][3:])
          typ = var_type_control(values[0])
     if typ == 'int':
          is_int(values[0])
     elif typ == 'string':
          values[0] =is_string(values[0])
     elif typ == 'bool':
          values[0] = str(values[0]).lower()
          is_bool(values[0])
     
     elif typ == "nil":
         return
     else:
          values.append(check_val(inst[0], typ))
     if values[0] == None:
          error(56,"missing value")
     if DEBUG:
          print("----Type of value, printed value------")
          print(typ)
     #values[0] = str(values[0]).encode('utf-8').decode('unicode_escape').encode('latin-1').decode("utf-8") 
     #values[0] =bytes(values[0], 'utf-8')
    # values[0] = values[0].decode("unicode_escape")#string_escape
     print(values[0], end='')

     if DEBUG:
          print("")
          print("----------------------------------")

"""
brief:    returns checked values for arithmetic operation
"""
def aritmetic_op(val, values, labels, op_type, op_eq):
     if op_type != "not_important":
          values.append(check_val(val[0], "var"))
          var_check(values[0][:2], values[0][3:])
     else:
          values.append(val[0].text)

     typ1 = get_atrib_type(val[1].attrib["type"])
     typ2 = get_atrib_type(val[2].attrib["type"])
     if op_type == "bool":
          if ((typ1 != "var" and typ2 != "var") and (typ1 != "nil" and typ2 != "nil") and (typ1 != typ2)):
               error(53,"Wrong type of operand for EQ: " + str(typ1) + " + " + str(typ2))
              
     if DEBUG:
          print("----values (of arithmetic op)-----")
          print("typ1: " + str(typ1) + ", typ2: " + str(typ2) + ", op_eq: " + str(op_eq)+", op_type: " + str(op_type))
          print("----------------------------------")
     
     if typ1 == op_type or typ1 == "var" or (op_type== "bool" and (typ1 == typ2) or (typ1 == "var" or typ2 == "var")) or op_eq:
          values.append(check_val(val[1], typ1))
          if typ1 == "var":
               if(val[1][3:] == "undefined"):
                    error(54, "variable undefined")
               var_check(values[1][:2], values[1][3:])
               values[1] = get_var(values[1][:2], values[1][3:])
          if op_type != "bool" and not op_eq:
               is_int(values[1])
         # check_val(values[1],typ1)
     else:
          print(typ1)
          error(53,"Wrong type of operand 1 some: " + str(val))
     if typ2 == op_type or typ2 == "var" or op_type=="bool" or op_eq:
          values.append(check_val(val[2], typ2))
          if typ2 == "var":
               if(val[2][3:] == "undefined"):
                    error(54, "variable undefined")
               var_check(values[2][:2], values[2][3:])
               values[2] = get_var(values[2][:2], values[2][3:])
          if op_type != "bool" and not op_eq:
               is_int(values[2])    
          #check_val(values[2],typ2)   
     else:
          error(53,"Wrong type of operand 2 " + str(op_type) + ": " + str(val))
     var_check(values[0][:2], values[0][3:])
     
     
     if typ1 == "var":
          if values[1] == None:
               error(56, "Unset value")
          elif values[1] == 'true' or values[1] == 'false':
               typ1 = 'bool'
          elif values[1] == 'nil':
               typ1 = 'nil'
          elif type(values[1]) == int:
               typ1 = 'int'
          elif type(values[1]) == str:
                    typ1 = 'string'
     if typ2 == "var":
          if values[2] == None:
               error(56, "Unset value")
          if values[2] == 'true' or values[2] == 'false':
               typ2 = 'bool'
          elif values[2] == 'nil':
               typ2 = 'nil'
          elif type(values[2]) == int:
               typ2 = 'int'
          elif type(values[2]) == str:
                    typ2 = 'string'
               
     if (typ1 == "nil" or typ2=="nil") and (op_type == "bool" or op_eq):
          pass
     elif typ2 != typ1:
          error(53, "types are not the same")


     if DEBUG:
          print("----values (of arithmetic op)-----")
          print(values)
          print("----------------------------------")
     
     if values[1] == None or values[2] == None:
          error(56, "Unset value")
     return values

"""
brief:    for instructions GETCHAR and STRI2INT,
          prepares values for them 
"""
def character_processing(val, values, labels, opcode):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)


     typ1 = get_atrib_type(val[1].attrib["type"])
     typ2 = get_atrib_type(val[2].attrib["type"])

     if typ1 == "string" or typ1 == "var":
          values.append(check_val(val[1], typ1))
          if typ1 == "var":
               var_check(values[1][:2], values[1][3:])
               values[1] = get_var(values[1][:2], values[1][3:])
          is_string(values[1])
     else:
          print(typ1)
          error(53,"Wrong type of operand string: " + str(val))

     if typ2 == "int" or typ2 == "var":
          values.append(check_val(val[2], typ2))
          if typ2 == "var":
               var_check(values[2][:2], values[2][3:])
               values[2] = get_var(values[2][:2], values[2][3:])
          is_int(values[2])       
     else:
          error(53,"Wrong type of operand int: " + str(val))

     if DEBUG:
          print("----values (of get char op)-----")
          print(values)
          print("----------------------------------")
     
     if values[1] == None or values[2] == None:
          error(56, "Unset value")
     if int(values[2]) >= len(values[1]) or int(values[2]) < 0:
          error(58, "string index out of range")
     if opcode== "GETCHAR":
          values[1] = values[1][int(values[2])]
     elif opcode == "STRI2INT":
          values[1] = ord(values[1][int(values[2])])
     if DEBUG:
          print("----result-----")
          print(values[1])
          print("----------------------------------")
     set_value(frame_1, var_name_1, values[1])


"""
brief:    set character in the given index in string
"""
def set_char(val, values, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))
     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     values[0] = get_var(frame_1, var_name_1)
     is_string(values[0])


     typ1 = get_atrib_type(val[1].attrib["type"])
     typ2 = get_atrib_type(val[2].attrib["type"])

     
     if typ1 == "int" or typ1 == "var":
          values.append(check_val(val[1], typ1))
          if typ1 == "var":
               var_check(values[1][:2], values[1][3:])
               values[1] = get_var(values[1][:2], values[1][3:])
          is_int(values[1])      
     else:
          error(53,"Wrong type of operand int: " + str(val))

     if typ2 == "string" or typ2 == "var":
          values.append(check_val(val[2], typ2))
          if typ2 == "var":
               var_check(values[2][:2], values[2][3:])
               values[2] = get_var(values[2][:2], values[2][3:])
          is_string(values[2])
     else:
          print(typ1)
          error(53,"Wrong type of operand string: " + str(val))


     if DEBUG:
          print("----values (of get char op)-----")
          print(values)
          print("----------------------------------")
     
     if values[0] == None or values[1] == None or values[2] == None or values[2] == "":
          error(56, "Unset value")
          
     if int(values[1]) >= len(values[0]) or int(values[1]) < 0:
          error(58, "string index out of range")

     values[1] = values[0][:int(values[1])] +  values[2][0] + values[0][(int(values[1])+1):]
     if DEBUG:
          print("----result-----")
          print(values[1])
          print("----------------------------------")
     set_value(frame_1, var_name_1, values[1])

"""
brief:    concatenate two strings into one
"""
def concat(val, values, labels):
     whole_value_1 = val[0]
     values.append(check_val(whole_value_1, "var"))

     frame_1 = values[0][:2]
     var_name_1 = values[0][3:]
     var_check(frame_1, var_name_1)

     typ1 = get_atrib_type(val[1].attrib["type"])
     typ2 = get_atrib_type(val[2].attrib["type"])

     if typ1 == "string" or typ1 == "var":
          values.append(check_val(val[1], typ1))
          if typ1 == "var":
               var_check(values[1][:2], values[1][3:])
               values[1] = get_var(values[1][:2], values[1][3:]     )
               is_string(values[1])
     else:
          error(53,"Wrong type of operand atring:" + str(val))
     if typ2 == "string" or typ2 == "var":
          values.append(check_val(val[2], typ2))
          if typ2 == "var":
               var_check(values[2][:2], values[2][3:])
               values[2] = get_var(values[2][:2], values[2][3:]     )
               is_string(values[2])       
     else:
          error(53,"Wrong type of operand atring:" + str(val))

     var_check(values[0][:2], values[0][3:])

     if DEBUG:
          print("----values (of concat)-----")
          print(values)
          print("----------------------------------")
     
     if values[1] == None or values[2] == None:
          error(56, "Unset value")

     values[1] = values[1] + values[2]     
     set_value(frame_1, var_name_1, values[1]     )

def is_var(val):
     pass
"""
brief:    checks integer value
"""
def is_int(val):
     try: 
          int(val)
          return 
     except:
          if(val == None):
               error(56,"Variable is unset: " + str(val))
          error(53,"Wrong type of operand int: " + str(val))
"""
brief:    checks boolean value
"""
def is_bool(val):
     if val == None:
          error(56, "Bool is unset")
     if val != "true" and val != "false":
          error(53,"Wrong type of operand bool: " + str(val))
"""
brief:    checks string value
"""
def is_string(val):
     res = isinstance(val, str) 
     if res == False:
          if(val == None):
               error(56,"Variable is unset: " + str(val))
          error(53,"Wrong type of operand String: " + str(val))
     sequences = re.findall("\\\\\d\d\d",val)
     for seq in sequences:
          val = val.replace(seq,chr(int(seq.lstrip('\\'))))
     return str(val)
"""
brief:    checks type value
"""
def is_type(val):
     pass
"""
brief:    checks nil value
"""
def is_nil(val):
     if val != "nil":
          if(val == None):
               error(56,"Variable is unset: " + str(val))
          error(53,"Wrong type of operand nil: " + str(val))
"""
brief:    checks attribute type
"""
def check_atrib_type(type, atrib_type):
     if(type != atrib_type):
          error(53, "invalid attributed type")
"""
brief:    return value of the variable
"""
def get_var(frame,var_name):

     if local_frame is not None and frame == "LF" and var_name in local_frame:
          return local_frame[var_name]
     elif temp_frame is not None and frame == "TF"and var_name in temp_frame:
          return temp_frame[var_name]
     elif var_name in global_frame and frame == "GF" :
          return global_frame[var_name]
     else:
          error(54, "Variable doesn't exist")

"""
brief:    sets value to the variable
"""
def set_value(frame_name, var_name, val):
     if frame_name == "TF":
          temp_frame[var_name] = val
          if DEBUG_FRAME:
               print("")
               print("---- TF: after changing val to var-----")
               for item in temp_frame:
                    print(str(item) + " = " + str(temp_frame[item]))
               print("----------------------------------")
     elif frame_name == "LF":
          local_frame[var_name] = val
          if DEBUG_FRAME:
               print("")
               print("---- LF: after changing val to var-----")
               for item in local_frame:
                    print(str(item) + " = " + str(local_frame[item]))
               print("----------------------------------")
     elif frame_name == "GF":
          global_frame[var_name] = val
          if DEBUG_FRAME:
               print("")
               print("---- GF: after changing val to var-----")
               for item in global_frame:
                    print(str(item + " = " + str(global_frame[item])))
               print("---------------------------------")

"""
brief:    returns attribute type
"""
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

"""
brief:    check if frame and variable exist
"""
def var_check(frame_name, var_name):
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

"""
brief:    return type of the variable
"""
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

"""
brief:    checks integer value
"""
def check_val(value, type):
     if type == "var":
          if value== None:
               error(56, "not defined")
          is_var(value.text)
          check_atrib_type("var", value.attrib["type"])
          return value.text
     elif type == "int":
          if value== None:
               error(56, "not defined")
          is_int(value.text)
          check_atrib_type("int", value.attrib["type"])
          return int(value.text)
     elif type == "bool":
          if value== None:
               error(56, "not defined")
          is_bool(value.text)
          check_atrib_type("bool", value.attrib["type"])
          return value.text
     elif type == "string":
          if value.text == None:
               return ""
          val = is_string(value.text)
          check_atrib_type("string", value.attrib["type"])
          return val
     elif type == "nil":
          check_atrib_type("nil", value.attrib["type"])
          is_nil(value.text)
          return value.text
     elif type == "type":
          if value.text not in ['int', 'bool', 'string']:
               print("52: Argument type má nesprávnou hodnotu.")
               sys.exit(52)
     elif type == "label":
          return value.text

def def_var(frame_name, var_name     ):
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
               if (inst[0].text in labels):
                    correct_n_of_arg(inst,1)
                    error(52,"Label "+ inst[0].text+" is is redefined")
               labels[inst[0].text] = int(inst.attrib["order"])
     if DEBUG:
          print("----List of labels-----")
          print(labels)
          print("----------------------------------")

def correct_n_of_arg(inst, n_of_arg):
     if len(inst) != n_of_arg:
          error(32, "invalid_n_of_args", inst)

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
     if len(sys.argv) != 2 and len(sys.argv) != 3:
          error(10)
     global inp, src

     if sys.argv[1][:8] == "--input=":
          inp = sys.argv[1][8:]
          try:
               inp = open(inp, 'r+')
          except:
               error(11, "error while openning  --input file: " + sys.argv[1][8:])
     elif sys.argv[1][:9] == "--source=":
          src = sys.argv[1][9:] 
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
          error(10,"Second argument wrong")

     if len(sys.argv) == 3:
          if sys.argv[2][:8] == "--input=":
               inp = sys.argv[2][8:]
               try:
                    inp = open(inp, 'r+')
               except:
                    error(11, "error while openning  --input file: " + sys.argv[2][8:])
          elif sys.argv[2][:9] == "--source=":
               src = sys.argv[1][9:] 
          else:
               error(10,"third argument wrong")

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
          56: "Interpretation runtime error",
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
     global_frame = {}
     stack = []
     frame_s = []
     call_s = []
     labels = {}
     i_done = -1
     inp = ""
     src = ""
     main()