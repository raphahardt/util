<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

//Core::depends('FileMapper');

/**
 * Description of JsonMapper
 *
 * @author usuario
 */
/*class JsonMapper extends FileMapper {

  protected function _formatInput($input) {
    $input = json_decode($input, true);
    
    $this->clearResult();
    foreach ($input as $data) {
      $this->push($data);
    }
    return true;
  }
  
  protected function _formatOutput() {
    $output = array();
    foreach ($this->result as $r) {
      $output[] = $r['data'];
    }
    return json($output);
  }
  
}*/