<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace api\routing;

/**
 * Description of actions
 *
 * @author developer
 */

class actions {

    /**
     * Stores output from $this->run();
     * @var mixed
     */
    private $output = array();
    
    /**
     * Stores action callbacks.
     * @name $actions_callback_map
     * @var array
     */
    private $actions_callback_map = array();

    public function __construct() {
        $this->__defaults();
    }

    private function __defaults() {
        $defaults = array(
            /* Default callbacks here */
            "GET" => array(
                "type"  => "function",
                "callback"  => "my_callback"
            ),
        );
        $this->__parse_defaults($defaults);
    }

    private function __parse_defaults(&$defaults) {
        foreach ($defaults as $key => $value) {
            if (isset($value["type"])) {
                if ($value["type"] == "function") {
                    if (isset($value["callback"])) {
                        if (!is_array($value["params"])) {
                            $value["params"] = array();
                        }
                        $this->set_action_callback($key, $value["callback"], $value["params"]);
                    }
                } else if ($value["type"] == "method") {
                    if (isset($value["method"]) && isset($value["object"])) {
                        if (!is_array($value["params"])) {
                            $value["params"] = array();
                        }
                        $this->set_action_callback($key, $value["method"], $value["params"], $value["object"]);
                    }
                }
            }
        }
    }
    
    /**
     * Run an action
     * @param string $action
     * @return boolean
     */
    public function run($action_name) {
        if (!$this->action_exists($action_name)) {
            $this->output = "Couldn't find action: " . $action_name . ".";
            return FALSE;
        }
        
        $action = $this->actions_callback_map[$action_name];
        if (is_string($action["type"])) {
            if ($action["type"] == "function") {
                $out = $this->run_function($action_name, $action);
                $this->add_to_output($out);
                if ($out === FALSE) {
                    return $out;
                }
            } else if ($action["type"] == "method") {
                $out = $this->run_method($action_name, $action);
                $this->add_to_output($out);
                if ($out === FALSE) {
                    return $out;
                }
            } else {
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    public function get_output() {
        return $this->output;
    }

    /**
     * Add data to $this->output.
     * @param mixed $data
     * @return void
     */
    private function add_to_output($data) {
        if ($data === FALSE) {
            $this->output[] = 0;
        } else {
            $this->output[] = $data;
        }
    }
    
    /**
     * Run a callback function.
     * @param string $name
     * @param array $data
     * @return boolean
     */
    private function run_function(&$name, &$data) {
        if (!isset($data["callback"])) {
            return FALSE;
        }
        if (is_callable($data["callback"])) {
            if (!is_array($data["params"])) {
                $data["params"] = array();
            }
            return call_user_func_array($data["callback"], $data["params"]);
        } else {
            return FALSE;
        }
    }
    
    /**
     * Run a method action.
     * @param string $field
     * @param array $data
     * @return boolean
     */
    private function run_method(&$name, &$data) {
        if (!isset($field["object"]) || !isset($field["method"])) {
            return FALSE;
        }
        if (method_exists($field["object"], $field["method"])) {
            if (!isset($data["params"])) {
                $data["params"] = array();
            }
            return call_user_method_array($data["callback"], $data["params"]);
        } else {
            return FALSE;
        }
    }

    /**
     * Sets action callbacks.
     * @param type $name
     * @param type $callback
     * @param type $params
     * @param type $object
     * @return boolean
     */
    public function set_action_callback($name, $callback, $params = array(), $if_action = NULL, $object = FALSE) {
        if (!is_string($name)) {
            return FALSE;
        }
        if ($object === FALSE) {
            if (is_callable($callback)) {
                $callback_data = array(
                    "type" => "function",
                    "callback" => &$callback,
                    "params" => &$params,
                );
                $this->actions_callback_map[$name] = &$callback_data;
                return TRUE;
            }
        } else if (is_object($object)) {
            if (method_exists($object, $callback)) {
                $callback_data = array(
                    "type" => "method",
                    "object" => &$object,
                    "method" => &$callback,
                    "params" => &$params
                );
                $this->actions_callback_map[$name] = &$callback_data;
                return TRUE;
            }
        }
    }

    /**
     * Check if action exists.
     * @param stirng $name
     * @return bool
     */
    public function action_exists($name) {
        return isset($this->actions_callback_map[$name]);
    }

    /**
     * Returns action callback data
     * @param type $name
     * @return boolean/action callback data
     */
    public function get_action_callback($name) {
        if (isset($this->actions_callback_map[$name])) {
            return $this->actions_callback_map[$name];
        }
        return FALSE;
    }

}
