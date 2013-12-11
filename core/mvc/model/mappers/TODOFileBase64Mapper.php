<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

//Core::depends('FileMapper');

/**
 * Description of FileBase64Mapper
 *
 * @author usuario
 */
/*class FileBase64Mapper extends FileMapper {
  
  protected function _formatInput($input) {
    $input = unserialize(base64_decode($input, true));
    
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
    return base64_encode(serialize($output));
  }
  
}*/