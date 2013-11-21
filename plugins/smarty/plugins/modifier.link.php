<?php
/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty capitalize modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     capitalize<br>
 * Purpose:  capitalize words in the string
 *
 * {@internal {$string|capitalize:true:true} is the fastest option for MBString enabled systems }}
 *
 * @param string  $string    string to capitalize
 * @param boolean $uc_digits also capitalize "x123" to "X123"
 * @param boolean $lc_rest   capitalize first letters, lowercase all following letters "aAa" to "Aaa"
 * @return string capitalized string
 * @author Monte Ohrt <monte at ohrt dot com> 
 * @author Rodney Rehm
 */
function smarty_modifier_link($string, $params = array()) {
  global $Router;
  
  // não está dentro do sistema, não modifica os urls
  if (!defined('DJCK') || !isset($Router))
    return 'javascript:;';
  
  try {
    
    if (!is_array($params)) {
      $params = array();
      
      $route_params = $Router->getParams($string);

      $unparsed_params = func_get_args();
      array_shift($unparsed_params); // primeiro parametro é $string
      
      // joga todos os parametros passados (em ordem) para os parametros da rota
      $count = count($unparsed_params);
      for($i=0;$i<$count;$i++) {
        if ($route_params[$i]) {
          $params[ $route_params[$i] ] = $unparsed_params[$i];
          unset($unparsed_params[$i]); // tira os parametros que ja foram
        }
      }
      
      // se sobraram parametros ainda, jogar num get generico
      if (count($unparsed_params) > 0) {
        if (!isset($params['params'])) $params['params'] = array();
        
        // joga parametros que "sobraram" num get array chamado "params"
        foreach($unparsed_params as $p) {
          if (is_array($p))
            $p = implode(',', $p);
          $params['params'][] = $p;
        }
      }
    }
    
    // faz o roteamento reverso
    $string = $Router->generate($string, $params);
  } catch (Exception $e) {
    // houve algum erro, retornar algo "amigavel" pro usuario
    $string = 'javascript:alert(\'Erro ao gerar este link, contate o administrador do site\');';
  }

  // retorna o link formatado
  return $string;
}
