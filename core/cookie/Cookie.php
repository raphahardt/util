<?php

/**
 * Classe para manipulação de cookies
 * 
 * Exemplo de uso:
 * <pre>
 * $cookie = new Cookie( 'nomeDoCookie', time()+3600 ); // expira daqui 3600 segundos (1 hora)
 * 
 * // define um valor
 * $cookie->set('valor');
 * 
 * // retorna o valor
 * $valor = $cookie->get();
 * </pre>
 * 
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 * @package core
 * @since 1.0 (8/4/13 Raphael)
 * @version 1.0 (8/4/13 Raphael)
 */
class Cookie {

  private $name;
  private $value;
  private $rawvalue;
  private $expire = 0;
  private $path = '/';
  private $domain;
  private $secure = false;
  private $httponly = false;

  public function __construct($name, $expire = 0, $path = null, $domain = null, $secure = null, $httponly = null) {

    if ($expire < 0)
      throw new Exception('Set a positive expire value');

    if (is_string($expire) && !is_numeric($expire)) {
      $expire = strtotime($expire);
    }
    
    // defaults
    if (defined('COOKIE_DOMAIN'))
      $this->domain = COOKIE_DOMAIN;
    
    if (defined('COOKIE_PATH'))
      $this->path = COOKIE_PATH;

    // definition
    $this->name = $name;
    $this->expire = $expire;
    if (isset($path))
      $this->path = $path;
    if (isset($domain))
      $this->domain = $domain;
    if (isset($secure))
      $this->secure = $secure;
    if (isset($httponly))
      $this->httponly = $httponly;

    // Não usar $_GET direto (ver: http://us1.php.net/manual/en/filter.examples.sanitization.php )
    // a partir do PHP 5.2, é recomendado acessar essas variaveis usando filters, que limpam (sanitizam)
    // as variaveis para se tornarem seguras para uso (desde 05/11/13)
    $this->rawvalue = filter_input(INPUT_COOKIE, $name, FILTER_UNSAFE_RAW); // TODO: deixar isso aqui seguro
    $this->value = is_array($this->rawvalue) ? array_map('rawurlencode', $this->rawvalue) : rawurlencode($this->rawvalue);
  }

  public function set($value) {
    
    if (headers_sent()) {
      throw new Exception('Headers already been sent');
    }
    
    $this->rawvalue = $value;
    $time = time() - 3600;
    
    if (is_array($value)) {
      
      foreach ($value as $index => &$v) {
        $v = rawurlencode($v);

        setrawcookie($this->name . '[' . $index . ']', 
                $v, 
                $this->expire, 
                $this->path, 
                $this->domain, 
                $this->secure, 
                $this->httponly);
      }
      unset($v); // destroi referencia

      $this->value = $value;

      // deleta se existir um cookie com o mesmo nome mas um não array
      setrawcookie($this->name, 
              '', 
              $time, 
              $this->path, 
              $this->domain, 
              $this->secure, 
              $this->httponly);
      
    } else {
      
      // deleta se existir um cookie com o mesmo nome mas um array
      if (is_array($this->value)) {

        foreach ($this->value as $index => $v) {
          setrawcookie($this->name . '[' . $index . ']', 
                  '', 
                  $time, 
                  $this->path, 
                  $this->domain, 
                  $this->secure, 
                  $this->httponly);
        }
      }

      $this->value = rawurlencode($value);
      setrawcookie($this->name, 
              $this->value, 
              $this->expire, 
              $this->path, 
              $this->domain, 
              $this->secure, 
              $this->httponly);
    }
  }

  public function delete() {
    $time = time() - 3600;
    if (is_array($this->value)) {

      foreach ($this->value as $index => $v) {
        setrawcookie($this->name . '[' . $index . ']', 
                '', 
                $time, 
                $this->path, 
                $this->domain, 
                $this->secure, 
                $this->httponly);
      }
    } else {
      setrawcookie($this->name, 
              '', 
              $time, 
              $this->path, 
              $this->domain, 
              $this->secure, 
              $this->httponly);
    }
    unset($this->value);
  }

  public function get() {
    return is_array($this->value) ? array_map('rawurldecode', $this->value) : rawurldecode($this->value);
  }
  
  public function setValue($val) {
    $this->set($val);
  }
  
  public function getValue() {
    return $this->get();
  }
  
  public function setExpire($time) {
    $this->expire = max(0,$time);
    $this->set($this->get());
  }
  
  public function getExpire() {
    return $this->expire;
  }
  
  public function setPath($path) {
    $this->path = $path;
    $this->set($this->get());
  }
  
  public function getPath() {
    return $this->path;
  }
  
  public function setDomain($domain) {
    $this->domain = $domain;
    $this->set($this->get());
  }
  
  public function getDomain() {
    return $this->domain;
  }
  
  public function setSecure($secure) {
    $this->secure = (bool)$secure;
    $this->set($this->get());
  }
  
  public function isSecure() {
    return $this->secure;
  }
  
  public function setHttpOnly($httponly) {
    $this->httponly = (bool)$httponly;
    $this->set($this->get());
  }
  
  public function isHttpOnly() {
    return $this->httponly;
  }

}