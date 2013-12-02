<?php

namespace Djck\database;

/**
 * Classe estática que guarda a configuração de conexão com o banco de dados.<br>
 * As configurações devem ser definidas utilizando set() com um array associativo
 * com todos os atributos necessários pra conexão.
 * 
 * Exemplo de uso:
 * <code>
 * BDConfig::set('NOME DA CONFIG', array(
 *    '#host' => '0.0.0.0:000', // host/ip do servidor
 *    '#user' => 'root',        // usuario
 *    '#password' => '123',     // senha
 *    '#schema' => 'db',        // nome do banco
 *    '#charset' => 'UTF8'  // (opcional) charset do banco
 * ));
 * </code>
 * 
 * Para extrair a configuração acima, basta usar <code>BDConfig::get('NOME DA CONFIG')</code>.
 * 
 * Para criar uma conexão com o banco de dados, utilize <code>BD::getInstance('NOME DA CONFIG')</code>.
 * 
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 * @since 1.0 (10/04/13 Raphael)
 * @version 1.0 (10/04/13 Raphael)
 */
final class DbcConfig {
  
  /**
   * Charset padrão para todas as conexões
   * @var string
   * @access private
   */
  static private $default_charset = 'UTF-8';
  
  /**
   * Porta padrão para todas as conexões
   * @var string
   * @access private
   */
  static private $default_port = 3306;
  
  /**
   * Todas as configurações de banco salvas
   * @var array
   * @access private
   */
  static private $config = array();
  
  // classe estática
  private function __construct() {}
  
  /**
   * Define uma configuração de banco. Deve ser passado um array associativo com os
   * seguintes campos:
   * #host, #user, #password, #schema e #charset.
   * 
   * Todos eles são opcionais e, caso não sejam definidos, é usado o valor da configuração
   * default.
   * 
   * @param string $name Nome da configuração. Se o nome da config já existe, ela é sobrescrita
   * @param array $config Array com os parametros de configuração
   * @throws Exception
   */
  static public function set($name, $config) {
    // acerta o nome
    $name = self::_normalizeName($name);
    
    // pega a config default como base
    if ($name !== 'default') $config_base = self::get('default');
    
    // se não foi definido default
    if ($config_base === false) {
      $config_base = array(
          '#host' => 'localhost',
          '#user' => 'root',
          '#password' => '',
          '#schema' => 'db'
      );
    }
    
    // default charset
    if (!isset($config_base['#charset']))
      $config_base['#charset'] = self::$default_charset;
    
    // default port
    if (!isset($config_base['#port']))
      $config_base['#port'] = self::$default_port;
    
    // e substitui só o que foi definido
    if (isset($config['#host'])) $config_base['#host'] = $config['#host'];
    if (isset($config['#user'])) $config_base['#user'] = $config['#user'];
    if (isset($config['#password'])) $config_base['#password'] = $config['#password'];
    if (isset($config['#schema'])) $config_base['#schema'] = $config['#schema'];
    if (isset($config['#charset'])) $config_base['#charset'] = $config['#charset'];
    if (isset($config['#port'])) $config_base['#port'] = $config['#port'];
    
    // adiciona/substitui a configuracao
    self::$config[$name] = $config_base;
    
  }
  
  /**
   * Retorna a configuração definida anteriormente.
   * 
   * @param string $name Nome da config que foi definida anteriormente
   * @return array Um array com toda a configuração, <b>FALSE</b> em caso de não encontrar
   */
  static public function get($name) {
    // acerta o nome
    $name = self::_normalizeName($name);
    return isset(self::$config[$name]) ? self::$config[$name] : false;
  }
  
  /**
   * Clona uma configuração em outra.
   * 
   * @param type $dest
   * @param type $src
   */
  static public function copy($dest, $src) {
    self::set($dest, self::get($src));
  }
  
  /**
   * Serve apenas para que o nome da config seja case-insensitive.
   * @param string $name Nome da config, sem normalização
   * @return string Nome normalizado
   * @access private
   */
  static private function _normalizeName($name) {
    $name = strtolower($name);
    return $name;
  }
  
}