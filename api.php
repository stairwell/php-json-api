<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace api;

include_once "./routing/actions.php";
include_once "./routing/validate.php";

/**
 * Description of api
 *
 * @author developer
 */
class api {

    /**
     * Stores default field_names.
     * @var array
     */
    private $default_field_names = array(
        "Error" => "error",
        "ErrorMessages" => "error_messages",
        "Timestamp" => "timestamp",
    );

    /**
     * What fields are required
     * @var array
     */
    private $required_fields = array(
        "api_key" => array(
            "location" => "GET",
            "error_msg" => "API Key required",
        ),
    );

    /**
     * A soon to be json object.
     * @var array
     */
    private $json_object = array();

    /**
     * Action from url bar.
     * @var string
     */
    private $action;

    /**
     * Validation class
     * @var validate
     */
    private $validate;

    /**
     * Actions callback class.
     * @var \api\routing\actions
     */
    private $actions;

    /**
     * 'api' Class constructor.
     * @return null
     */
    public function __construct() {
        $this->action = api::find_action();
        $this->actions = new \api\routing\actions();

        $this->validate = new \api\validation\validate();
    }

    public function execute() {
        if (empty($this->action)) {
            $this->add_errors("Malformed Query");
            $this->print_query();
            return;
        }

        if (!$this->actions->action_exists($this->action)) {
            $this->add_errors("Invalid Action");
            $this->print_query();
            return;
        }

        if (!$this->fields_exist()) {
            $this->print_query();
            return;
        }

        if (!$this->validate->valid($this->action)) {
            $this->add_errors($this->validate->get_errors());
        }

        if ($this->actions->run($this->action)) {
            $output = $this->actions->get_output();
            if (is_array($output)) {
                foreach ($output as $value_tmp) {
                    foreach ($value_tmp as $key => $value) {
                        $this->json_object[$key] = $value;
                    }
                }
            } else {
                $this->json_object[] = $output;
            }
        } else {
            $this->add_errors($this->actions->get_output());
        }
        $this->print_query();
    }

    /**
     * Ensure that all required fields exist.
     * @return boolean
     */
    private function fields_exist() {
        $errors = array();
        foreach ($this->required_fields as $field => $field_data) {
            if (!isset($field_data["location"])) {
                $ret = FALSE;
            } else if ($field_data["location"] == "GET") {
                $ret = isset($_GET[$field]);
            } else if ($field_data["location"] == "POST") {
                $ret = isset($_POST[$field]);
            } else if ($field_data["location"] == "BOTH") {
                $ret = isset($_GET[$field]) || isset($_POST[$field]);
            } else {
                $ret = FALSE;
            }
            if ($ret === FALSE) {
                if (isset($field_data["error_msg"])) {
                    $errors[] = $field_data["error_msg"];
                } else {
                    $errors[] = "Required parameter: " + $field + ".";
                }
            }
        }

        if (empty($errors)) {
            return TRUE;
        } else {
            $this->add_errors($errors);
            return FALSE;
        }
    }

    /**
     * Add errors to $this->json_object.
     * @param void
     */
    private function add_errors($errors = "Malformed Query") {
        $this->json_object[$this->default_field_names["Error"]] = TRUE;
        if (is_array($errors)) {
            if (isset($this->json_object[$this->default_field_names["ErrorMessages"]])) {
                array_merge($this->json_object[$this->default_field_names["ErrorMessages"]], $errors);
            } else {
                $this->json_object[$this->default_field_names["ErrorMessages"]] = $errors;
            }
        } else if (is_string($errors)) {
            if (!isset($this->json_object[$this->default_field_names["ErrorMessages"]])) {
                $this->json_object[$this->default_field_names["ErrorMessages"]] = array();
            }
            $this->json_object[$this->default_field_names["ErrorMessages"]][] = $errors;
        }
    }

    /**
     * Adds default fields to $this->json_object.
     * @return void
     */
    private function add_default_fields() {
        $this->json_object[$this->default_field_names["Timestamp"]] = date("Y-m-d H:i:s");
    }

    /**
     * Prints json object.
     * @return void
     */
    private function print_query($add_default_fields = TRUE) {
        if ($add_default_fields === TRUE) {
            $this->add_default_fields();
        }
        echo "<pre>" . json_encode($this->json_object, JSON_PRETTY_PRINT) . "</pre>";
    }

    /**
     * Find action.
     * @return string
     */
    private static function find_action() {
        $array = explode("/", trim(substr($_SERVER["REQUEST_URI"], strpos($_SERVER["REQUEST_URI"], $_SERVER["SCRIPT_NAME"]) + strlen($_SERVER["SCRIPT_NAME"])), "/"));
        if (strpos($array[0], "?") !== FALSE) {
            $array[0] = substr($array[0], 0, strpos($array[0], "?"));
        }
        return $array[0];
    }

}
