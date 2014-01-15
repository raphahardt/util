<?php


// definindo um model

class Cliente extends Model {
  
  public function __construct() {
    
    // cria o mapper que é como o model vai manipular os dados e que tipo de persistencia será usada
    $mapper = new TempMapper();
    $mapper->setFields(array('coluna1', 'colun2', 'col3'));
    $mapper->setEntity('');
    
    // faz as definições para o model
    $this->setMapper($mapper);
    
    parent::__construct();
  }
  
}

// criando um novo registro para cliente
$clientes = new Cliente();
$novocli = $clientes->create();
$novocli['coluna1'] = 'Joao paulo';
$novocli['colun2'] = 'Rua A, 123';
$novocli['col3'] = 'São paulo';

$clientes->digest(); // para commitar mudanças

// alterando um registro
$clientes = new Cliente();
$cli = $clientes->get(12); // pega cliente com ID 12
$cli['colun2'] = 'Rua B, 456';

$clientes->digest(); // commit

// alterando varios registros
$clientes = new Cliente();
$clis = $clientes->getAll(new query\Criteria($clientes->id, '>', 10));
$clis['col3'] = 'Campinas'; // muda todos registros para campinas

$clientes->digest(); // commit

// deletando um/varios registro(s)
$clientes = new Cliente();
$cli = $clientes->get(13); // ou
$cli = $clientes->getAll(new query\Criteria($clientes->id, '<', 40));
$cli->delete();

$clientes->digest(); // commit

// listando clientes
$clientes = new Cliente();
$list = $clientes->columns('coluna1', 'colun2')->getAll();
foreach ($list as $id => $cli) {
  print_r($cli); // cada $cli será um array associativo, mostrando apenas as duas colunas
  
  // para conseguir alterar cada registro, faça:
  $cliobj = $clientes->get($id); // ou get($cli['id'])
  $cliobj['colun2'] = 12;
  $cliobj['col3'] = 'VAI DAR ERRO'; // coluna nao foi selecionada
  
  // fora do for: $clientes->digest();
}

// alterando colunas usando elas mesmo (exemplo: update c set c.a = c.a + 2
$clientes = new Cliente();
$list = $clientes->getAll();
$list['col3'] = _e('+', $list->col3, 4); // col3 = col3 + 4
$clientes->digest();

// outras filtragens
$list = $clientes->from(10)->to(20)->getAll(); // limit 10,10
$list = $clientes->from(30)->limit(5)->getAll(); // limit 30,5
// obs: limit() tem precedencia sobe to(). se os dois forem usados juntos, valerá o limit()