<?php

/**
 * 
 * Patterns are of the form {alpha(2) in ('AC')}{numeric(4) [> number] [< number]}
 * 
 * 
 * 
 */
abstract class CityPay_Pattern_Token_Abstract_Condition_Component {
    
}

class CityPay_Pattern_Token_Compound_Condition_Marker extends CityPay_Pattern_Token_Abstract_Condition_Component {
    
}

class CityPay_Pattern_Token_Compound_Condition_Operator extends CityPay_Pattern_Token_Abstract_Condition_Component {
    public $operator;
    public function __construct($operator) {
        $this->operator = $operator;
    }
}

class CityPay_Pattern_Token_Compound_Condition extends CityPay_Pattern_Token_Abstract_Condition_Component {
    public $conditions;
}

class CityPay_Pattern_Token_Condition extends CityPay_Pattern_Token_Abstract_Condition_Component {
    public $operator, $value;
    public function __construct($operator, $value = null) {
        $this->operator = $operator;
        $this->value = $value;
    }
}

abstract class CityPay_Pattern_Abstract_Token {
    
}

class CityPay_Pattern_Token_CharacterString extends CityPay_Pattern_Abstract_Token {
    
}

class CityPay_Pattern_Token_Expression extends CityPay_Pattern_Abstract_Token {
    public $min_length, $max_length, $conditions;
    public function __construct($min_length, $max_length, $conditions) {
        $this->min_length = $min_length;
        $this->max_length = $max_length;
        $this->conditions = $conditions;
    }
    
    /*public function appendCondition($operator, $value) {      
        $this->conditions[] = new CityPay_Pattern_Token_Condition(
                $operator,
                $value
            );
    }*/
}

class CityPay_Pattern_Token_Alpha extends CityPay_Pattern_Token_Expression {
    public function __construct($arguments, $conditions) {
        parent::__construct($arguments[0], $arguments[0], $conditions);
    }
}

class CityPay_Pattern_Token_Numeric extends CityPay_Pattern_Token_Expression {
    public function __construct($arguments, $conditions) {
        parent::__construct($arguments[0], $arguments[0], $conditions);
    }
}

class CityPay_Pattern_Parser {
    
    const NO_ERROR = 0x00;
    const INPUT_EXHAUSTED = 0x01;
    const INVALID_INPUT = 0x02;
    
    //protected $c, $i, $i_max;
    //protected $pattern;
    
    private function _createNewPatternToken($type, $arguments = null, $conditions = null) {
        switch ($type) {
            case 'alpha':
                return new CityPay_Pattern_Token_Alpha(
                        $arguments,
                        $conditions
                    );
                
            case 'numeric':
                return new CityPay_Pattern_Token_Numeric(
                        $arguments,
                        $conditions
                    );
        }
        
        // TODO: decide whether we need to return an error code or
        // raise an exception.
        
        return null;
    }
    
    private function _parseOperand(&$pattern, &$i, &$i_max, &$operand, &$result, $delimiters = ',') {
        $_operand = '';
        $c = $pattern[$i];
        if ($c == '\'' || $c == '"') {
            // get string
            $_delimiter = $c;
            while (++$i < $i_max) {
                $c = $pattern[$i];
                if ($c == $_delimiter) {
                    $i++;
                    break;
                } elseif ($c == '\\') {
                    if (++$i < $i_max) {
                        $c = $pattern[$i];
                        if ($c == $_delimiter) {
                            $_operand .= $c;
                        } else {
                            $_operand .= $c;
                            // TODO: check desired semantics on recognizing
                            // TODO: an incorrectly escaped character
                        }
                    }
                } else {
                    $_operand .= $c;
                }
            }
        } elseif ($c >= '0' && $c <= '9') {
            // get numeric value
            $_operand .= $c;
            while (++$i < $i_max) {
                $c = $pattern[$i];
                if ($c >= "0" && $c <= "9") {
                    $_operand .= $c;
                } else if ($c == ' ' || $c == '\t'
                    || $c == '\r' || $c == '\t'
                    || strpos($delimiters, $c) !== false)  {
                    break;
                } else {
                    // TODO: check desired behaviour on finding an inappropriate character
                    // (ie. non-numeric, non-argument delimiting, non-arguments delimiting)
                    // in the input stream.
                }
            } 
        }
                
        $operand = $_operand;
        return self::NO_ERROR;
    }
    
