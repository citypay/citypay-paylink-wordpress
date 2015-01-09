<?php

require('../../../wordpress/wp-content/plugins/citypay/includes/class-citypay-pattern.php');

class CityPay_Pattern_Parser_Test extends PHPUnit_Framework_TestCase {
    
    public function test_parseOperand()
    {
        $arguments = array(
                strval('123') => array('r' => 0x00, 'argument' => 123),
                '\'abc\'' => array('r' => 0x00, 'argument' => 'abc'),
                '"abc"' => array('r' => 0x00, 'argument' => 'abc'),
                "\"def\"" => array('r' => 0x00, 'argument' => 'def'),
                "'def'" => array('r' => 0x00, 'argument' => 'def'),            
            );
        
        $result = 0x00; $argument = null; 
        $parser = new CityPay_Pattern_Parser();    
        foreach ($arguments as $pattern => $results) {
            $pattern = (string) $pattern;
            $i = 0x00; $i_max = strlen($pattern);
            $r = $parser->_parsePatternTokenExpressionArgument_probe($pattern, $i, $i_max, $argument, $result, ',');
            $this->assertEquals($results['argument'], $argument);
            $this->assertEquals($results['r'], $r);
        }
        
        return;
    }
    
    public function test_parseOperands()
    {           
        $arrayArguments = array(
                '()' => array('r' => 0x00, 'arguments' => array()),
                '(123)' => array('r' => 0x00, 'arguments' => array(123)),
                '(\'abc\')' => array('r' => 0x00, 'arguments' => array('abc')),
                '(123, "abc", "def", "\'ghi\'", \'"jkl"\', \'mno\')'
                    => array('r' => 0x00, 'arguments' => array(123, 'abc', 'def', '\'ghi\'', '"jkl"', 'mno'))
            );
                
        $result = 0x00; $arguments = null;
        $parser = new CityPay_Pattern_Parser();
        foreach ($arrayArguments as $pattern => $results) {
            $pattern = (string) $pattern;
            $i = 0x00; $i_max = strlen($pattern);
            $r = $parser->_parsePatternTokenExpressionArguments_probe($pattern, $i, $i_max, $arguments, $result, '(', ',', ')');          
            $this->assertEquals($results['arguments'], $arguments);
            $this->assertEquals($results['r'], $r);
        }     
    }
    
    public function test_parsePatternTokenExpressionType()
    {
        $patterns = array(
                'alpha(' => array('r' => 0x00, 'type' => 'alpha'),
                'numeric(' => array('r' => 0x00, 'type' => 'numeric')
            );
    
        $result = 0x00; $type = '';
        $parser = new CityPay_Pattern_Parser();
        foreach ($patterns as $pattern => $results) {
            $i = 0x00; $i_max = strlen($pattern);
            $r = $parser->_parsePatternTokenExpressionType_probe($pattern, $i, $i_max, $type, $result);
            $this->assertEquals($results['type'], $type);
            $this->assertEquals($results['r'], $r);
        }
        
        return;
    }
    
    public function test_parsePatternTokenExpressionArgument()
    {
        $arguments = array(
                strval('123') => array('r' => 0x00, 'argument' => 123),
                '\'abc\'' => array('r' => 0x00, 'argument' => 'abc'),
                '"abc"' => array('r' => 0x00, 'argument' => 'abc'),
                "\"def\"" => array('r' => 0x00, 'argument' => 'def'),
                "'def'" => array('r' => 0x00, 'argument' => 'def'),            
            );
        
        $result = 0x00; $argument = null; 
        $parser = new CityPay_Pattern_Parser();    
        foreach ($arguments as $pattern => $results) {
            $pattern = (string) $pattern;
            $i = 0x00; $i_max = strlen($pattern);
            $r = $parser->_parsePatternTokenExpressionArgument_probe($pattern, $i, $i_max, $argument, $result);
            $this->assertEquals($results['argument'], $argument);
            $this->assertEquals($results['r'], $r);
        }
        
        return;
    }
    
    public function test_parsePatternTokenExpressionArguments()
    {           
        $arrayArguments = array(
                '()' => array('r' => 0x00, 'arguments' => array()),
                '(123)' => array('r' => 0x00, 'arguments' => array(123)),
                '(\'abc\')' => array('r' => 0x00, 'arguments' => array('abc')),
                '(123, "abc", "def", "\'ghi\'", \'"jkl"\', \'mno\')'
                    => array('r' => 0x00, 'arguments' => array(123, 'abc', 'def', '\'ghi\'', '"jkl"', 'mno'))
            );
                
        $result = 0x00; $arguments = null;
        $parser = new CityPay_Pattern_Parser();
        foreach ($arrayArguments as $pattern => $results) {
            $pattern = (string) $pattern;
            $i = 0x00; $i_max = strlen($pattern);
            $r = $parser->_parsePatternTokenExpressionArguments_probe($pattern, $i, $i_max, $arguments, $result);          
            $this->assertEquals($results['arguments'], $arguments);
            $this->assertEquals($results['r'], $r);
        }     
    }
    
    /*public function test_parsePatternTokenExpressionCondition()
    {
        $arrayConditions = array(
                '> \'abc\'' => array('r' => 0x00, 'condition' => new CityPay_Pattern_Token_Condition('>', 'abc')),
                '> 123' => array('r' => 0x00, 'condition' => new CityPay_Pattern_Token_Condition('>', 123)),
                '>= \'abc\'' => array('r' => 0x00, 'condition' => new CityPay_Pattern_Token_Condition('>=', 'abc')),
                '>= 123' => array('r' => 0x00, 'condition' => new CityPay_Pattern_Token_Condition('>=', 123)),
                'in (\'abc\', \'def\', \'ghi\')' => array('r' => 0x00, 'condition' => new CityPay_Pattern_Token_Condition('in', array('abc', 'def', 'ghi'))),
                'in (\'abc\', \'def\', \'ghi\')}' => array('r' => 0x00, 'condition' => new CityPay_Pattern_Token_Condition('in', array('abc', 'def', 'ghi')))
            );
        
        $result = 0x00; $condition = null;
        $parser = new CityPay_Pattern_Parser();
        foreach ($arrayConditions as $pattern => $results) {
            $pattern = (string) $pattern;
            $i = 0x00; $i_max = strlen($pattern);
            $r = $parser->_parsePatternTokenExpressionCondition_probe($pattern, $i, $i_max, $condition, $result);
            $this->assertEquals($results['r'], $r);
            $this->assertEquals($results['condition'], $condition);
        }
    }*/
    
    public function test_parsePatternTokenExpression()
    {
        $arrayExpressions = array(
                '{alpha(3) in (\'ABC\', \'DEF\', \'GHI\')}'
                    => array(
                            'r' => 0x00,
                            'expression' => new CityPay_Pattern_Token_Alpha(
                                    array('3'),
                                    array(
                                            new CityPay_Pattern_Token_Condition(
                                                    'in',
                                                    array('ABC', 'DEF', 'GHI')
                                                )
                                        )
                                )
                        )
            );
        
        $result = 0x00; $expression = null;
        $parser = new CityPay_Pattern_Parser();
        foreach ($arrayExpressions as $pattern => $results) {
            $pattern = (string) $pattern;
            $i = 0x00; $i_max = strlen($pattern);
            $r = $parser->_parsePatternTokenExpression_probe($pattern, $i, $i_max, $expression, $result);
            $this->assertEquals($results['r'], $r);
            $this->assertEquals($results['expression'], $expression);
        }
    }
}

