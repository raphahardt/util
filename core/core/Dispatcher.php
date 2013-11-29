<?php

namespace Djck;

use Djck\Core;

class DispatcherException extends CoreException {};

/**
 * Description of Dispatcher
 *
 * @author usuario
 */
class Dispatcher {
  
  function route($target, $params=array(), $response_code = null) {
    
    static $iterations = 0;
    ++$iterations;
    
    if ($iterations > 10) {
      throw new DispatcherException('A rota '.$target.' entrou em loop');
    }
    
    $controller = $target;
    
    // pega a ação e o controller a ser usado
    if (is_array($controller))
      list($class_name, $action) = each($controller);

    //acerto o path do meu view
    //$dir = $path;
    
    // controla o buffer
    if (ob_get_level() > 0)
      ob_end_clean(); // limpa qualquer coisa que vier de outro redirecionamento
    
    //ob_start(defined('OUTPUT_ZLIB') ? 'ob_gzhandler' : null); // inicia um novo controle de buffer
    ob_start(array('\Djck\Core', 'outputbuffer_handler')); // inicia um novo controle de buffer
    
    // verifica se a classe existe
    if (class_exists($class_name)) {
      
      // reflexão da classe do controller
      $rflc_class = new \ReflectionClass($class_name);
      
      $class = new $class_name(); //TODO: tirar q e colocar url de um lugar melhor

      $class->Request->addParams($params);
      if (isset($response_code)) $class->Response->statusCode($response_code);
      
      // alguns headers padrões
      if (defined('SITE_CHARSET')) {
        $class->Response->charset(SITE_CHARSET);
      }
      
      if (defined('SITE_OFFLINE') && SITE_OFFLINE === true) {
        $class->Response->header('X-Robots-Tag', 'noindex, nofollow');
      }
      
      // before
      if ($class->beforeExecute() !== false) {
        
        if (empty($action))
          $action = 'index';
        
        // preprend execute (executeAction)
        $action_original = $action;
        $action = 'execute'.ucfirst($action);
        
        if (!method_exists($class_name, $action)) {
          $this->route('error', null, 404);
          return;
        }
        
        // pegar todos os métodos publicos para implementacao AOP (aspecto)
        $rflc_methods = $rflc_class->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // reflexão do metodo e dos parametros dela
        $rflc_method = $rflc_class->getMethod($action);
        $rflc_parameters = $rflc_method->getParameters();
        
        // array que vai guardar os parametros que vão ser passados pro metodo
        // como view, etc..
        $params_to_pass = array();
        
        // verifica os parametros do metodo (ação) e passa os objetos corretos conforme
        // o metodo precise
        foreach ($rflc_parameters as $rflc_param) {
          $param_name = $rflc_param->getName();
          switch ($param_name) {
            case 'View':
              // se o metodo tiver $view, instanciar uma view automaticamente de 
              // acordo com o tipo de objeto que veio
              $class_view = $rflc_param->getClass()->getName();
              //Core::depends($class_view); // TODO depender da classe chamada, ou deixar assim...
              
              $view = new $class_view($class->viewPath."$action_original.tpl");
              
              $params_to_pass[] = $view;
              break;
            case 'token':
              // se vier $token como parametro, ele ja devolve o token enviado por request
              // tanto GET ou POST
              // Não usar $_GET direto (ver: http://us1.php.net/manual/en/filter.examples.sanitization.php )
              // a partir do PHP 5.2, é recomendado acessar essas variaveis usando filters, que limpam (sanitizam)
              // as variaveis para se tornarem seguras para uso (desde 05/11/13)
              $params_to_pass[] = filter_input(INPUT_REQUEST, 'token', 
                      FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
              break;
            default:
              // se for um parametro normal
              if (isset($params[ $param_name ])) {
                $params_to_pass[] = $params[ $param_name ];
              } else {
                // passa null para os parametros obrigatorios
                if ($rflc_param->isOptional() !== true)
                  $params_to_pass[] = null;
              }
          }
        }
        
        // chama o metodo       
        
        // AOP implement
        $aop_action = ucfirst($action);
        if (method_exists($class_name, "beforeExecuteAll")) {
          $ctrl_response = $class->{"beforeExecuteAll"}($params_to_pass);
        }
        if (method_exists($class_name, "before$aop_action")) {
          $ctrl_response = $class->execute("before$aop_action", $params_to_pass);
        }
        
        //$ctrl_response = call_user_func_array(array(&$class, $action), $params_to_pass);
        $ctrl_response = $class->execute($action, $params_to_pass);
        
        // AOP implement
        if (method_exists($class_name, "after$aop_action")) {
          $ctrl_response = $class->{"after$aop_action"}($ctrl_response);
        }
        if (method_exists($class_name, "afterExecuteAll")) {
          $ctrl_response = $class->{"afterExecuteAll"}($ctrl_response);
        }
        
        // after
        $class->afterExecute();
        
      }
      
      //if (_DEV && $class->response->type() !== 'json')
        //finish(false);
      
      // pega o conteudo do buffer
      $contents = ob_get_clean();
      //ob_end_flush();
      
      // manda os headers definidos automaticamente
      $class->Response->body($contents);
      $class->Response->send();
      
    } else {
      ob_end_flush();
      
      throw new DispatcherException('Controller ' . $class_name . ' ('.$target.') não foi registrado');
    }
  }
  
  function dispatch(Router $router, $url) {
    $match = $router->match(SITE_URL.'/'.$url);
    
    if ($match === false) {
      $match = $router->match(SITE_URL.'/error');
      $this->route($match['target'], null, 404);
    } else {
      $this->route($match['target'], $match['params']);
    }
  }
  
}
