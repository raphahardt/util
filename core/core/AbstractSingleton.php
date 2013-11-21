<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Core::depends('AbstractObject');

abstract class AbstractSingleton {

  /**
   * Guarda as instancias singletons das classes instanciadas.
   * @static
   * @var \AbstractSingleton 
   */
  static private $_instances = array();
  
  static protected $_class = __CLASS__;

  /**
   * Método que retorna uma instancia da classe singleton.
   * @static
   * @param $arg0 Pode-se passar até 3 parametros pela inicialização
   * @param $arg1
   * @param $arg2 
   * @return \AbstractSingleton
   */
  static public function getInstance() {
    
    if (function_exists('get_called_class')) { // PHP 5.3>=
      $class = get_called_class();
    } else { // PHP 5.3 <
      $class = self::$_class;
    }
    
    if ($class === __CLASS__) {
      throw new BadMethodCallException('Você deve definir \'self::$_class = __CLASS__\' '
              . 'na classe que chama este método');
    }
    
    $args = func_get_args();
    
    // primeiro argumento aumenta um nível de instanciamento para as singletons
    if ($args[0]) {
      if (is_array($args[0])) {
        $args[0] = implode('-', $args[0]);
      } else {
        $args[0] = (string)$args[0];
      }
      $instances = &self::$_instances[$class][$args[0]];
    } else {
      // senão, instancia a classe na raiz
      $instances = &self::$_instances[$class];
    }
    
    if (empty($instances)) {
      $instances = new $class($args[0], $args[1], $args[2]); // manda até 3 parametros para classe
    } else {
      $instances->reinit();
    }
    return $instances;
  }
  
  /**
   * Deleta todas as instancias da classe criadas
   */
  static public function destroyInstances() {
    if (function_exists('get_called_class')) { // PHP 5.3>=
      $class = get_called_class();
    } else { // PHP 5.3 <
      $class = self::$_class;
    }
    
    if ($class === __CLASS__) {
      throw new BadMethodCallException('Você deve definir \'self::$_class = __CLASS__\' '
              . 'na classe que chama este método');
    }
    
    $instances = &self::$_instances[$class];

    // vai em cada instancia, fecha cada conexao e destroi
    if (is_array($instances)) {
      foreach ($instances as $key => $v) {
        $instances[$key]->destroy();
        unset($instances[$key]);
      }
    } else {
      $instances->destroy();
      unset(self::$_instances[$class]); 
      // ^ é assim porque unset($instances) apaga só a referencia, e não a variavel verdadeira
    }
    // destroi o array e a referencia
    unset($instances);
  }
  
  /**
   * Método que reinicializa a classe singleton. É usada para "limpar" a classe caso
   * ela já esteja instanciada. Cada classe singleton deve implementar seu próprio código
   * de "autolimpeza"
   * @return void
   */
  abstract public function reinit();
  
  /**
   * Método que finaliza a classe singleton. Geralmente é usada para fechar qualquer
   * conexão aberta ou coisas do genero, antes de destruir a referencia praquela classe.
   * Cada singleton deve implementar seu próprio código de destruição
   */
  abstract public function destroy();
  
  /**
   * Retorna o nome da classe. Serve apenas para manter compatibilidade com o PHP5.3< que
   * não possui suporte para Late Static Binding
   * (http://us3.php.net/manual/en/function.get-called-class.php)
   * O corpo da classe deve ser este aqui, obrigatoriamente:
   * <code>
   * static protected function getClass() {
   *   self::$_class = __CLASS__;
   *   return self::$_class;
   * }
   * </code>
   */
  /*static public function getClass() {
    
  }*/

}
