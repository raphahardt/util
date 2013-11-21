<?php

require 'core/bootstrap.php';

$Dispatcher = new Dispatcher();
$Dispatcher->dispatch($Router, $Q);