    private function _parseOperands(&$pattern, &$i, &$i_max, &$operands, &$result, $delimiters_begin = '(', $delimiters_int = ',', $delimiters_end = ')')
    {
        if ($i >= $i_max) {
            return self::INPUT_EXHAUSTED;
        }
        
        $r = self::_purgeWhitespace($pattern, $i, $i_max);
        if ($r != self::NO_ERROR) {
            return $r;
        }

        $c = $pattern[$i];
        if (strpos($delimiters_begin, $c) === false) {
            return self::INVALID_INPUT;
        }
        
         
        
        if (++$i >= $i_max) {
            return self::INPUT_EXHAUSTED;
        }
        
        $r = self::_purgeWhitespace($pattern, $i, $i_max);
        if ($r != self::NO_ERROR) {
            return $r;
        }
        
        
        
        if ($i >= $i_max) {
            return self::INPUT_EXHAUSTED;
        }
        
        $c = $pattern[$i];
        if ($c == ')') {
            $arguments = array();
            return self::NO_ERROR;
        }
        
        $_operands = array();
        while ($i < $i_max) {
            // skip whitespace            
            $r = self::_parseOperand($pattern, $i, $i_max, $operand, $result, ($delimiters_int.$delimiters_end));
            if ($r == self::NO_ERROR) {
                $_operands[] = $operand;
            } else {
                return $r;
            }
            
            $r = self::_purgeWhitespace($pattern, $i, $i_max);
            if ($r != self::NO_ERROR) {
                return $r;
            }
            
            $c = $pattern[$i];
            if (strpos($delimiters_int, $c) !== false) {
                // TODO: check desired behaviour on finding an inappropriate delimiter
                // for an argument - return error code, or raise an exception.
                $r = self::_purgeWhitespace($pattern, ++$i, $i_max);
                if ($r != self::NO_ERROR) {
                    return $r;
                }
                        
                $c = $pattern[$i];
            } elseif (strpos($delimiters_end, $c) !== false) {
                $i++;
                if (sizeof($_operands) > 0x00) {
                    $operands = $_operands;
                }
                return self::NO_ERROR;
            } else {
                return self::INVALID_INPUT;
            }
        }
       
        return self::INPUT_EXHAUSTED;
    }
            
    private function _operatorHasRightHandOperand($operator) {
        static $operatorsWithRightHandOperand = array('>', '>=', '<', '<=', '==', '!=', 'in');
        return (in_array($operator, $operatorsWithRightHandOperand));
    }
    
    private function _parseBooleanOperatorRightHandOperand(&$pattern, &$i, &$i_max, &$operand, &$result) {
        return self::_parseOperand($pattern, $i, $i_max, $operand, $result, '&|}');
    }
    
    private function _parseInOperatorRightHandOperand(&$pattern, &$i, &$i_max, &$operand, &$result) {
        return self::_parseOperands($pattern, $i, $i_max, $operand, $result, '(', ',', ')');
    }
    
