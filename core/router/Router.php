<?php

namespace Djck\router;

use Djck\Core;
use Djck\CoreException;

/**
 * Baseado no AutoRouter https://github.com/dannyvankooten/AltoRouter
 */

class RouterException extends CoreException {}

class Router {

  /**
   * Registred routes
   * @var array
   * @access protected 
   */
  protected $routes = array();
  
  /**
   * Registred routes with name. 
   * @var array
   * @access protected 
   */
  protected $namedRoutes = array();
  
  /**
   * Base path where routes will resolve
   * @var string
   * @access protected 
   */
  protected $basePath = '';

  /**
   * Set the base path.
   * Useful if you are running your application from a subdirectory.
   */
  public function __construct($baseurl) {
    $this->basePath = $baseurl;
  }

  /**
   * Map a route to a target
   *
   * @param string $method One of 4 HTTP Methods, or a pipe-separated list of multiple HTTP Methods (GET|POST|PUT|DELETE)
   * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
   * @param mixed $target The target where this route should point to. Can be anything.
   * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
   *
   */
  public function map($route, $target, $name = null) {

    if ($route != '*') {
      $route = $this->basePath . $route;
    }

    $this->routes[] = array($route, $target, $name);

    if ($name) {
      if (isset($this->namedRoutes[$name])) {
        throw new RouterException("Can not redeclare route '{$name}'");
      } else {
        $this->namedRoutes[$name] = array(
            'route' => $route,
            'controller' => is_array($target) ? key($target) : null
        );
      }
    }

    return;
  }

  /**
   * Reversed routing
   *
   * Generate the URL for a named route. Replace regexes with supplied parameters
   *
   * @param string $routeName The name of the route.
   * @param array @params Associative array of parameters to replace placeholders with.
   * @return string The URL of the route with named parameters in place.
   */
  public function generate($routeName, array $params = array()) {

    // Check if named route exists
    if (!isset($this->namedRoutes[$routeName])) {
      throw new RouterException("Route '{$routeName}' does not exist.");
    }

    // Replace named parameters
    $route = $this->namedRoutes[$routeName]['route'];
    $url = $route;
    $matches = array();

    //                  .1111111..2222222222....3333333333....44444.
    if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

      foreach ($matches as $i => $match) {
        list($block, $pre, $type, $param, $optional) = $match;

        if ($pre) {
          $block = substr($block, 1);
        }
        
        if ($optional || isset($params[$param])) {
          // tira dos matches encontrados e que ja foram subtituidos
          unset($matches[$i]);
        }

        if (isset($params[$param])) {
          $url = str_replace($block, $params[$param], $url);
          unset($params[$param]); // retira o parametro que ja foi
          
        } elseif ($optional) {
          $url = str_replace($pre . $block, '', $url);
          
        }
        
      }
    }
    
    // se sobrou parametros sem passar, jogar erro
    if (($c = count($matches)) > 0) {
      throw new RouterException("$c params left in '{$routeName}' route.");
    }
    
    // completa a url com os parametros que sobraram, e passa via get
    if (is_array($params) && count($params) > 0) {
      foreach ($params as $param => $value) {
        if (strpos($url, '?') !== false)
          $url .= '&';
        else
          $url .= '?';
        
        if (is_array($value)) {
          $tmp = array();
          foreach ($value as $k => $v) {
            $tmp[] = $param.'['.$k.']='.urlencode($v);
          }
          $url .= implode('&', $tmp);
        } else {
          $url .= $param.'='.urlencode($value);
        }
      }
    }

