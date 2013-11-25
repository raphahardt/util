<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

if (!function_exists('def')) {
  
  /**
   * Define uma constante, somente se ela não tiver sido definida antes
   * O comportamento padrão da define() 
   * @param type $constant
   * @param type $value
   */
  function def($constant, $value) {
    if (!defined($constant)) {
      define($constant, $value, false);
    }
  }
  
}

if (!function_exists('enum')) {
  
  function enum() {
    $args = func_get_args();
    foreach ($args as $key => $arg) {
      if (defined($arg)) {
        throw new RuntimeException('Redefinition of defined constant ' . $arg);
      }

      define($arg, $key);
    }
  }

}

if (!function_exists('cfg')) {
  
  /**
   * Carrega um arquivo de configuração e retorna um array com as configurações do arquivo
   * @param string $file Nome do arquivo de configuração
   * @return array
   * @throws CoreException
   */
  function cfg($file) {
    Core::depends('Parser');
    
    // a funcao irá retornar a config "mergida" do core e do app, automaticamente
    // sempre as config de app sobrescrevem/adicionam as de core
    $file_ = DS.'cfg'.DS.$file. Parser::$extension;
    
    // nenhuma cfg foi encontrada
    $exists_core = is_file(DJCK.$file_);
    $exists_app = is_file(APP_PATH.$file_);
    if (!$exists_core && !$exists_app) {
      throw new CoreException('Config "'.$file.'" não encontrada');
    }
    
    $cfg_core = $exists_core ? Parser::decode(file_get_contents(DJCK.$file_))     : null;
    $cfg_app  = $exists_app  ? Parser::decode(file_get_contents(APP_PATH.$file_)) : null;
    
    $cfg = array_merge_recursive((array)$cfg_core, (array)$cfg_app);
    array_walk_recursive($cfg, '_iterator_normalize_cfg');
    
    return $cfg;
    
  }
  
  /**
   * Arruma algumas variaveis do sistema para o valor correto, e acerta algumas coisas
   * como separador de pastas (/) para o correto
   * @param string $config
   * @return string
   */
  function _iterator_normalize_cfg(&$config, $key) {
    if (preg_match('/\$([A-Z_]+)/', $config, $match)) {
      $config = str_replace($match[0], constant($match[1]), $config);
    }
    if (strpos($config, '\\') !== false) {
      $config = str_replace('\\', DS, $config);
    }
  }
  
  /**
   * Retorna se a string parece com uma indicação de diretorio ou não
   * ex: c:\teste\arquivo.txt => true
   *     10/12/2013 => false
   *     input/teste => true    // discutivel
   * @deprecated
   * @param string $string
   * @param string $helper String que ajuda a identificar se $string é realmente um diretorio
   * @return boolean
   */
  function _seems_path($string, $helper) {
    // se a string começar com / ou \, já considerar diretorio
    if (strpos($string, '/') === 0 || strpos($string, '\\') === 0) {
      return true;
    }
    // verifica se o helper contem algumas das palavras abaixo, se tiver, é um diretorio
    foreach (array('path', 'dir', 'folder') as $h) {
      if (strpos($helper, $h) !== false) {
        return true;
      }
    }
    // se a string contiver barra, mas no meio da string
    if (strpos($string, '/') !== false || strpos($string, '\\') !== false) {
      // FIXME
      // não terminei, talvez não seja necessaria essa função......
    }
    return false;
  }
  
}

if (!function_exists('class_alias')) {
  
  function class_alias($original, $alias) {
    // se for menor que PHP5.3, a unica forma é usar 'extends', mas não é uma soluçao elegante
    // if (!version_compare(PHP_VERSION, '5.3.0', '>='))
    eval("class $alias extends $original {};");
  }
  
}

