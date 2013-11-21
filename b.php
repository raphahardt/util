<?php

require 'core/bootstrap.php';

Core::uses('Arquivo', 'model/testes');

$arq = new Arquivo();

/*$fp = fopen(TEMP_PATH.'/arquivos.txt', 'a+b');
flock($fp, LOCK_EX); // exclusive lock 
ftruncate($fp, 0); //is needed here! <---- 

// write to the file 
for ($i = 1; $i <= 10; $i++) {
  fwrite($fp, 'b ' . time() . ' test ' . $i . "\n");
  sleep(1);
}
flock($fp, LOCK_UN); // release the lock 
fclose($fp);*/

for ($i=1;$i<=50;$i++) {
  echo '<hr><h1>inserindo registro '.$i.'</h1>';
  //$arq['id'] = $i;
  $arq->Mapper->nullset();
  $arq['data'] = 'BBBB-'.microtime();
  
  $arq->insert();
  
  usleep(mt_rand(99970, 100030));
}

finish();