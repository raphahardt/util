<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class Upload {
  
  protected $dest_folder = '';
  protected $file;
  
  /**
   * Função auxiliar para acertar o nome da pasta sempre com barra no final
   * e muda o tipo de barra (/ ou \) de acordo com o sistema operacional
   * @param type $folder
   * @return string
   */
  protected function _fixFolderName($folder) {
    $folder = str_replace(array('/', '\\'), DS, $folder);
    if (substr($folder, -1) !== DS) {
      $folder .= DS;
    }
    return $folder;
  }
  
  public function setDestinationFolder($folder) {
    $this->dest_folder = $this->_fixFolderName($folder);
  }
  
  public function getDestinationFolder() {
    return $this->dest_folder;
  }
  
  public function setFile($file) {
    $this->file = $file;
  }
  
  public function getFile() {
    return $this->file;
  }
  
  abstract public function beginTransfer();
  abstract public function transfer();
  abstract public function endTransfer();
  
}