if (!function_exists('env')) {

  /**
   * Gets an environment variable from available sources, and provides emulation
   * for unsupported or inconsistent environment variables (i.e. DOCUMENT_ROOT on
   * IIS, or SCRIPT_NAME in CGI mode). Also exposes some additional custom
   * environment information.
   *
   * @param string $key Environment variable name.
   * @return string Environment variable setting.
   * @link http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#env
   */
  function env($key) {
    if ($key === 'HTTPS') {
      if (isset($_SERVER['HTTPS'])) {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
      }
      return (strpos(env('SCRIPT_URI'), 'https://') === 0);
    }

    if ($key === 'SCRIPT_NAME') {
      if (env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
        $key = 'SCRIPT_URL';
      }
    }

    $val = null;
    if (isset($_SERVER[$key])) {
      $val = $_SERVER[$key];
    } elseif (isset($_ENV[$key])) {
      $val = $_ENV[$key];
    } elseif (getenv($key) !== false) {
      $val = getenv($key);
    }

    if ($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR')) {
      $addr = env('HTTP_PC_REMOTE_ADDR');
      if ($addr !== null) {
        $val = $addr;
      }
    }

    if ($val !== null) {
      return $val;
    }

    switch ($key) {
      case 'DOCUMENT_ROOT':
        $name = env('SCRIPT_NAME');
        $filename = env('SCRIPT_FILENAME');
        $offset = 0;
        if (!strpos($name, '.php')) {
          $offset = 4;
        }
        return substr($filename, 0, -(strlen($name) + $offset));
      case 'PHP_SELF':
        return str_replace(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
      case 'CGI_MODE':
        return (PHP_SAPI === 'cgi');
      case 'HTTP_BASE':
        $host = env('HTTP_HOST');
        $parts = explode('.', $host);
        $count = count($parts);

        if ($count === 1) {
          return '.' . $host;
        } elseif ($count === 2) {
          return '.' . $host;
        } elseif ($count === 3) {
          $gTLD = array(
              'aero',
              'asia',
              'biz',
              'cat',
              'com',
              'coop',
              'edu',
              'gov',
              'info',
              'int',
              'jobs',
              'mil',
              'mobi',
              'museum',
              'name',
              'net',
              'org',
              'pro',
              'tel',
              'travel',
              'xxx'
          );
          if (in_array($parts[1], $gTLD)) {
            return '.' . $host;
          }
        }
        array_shift($parts);
        return '.' . implode('.', $parts);
    }
    return null;
  }

}

if (!function_exists('g_token')) {
  
  /**
   * Gera uma string token unica
   * @return string
   */
  function g_token() {
    return md5(uniqid()).mt_rand(5, 15).mt_rand(0, 5);
  }
  
}

if (!function_exists('fmt_value')) {
  
  /**
   * Formata um valor com um sufixo quando o número encontra o numero de casas decimais
   * definidas em $suffix
   * Exemplos: 1 200 = 1,2 mil
   *           20 413 000 = 20,4 milhões
   * @param type $value Valor a ser formatado
   * @param type $base Base númerica do valor a ser formatado. Padrão é 1000
   * @param type $decimals Quantas casas decimais mostrar após a virgula. Padrão é 1
   * @param type $suffix O que mostrar após o número quando encontrar o log() do valor na
   *                     $base definida. Deve ser um array onde o key é o log() desejado
   *                     e o valor pode ser uma string ou um array com dois valores (um
   *                     para a escrita no singular e outro para escrita no plural)
   */
  function fmt_value($value, $base=1000, $decimals=1, $suffix=array(
      1=>'mil',
      2=>array('milhão','milhões'),
      3=>array('bilhão','bilhões')) ) {
    $key = floor(log($value, $base)); // pega o log que vai ser o key dos sufixos
    $num = $value / (pow($base, $key)); // cria o numero que vai ser o valor a ser mostrado
    $suf = (is_array($suffix[$key])) ? 
             ((int)$num != 1 ? $suffix[$key][1] : $suffix[$key][0]) :
             $suffix[$key];
    
    $num_fmted = round($num, $decimals);
    
    return "$num_fmted $suf";
  }
  
  /**
   * Formata um valor em sufixo em bytes
   * Exemplo: 1024 = 1 KB
   * @param type $value Valor a ser formatado
   * @return string String com valor formatado, com sufixo (B,KB,MB,GB,TB)
   */
  function fmt_bytes($value) {
    return fmt_value($value, 1000, 3, array(
       0=>'B',
       1=>'KB',
       2=>'MB',
       3=>'GB',
       4=>'TB',
    ));
  }
  
  /**
   * Formata um valor em sufixo em bibytes
   * Exemplo: 1024 = 1 KiB
   * @param type $value Valor a ser formatado
   * @return string String com valor formatado, com sufixo (B,KiB,MiB,GiB,TiB)
   */
  function fmt_bibytes($value) {
    return fmt_value($value, 1024, 3, array(
       0=>'B',
       1=>'KiB',
       2=>'MiB',
       3=>'GiB',
       4=>'TiB',
    ));
  }
  
}

if (!function_exists('json')) {
  
  if (!defined('JSON_FORCE_OBJECT')) {
    define('JSON_FORCE_OBJECT', 16);
  }
  
  /**
   * Codifica um array para uma string json
   * Normaliza as funções das versões 5 até 5.4
   * @return string
   */
  function json($string, $options = 0) {
    if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
      return json_encode($string, $options);
    } else {
      if (($options & JSON_FORCE_OBJECT) == JSON_FORCE_OBJECT) {
        $new_string = array();
        foreach ($string as $key => $v) {
          $new_string[(string)$key] = $v;
        }
        $string = $new_string;
        unset($new_string);
      }
      
      return json_encode($string);
    }
  }
  
}

