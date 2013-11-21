<?php

class MenuNode implements ArrayAccess, Countable, Iterator {
  
  public $url;
  public $aco;
  public $icon;
  public $key;
  public $title;
  public $active;
  public $children;
  
  public function count() {
    return count($this->children);
  }

  public function current() {
    return current($this->children);
  }

  public function key() {
    return key($this->children);
  }

  public function next() {
    return next($this->children);
  }

  public function rewind() {
    reset($this->children);
  }

  public function valid() {
    return !is_null(current($this->children));
  }  
  
  public function offsetExists($offset) {
    return isset($this->{$offset});
  }

  public function offsetGet($offset) {
    return $this->{$offset};
  }

  public function offsetSet($offset, $value) {
    //if (is_null($offset)) throw new CoreException('Append em menu não é permitido');
    $this->{$offset} = $value;
  }

  public function offsetUnset($offset) {
    unset($this->{$offset});
  }

}

class Menu {

  private $_menu = array();
  private $_compiled_menu;

  public function add($title, $url, $icon = null, $aco = null) {

    // array de submenus
    $children = array();

    // verifica se tem filhos (argumentos array depois de $aco)
    $args = func_get_args();
    $args_c = count($args);
    if ($args_c > 4) {
      for ($i = 4; $i < $args_c; $i++) {
        $children[] = $args[$i];
      }
    }

    // retorna o array criado
    return array(
      '#title' => $title,
      '#url' => $url,
      '#aco' => $aco,
      '#icon' => $icon,
      '#children' => $children
    );
  }

  public function construct() {
    // referencia pra facilitar
    $_menu = & $this->_menu;

    // se menu ainda nao tiver sido criado, setar como array vazio
    if (!isset($_menu) || !is_array($_menu))
      $_menu = array();

    // verifica se tem filhos (argumentos array depois de $aco)
    $args = func_get_args();
    $args_c = count($args);
    if ($args_c > 0) {
      for ($i = 0; $i < $args_c; $i++) {
        $_menu[] = $args[$i];
      }
    }

    // retorna o array criado
    return true;
  }

  public function show() {

    if (isset($this->_compiled_menu))
      return $this->_compiled_menu;
    
    $this->_compiled_menu = $this->_show($this->_menu);
    return $this->_compiled_menu;
  }

  private function _show($menu) {
    
    $ms = array();
    foreach ($menu as $m) {

      if ($m['#aco']) {
        //if (!Usuario::hasPermission($m['#aco'])) {
          //continue;
        //}
      }
            
      // instancia menu
      $index = $m['#title']; 
      $ms[$index] = new MenuNode();

      if (!empty($m['#children'])) {
        $ms[$index]->children = $this->_show($m['#children']);
      }

      // verifica se algum filho está ativo, para ativar o pai também
      $children_active = false;
      if (is_array($ms[$index]->children)) {
        foreach ($ms[$index]->children as $c) {
          if ($c->active == true) {
            $children_active = true;
            break;
          }
        }
      }

      $o = &$ms[$index];
      $o->url = $m['#url'];
      $o->aco = $m['#aco'];
      $o->icon = $m['#icon'];
      $o->key = uniqid();
      $o->title = $index;
      $o->active = strpos($_GET['q'], $m['#url']) === 0 || $children_active;

      // apaga menus pai vazios
      if (is_array($ms[$index]->children) && count($ms[$index]->children) == 0) {
        unset($ms[$index]);
      }
    }
    return $ms;
  }

}

class ToolbarButton {

  public $action; // ação do botão
  public $default = false; // se o botão vai ser verde ou não
  public $icon; // icone do botao
  public $divider = false; // se é para dividir botões em grupos ou não
  public $javascript; // se o botão faz um javascript ou link
  public $title; // nome do botão

  function __construct($title, $action = '', $icon = null, $default = false, $javascript = null) {
    if ($title == '-') {
      // é um divider
      $this->divider = true;
    } else {
      // é um botão
      $this->title = $title;
      $this->action = $action;
      $this->default = $default == true;
      $this->icon = $icon;
      $this->javascript = $javascript;
    }
  }

}