    return $url;
  }
  
  // devolve os parametros de url que foram definidos pra rota, num array associativo
  public function getParams($routeName) {
    // Check if named route exists
    if (!isset($this->namedRoutes[$routeName])) {
      throw new RouterException("Route '{$routeName}' does not exist.");
    }

    // Replace named parameters
    $route = $this->namedRoutes[$routeName]['route'];
    $params = array();
    
    //                  .1111111..2222222222....3333333333....44444.
    if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

      foreach ($matches as $match) {
        list($block, $pre, $type, $param, $optional) = $match;
        
        if ($param) {
          $params[] = $param;
        }
      }
    }
    
    return $params;
  }

  /**
   * Match a given Request Url against stored routes
   * @param string $requestUrl
   * @param string $requestMethod
   * @return array|boolean Array with route information on success, false on failure (no match).
   */
  public function match($requestUrl = null) {

    $params = array();
    $match = false;

    // set Request Url if it isn't passed as parameter
    if ($requestUrl === null) {
      $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    }
    $baseUrlLength = strlen($this->basePath)+1; // +1 = barra

    // Strip query string (?a=b) from Request Url
    if (($strpos = strpos($requestUrl, '?')) !== false) {
      $requestUrl = substr($requestUrl, 0, $strpos);
    }
    
    // deixa a barra no final sempre opcional ----- mudança
    if (strlen($requestUrl) - $baseUrlLength > 1 && substr($requestUrl, -1) === '/') {
      $requestUrl = substr($requestUrl, 0, -1);
    }

    // Force request_order to be GP
    // http://www.mail-archive.com/internals@lists.php.net/msg33119.html
    //$_REQUEST = array_merge($_GET, $_POST);

    foreach ($this->routes as $handler) {
      list($_route, $target, $name) = $handler;
      
      // verifica se o target é um link pra outra rota ou se é um controller mesmo
      if (is_string($target)) {
        list($url, $method) = explode('#', $target);
        if (!$method)
          $method = null;

        if (isset($this->namedRoutes[$url])) {
          $class = $this->namedRoutes[$url]['controller'];
          if ($class)
            $target = array($class => $method);
        }
        unset($url, $method, $class);
        // se ainda sim não vier um array, o controller não foi definido corretamente
        if (!is_array($target)) {
          throw new RouterException('Rota '.$target.' não foi definida corretamente.'.
                  'Verifique se ela está sendo linkada com uma rota que não tenha nome ou controller definido');
        }
      }
      
      //echo $requestUrl,'<br>',$_route,'<br>';
      
      // Check for a wildcard (matches all)
      if ($_route === '*') {
        $match = true;
      } elseif (isset($_route[0]) && $_route[0] === '@') {
        $match = preg_match('`' . substr($_route, 1) . '`', $requestUrl, $params);
      } else {
        
        $route = null;
        $regex = false;
        $j = 0;
        $n = isset($_route[0]) ? $_route[0] : null;
        $i = 0;

        // Find the longest non-regex substring and match it against the URI
        while (true) {
          if (!isset($_route[$i])) {
            break;
          } elseif (false === $regex) {
            $c = $n;
            $regex = $c === '[' || $c === '(' || $c === '.';
            if (false === $regex && false !== isset($_route[$i + 1])) {
              $n = $_route[$i + 1];
              $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
            }
            if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
              continue 2;
            }
            $j++;
          }
          $route .= $_route[$i++];
        }

        $regex = $this->compileRoute($route);
        $match = preg_match($regex, $requestUrl, $params);
      }

      if (($match == true || $match > 0)) {

        if ($params) {
          foreach ($params as $key => $value) {
            if (is_numeric($key))
              unset($params[$key]);
          }
        }

        return array(
            'target' => $target,
            'params' => $params,
            'name' => $name
        );
      }
    }
    return false;
  }

  /**
   * Compile the regex for a given route (EXPENSIVE)
   */
  private function compileRoute($route) {
    if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

      $match_types = array(
          'i' => '[0-9]++',
          'a' => '[0-9A-Za-z]++',
          'h' => '[0-9A-Fa-f]++',
          '*' => '.+?',
          '**' => '.++',
          '' => '[^/]++'
      );

      foreach ($matches as $match) {
        list($block, $pre, $type, $param, $optional) = $match;

        if (isset($match_types[$type])) {
          $type = $match_types[$type];
        }
        if ($pre === '.') {
          $pre = '\.';
        }

        //Older versions of PCRE require the 'P' in (?P<named>)
        $pattern = '(?:'
                . ($pre !== '' ? $pre : null)
                . '('
                . ($param !== '' ? "?P<$param>" : null)
                . $type
                . '))'
                . ($optional !== '' ? '?' : null);

        $route = str_replace($block, $pattern, $route);
      }
    }
    return "`^$route$`";
  }

}