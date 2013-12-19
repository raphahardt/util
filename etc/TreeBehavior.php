<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Node extends stdClass {
  
  protected $Model;
  
  protected $id;
  protected $parent_id;
  protected $lft;
  protected $rght;
  protected $lvel;
  protected $height;
  protected $alias;
  
  /**
   * Guarda as instancias singletons das classes instanciadas.
   * @static
   * @var Node[]
   */
  static private $_instance = array();
  
  /**
   * Método que retorna uma instancia da classe singleton.
   * @static
   * @return Node
   */
  static public function getInstance(Model $Model, $id, $data = null) {
    $instance = &self::$_instance[get_class($Model)][$id];
    if (empty($instance)) {
      $instance = new self($Model, $id, $data);
    } else {
      $instance->reinit();
    }
    return $instance;
  }
  
  /**
   * Deleta todas as instancias da classe criadas
   */
  static public function destroyInstance(Model $Model, $id) {
    if (isset(self::$_instance[get_class($Model)][$id])) {
      self::$_instance[get_class($Model)][$id]->destroy();
      unset(self::$_instance[get_class($Model)][$id]); 
    }
  }
  
  /**
   * Método que reinicializa a classe singleton. É usada para "limpar" a classe caso
   * ela já esteja instanciada. Cada classe singleton deve implementar seu próprio código
   * de "autolimpeza"
   * @return void
   */
  public function reinit() {
    $Model =& $this->Model;
    
    $Model->setFilter(new SQLCriteria($Model->id, '=', $this->getId()));
    if (!$Model->select()) {
      throw new Exception('Erro ao buscar o node '.$this->getId());
    } else {
      
      $this->setId($Model['id']);
      $this->setParentId($Model['parent_id']);
      $this->setLeft($Model['lft']);
      $this->setRight($Model['rght']);
      $this->setLevel($Model['lvel']);
      $this->setHeight($Model['height']);
      $this->setAlias($Model['alias']);
      
    }
  }
  
  /**
   * Método que finaliza a classe singleton. Geralmente é usada para fechar qualquer
   * conexão aberta ou coisas do genero, antes de destruir a referencia praquela classe.
   * Cada singleton deve implementar seu próprio código de destruição
   */
  public function destroy() {
    
  }
  
  
  
  public function __construct(Model $Model, $id, $data = null) {
    if (!$Model->isCollection()) {
      throw new Exception('O node requer que o model seja um collection');
    }
    $this->Model = $Model;
    
    $this->setId((int)$id);
    if ($data) {
      
      $this->setParentId($data['parent_id']);
      $this->setLeft($data['lft']);
      $this->setRight($data['rght']);
      $this->setLevel($data['lvel']);
      $this->setHeight($data['height']);
      $this->setAlias($data['alias']);
      
    } else {
      $this->reinit();
    }
    
  }
  
  public function getLeft() {
    return $this->lft;
  }
  public function setLeft($lft) {
    $this->lft = $lft;
  }
  public function getRight() {
    return $this->rght;
  }
  public function setRight($rght) {
    $this->rght = $rght;
  }
  public function getParentId() {
    return $this->parent_id;
  }
  public function setParentId($parent_id) {
    $this->parent_id = $parent_id;
  }
  public function getId() {
    return $this->id;
  }
  public function setId($id) {
    $this->id = $id;
  }
  public function getLevel() {
    return $this->lvel;
  }
  public function setLevel($lvel) {
    $this->lvel = $lvel;
  }
  public function getHeight() {
    return $this->height;
  }
  public function setHeight($height) {
    $this->height = $height;
  }
  public function getAlias() {
    return $this->alias;
  }
  public function setAlias($alias) {
    $this->alias = $alias;
  }
  
  /**
   * Retorna o node pai do node.
   * 
   * @return Node
   */
  public function getParentNode() {
    // where id = node_id.parent_id
    return static::getInstance($this->Model, $this->getParentId());
  }
  
  /**
   * Retorna todos os pais do node.
   * 
   * @return Node[]
   */
  public function getPathNodes() {
    // where left < node_id.left and right > node_id.right
    if ($this->isRootNode()) {
      return array();
    }
    $model =& $this->Model;
    $model->setFilter(new SQLCriteria($model->lft, '<', $this->getLeft()),
                      new SQLCriteria($model->rght, '>', $this->getRight()));
    $model->setOrderBy($model->lft, 'asc');
    
    $nodes = array();
    if ($model->select()) {
      foreach ($model as $m) {
        $nodes[] = static::getInstance($model, $m['id'], $m);
      }
    }
    
    return $nodes;
    
  }
  
  /**
   * Retorna os nodes filhos do node.
   * 
   * @param boolean $all 
   *    Se TRUE, retorna todos os filhos, se FALSE, somente os filhos diretos
   * @return Node[]
   */
  public function getChildNodes($all = true) {
    // all=true: where left > node_id.left and right < node_id.right order by left asc
    // all=false: where parent_id = node_id.id
    
    $model =& $this->Model;
    if ($all === true) {
      $model->setFilter(new SQLCriteria($model->lft, '>', $this->getLeft()),
                        new SQLCriteria($model->rght, '<', $this->getRight()));
    } else {
      $model->setFilter(new SQLCriteria($model->parent_id, '=', $this->getId()));
    }
    $model->setOrderBy($model->lft, 'asc');
    
    $nodes = array();
    if ($model->select()) {
      foreach ($model as $m) {
        $nodes[] = static::getInstance($model, $m['id'], $m);
      }
    }
    
    return $nodes;
    
  }
  
  /**
   * Retorna os nodes que são folhas, filhos do node.
   * 
   * @return Node[]
   */
  public function getLeafNodes() {
    // where rght = lft+1
    
    $model =& $this->Model;
    $model->setFilter(new SQLCriteria($model->lft, '>', $this->getLeft()),
                      new SQLCriteria($model->rght, '<', $this->getRight()),
                      new SQLCriteria($model->rght, '=', new SQLExpression('+', $model->lft, 1)));
    
    $model->setOrderBy($model->lft, 'asc');
    
    $nodes = array();
    if ($model->select()) {
      foreach ($model as $m) {
        $nodes[] = static::getInstance($model, $m['id'], $m);
      }
    }
    
    return $nodes;
    
  }
  
  /**
   * Retorna o número de filhos do node.
   * 
   * @param boolean $all
   * @return int
   */
  public function getChildNodesCount($all = true) {
    // all=true: count = (node_id.right - node_id.left - 1) / 2
    // all=false: count(*) where parent_id = node_id.id
    if ($all === true) {
      return ($this->getRight() - $this->getLeft() - 1) / 2;
    } else {
      
      $model =& $this->Model;
      $model->setFilter(new SQLCriteria($model->parent_id, '=', $this->getId()));
      return $model->count();
      
    }
  }
  
  /**
   * Retorna os nodes que são irmãos diretos do node.
   * 
   * @return Node[]
   */
  public function getSiblingNodes() {
    // where parent_id = node_id.parent_id
    $model =& $this->Model;
    $model->setFilter(new SQLCriteria($model->parent_id, '=', $this->getParentId()));
    
    $model->setOrderBy($model->lft, 'asc');
    
    $nodes = array();
    if ($model->select()) {
      foreach ($model as $m) {
        $nodes[] = static::getInstance($model, $m['id'], $m);
      }
    }
    
    return $nodes;
  }
  
  /**
   * Retorna o node irmão da esquerda do node.
   * 
   * @return Node
   */
  public function getPrevSiblingNode() {
    // where right = node_id.left-1
    $model =& $this->Model;
    $model->setFilter(new SQLCriteria($model->rght, '=', $this->getLeft()-1));
    
    if ($model->select()) {
      $m = $model->first();
      return static::getInstance($model, $m['id'], $m);
    }
    
    return null;
  }
  
  /**
   * Retorna o node irmão da direita do node.
   * 
   * @return Node
   */
  public function getNextSiblingNode() {
    // where left = node_id.right+1
    $model =& $this->Model;
    $model->setFilter(new SQLCriteria($model->lft, '=', $this->getRight()+1));
    
    if ($model->select()) {
      $m = $model->first();
      return static::getInstance($model, $m['id'], $m);
    }
    
    return null;
  }
  
  /**
   * Retorna se o node é uma folha.
   * 
   * @return boolean
   */
  public function isLeafNode() {
    return $this->getRight() == ($this->getLeft() + 1);
  }
  
  /**
   * Retorna se o node é irmão direto do node.
   * 
   * @param int $id
   * @return boolean
   */
  public function isSiblingNode($id) {
    $cmp_node = static::getInstance($this->Model, $id);
    
    return ($cmp_node->getParentId() === $this->getParentId() &&
            $cmp_node->getId() !== $this->getId() );
  }
  
  /**
   * Retorna se o node é pai do node.
   * 
   * @param int $id
   * @return boolean
   */
  public function isParentNode($id) {
    $cmp_node = static::getInstance($this->Model, $id);
    
    return ($cmp_node->getId() === $this->getParentId());
  }
  
  /**
   * Retorna se o node é root (nível 1)
   * 
   * @return boolean
   */
  public function isRootNode() {
    return !$this->getParentId();
  }
  
  
  
}

