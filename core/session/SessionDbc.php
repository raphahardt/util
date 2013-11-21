<?php

Core::uses('SessionCommon', 'core/session');

Core::depends('Dbc');
Core::uses('Request', 'core/network'); // para saber o ip do visitante

class SessionDbc extends SessionCommon {
  
  public $table_name = 'core_session';
  
  public function open($save_path, $session_name) {
    return true;
  }

  public function close() {
    return true;
  }

  public function read($sid) {
    
    $string = '';
    
    $dbc = Dbc::getInstance();
    $dbc->prepare('select * from '.$this->table_name.' where sid = ?');
    $dbc->bind_param(0, $sid);
    if ($dbc->execute()) {
      $row = $dbc->fetch_assoc();
      
      $string = (string)$row['sessao'];
    }
    $dbc->free();
    
    return $string;
  }

  public function write($sid, $data) {
    
    $req = new Request();
    
    $ip = $req->clientIp();
    $timestamp = time();
    //$data = self::_encryptData($data, SESSION_NAME);
    
    // pega o id do usuario logado
    $user = $_SESSION[ SESSION_USER_NAME ];
    $uid = (is_object($user) && $user->id) ? $user->id : 0;
    
    $dbc = Dbc::getInstance();
    $dbc->prepare('insert into '.$this->table_name.' (id,sid,ip,timestamp,sessao) values (?,?,?,?,?) on duplicate key update id=?, timestamp=?,sessao=?');
    // insert
    $dbc->bind_param(0,$uid);
    $dbc->bind_param(1,$sid);
    $dbc->bind_param(2,$ip);
    $dbc->bind_param(3,$timestamp);
    $dbc->bind_param(4,$data);
    // update
    $dbc->bind_param(5,$uid);
    $dbc->bind_param(6,$timestamp);
    $dbc->bind_param(7,$data);
    $success = $dbc->execute();

    $dbc->free();
    
    return $success;
    
  }

  public function destroy($sid) {
    
    $dbc = Dbc::getInstance();
    $dbc->prepare('delete from '.$this->table_name.' where sid = ?');
    $dbc->bind_param(0,$sid);
    $success = $dbc->execute();

    $dbc->free();
    
    return $success || parent::destroy($sid);
    
  }

  public function gc($lifetime) {
    
    $time = time() - $lifetime;
    
    $dbc = Dbc::getInstance();
    $dbc->prepare('delete from '.$this->table_name.' where timestamp < ?');
    $dbc->bind_param(0,$time);
    $success = $dbc->execute();

    $dbc->free();
    
    return $success;
    
  }
  
}
