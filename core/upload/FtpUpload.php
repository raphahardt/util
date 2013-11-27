<?php

namespace Djck\upload;

use Djck\upload\Upload;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Core::uses('Upload', 'Djck\upload');

class FtpUpload extends Upload {
  
  private $ftp_config = null;
  private $ftp_conn;
  
  function __construct($config = null) {
    $this->ftp_config = array(
        'host' => '10.1.1.6',
        'port' => 21,
        'timeout' => 90,
        'login' => 'webmaster',
        'pwd' => 'xs23067',
        'passive' => false
    );
  }
  
  protected function _fixFolderName($folder) {
    return str_replace(DS, '/', parent::_fixFolderName($folder));
  }

  public function beginTransfer() {
    if ($this->ftp_conn) return false; // ja existe conexao
    
    $cfg = $this->ftp_config;
    
    // connect
    $this->ftp_conn = ftp_connect($cfg['host'], $cfg['port'], $cfg['timeout']);
    
    // login
    $login = ftp_login($this->ftp_conn, $cfg['login'], $cfg['pwd']);
    ftp_pasv($this->ftp_conn, (bool)$cfg['passive']);
    
    return $this->ftp_conn && $login;
  }
  
  function file_exists($path){
    return (ftp_size($this->ftp_conn, $path) > 0);
  }
  
  public function transfer() {
    if (!$this->ftp_conn) return false;
      
    ftp_chdir($this->ftp_conn, $this->dest_folder);
    return ftp_put($this->ftp_conn, $this->file['name'], $this->file['tmp_name'], FTP_BINARY);
    
  }
  
  public function endTransfer() {
    if ($this->ftp_conn) {
      ftp_close($this->ftp_conn);
    }
  }
  
}