/**
 * Manipula um Model como uma árvore MPTT (Modified Preorder Tree Traversal)
 * 
 */
class TreeBehavior extends Behavior {
  
  public $priority = 5;
  
  protected $tree = array();
  
  
  public function getNode(Model $Model, $id) {
    if ($id === null) return null;
    return Node::getInstance($Model, $id);
  }
  
  public function getNodesByLevel(Model $Model, $level = 1) {
    $nodes = array();
    $Model->setFilter(new SQLCriteria($Model->lvel, '=', (int)$level));
    if ($Model->select()) {
      foreach ($Model as $node) {
        $nodes[] = Node::getInstance($Model, $node['id'], $node);
      }
    }
    return $nodes;
  }
  
  
  public function appendNode(Model $Model, $parent_id = null) {
    
    // guarda os valores originais alterados numa var temporaria, pq os updates
    // abaixo vão dar um saveState que vai parecer que ja foram alterados
    $new_values = $Model->Mapper->getData();
    if ($Model->Mapper instanceof DatabaseItfMapper) 
      $Model->Mapper->saveState();
    
    // procura o node pai
    $parent = $this->getNode($Model, $parent_id);
    if ($parent !== null) {
      // filho
      $new_lft = $parent->getRight();
      $new_lvel = $parent->getLevel() + 1;
    } else {
      // root
      $roots = $this->getNodesByLevel($Model, 1);
      if (!empty($roots)) {
        $parent = end($roots);
        $new_lft = $parent->getRight() + 1;
        $new_lvel = $parent->getLevel();
      } else {
        $new_lft = 1;
        $new_lvel = 1;
      }
    }
    
    // antes de tudo, verifico se o pai é folha, e, se for, adiciono na altura
    // de todos os pais + 1
    if ($parent && $parent->isLeafNode()) {
      //$Model['height'] = new SQLExpression('+', $Model->height, 1);
      $Model->setFilter(new SQLCriteria($Model->lft, '<=', $parent->getLeft()),
                        new SQLCriteria($Model->rght, '>=', $parent->getRight()));
      $Model->setOrderBy($Model->lft, 'asc');
      if ($Model->select()) {
        $lvl = $new_lvel;
        foreach ($Model as $n) {
          if ($n['height'] <= $lvl) {
            $Model['height'] = $lvl;
            $Model->setFilter(new SQLCriteria($Model->id, '=', $n['id']));
            $Model->update($Model->height);
          }
          --$lvl;
        }
      }
    }
    
    // primeiro faço os updates de fix tree na tabela, para depois sim inserir o node
    $Model['rght'] = new SQLExpression('+', $Model->rght, 2);
    $Model->setFilter(new SQLCriteria($Model->rght, '>=', $new_lft));
    $Model->update($Model->rght);
    
    $Model['lft'] = new SQLExpression('+', $Model->lft, 2);
    $Model->setFilter(new SQLCriteria($Model->lft, '>=', $new_lft));
    $Model->update($Model->lft);
    
    // agora altera o registro atual
    $Model->Mapper->set($new_values);
    $Model['parent_id'] = $parent_id;
    $Model['lvel'] = $new_lvel;
    $Model['lft'] = $new_lft;
    $Model['rght'] = $new_lft+1;
    $Model['height'] = 1;
    $Model->add();
    
    if ($Model->insert()) {    
      return $Model['id'];
    } else {
      return false;
    }
    
  }
  