class ToolbarSubmitButton extends ToolbarButton {

  public $formId; // qual form ele vai submeter
  public $type = 'submit'; // é um botão de submit

  function __construct($title, $formId = '', $icon = null, $default = false, $javascript = null) {
    $this->formId = $formId;
    parent::__construct($title, '', $icon, $default, $javascript);
  }

}

class ToolbarSelect {

  public $type = 'select'; // tipo de botao
  public $selected; // se o botão vai ser verde ou não
  public $options = array(); // opções do select
  public $label = ''; // valor inicial se não tiver nada selecionado

  function __construct($default, $values = array()) {

    $args = func_get_args();
    $args_c = count($args);

    // se for só um argumento
    if ($args_c == 1) {
      if (is_array($default)) {
        foreach ($default as &$opt) {
          // se não tiver key, deixar o mesmo que valor
          if (!is_array($opt)) {
            $opt = array($opt => $opt);
          }
        }
        unset($opt);
        $options = $default;
      } else {
        // se não for um array, cria um select de uma opção só
        $options[][$default] = $default;
      }
    } elseif ($args_c > 1) {
      // se for mais de um arg
      // valor default
      $this->selected = $default;

      if ($args_c == 2 && is_array($values)) {
        $options = $values;
      } else {

        // pega todos as opções e constroi a matriz pro select
        $options = array();
        for ($i = 1; $i < $args_c; $i++) {
          if (!is_array($args[$i])) {
            // se não tiver key, deixar o mesmo que valor
            $args[$i] = array($args[$i] => $args[$i]);
          }
          $options[] = $args[$i];
        }
      }
    }

    $tmp_label = $this->label;

    // cria o vetor do <select>
    foreach ($options as $option) {
      foreach ($option as $value => $label) {

        $o = new stdClass();
        $o->value = (string) $value;
        $o->label = $label;
        $o->selected = ($o->value === $this->selected);

        if ($o->selected) {
          $tmp_label = ($this->label ? $this->label . ': ' : '') . strip_tags($label);
        }

        // adiciona a opção
        $this->options[] = $o;
      }
    }
    // muda o label
    $this->label = $tmp_label;
  }

}

class ToolbarSelectJS extends ToolbarSelect {

  public $javascript = true;

  public function __construct($func, $default, $values = array()) {

    $args = func_get_args();
    $args_c = count($args);

    // se for só dois argumentos
    if ($args_c == 2) {
      if (is_array($default)) {
        foreach ($default as &$opt) {
          // se não tiver key, deixar o mesmo que valor
          if (!is_array($opt)) {
            $opt = array($opt => $opt);
          }
        }
        unset($opt);
        $options = $default;
      } else {
        // se não for um array, cria um select de uma opção só
        $options[][$default] = $default;
      }
    } elseif ($args_c > 2) {
      // se for mais de um arg
      // valor default
      $this->selected = $default;

      if ($args_c == 3 && is_array($values)) {
        $options = $values;
      } else {

        // pega todos as opções e constroi a matriz pro select
        $options = array();
        for ($i = 2; $i < $args_c; $i++) {
          if (!is_array($args[$i])) {
            // se não tiver key, deixar o mesmo que valor
            $args[$i] = array($args[$i] => $args[$i]);
          }
          $options[] = $args[$i];
        }
      }
    }

    $tmp_label = $this->label;

    // cria o vetor do <select>
    foreach ($options as $option) {
      foreach ($option as $value => $label) {

        $o = new stdClass();
        $o->value = (string) $value;
        $o->label = $label;
        $o->selected = ($o->value === $this->selected);
        $o->function = str_replace(':value', $o->value, $func);

        if ($o->selected) {
          $tmp_label = ($this->label ? $this->label . ': ' : '') . strip_tags($label);
        }

        // adiciona a opção
        $this->options[] = $o;
      }
    }
    // muda o label
    $this->label = $tmp_label;
  }

}