if (!function_exists('array_column')) {

  /**
   * Returns the values from a single column of the input array, identified by
   * the $columnKey.
   *
   * Optionally, you may provide an $indexKey to index the values in the returned
   * array by the values from the $indexKey column in the input array.
   *
   * @param array $input A multi-dimensional array (record set) from which to pull
   *                     a column of values.
   * @param mixed $columnKey The column of values to return. This value may be the
   *                         integer key of the column you wish to retrieve, or it
   *                         may be the string key name for an associative array.
   * @param mixed $indexKey (Optional.) The column to use as the index/keys for
   *                        the returned array. This value may be the integer key
   *                        of the column, or it may be the string key name.
   * @return array
   */
  function array_column($input = null, $columnKey = null, $indexKey = null) {
    // Using func_get_args() in order to check for proper number of
    // parameters and trigger errors exactly as the built-in array_column()
    // does in PHP 5.5.
    $argc = func_num_args();
    $params = func_get_args();

    if ($argc < 2) {
      trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
      return null;
    }

    if (!is_array($params[0])) {
      trigger_error('array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given', E_USER_WARNING);
      return null;
    }

    if (!is_int($params[1]) && !is_float($params[1]) && !is_string($params[1]) && $params[1] !== null && !(is_object($params[1]) && method_exists($params[1], '__toString'))
    ) {
      trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
      return false;
    }

    if (isset($params[2]) && !is_int($params[2]) && !is_float($params[2]) && !is_string($params[2]) && !(is_object($params[2]) && method_exists($params[2], '__toString'))
    ) {
      trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
      return false;
    }

    $paramsInput = $params[0];
    $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

    $paramsIndexKey = null;
    if (isset($params[2])) {
      if (is_float($params[2]) || is_int($params[2])) {
        $paramsIndexKey = (int) $params[2];
      } else {
        $paramsIndexKey = (string) $params[2];
      }
    }

    $resultArray = array();

    foreach ($paramsInput as $row) {

      $key = $value = null;
      $keySet = $valueSet = false;

      if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
        $keySet = true;
        $key = (string) $row[$paramsIndexKey];
      }

      if ($paramsColumnKey === null) {
        $valueSet = true;
        $value = $row;
      } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
        $valueSet = true;
        $value = $row[$paramsColumnKey];
      }

      if ($valueSet) {
        if ($keySet) {
          $resultArray[$key] = $value;
        } else {
          $resultArray[] = $value;
        }
      }
    }

    return $resultArray;
  }

}

