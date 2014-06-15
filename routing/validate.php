<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace api\validation;

/**
 * Description of validation
 *
 * @author developer
 */
class validate {
    
    /**
     * Stores validation callbacks
     * @var array
     */
    private $validation_callback_map = array();
    
    /**
     * Stores errors
     * @var array
     */
    private $errors = array();
    
    /**
     * Sets action callbacks.
     * @param type $field_name
     * @param type $callback
     * @param type $params
     * @param type $object
     * @return boolean
     */
    public function set_validation_callback($field_name, $callback, $params = array(), $if_action = NULL, $object = FALSE) {
        if (!is_string($field_name)) {
            return FALSE;
        }
        if ($object === FALSE) {
            if (is_callable($callback)) {
                $callback_data = array(
                    "type"      => "function",
                    "callback"  => &$callback,
                    "params"    => &$params,
                    "if_action" => $if_action,
                );
                $this->validation_callback_map[$field_name] = &$callback_data;
                return TRUE;
            }
        } else if (is_object($object)) {
            if (method_exists($object, $callback)) {
                $callback_data = array(
                    "type"      => "method",
                    "object"    => &$object,
                    "method"    => &$callback,
                    "params"    => &$params,
                    "if_action" => $if_action,
                );
                $this->validation_callback_map[$field_name] = &$callback_data;
                return TRUE;
            }
        }
    }
    
    /**
     * Validate all fields.
     * @return bool
     */
    public function valid($action) {
        $errors = array();
        
        foreach ($this->validation_callback_map as $field => $callback_data) {
            if (is_string($callback_data["if_action"])) {
                if ($callback_data["if_action"] !== $action) {
                    return TRUE;
                }
            }
            if ($callback_data["type"] == "function") {
                $ret = $this->validate_function($field, $callback_data);
                if ($ret !== TRUE) {
                    if (isset($callback_data["error_msg"])) {
                        $errors[] = $callback_data["error_msg"];
                    } else if ($ret === FALSE) {
                        $errors[] = "Invalid " . $field . ".";
                    } else {
                        $errors[] = $ret;
                    }
                }
            } else if ($callback_data["type"] == "method") {
                $ret = $this->validate_method($field, $callback_data);
                if ($ret !== TRUE) {
                    if (isset($callback_data["error_msg"])) {
                        $errors[] = $callback_data["error_msg"];
                    } else if ($ret === FALSE) {
                        $errors[] = "Invalid " . $field . ".";
                    } else {
                        $errors[] = $ret;
                    }
                }
            } else {
                /* Descriptive... */
                $errors[] = "Error";
            }
        }
        
        $this->errors = $errors;
        return empty($errors);
    }
    
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Executes a validate function.
     * @param type $field
     * @param type $data
     * @return bool?
     */
    private function validate_function(&$field, &$data) {
        if (!isset($field["callback"])) {
            return FALSE;
        }
        if (is_callable($data["callback"])) {
            if (!isset($data["params"])) {
                $data["params"] = array();
            }
            return call_user_func_array($data["callback"], $data["params"]);
        } else {
            return FALSE;
        }
    }
    
     /**
     * Executes a validate method.
     * @param type $field
     * @param type $data
     * @return bool?
     */
    private function validate_method(&$field, &$data) {
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
     * Returns validation callback data
     * @param type $name
     * @return boolean/action callback data
     */
    public function get_validation_callback($name) {
        if (isset($this->validation_callback_map[$name])) {
            return $this->validation_callback_map[$name];
        }
        return FALSE;
    }
}
