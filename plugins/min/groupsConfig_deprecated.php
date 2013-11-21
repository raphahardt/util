<?php
/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */

/** 
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 *
 * See http://code.google.com/p/minify/wiki/CustomSource for other ideas
 **/

/*require_once 'init.php';

if (!defined('_MIN_FILE')) exit();

define('_DEFS_ONLY', true);
require_once _MIN_FILE;*/

$base = $app_groups = $core_groups = array();
if (defined('APP_PATH') && is_file(APP_PATH . DS . 'cfg' . DS . 'min_groupscfg.php')) {
  $app_groups = (include APP_PATH . DS . 'cfg' . DS . 'min_groupscfg.php');
} 

if (defined('DJCK') && is_file(DJCK . DS . 'cfg' . DS . 'min_groupscfg.php')) {
  $core_groups = (include DJCK . DS . 'cfg' . DS . 'min_groupscfg.php');
}

return array_merge($base, $core_groups, $app_groups);