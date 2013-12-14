<?php

namespace Djck;

require 'core/bootstrap.php';

$Dispatcher = new Dispatcher();
$Dispatcher->dispatch($Router, $Q);
