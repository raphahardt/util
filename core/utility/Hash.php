<?php
/**
 * Description of Hash
 *
 * @author Rapha e Dani
 */
abstract class Hash {

  /**
   * Inserts $data into an array as defined by $path.
   *
   * @param mixed $list Where to insert into
   * @param mixed $path A dot-separated string.
   * @param array $data Data to insert
   * @return array
   * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::insert
   */
  public static function insert($list, $path, $data = null) {
    if (!is_array($path)) {
      $path = explode('.', $path);
    }
    $_list = & $list;

    $count = count($path);
    foreach ($path as $i => $key) {
      if (is_numeric($key) && intval($key) > 0 || $key === '0') {
        $key = intval($key);
      }
      if ($i === $count - 1 && is_array($_list)) {
        $_list[$key] = $data;
      } else {
        if (!isset($_list[$key])) {
          $_list[$key] = array();
        }
        $_list = & $_list[$key];
      }
      if (!is_array($_list)) {
        $_list = array();
      }
    }
    return $list;
  }

  /**
   * Removes an element from a Set or array as defined by $path.
   *
   * @param mixed $list From where to remove
   * @param mixed $path A dot-separated string.
   * @return array Array with $path removed from its value
   * @link http://book.cakephp.org/2.0/en/core-utility-libraries/set.html#Set::remove
   */
  public static function remove($list, $path = null) {
    if (empty($path)) {
      return $list;
    }
    if (!is_array($path)) {
      $path = explode('.', $path);
    }
    $_list = & $list;

    foreach ($path as $i => $key) {
      if (is_numeric($key) && intval($key) > 0 || $key === '0') {
        $key = intval($key);
      }
      if ($i === count($path) - 1) {
        unset($_list[$key]);
      } else {
        if (!isset($_list[$key])) {
          return $list;
        }
        $_list = & $_list[$key];
      }
    }
    return $list;
  }

}