    private function _parsePatternTokenExpressionArgument(&$pattern, &$i, &$i_max, &$argument, &$result) {
        $_argument = '';
        $c = $pattern[$i];
        if ($c == '\'' || $c == '"') {
            // get string
            $delimiter = $c;
            while (++$i < $i_max) {
                $c = $pattern[$i];
                if ($c == $delimiter) {
                    $i++;
                    break;
                } elseif ($c == '\\') {
                    if (++$i < $i_max) {
                        $c = $pattern[$i];
                        if ($c == $delimiter) {
                            $_argument .= $c;
                        } else {
                            $_argument .= $c;
                            // TODO: check desired semantics on recognizing
                            // TODO: an incorrectly escaped character
                        }
                    }
                } else {
                    $_argument .= $c;
                }
            }
        } elseif ($c >= '0' && $c <= '9') {
            // get numeric value
            $_argument .= $c;
            while (++$i < $i_max) {
                $c = $pattern[$i];
                if ($c >= "0" && $c <= "9") {
                    $_argument .= $c;
                } else if ($c == ' ' || $c == '\t'
                    || $c == '\r' || $c == '\t'
                    || $c == ',' || $c == ')') {
                    break;
                } else {
                    // TODO: check desired behaviour on finding an inappropriate character
                    // (ie. non-numeric, non-argument delimiting, non-arguments delimiting)
                    // in the input stream.
                }
            } 
        }
                
        $argument = $_argument;
        return self::NO_ERROR;
    }
            
    private function _parsePatternTokenExpressionArguments(&$pattern, &$i, &$i_max, &$arguments, &$result) {
        $c = $pattern[$i];
        if ($c == '(') {
            $i++;
        } else {
            return self::INVALID_INPUT;
        }
        
        $r = self::_purgeWhitespace($pattern, $i, $i_max);
        if ($r != self::NO_ERROR) {
            return $r;
        }
        
        if ($i >= $i_max) {
            return self::INPUT_EXHAUSTED;
        }
        
        $c = $pattern[$i];
        if ($c == ')') {
            $arguments = array();
            return self::NO_ERROR;
        }
        
        $_arguments = array();
        while ($i < $i_max) {
            // skip whitespace            
            $c = $pattern[$i];
            $r = self::_parsePatternTokenExpressionArgument($pattern, $i, $i_max, $argument, $result);
            if ($r == self::NO_ERROR) {
                $_arguments[] = $argument;
            } else {
                return $r;
            }
            
            $r = self::_purgeWhitespace($pattern, $i, $i_max);
            if ($r != self::NO_ERROR) {
                return $r;
            }
            
            $c = $pattern[$i];
            if ($c == ',') {
                // TODO: check desired behaviour on finding an inappropriate delimiter
                // for an argument - return error code, or raise an exception.
                $i++;
            } else if ($c == ')') {
                if (sizeof($_arguments) > 0x00) {
                    $arguments = $_arguments;
                }
                return self::NO_ERROR;
            } else {
                return self::INVALID_INPUT;
            }
            
            $r = self::_purgeWhitespace($pattern, $i, $i_max);
            if ($r != self::NO_ERROR) {
                return $r;
            }
        }
       
        return self::INPUT_EXHAUSTED;
    }
    
    private function _parsePatternTokenExpressionCondition(&$pattern, &$i, &$i_max, &$condition, &$result) {
        $c = $pattern[$i];
        if ($c == '>' || $c == '<' || $c == '=' || $c == '!') {
            $_operator = $c;
            if (++$i >= $i_max) {
                return self::INPUT_EXHAUSTED;
            }
            
            $c = $pattern[$i];
            if ($c == '=') {
                $_operator .= $c;
            } else {
                // do nothing: the next part is either whitespace
                // or a value (error will turn up there).
            }
        } elseif ($c == 'i') {
            if (++$i >= $i_max) {
                return self::INPUT_EXHAUSTED;
            }

            $c = $pattern[$i];
            if ($c == 'n') {
                $_operator = 'in';
            }
        } else {
            return self::INVALID_INPUT;
        }
        
        if (++$i >= $i_max) {
            return self::INPUT_EXHAUSTED;
        }
        
        $r = self::_purgeWhitespace($pattern, $i, $i_max);
        if ($r != self::NO_ERROR) {
            return $r;
        }
        
        if (self::_operatorHasRightHandOperand($_operator)) {
            if ($_operator == 'in') {
                $r = self::_parseInOperatorRightHandOperand($pattern, $i, $i_max, $_operand, $result);
            } else {
                $r = self::_parseBooleanOperatorRightHandOperand($pattern, $i, $i_max, $_operand, $result);
            }
            
            if ($r != self::NO_ERROR) {
                return $r;
            }
            
            $condition = new CityPay_Pattern_Token_Condition($_operator, $_operand);
        } else {
            $condition = new CityPay_Pattern_Token_Condition($_operator);
        }
        
        return self::NO_ERROR;
    }
    
