<?php

class ModelException extends CoreException {}

/**
 * Representa um modelo de dados do sistema.
 * É composto de um Data Mapper (Mapper) e um ou mais comportamentos (Behavior).
 * Os comportamentos que são inseridos no Model definem como ele irá ser tratado,
 * tanto na parte de manipulação da instancia (objeto) tanto na manipulação dos dados.
 * 
 * O mapper define aonde o Model fará a persistencia de seus dados.
 * 
 * @uses Mapper Classe que faz a persistencia dos dados do Model
 * @uses Behavior Classe que dá um ou mais comportamentos pro Model
 * @see Mapper, Behavior
 * 
 * @abstract
 * 
 * @package mvc
 * @subpackage model
 * 
 * @property-read mixed $nome_do_campo Campo do Model
 * @method boolean isSingle() Verifica se o Model contem o Behavior
 * @method boolean isOnlySingle() Verifica se o Model contem o Behavior
 * @method boolean isCollection() Verifica se o Model contem o Behavior
 * @method boolean isOnlyCollection() Verifica se o Model contem o Behavior
 * @method boolean isTree() Verifica se o Model contem o Behavior
 * @method boolean isOnlyTree() Verifica se o Model contem o Behavior
 * @method boolean usesSingle() Usa o Behavior
 * @method boolean usesCollection() Usa o Behavior
 * @method boolean usesTree() Usa o Behavior
 * 
 * 
 * @author Raphael Hardt <sistema13@furacao.com.br>
 * @since 0.1 (24/09/2013)
 * @version 0.1 (24/09/2013)
 */
class Model implements ArrayAccess, Countable, Iterator {
  
  /**
   * Instancias únicas dos Behaviors usados no momento. Models compartilham Behaviors
   * já instanciados. 
   * @var array
   * @static
   */
  static protected $behavior_instances = array();
  
  /**
   * Behaviors do Model
   * @var array|Behavior 
   */
  protected $behaviors = array();
  
  /**
   * Behavior selecionado ao utilizar o uses()
   * @var Behavior 
   */
  protected $_selected_behavior = null;
  
  /**
   * Mapper do Model
   * @var Mapper 
   */
  public $Mapper = null;
  
  public function __construct() {
    // configurações default - colocar aqui
    // replica para todos os Models, de todas as aplicações!
    
    if (empty($this->behaviors)) {
      // se não for definido nenhum Behavior, usar single
      $this->addBehavior('Single');
    }
    
    if (!($this->Mapper instanceof Mapper)) {
      // se não for definido nenhum Mapper, usar mapper temporario
      $this->Mapper = new TempMapper();
    }
    return $this;
  }
  
  /**
   * Metodo auxiliar singleton para criacao de instancias de Behaviors.
   * Os Behaviors são independentes de Model, e apenas uma instancia é necessária
   * para ser usada por todos os models.
   * @param string $behavior Nome do Behavior
   * @return \Behavior
   * @access protected
   */
  protected function instanciateBehavior($behavior) {
    if (!isset(self::$behavior_instances[$behavior])) {
      $class = $behavior.'Behavior';
      self::$behavior_instances[$behavior] = new $class();
    }
    return self::$behavior_instances[$behavior];
  }
  
  /**
   * Adiciona um Behavior ao Model.
   * @param string $behavior Nome do Behavior, sem o sufixo "Behavior"
   */
  public function addBehavior($behavior) {
    $this->behaviors[$behavior] = $this->instanciateBehavior($behavior);
  }
  
  /**
   * Retorna o nome de todos os Behaviors do Model;
   * @return array
   */
  public function getBehaviors() {
    return array_keys($this->behaviors);
  }
  
  /**
   * Define os Behaviors do Model. Substitui qualquer um que já tenha sido definido antes
   * @param array Nomes dos behaviors que deseja definir ao Model
   */
  public function setBehaviors($behaviors) {
    foreach ((array)$behaviors as $behavior) {
      $this->addBehavior($behavior);
    }
  }
  
  /**
   * Define o Mapper de dados do Model.
   * @param \Mapper $mapper
   */
  public function setMapper(Mapper $mapper) {
    $this->Mapper = $mapper;
    $this->Mapper->init();
  }
  
  /**
   * Verifica se o Model contem certo Behavior. Pode ser usado das duas formas:
   * $model->is('Single') // sem o sufixo "Behavior"
   * $model->isSingle()
   * $model->isOnlySingle() // é o mesmo que is('Single', true)
   * @param string $behavior Nome do Behavior. Deve ser sem o sufixo, ex: SingleBehavior = Single
   * @param boolean $only Se TRUE, vai verificar se o Model contem APENAS este Behavior
   * @return boolean TRUE se o Behavior está contido, FALSE se não
   */
  public function is($behavior, $only = false) {
    // retorna se o model tem o behavior, e se foi chamado como "only", verifica se
    // só aquele behavior existe no model
    return isset($this->behaviors[$behavior]) && 
            ($only ? count($this->behaviors)==1 : true);
  }
  