if (!function_exists('dump')) {
  
  function dump($var) {
    $props = _var_props($var);
    
    $colors = array(
        'Dbc.php' => array('salmon', 'white'),
        'FileMapper.php' => array('yellow', 'black'),
    );
    list($backc, $forec) = isset($colors[ $props['file'] ]) ? 
            $colors[ $props['file'] ] : 
            array('cyan', 'blue');
    echo '<pre style="border:1px solid '.$backc.';background:white;font-family:Consolas,monospaced;font-size:13px;">';
    echo '<div style="color:'.$forec.';background:'.$backc.';padding:10px;">';
    echo '<strong>Nome: '.$props['name'].'</strong><br>';
    echo '<strong>Arquivo: '.$props['file'].' linha '.$props['line'].'</strong><br>';
    echo '</div><div style="padding:10px">';
    if (is_array($var) || is_object($var))
      print_r($var);
    else
      var_dump($var);
    /*print_r(array(
        DJCK,
        CORE_PATH,
        APP_PATH,
        SITE_FULL_URL,
        SITE_URL,
        STATIC_URL,
    ));*/
    echo '</div>';
    echo '</pre>';
    //exit;
  }
  
  function _var_props( $v ) {
    $trace = debug_backtrace();
    $vLine = file( $trace[1]['file'] );
    $fLine = $vLine[ $trace[1]['line'] - 1 ];
    preg_match( "#dump\((\\$?(\w+)(\s*(::|\->)\w+\s*\(?[^\)]*\)?|\[[^\]]*\])*)#", $fLine, $match );
    return array(
        'name' => $match[1] ? $match[1] : 'unknown',
        'file' => basename($trace[1]['file']),
        'line' => $trace[1]['line'],
        'line_string' => $fLine,
    );
  }
  
  function finish($exit=true) {
    $now = microtime(true);
    $times = array(
        FINISH_BASICS - START,
        FINISH_CORE_LOAD - FINISH_BASICS,
        FINISH_DEFS - FINISH_CORE_LOAD,
        FINISH_LOAD - FINISH_DEFS,
        $now - FINISH_LOAD,
    );
    $end = microtime(true) - START;
    echo '<pre style="border:1px solid purple;background:white;font-family:Consolas,monospaced;font-size:13px;">';
    echo '<div style="padding:10px;">';
    echo '<strong>Bootstrap basics:</strong> '.($times[0]*1000).'ms ('.(round($times[0], 2)).' segundos)<br>';
    echo '<strong>Core loading:</strong> '.    ($times[1]*1000).'ms ('.(round($times[1], 2)).' segundos)<br>';
    echo '<strong>Definitions:</strong> '.     ($times[2]*1000).'ms ('.(round($times[2], 2)).' segundos)<br>';
    echo '<strong>Load classes:</strong> '.    ($times[3]*1000).'ms ('.(round($times[3], 2)).' segundos)<br>';
    echo '<strong>Application:</strong> '.     ($times[4]*1000).'ms ('.(round($times[4], 2)).' segundos)<br>';
    echo '</div>';
    echo '<div style="color:white;background:purple;padding:10px;">';
    echo '<strong>Total: '.($end*1000).'ms ('.(round($end, 2)).' segundos)</strong><br>';
    echo '</div></pre>';
    if ($exit) exit;
  }
  
}

if (!function_exists('str_putcsv')) {

  /**
   * Transforma um array em uma string em CSV.
   * ex:
   * array('col1', 'col2', 'col3 aaa', '', 'col5') -> col1,col2,"col3 aaa",,col5
   * @param array $input Array que será convertido
   * @param string $delimiter Caractere que será o separador. Padrão: vírgula
   * @param string $enclosure Caractere que será o encapsulador. Padrão: aspas
   * @return string
   */
  function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
    // Open a memory "file" for read/write...
    $fp = fopen('php://temp', 'r+');
    // ... write the $input array to the "file" using fputcsv()...
    fputcsv($fp, $input, $delimiter, $enclosure);
    // ... rewind the "file" so we can read what we just wrote...
    rewind($fp);
    // ... read the entire line into a variable...
    $data = fgets($fp);
    // ... close the "file"...
    fclose($fp);
    // ... and return the $data to the caller, with the trailing newline from fgets() removed.
    return rtrim($data, "\n");
  }

}

if (!function_exists('str_getcsv')) {

  /**
   * Transforma uma string em CSV em um array com os valores.
   * ex:
   * col1,col2,"col3 aaa",,col5 -> array('col1', 'col2', 'col3 aaa', '', 'col5')
   * @param string $input Linha CSV a ser convertida
   * @param string $delimiter Caractere que será o separador. Padrão: vírgula
   * @param string $enclosure Caractere que será o encapsulador. Padrão: aspas
   * @param string $escape Caractere que será o escape do encapsulador e de caracteres especiais
   * @return array
   */
  function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = '\\') {
    // Open a memory "file" for read/write...
    $fp = fopen('php://temp', 'r+');
    // ... write the $input array to the "file" using fputcsv()...
    fputs($fp, $input);
    // ... rewind the "file" so we can read what we just wrote...
    rewind($fp);
    // ... read the entire line into a variable...
    if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
      $data = fgetcsv($fp, 4196, $delimiter, $enclosure, $escape);
    } else {
      $data = fgetcsv($fp, 4196, $delimiter, $enclosure);
    }
    // ... close the "file"...
    fclose($fp);
    // ... and return the $data to the caller, with the trailing newline from fgets() removed.
    return $data;
  }

}