    private function _parsePatternTokenExpressionConditions(&$pattern, &$i, &$i_max, &$conditions, &$result) {
        $conditions = array();        
        while ($i < $i_max) {
            // detect operator.
            $c = $pattern[$i];
            if ($c == '(') {
                // compound condition
                $conditions[] = new CityPay_Pattern_Token_Compound_Condition_Marker();
            } elseif ($c == ')') {
                // TODO: create compound condition according to rules of precedence,
                // whatever they are?
            } elseif ($c == '>' || $c == '<' || $c == '=' || $c == '!' || $c == 'i') {
               $r = self::_parsePatternTokenExpressionCondition($pattern, $i, $i_max, $condition, $result);
               if ($r != self::NO_ERROR) {
                   return $r;
               }
               
               $conditions[] = $condition;
            } elseif ($c == '}') {
                return self::NO_ERROR;
            } else {
                $i++; 
            }
                      
            $r = self::_purgeWhitespace($pattern, $i, $i_max);
            if ($r != self::NO_ERROR) {
                return $r;
            }
        }
        
        return self::INPUT_EXHAUSTED;
    }
      
    private function _parsePatternTokenExpressionType(&$pattern, &$i, &$i_max, &$type, &$result) {
        $_type  = '';
        while ($i < $i_max) {
            $c = $pattern[$i];
            if (($c >= 'A' && $c <= 'Z') || ($c >= 'a' && $c <= 'z')) {
                $_type .= $c;
                $i++;
            } elseif ($c == '(' || $c == '}') {
                $type = $_type;
                return self::NO_ERROR;
            } else {
                return self::INVALID_INPUT;
            }
        }
        
        return self::INPUT_EXHAUSTED;
    }
    
    private function _parsePatternTokenExpression(&$pattern, &$i, &$i_max, &$expression, &$result, $delimiter_begin = '{', $delimiter_end = '}') {

        $type = '';
        $arguments = null;
        $conditions = null;
   
        $r = self::_purgeWhitespace($pattern, $i, $i_max);
        if ($r != self::NO_ERROR) {
            return $r;
        }
    
        $c = $pattern[$i];
        if (strpos($delimiter_begin, $c) === false) {
            return self::INVALID_INPUT;
        }

        if (++$i >= $i_max) {
            return self::INPUT_EXHAUSTED; // TODO: error code or raise exception, bad token
        }

        $r = self::_purgeWhitespace($pattern, $i, $i_max);
        if ($r != self::NO_ERROR) {
            return $r;
        }

        // get token expression type
        $r = self::_parsePatternTokenExpressionType($pattern, $i, $i_max, $type, $result);
        if ($r != self::NO_ERROR) {
            return $r;
        }

        $c = $pattern[$i];
        if ($c == '(') { 
            $r = self::_parsePatternTokenExpressionArguments($pattern, $i, $i_max, $arguments, $result);
            if ($r != self::NO_ERROR) {
                return $r;
            }
        }
        
        if (++$i >= $i_max) {
            return self::INPUT_EXHAUSTED;
        }
        
        $r = self::_purgeWhitespace($pattern, $i, $i_max);
        if ($r != self::NO_ERROR) {
            return $r;
        }
        
        $c = $pattern[$i];
        if (strpos($delimiter_end, $c) !== false) {
            $expression = self::_createNewPatternToken($type, $arguments);
            return true;
        }
        
        $r = self::_parsePatternTokenExpressionConditions($pattern, $i, $i_max, $conditions, $result);
        if ($r != self::NO_ERROR) {
            return $r;
        }
        
        $c = $pattern[$i];
        if (strpos($delimiter_end, $c) !== false) {
            $i++;
            $expression = self::_createNewPatternToken($type, $arguments, $conditions);
            return self::NO_ERROR;
        }
        
        return self::INVALID_INPUT;
    }
    
    
    private function _purgeWhitespace(&$pattern, &$i, &$i_max)
    {
        while ($i < $i_max) {
            $c = $pattern[$i];
            if ($c == ' ' || $c == '\t' || $c == '\r' || $c == '\n') {
                $i++;
            } else {
                return self::NO_ERROR;
            }
        }
        
        return self::INPUT_EXHAUSTED;
    }
    
    
    // Test probes
    public function _parseOperand_probe(&$pattern, &$i, &$i_max, &$operand, &$result, $delimiters = ',') {
        return self::_parseOperand($pattern, $i, $i_max, $operand, $result, $delimiters);
    }
    