  public function addNode(Model $Model, $parent_id = null) {
    return $this->appendNode($Model, $parent_id);
  }
  
  public function removeNode(Model $Model, $id, $all = true) {
    // remove_children = false: update all set right-2 where right > node_id.right... depois left
    // remove_children = true: update all set right-2-(2*getchildrencound(node_id)) where right > node_id.right... depois left
    //                         delete getchildren(node_id, all)
    
    $node_del = $this->getNode($Model, $id);
    
    $nodes_delet = array();
    $nodes_delet[] = $node_del;
    $children_count = 0;

    if ($all == true) {
      $children_count = $node_del->getChildNodesCount();
      if ($children_count > 0) {
        $nodes_delet = array_merge($nodes_delet, $node_del->getChildNodes());
      }
    }
    
    //guarda o right do node a ser deletado para fazer o update nos outros nodes
    $tmp_left = $node_del->getLeft();
    $tmp_right = $node_del->getRight();
    $tmp_id = $node_del->getId();
    $tmp_parentid = $node_del->getParentId();
    
    // constroi o query de deletar os nodes
    $value = array();
    foreach ($nodes_delet as $n) {
      $value[] = $n->getId();
    }
    $Model->setFilter(new SQLCriteria($Model->id, '=', $value));

    // primeiro deleta o(s) node(s)
    $Model->delete();
    
    // RGHT = RGHT - (num_filhos + 1) * 2)
    $Model['rght'] = new SQLExpression('-', $Model->rght, (($children_count + 1) * 2));
    $Model->setFilter(new SQLCriteria($Model->rght, '>', $tmp_right));
    $Model->update($Model->rght);
    
    // LFT = LFT - (num_filhos + 1) * 2)
    $Model['lft'] = new SQLExpression('-', $Model->lft, (($children_count + 1) * 2));
    $Model->setFilter(new SQLCriteria($Model->lft, '>', $tmp_right));
    $Model->update($Model->lft);
    
    if ($all !== true) {
      
      // LFT = LFT - 1, RGHT = RGHT - 1
      $Model['lft'] = new SQLExpression('-', $Model->lft, 1);
      $Model['rght'] = new SQLExpression('-', $Model->rght, 1);
      $Model['lvel'] = new SQLExpression('-', $Model->lvel, 1);
      $Model->setFilter(new SQLCriteria($Model->lft, '>', $tmp_left), new SQLCriteria($Model->rght, '<', $tmp_right));
      $Model->update($Model->lft, $Model->rght);
      
      // (hack) para model reconhecer que mudou esse campo
      if ($Model->Mapper instanceof DatabaseItfMapper) {
        $Model['parent_id'] = 99999999;
        $Model->Mapper->saveState();
      }
      
      if ($tmp_parentid) {
        // PARENT_ID = parent_id_node_del
        $Model['parent_id'] = $tmp_parentid;
      } else {
        // PARENT_ID = null
        $Model['parent_id'] = null;
      }
      $Model->setFilter(new SQLCriteria($Model->parent_id, '=', $tmp_id));
      $Model->update($Model->parent_id);
      
    }
    
    // agora altera o registro atual
    return true;
  }
  
