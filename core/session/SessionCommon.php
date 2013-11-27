<?php

namespace Djck\session;

use Djck\Core;
use Djck\CoreException;
use Djck\cookie\Cookie;

class SessionException extends CoreException {}

abstract class SessionCommon implements \ArrayAccess, \Countable {
  
  private $reserved_names = array(
      SESSION_USER_NAME => 1, SESSION_TOKEN_NAME => 1, 'id' => 1, 'logged' => 1
  );
  
  static private $started = false;
  
  private $cookie;
  
  public function __construct() {
    
    if (!isset($this->cookie)) {
      // define o obj cookie da session
      $this->cookie = new Cookie( SESSION_NAME, 
        ((SESSION_TIMEOUT > 0) ?
          time() + SESSION_TIMEOUT :
          0), 
        null, null, null, true );
    }
    
    if (!self::$started) {
      
      // pega o sid antigo pelo cookie, ou se não existir, cria um novo
      $sid = $this->cookie->get() ? $this->cookie->get() : $this->generateId();

      // verifica se o sid está correto (evita roubo de informações pelo cookie)
      if (!$this->checkId($sid)) {
        $_SESSION = array(); // apaga a variavel mágica da session (segurança)
        $sid = $this->generateId();
        //throw new Exception ('Tentando roubar informações, né?');
      }
      
      // inicio das config da session ---
      
      // define os handlers de manipulação
      session_set_save_handler(
          array($this, 'open'),
          array($this, 'close'),
          array($this, 'read'),
          array($this, 'write'),
          array($this, 'destroy'),
          array($this, 'gc')
      );
      
      // registra função de shutdown: isso faz com que a session sempre seja
      // gravada mesmo que um exit() seja chamado
      register_shutdown_function('session_write_close');
      
      // define configurações de cookie da session
      session_set_cookie_params(SESSION_TIMEOUT, 
              $this->cookie->getPath(), 
              $this->cookie->getDomain(),
              $this->cookie->isSecure(),
              $this->cookie->isHttpOnly());
      session_name(SESSION_NAME);
      session_id($sid);
      
      // inicia manipulação da session
      session_start();
      
      self::$started = true;
    }
  }
  
  /**
   * Gera uma chave única para sid de sessions.
   * A chave é composta de:
   * A-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWWNNNNNNNN-B
   * onde:
   * A: digito verificador de 5 até 9
   * X: browser (user-agent) do usuario, com hash aleatório e codificado em md5
   * W: string única, codificada em md5
   * N: ip do usuário
   * B: digito verificador de 0 até 4
   * @return string
   */
  private function generateId() {
    return sprintf('%d-%s%s%u-%d', 
          mt_rand(5,9),
          substr(md5(uniqid()), 0, 14),                // chave unica aleatoria (md5)
          md5(env('HTTP_USER_AGENT').mt_rand(0, 3)),   // browser do usuario
          ip2long(env('REMOTE_ADDR')),                 // ip do usuario
          mt_rand(0,4)
          );
  }
  
  /**
   * Verifica se o sid é valido e corresponde com a sessão do usuário.
   * Retorna FALSE se o browser não for correto, se o IP não for valido ou correto ou
   * se a chave única for invalida.
   * @param string $sid sid da session a ser verificada
   * @return boolean TRUE se o sid for válido
   */
  private function checkId($sid) {
    list($dig1, $middle, $dig2) = explode('-', $sid);
    $md5 = substr($middle, 0, 14);
    $agent = substr($middle, 14, 32);
    $ip = (double)substr($middle, 46);
    

    // confere browser
    if (md5(env('HTTP_USER_AGENT').'0') !== $agent && 
        md5(env('HTTP_USER_AGENT').'1') !== $agent && 
        md5(env('HTTP_USER_AGENT').'2') !== $agent && 
        md5(env('HTTP_USER_AGENT').'3') !== $agent)
      return false;

    // confere ip
    if (ip2long(env('REMOTE_ADDR')) != $ip)
      return false;

    // confere md5
    if (preg_match('/[^0-9a-f]/', $md5))
      return false;

    // tudo ok, sid confere
    return true;
  }
  
  /**
   * Gera uma nova sid para a session atual, mantendo todas as informações da sessão.
   * Serve como uma camada para evitar roubo de informação por cookie.
   * Essa função é usada especialmente após logins.
   */
  public function regenerateId() {
    if (isset($this->cookie) && self::$started) {
      $sid = session_id();
      $new_sid = $this->generateId();

      $data = $this->read($sid);
      $this->destroy($sid);
      $this->write($new_sid, $data);

      session_id($new_sid);
      $this->cookie->set($new_sid);
    }
  }
  
  /**
   * Interrompe a escrita nas sessions.
   * É usado principalmente logo após gravar as informações de usuario na session,
   * para evitar mudanças após o script
   */
  public function interrupt() {
    session_write_close();
  }
  
  abstract public function open($save_path, $session_name);

  abstract public function close();

  abstract public function read($sid);

  abstract public function write($sid, $data);

  public function destroy($sid) {
    //$_SESSION = array();
    if (isset($this->cookie)) {
      $this->cookie->delete();
    }
    return true;
  }

  abstract public function gc($lifetime);
  

  public function count() {
    return count($_SESSION);
  }

  public function offsetExists($offset) {
    return isset($_SESSION[$offset]);
  }

  public function offsetGet($offset) {
    if (isset($this->reserved_names[$offset])) {
      throw new SessionException('Nome de session reservada do sistema, não é possível buscar '.
              'seu valor diretamente.');
    }
    return $_SESSION[$offset];
  }

  public function offsetSet($offset, $value) {
    if (isset($this->reserved_names[$offset])) {
      throw new SessionException('Nome de session reservada do sistema, não sobrescrever');
    }
    $_SESSION[$offset] = $value;
  }

  public function offsetUnset($offset) {
    if (isset($this->reserved_names[$offset])) {
      throw new SessionException('Nome de session reservada do sistema, não destruir');
    }
    unset($_SESSION[$offset]);
  }
  
}
