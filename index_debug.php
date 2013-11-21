<?php

function profiler($return = false) {
  static $m = 0;
  if ($return)
    return $m . " bytes";
  if (($mem = memory_get_usage()) > $m)
    $m = $mem;
}

function fff($str) {
  return '';
}

//register_tick_function('profiler');

declare(ticks = 1) {

  ob_start('fff');
  require 'core/bootstrap.php';

  $Dispatcher = new Dispatcher();
  $Dispatcher->dispatch($Router, $_GET['q']);
  
  ob_end_clean();
}

dump(profiler(true));
finish();