  public function removeNodes(Model $Model, $all = true) {
    $success = true;
    if ($Model->select()) {
      $nodes = $Model->getArray();
      foreach ($nodes as $n) {
        $this->removeNode($Model, $n['id'], $all);
      }
    }
    return $success;
  }
  
  public function moveNode(Model $Model, $id_src, $id_dst) {
    
    $src = $this->getNode($Model, $id_src);
    
    $a = $src->getLeft();
    $b = $src->getRight();
    $lvl_ant = $src->getLevel()-1;
    
    if ($id_dst !== null) {
      // movendo para um destino existente
      $dst = $this->getNode($Model, $id_dst);

      $lft_dst = $dst->getLeft();
      $c = $dst->getRight();
      $lvl_dst = $dst->getLevel();
    } else {
      // movendo pra root
      $lft_dst = 0;
      $c = 1;
      $lvl_dst = 0;
    }
    // o fator soma é o numero de filhos do nó que eu quero mover, mais ele mesmo, vezes 2
    // esse será o fator que será somado aos nós e que abrirá um "buraco" para o nó
    // que eu quero mover ficar
    $soma = ($src->getChildNodesCount(true) + 1) * 2; // +1 = proprio node
    // verifico se o nó que eu quero mover está indo pra direita (não reverso) ou esquerda
    // (reverso). se eu estiver movendo pra esquerda (reverso), preciso somar o fator de
    // soma no left e right do nó que estou movendo, pois ao abrir o "buraco" nos nós,
    // o próprio nó a ser movido (que estava na direita) também será "somado" junto
    $reverse = $a > $c;
    $d = $reverse ? $soma : 0;
    
    if ($a < $lft_dst && $b > $c) { // verifica se o nó destino é filho do nó a ser movido
      // (só vou mudar o parent_id só se o nó destino não for filho do nó a ser movido)
      return false;
    } else {
      
      // primeiro acerto o parent_id do nó a ser movido
      $Model['parent_id'] = $id_dst;
      $Model->setFilter(new SQLCriteria($Model->id, '=', $id_src));
      $Model->update($Model->parent_id);

      // depois faço os updates para abrir um espaço para colocar o nó a ser movido com seus filhos
      $Model['rght'] = new SQLExpression('+', $Model->rght, $soma);
      $Model->setFilter(new SQLCriteria($Model->rght, '>=', $c));
      $Model->update($Model->rght);

      $Model['lft'] = new SQLExpression('+', $Model->lft, $soma);
      $Model->setFilter(new SQLCriteria($Model->lft, '>=', $c));
      $Model->update($Model->lft);

      // agora faço o update que vai mover o node para o destino
      // aqui eu acerto o left e o right do nó a ser movido e de seus filhos tendo como
      // base o right do nó destino. no caso de movimento reverso, preciso somar o fator
      // de soma nos lefts e rights, pois eles também estão sendo somados pelo "buraco"
      // aberto (updates logo acima)
      $Model['lft'] = new SQLExpression('+', array($c, new SQLExpression('-', $Model->lft, $a+$d)));
      $Model['rght'] = new SQLExpression('+', array($c, new SQLExpression('-', $Model->rght, $a+$d)));
      $Model['lvel'] = new SQLExpression('+', array($lvl_dst, new SQLExpression('-', $Model->lvel, $lvl_ant)));
      $Model->setFilter(new SQLCriteria($Model->lft, '>=', $a+$d), new SQLCriteria($Model->rght, '<=', $b+$d));
      $Model->update($Model->lft, $Model->rght, $Model->lvel);

      // agora acerto os rights e lefts que ficaram somados para fechar o espaço aberto
      $Model['rght'] = new SQLExpression('-', $Model->rght, $soma);
      $Model->setFilter(new SQLCriteria($Model->rght, '>=', $b+$d));
      $Model->update($Model->rght);

      $Model['lft'] = new SQLExpression('-', $Model->lft, $soma);
      $Model->setFilter(new SQLCriteria($Model->lft, '>=', $b+$d));
      $Model->update($Model->lft);
    }
    
    return true;
    
  }
  