  /**
   * Seleciona um Behavior contido no Model para ser chamado seus métodos de maneira
   * particular. Deve ser usado da seguinte forma:
   * $model->uses('Collection')->metodoDoCollection();
   * $model->usesCollection()->metodo(); // o mesmo que acima
   * Este método é útil para funções de Behaviors que possuem o mesmo nome, e a precedencia
   * de uma faz o metodo da outra nunca ser chamada.
   * 
   * @param string $behavior Nome do Behavior que deseja utilizar, sem o sufixo.
   * @return \Model
   * @todo tenho que fazer isso
   */
  public function uses($behavior) {
    $this->_selected_behavior = isset($this->behaviors[$behavior]) ? 
            $this->behaviors[$behavior] : false;
    return $this;
  }
  
  /**
   * Chama os métodos injetados dos Behavior contidos no Model.
   * Ao definir que um Model irá conter certo Behavior, todos os métodos dele poderão
   * ser chamados diretamente do Model.
   * Por ex:
   * $model = new Usuario(); // extends Model
   * $model->select(); // exception: metodo não existe
   * $model->addBehavior('Single');
   * $model->select(); // ok (metodo de SingleBehavior foi injetado no model)
   * 
   * Os métodos são chamados na ordem em que eles são adicionados
   * ex:
   * $model->addBehavior('Single');
   * $model->addBehavior('Collection');
   * $model->update(); // SingleCollection::update()
   * 
   * @param string $name Nome do método
   * @param array $arguments Argumentos passados pro método
   * @return mixed Depende do retorno do método do Behavior
   * @throws ModelException
   */
  public function __call($name, $arguments=array()) {
    // se for a funcao magica "is" ou "isOnly"
    if (strpos($name, 'is') === 0) {
      // a funcao "is" retorna se o model contem certo behavior ou não
      $only = false;
      $name = substr($name, 2);
      if (strpos($name, 'Only') === 0) {
        $only = true;
        $name = substr($name, 4);
      }
      
      return $this->is($name, $only);
      
    } elseif (strpos($name, 'uses') === 0) {
      // a funcao "uses" faz com que o __call seja só pro behavior selecionado.
      // serve para chamar metodos homonimos de behaviors diferentes
      $name = substr($name, 4);
      
      return $this->uses($name);
    }
    
    // se usou o uses(), ele vai chamar só com o behavior selecionado, e depois 
    // apagar a referencia dele
    if ($this->_selected_behavior !== null) {
      if ($this->_selected_behavior === false) // nao encontrou
        $behaviors = array();
      else
        $behaviors = array($this->_selected_behavior);
      $this->_selected_behavior = null;
    } else {
      // se não, tenta em todos os behaviors do model, por ordem
      $behaviors = $this->behaviors;
    }
    
    if (empty($behaviors))
      throw new ModelException('Método '.$name.'() não existe no Model '.get_class($this));
    
    foreach ($behaviors as $behavior) {
      if (!method_exists($behavior, $name)) {
        $n = false;
        continue;
      }
      $n = true;
      // injeta o model na funcao do behavior
      // veja http://www.php.net/manual/en/function.call-user-func-array.php#100794
      switch (count($arguments)) {
        case 0:
          return $behavior->$name($this);
        case 1:
          return $behavior->$name($this, $arguments[0]);
        case 2:
          return $behavior->$name($this, $arguments[0], $arguments[1]);
        case 3:
          return $behavior->$name($this, $arguments[0], $arguments[1], $arguments[2]);
        case 4:
          return $behavior->$name($this, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
        default:
          return call_user_func_array(array($behavior, $name), array_merge(array($this), $arguments));
      }
    }
    if (!$n) throw new ModelException('Método '.$name.'() não existe no Model '.get_class($this));
  }
  
  public function getEntity() {
    return $this->Mapper->getEntity();
  }
  
  public function __get($name) {
    return $this->Mapper->__get($name);
  }
  
  public function count() {
    return $this->Mapper->count();
  }

  public function current() {
    return $this->__call('current');
  }

  public function key() {
    return $this->__call('key');
  }

  public function next() {
    return $this->__call('next');
  }
  
  public function rewind() {
    return $this->__call('rewind');
  }

  public function valid() {
    return $this->__call('valid');
  }  

  public function offsetExists($offset) {
    return $this->__call('offsetExists', array($offset));
  }

  public function offsetGet($offset) {
    return $this->__call('offsetGet', array($offset));
  }

  public function offsetSet($offset, $value) {
    $this->__call('offsetSet', array($offset, $value));
  }

  public function offsetUnset($offset) {
    $this->__call('offsetUnset', array($offset));
  }

}