<?php

class cp_paylink_config_stack
{
    private $stack;
    private $ptr;
    
    function __construct() {
        $this->stack = array();
        $this->ptr = 0;
        array_push($this->stack, array());
    }
    
    public function peek() {
        return $this->stack[$this->ptr];
    }
    
    public function pop() {
        $this->ptr--;
        return array_pop($this->stack);
    }
    
    public function push($config) {
        $this->ptr++;
        return array_push($this->stack, $config);
    }
    
    public function push_new() {
        $this->ptr++;
        return array_push($this->stack);
    }
    
    public function set($name, &$value) {
        $this->stack[$this->ptr][$name] = $value;
    }
    
    public function &get($name) {
        return $this->stack[$this->ptr][$name];
    }
}