  public function saveNode(Model $Model) {
    $Model->update();
  }
  
  public function _getTree(Model $Model, $node_id = null) {
    
    if (!($Model->Mapper instanceof DatabaseItfMapper)) {
      throw new Exception('Tipo de mapper '.get_class($Model->Mapper).' não suportado pelo TreeBehavior');
    }
    
    $Model->Mapper->setOrderBy(array($Model->lft, 'asc'));
    //$Model->Mapper->unsetFilter(); // o filtro deve estar limpo para buscar todos os registros
    
    // start with an empty $right stack
    $right = array();
    $ids = array();
    
    $array = array();
    
    if ($Model->select()) {
      foreach ($Model as $row) {
        // só faz o stack se for para retornar uma matriz
        //if ($plain === false) {
        // only check stack if there is one
        if (count($right) > 0) {
          // check if we should remove a node from the stack
          while ($right[count($right) - 1] < $row['rght']) {

            array_pop($right);
            array_pop($ids);
            if (empty($right))
              break;
          }
        }
        //}
        // guarda o level dele no result
        $row['lvel'] = count($right);

        $level = & $array;
        for ($i = 0; $i < count($right); $i++) {
          if (!is_array($level))
            $level = array();

          $level = & $level[$ids[$i]];
        }

        // caso seja para retornar varios objetos
        $level[$row['id']] = array('#node' => $row);
        
        //echo str_repeat('__',count($right)).$row['ALIAS']."\n";  
        //if ($plain === false) {
        // add this node to the stack  
        $right[] = $row['rght'];
        $ids[] = $row['id'];
        //}
      }
    }

    unset($level); //destroi referencia
    
    $this->tree_result = $array;
    
  }
  
}