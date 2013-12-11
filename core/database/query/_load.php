<?php

/**
 * Arquivo de carregamento de pacotes
 */

use Djck\Core;

Core::importPackage('Djck\database\query\exceptions');
Core::importPackage('Djck\database\query\interfaces');

Core::usesPackage('Djck\database\query');