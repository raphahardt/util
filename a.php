<?php

require 'core/bootstrap.php';

Core::uses('Arquivo', 'model/testes');

$arq = new Arquivo();

//$arq->startTransaction();

for ($i=1;$i<=10;$i++) {
  echo '<hr><h1>inserindo registro '.$i.'</h1>';
  //$arq['id'] = $i;
  $arq->Mapper->nullset();
  $arq['data'] = microtime();
  
  $arq->insert();
  
  usleep(mt_rand(999700, 1000300));
}
//$arq->endTransaction(true);

finish();