if (!function_exists('seems_utf8')) {
  
  /**
   * FUNCAO COPIADA DE: WordPress
   * Checks to see if a string is utf8 encoded.
   *
   * NOTE: This function checks for 5-Byte sequences, UTF8
   *       has Bytes Sequences with a maximum length of 4.
   *
   * @author bmorel at ssi dot fr (modified)
   * @since 1.2.1
   *
   * @param string $str The string to be checked
   * @return bool True if $str fits a UTF-8 model, false otherwise.
   */
  function seems_utf8($str) {
    $length = strlen($str);
    for ($i = 0; $i < $length; $i++) {
      $c = ord($str[$i]);
      if ($c < 0x80)
        $n = 0;# 0bbbbbbb
      elseif (($c & 0xE0) == 0xC0)
        $n = 1;# 110bbbbb
      elseif (($c & 0xF0) == 0xE0)
        $n = 2;# 1110bbbb
      elseif (($c & 0xF8) == 0xF0)
        $n = 3;# 11110bbb
      elseif (($c & 0xFC) == 0xF8)
        $n = 4;# 111110bb
      elseif (($c & 0xFE) == 0xFC)
        $n = 5;# 1111110b
      else
        return false;# Does not match any model
      for ($j = 0; $j < $n; $j++) { # n bytes matching 10bbbbbb follow ?
        if (( ++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
          return false;
      }
    }
    return true;
  }

}

if (!function_exists('utf8d')) {
  
  /**
   * Função melhorada do utf8_decode()
   * @param string $string
   * @return string
   */
  function utf8d($string) {
    if (!seems_utf8($string)) {
      return $string;
    }
    return utf8_decode($string);
  }
  
}

if (!function_exists('utf8e')) {
  
  /**
   * Função melhorada do utf8_encode()
   * @param string $string
   * @return string
   */
  function utf8e($string) {
    if (seems_utf8($string)) {
      return $string;
    }
    return utf8_encode($string);
  }
  
}

if (!function_exists('remove_accents')) {
  
  /**
   * FUNCAO COPIADA DE: WordPress
   * Converts all accent characters to ASCII characters.
   *
   * If there are no accent characters, then the string given is just returned.
   *
   * @since 1.2.1
   *
   * @param string $string Text that might have accent characters
   * @return string Filtered string with replaced "nice" characters.
   * @author WordPress.org
   */
  function remove_accents($string) {
    if (!preg_match('/[\x80-\xff]/', $string))
      return $string;

    if (seems_utf8($string)) {
      $chars = array(
          // Decompositions for Latin-1 Supplement
          chr(194) . chr(170) => 'a', chr(194) . chr(186) => 'o',
          chr(195) . chr(128) => 'A', chr(195) . chr(129) => 'A',
          chr(195) . chr(130) => 'A', chr(195) . chr(131) => 'A',
          chr(195) . chr(132) => 'A', chr(195) . chr(133) => 'A',
          chr(195) . chr(134) => 'AE', chr(195) . chr(135) => 'C',
          chr(195) . chr(136) => 'E', chr(195) . chr(137) => 'E',
          chr(195) . chr(138) => 'E', chr(195) . chr(139) => 'E',
          chr(195) . chr(140) => 'I', chr(195) . chr(141) => 'I',
          chr(195) . chr(142) => 'I', chr(195) . chr(143) => 'I',
          chr(195) . chr(144) => 'D', chr(195) . chr(145) => 'N',
          chr(195) . chr(146) => 'O', chr(195) . chr(147) => 'O',
          chr(195) . chr(148) => 'O', chr(195) . chr(149) => 'O',
          chr(195) . chr(150) => 'O', chr(195) . chr(153) => 'U',
          chr(195) . chr(154) => 'U', chr(195) . chr(155) => 'U',
          chr(195) . chr(156) => 'U', chr(195) . chr(157) => 'Y',
          chr(195) . chr(158) => 'TH', chr(195) . chr(159) => 's',
          chr(195) . chr(160) => 'a', chr(195) . chr(161) => 'a',
          chr(195) . chr(162) => 'a', chr(195) . chr(163) => 'a',
          chr(195) . chr(164) => 'a', chr(195) . chr(165) => 'a',
          chr(195) . chr(166) => 'ae', chr(195) . chr(167) => 'c',
          chr(195) . chr(168) => 'e', chr(195) . chr(169) => 'e',
          chr(195) . chr(170) => 'e', chr(195) . chr(171) => 'e',
          chr(195) . chr(172) => 'i', chr(195) . chr(173) => 'i',
          chr(195) . chr(174) => 'i', chr(195) . chr(175) => 'i',
          chr(195) . chr(176) => 'd', chr(195) . chr(177) => 'n',
          chr(195) . chr(178) => 'o', chr(195) . chr(179) => 'o',
          chr(195) . chr(180) => 'o', chr(195) . chr(181) => 'o',
          chr(195) . chr(182) => 'o', chr(195) . chr(184) => 'o',
          chr(195) . chr(185) => 'u', chr(195) . chr(186) => 'u',
          chr(195) . chr(187) => 'u', chr(195) . chr(188) => 'u',
          chr(195) . chr(189) => 'y', chr(195) . chr(190) => 'th',
          chr(195) . chr(191) => 'y', chr(195) . chr(152) => 'O',
          // Decompositions for Latin Extended-A
          chr(196) . chr(128) => 'A', chr(196) . chr(129) => 'a',
          chr(196) . chr(130) => 'A', chr(196) . chr(131) => 'a',
          chr(196) . chr(132) => 'A', chr(196) . chr(133) => 'a',
          chr(196) . chr(134) => 'C', chr(196) . chr(135) => 'c',
          chr(196) . chr(136) => 'C', chr(196) . chr(137) => 'c',
          chr(196) . chr(138) => 'C', chr(196) . chr(139) => 'c',
          chr(196) . chr(140) => 'C', chr(196) . chr(141) => 'c',
          chr(196) . chr(142) => 'D', chr(196) . chr(143) => 'd',
          chr(196) . chr(144) => 'D', chr(196) . chr(145) => 'd',
          chr(196) . chr(146) => 'E', chr(196) . chr(147) => 'e',
          chr(196) . chr(148) => 'E', chr(196) . chr(149) => 'e',
          chr(196) . chr(150) => 'E', chr(196) . chr(151) => 'e',
          chr(196) . chr(152) => 'E', chr(196) . chr(153) => 'e',
          chr(196) . chr(154) => 'E', chr(196) . chr(155) => 'e',
          chr(196) . chr(156) => 'G', chr(196) . chr(157) => 'g',
          chr(196) . chr(158) => 'G', chr(196) . chr(159) => 'g',
          chr(196) . chr(160) => 'G', chr(196) . chr(161) => 'g',
          chr(196) . chr(162) => 'G', chr(196) . chr(163) => 'g',
          chr(196) . chr(164) => 'H', chr(196) . chr(165) => 'h',
          chr(196) . chr(166) => 'H', chr(196) . chr(167) => 'h',
          chr(196) . chr(168) => 'I', chr(196) . chr(169) => 'i',
          chr(196) . chr(170) => 'I', chr(196) . chr(171) => 'i',
          chr(196) . chr(172) => 'I', chr(196) . chr(173) => 'i',
          chr(196) . chr(174) => 'I', chr(196) . chr(175) => 'i',
          chr(196) . chr(176) => 'I', chr(196) . chr(177) => 'i',
          chr(196) . chr(178) => 'IJ', chr(196) . chr(179) => 'ij',
          chr(196) . chr(180) => 'J', chr(196) . chr(181) => 'j',
          chr(196) . chr(182) => 'K', chr(196) . chr(183) => 'k',
          chr(196) . chr(184) => 'k', chr(196) . chr(185) => 'L',
          chr(196) . chr(186) => 'l', chr(196) . chr(187) => 'L',
          chr(196) . chr(188) => 'l', chr(196) . chr(189) => 'L',
          chr(196) . chr(190) => 'l', chr(196) . chr(191) => 'L',
          chr(197) . chr(128) => 'l', chr(197) . chr(129) => 'L',
          chr(197) . chr(130) => 'l', chr(197) . chr(131) => 'N',
          chr(197) . chr(132) => 'n', chr(197) . chr(133) => 'N',
          chr(197) . chr(134) => 'n', chr(197) . chr(135) => 'N',
          chr(197) . chr(136) => 'n', chr(197) . chr(137) => 'N',
          chr(197) . chr(138) => 'n', chr(197) . chr(139) => 'N',
          chr(197) . chr(140) => 'O', chr(197) . chr(141) => 'o',
          chr(197) . chr(142) => 'O', chr(197) . chr(143) => 'o',
          chr(197) . chr(144) => 'O', chr(197) . chr(145) => 'o',
          chr(197) . chr(146) => 'OE', chr(197) . chr(147) => 'oe',
          chr(197) . chr(148) => 'R', chr(197) . chr(149) => 'r',
          chr(197) . chr(150) => 'R', chr(197) . chr(151) => 'r',
          chr(197) . chr(152) => 'R', chr(197) . chr(153) => 'r',
          chr(197) . chr(154) => 'S', chr(197) . chr(155) => 's',
          chr(197) . chr(156) => 'S', chr(197) . chr(157) => 's',
          chr(197) . chr(158) => 'S', chr(197) . chr(159) => 's',
          chr(197) . chr(160) => 'S', chr(197) . chr(161) => 's',
          chr(197) . chr(162) => 'T', chr(197) . chr(163) => 't',
          chr(197) . chr(164) => 'T', chr(197) . chr(165) => 't',
          chr(197) . chr(166) => 'T', chr(197) . chr(167) => 't',
          chr(197) . chr(168) => 'U', chr(197) . chr(169) => 'u',
          chr(197) . chr(170) => 'U', chr(197) . chr(171) => 'u',
          chr(197) . chr(172) => 'U', chr(197) . chr(173) => 'u',
          chr(197) . chr(174) => 'U', chr(197) . chr(175) => 'u',
          chr(197) . chr(176) => 'U', chr(197) . chr(177) => 'u',
          chr(197) . chr(178) => 'U', chr(197) . chr(179) => 'u',
          chr(197) . chr(180) => 'W', chr(197) . chr(181) => 'w',
          chr(197) . chr(182) => 'Y', chr(197) . chr(183) => 'y',
          chr(197) . chr(184) => 'Y', chr(197) . chr(185) => 'Z',
          chr(197) . chr(186) => 'z', chr(197) . chr(187) => 'Z',
          chr(197) . chr(188) => 'z', chr(197) . chr(189) => 'Z',
          chr(197) . chr(190) => 'z', chr(197) . chr(191) => 's',
          // Decompositions for Latin Extended-B
          chr(200) . chr(152) => 'S', chr(200) . chr(153) => 's',
          chr(200) . chr(154) => 'T', chr(200) . chr(155) => 't',
          // Euro Sign
          chr(226) . chr(130) . chr(172) => 'E',
          // GBP (Pound) Sign
          chr(194) . chr(163) => '',
          // Vowels with diacritic (Vietnamese)
          // unmarked
          chr(198) . chr(160) => 'O', chr(198) . chr(161) => 'o',
          chr(198) . chr(175) => 'U', chr(198) . chr(176) => 'u',
          // grave accent
          chr(225) . chr(186) . chr(166) => 'A', chr(225) . chr(186) . chr(167) => 'a',
          chr(225) . chr(186) . chr(176) => 'A', chr(225) . chr(186) . chr(177) => 'a',
          chr(225) . chr(187) . chr(128) => 'E', chr(225) . chr(187) . chr(129) => 'e',
          chr(225) . chr(187) . chr(146) => 'O', chr(225) . chr(187) . chr(147) => 'o',
          chr(225) . chr(187) . chr(156) => 'O', chr(225) . chr(187) . chr(157) => 'o',
          chr(225) . chr(187) . chr(170) => 'U', chr(225) . chr(187) . chr(171) => 'u',
          chr(225) . chr(187) . chr(178) => 'Y', chr(225) . chr(187) . chr(179) => 'y',
          // hook
          chr(225) . chr(186) . chr(162) => 'A', chr(225) . chr(186) . chr(163) => 'a',
          chr(225) . chr(186) . chr(168) => 'A', chr(225) . chr(186) . chr(169) => 'a',
          chr(225) . chr(186) . chr(178) => 'A', chr(225) . chr(186) . chr(179) => 'a',
          chr(225) . chr(186) . chr(186) => 'E', chr(225) . chr(186) . chr(187) => 'e',
          chr(225) . chr(187) . chr(130) => 'E', chr(225) . chr(187) . chr(131) => 'e',
          chr(225) . chr(187) . chr(136) => 'I', chr(225) . chr(187) . chr(137) => 'i',
          chr(225) . chr(187) . chr(142) => 'O', chr(225) . chr(187) . chr(143) => 'o',
          chr(225) . chr(187) . chr(148) => 'O', chr(225) . chr(187) . chr(149) => 'o',
          chr(225) . chr(187) . chr(158) => 'O', chr(225) . chr(187) . chr(159) => 'o',
          chr(225) . chr(187) . chr(166) => 'U', chr(225) . chr(187) . chr(167) => 'u',
          chr(225) . chr(187) . chr(172) => 'U', chr(225) . chr(187) . chr(173) => 'u',
          chr(225) . chr(187) . chr(182) => 'Y', chr(225) . chr(187) . chr(183) => 'y',
          // tilde
          chr(225) . chr(186) . chr(170) => 'A', chr(225) . chr(186) . chr(171) => 'a',
          chr(225) . chr(186) . chr(180) => 'A', chr(225) . chr(186) . chr(181) => 'a',
          chr(225) . chr(186) . chr(188) => 'E', chr(225) . chr(186) . chr(189) => 'e',
          chr(225) . chr(187) . chr(132) => 'E', chr(225) . chr(187) . chr(133) => 'e',
          chr(225) . chr(187) . chr(150) => 'O', chr(225) . chr(187) . chr(151) => 'o',
          chr(225) . chr(187) . chr(160) => 'O', chr(225) . chr(187) . chr(161) => 'o',
          chr(225) . chr(187) . chr(174) => 'U', chr(225) . chr(187) . chr(175) => 'u',
          chr(225) . chr(187) . chr(184) => 'Y', chr(225) . chr(187) . chr(185) => 'y',
          // acute accent
          chr(225) . chr(186) . chr(164) => 'A', chr(225) . chr(186) . chr(165) => 'a',
          chr(225) . chr(186) . chr(174) => 'A', chr(225) . chr(186) . chr(175) => 'a',
          chr(225) . chr(186) . chr(190) => 'E', chr(225) . chr(186) . chr(191) => 'e',
          chr(225) . chr(187) . chr(144) => 'O', chr(225) . chr(187) . chr(145) => 'o',
          chr(225) . chr(187) . chr(154) => 'O', chr(225) . chr(187) . chr(155) => 'o',
          chr(225) . chr(187) . chr(168) => 'U', chr(225) . chr(187) . chr(169) => 'u',
          // dot below
          chr(225) . chr(186) . chr(160) => 'A', chr(225) . chr(186) . chr(161) => 'a',
          chr(225) . chr(186) . chr(172) => 'A', chr(225) . chr(186) . chr(173) => 'a',
          chr(225) . chr(186) . chr(182) => 'A', chr(225) . chr(186) . chr(183) => 'a',
          chr(225) . chr(186) . chr(184) => 'E', chr(225) . chr(186) . chr(185) => 'e',
          chr(225) . chr(187) . chr(134) => 'E', chr(225) . chr(187) . chr(135) => 'e',
          chr(225) . chr(187) . chr(138) => 'I', chr(225) . chr(187) . chr(139) => 'i',
          chr(225) . chr(187) . chr(140) => 'O', chr(225) . chr(187) . chr(141) => 'o',
          chr(225) . chr(187) . chr(152) => 'O', chr(225) . chr(187) . chr(153) => 'o',
          chr(225) . chr(187) . chr(162) => 'O', chr(225) . chr(187) . chr(163) => 'o',
          chr(225) . chr(187) . chr(164) => 'U', chr(225) . chr(187) . chr(165) => 'u',
          chr(225) . chr(187) . chr(176) => 'U', chr(225) . chr(187) . chr(177) => 'u',
          chr(225) . chr(187) . chr(180) => 'Y', chr(225) . chr(187) . chr(181) => 'y',
          // Vowels with diacritic (Chinese, Hanyu Pinyin)
          chr(201) . chr(145) => 'a',
          // macron
          chr(199) . chr(149) => 'U', chr(199) . chr(150) => 'u',
          // acute accent
          chr(199) . chr(151) => 'U', chr(199) . chr(152) => 'u',
          // caron
          chr(199) . chr(141) => 'A', chr(199) . chr(142) => 'a',
          chr(199) . chr(143) => 'I', chr(199) . chr(144) => 'i',
          chr(199) . chr(145) => 'O', chr(199) . chr(146) => 'o',
          chr(199) . chr(147) => 'U', chr(199) . chr(148) => 'u',
          chr(199) . chr(153) => 'U', chr(199) . chr(154) => 'u',
          // grave accent
          chr(199) . chr(155) => 'U', chr(199) . chr(156) => 'u',
      );

      $string = strtr($string, $chars);
    } else {
      // Assume ISO-8859-1 if not UTF-8
      $chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158)
        . chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194)
        . chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202)
        . chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210)
        . chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218)
        . chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227)
        . chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235)
        . chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243)
        . chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251)
        . chr(252) . chr(253) . chr(255);

      $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

      $string = strtr($string, $chars['in'], $chars['out']);
      $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
      $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
      $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }

    return $string;
  }
}