    public function _parseOperands_probe(&$pattern, &$i, &$i_max, &$operand, &$result, $delimiters_begin = '(', $delimiters_int = ',', $delimiters_end = ')') {
        return self::_parseOperands($pattern, $i, $i_max, $operand, $result, $delimiters_begin, $delimiters_int, $delimiters_end);
    }
    
    public function _parsePatternTokenExpressionType_probe(&$pattern, &$i, &$i_max, &$type, &$result) {
        return self::_parsePatternTokenExpressionType($pattern, $i, $i_max, $type, $result);
    }
    
    public function _parsePatternTokenExpressionArgument_probe(&$pattern, &$i, &$i_max, &$argument, &$result) {
        return self::_parsePatternTokenExpressionArgument($pattern, $i, $i_max, $argument, $result);
    }
    
    public function _parsePatternTokenExpressionArguments_probe(&$pattern, &$i, &$i_max, &$arguments, &$result) {
        return self::_parsePatternTokenExpressionArguments($pattern, $i, $i_max, $arguments, $result);
    }
    
    public function _parsePatternTokenExpressionCondition_probe(&$pattern, &$i, &$i_max, &$condition, &$result) {
        return self::_parsePatternTokenExpressionCondition($pattern, $i, $i_max, $condition, $result);
    }
    
    public function _parsePatternTokenExpressionConditions_probe(&$pattern, &$i, &$i_max, &$conditions, &$result) {
        return self::_parsePatternTokenExpressionConditions($pattern, $i, $i_max, $conditions, $result);
    }
    
    public function _parsePatternTokenExpression_probe(&$pattern, &$i, &$i_max, &$expression, &$result, $delimiter_begin = '{', $delimiter_end = '}') {
        return self::_parsePatternTokenExpression($pattern, $i, $i_max, $expression, $result, $delimiter_begin, $delimiter_end);
    }
}

class CityPay_Pattern {
    
    private $patterns;
    
    public function __construct() {
        $this->patterns = array();
    }
    
 
    
    private function _parse($pattern) {
        $_token = null;
        $_scratch = '';
        $_pattern = trim($pattern);
        $_tokens = array();
        $i = 0; $i_max = strlen($_pattern);
        while ($i < $i_max) {
            $c = $_pattern[$i];
            if ($c == '{') {
                if (!empty($_scratch)) {
                    $_tokens[] = new CityPay_Pattern_Token_CharacterString($_scratch);
                    $_scratch = '';
                }
                $pattern[] = _parsePatternTokenExpression($_pattern, $i, $i_max);
            } else if ($c == '\\') {
                // process escapes
                if (++$i < $i_max) {
                    $c = $_pattern[$i];
                    $_scratch .= $c;
                } else {
                    // error
                    break;
                }
            } else {
                // append to a character class
                $_scratch .= $c;
            }
            $i++;
        }
    }
    
    private function _purgeWhitespace(&$_pattern, &$i, &$i_max) {
        while ($i < $i_max) {
            $c = $_pattern[$i++];
            if ($c != ' ' || $c != '\t' || $c != '\r' || $c != '\n') {
                break;
            }
        }
    }
    
    public static function match($string, $pattern = null) {
        
        
        
        
        
        
    }
}
