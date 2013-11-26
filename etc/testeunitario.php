<?php

/**
 * Sistema de ijfisdojfs
 */

require 'core/bootstrap.php';

Core::uses('Cte', 'model/testes');

// TESTE DE INCLUSAO E CONSULTA DE ITENS


$storage = new CacheArray('a');

for ($i=0;$i<1000;$i++) {
  $storage[] = 'registro'.($i+1);
}

// nao existe
$storage[1199] = 'registro 1200 HA';
$storage[1399] = 'registro 1400 HA';

$storage[199] = 'AGORA É 200!!!!!!!!!';

dump($storage->count());

foreach ($storage as $st) {
  echo $st.'<br>';
}

//dump($storage[979]); // 980
//dump($storage->offset);

// TESTE DE RECONHECIMENTO DE INTERFACE DE CLASSES PAI DENTRO DA CLASSE FILHA

/*abstract class CacheArray2 implements ArrayAccess {
  
  private $storage = array();
  
  public function offsetExists($offset) {
    return isset($this->storage[$offset]);
  }

  public function &offsetGet($offset) {
    return $this->storage[$offset];
  }

  public function offsetSet($offset, $value) {
    if ($offset===null) {
      $this->storage[] = $value;
      return;
    }
    $this->storage[$offset] = $value;
  }

  public function offsetUnset($offset) {
    unset($this->storage[$offset]);
  }
  
  public function a() {
    dump($this instanceof It2);
  }

}
interface It1 {}
interface It2 {}
class C1 extends CacheArray2 implements It1 {
  
}

class C2 extends CacheArray2 implements It1, It2 {
  
}

$a = new C1();
$b = new C2();
$a[] = 'fsdfos1';
$a[] = 'fsdfos2';
$a[] = 'fsdfos3';
//array_pop($a); // nao funciona
dump($a);*/

// TESTE COM ARRAY PASSANDO POR REFERENCIA

/*$a = new CacheArray();
$a['a'] = array(4,5,6);

$a['a'][1] = 9;
$a['b'] = 'kkk';

$b = $a['a'];
$b_ref = &$a['a'];

$b_ref[0] = 15;
$b[0] = 10;

dump($a);
dump($b[0]);
dump($b_ref[0]+2);
 * 
 */

/*Core::uses('Usuario', 'model/usuario');

$cte = new Usuario();
$cte->addBehavior('Collection');
$cte->select();

dump(SingleBehavior::$in_iteration);

foreach ($cte as $c) {
  dump(SingleBehavior::$in_iteration);
  dump($c['id']);
  dump($cte[2]['id']);
}
dump(SingleBehavior::$in_iteration);

$cte[2];
dump($cte['id']);
dump($cte['id']);
*/
finish();

exit; 

/*class Exp {
  public $exps = array();
  public $sep = 'and';
  function __construct($sep, $exps=array()) {
    $this->sep = $sep;
    $this->exps = $exps;
  }
  
  function __toString() {
    return '['.implode($this->sep, $this->exps).']';
  }
}

function parse_query($q, $values=null) {
  
  // pega o query e os valores
  $params = func_get_args();
  $q = array_shift($params);
  
  // pega as subexpressoes
  if (strpos($q, '(') !== false && strpos($q, ')') !== false ) {
    $len=strlen($q);
    $stack = 0;
    $exps = array();
    $exp = array();
    for($i=0;$i<$len;$i++) {
      if ($q{$i} == '(') {
        ++$stack;
        $exps[$stack] = '';
      } elseif ($q{$i} == ')') {
        if ($stack==1)
          $exp[] = $exps[$stack];
        else
          $exps[$stack-1] .= '('.$exps[$stack].')';
        --$stack;
      } else {
        $exps[$stack] .= $q{$i};
      }
    }
    foreach ($exp as $e) {
      parse_query($e);
    }
  }
  
  $ands = explode('||', $q);
  
  return;
}

parse_query('teste (exp = 1 && (exp4 = 4 || (exp5 = 5)) && exp3 = 3) tt (exp2 = exp1)');*/



if (!headers_sent())
  header('Content-type: text/html; charset=UTF-8', true, 200);

class UserP extends Model {
  function __construct() {
    
    $table = new SQLTable('int_usuario', 's');
    $table->addField('id');
    $table->addField('nome');
    $table->addField('login');
    $table->addField('senha');
    
    /*$mapper = new FileMapper();
    $mapper->setEntity(CORE_PATH.'/mvc/model/teste.txt');
    $mapper->setFields(array('id', 'nome', 'descricao', 'data'));*/
    $mapper = new BDMapper();
    $mapper->setEntity($table);
    
    $this->setMapper($mapper);
    
    $this->addBehavior('Single');
    $this->addBehavior('Collection');
    
    return parent::__construct();
  }
}


$u = new UserP();
//$u->setFilter(_c($u->id, '>', 4), _c($u->id, '<', 8));
$u->setOrderBy($u->nome);
$u->setOffset(1);
$u->setLimit(5);
/*$u['nome'] = 'JFIOSDJFISDJ';
$u->update();*/

/*$u['nome'] = 'rwerwe';
$u->insert();*/

/*dump($u->find(4));
$u['nome'] = 'fksdokfsdofksdok';
$u->update();*/

echo it('select')->expect($u->select())->toBe(true);
dump($u->count());

dump($u['nome']);
dump($u->nome);
dump($u[3]);

echo '<ul style="border:1px solid">';
foreach ($u as $id => $u2) {
  echo $id .' => ' . $u2['id'].'-'.$u2['nome'].'<br>';
}
echo '</ul>';

dump($u['nome']);

finish();

//exit;
//////////////////////////////////////////////////////////////////////////////////////////

/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class MyfileMapper extends BDMapper {
  
  function __construct() {
    
    $table = new SQLTable('teste', 's');
    $table->addField('id');
    $table->addField('nome');
    $table->addField('descricao');
    $table->addField('data');
    
    $this->setEntity($table);
    
    parent::__construct();
  }
}

class _MyfileMapper extends JsonMapper {
  
  function __construct() {
    
    $this->setEntity(CORE_PATH.'/mvc/model/texte64.txt');
    $this->setFields(array('id', 'nome', 'descricao', 'data'));
    
    $this->destroy(); // deleta o arquivo
    
    parent::__construct();
    
    
  }
  
  
}

// inicialização da tabela teste
$db = BD::getInstance();
// limpa
$db->prepare("TRUNCATE teste");
$db->execute();
$db->free();
unset($db);

$file = new MyfileMapper();

echo it('instancia do myfilemapper criada')->expect($file)->toInstanceOf('MyfileMapper');
echo it('contagem dos registros da tabela')->expect($file->count())->toBe(0);

for($i=0;$i<10;$i++)
$file->push(array(
    'nome' => 'te,ste'.$i,
    'data' => rand(),
));
$file->insert();

echo it('contagem dos registros da tabela')->expect($file->count())->toBe(10);

echo it('commit de 10 registros')->expect($file->commit())->toBe(true);

$file->select();

$file->first();
echo it('id do primeiro registro')->expect($file['id'])->toBeOfType('integer')->also()->toBeGreaterOrEqualThan(1);
$file->next();
echo it('id do segundo registro')->expect($file['id'])->toBeOfType('integer')->also()->toBeGreaterOrEqualThan(2);

$file->find(7);
echo it('"nome" do registro id 7')->expect($file['nome'])->toBeOfType('string')->also()->toBe('te,ste6');
echo it('"descricao" do registro id 7')->expect($file['descricao'])->toBeOfType('NULL')->also()->toBe(null);

$a=$file->remove();

echo it('removido id 7')->expect($a)->toBe(true);
echo it('nome depois de remover id 7')->expect($file['nome'])->toBe(null);
echo it('contagem dos registros da tabela depois de deletar 7')->expect($file->count())->toBe(9);

echo it('salvo no arquivo')->expect($file->commit())->toBe(true);

dump($file);

exit;
//////////////////////////////////////////////////////////////////////////////////////////////////////////////

class UserMapper extends BDMapper {
  
  function __construct() {
    
    $table = new SQLTable('fm_servicos', 's');
    $table->addField('id');
    $table->addField('descricao');
    $table->addField('ativo');
    
    $this->setEntity($table);
    
    parent::__construct();
  }
  
  
}

// inicialização da tabela teste
$db = BD::getInstance();
// limpa
$db->prepare("TRUNCATE fm_servicos");
$db->execute();
$db->free();
// popula
$db->prepare("
INSERT INTO `fm_servicos` (`id`, `descricao`, `ativo`) VALUES
(1, 'Suspensão', 1),
(2, 'Alinhamento', 1),
(3, 'Balanceamento', 1),
(4, 'Embreagem', 1),
(5, 'Câmbio', 1),
(6, 'Freios', 1),
(7, 'Pneus', 1),
(8, 'Vidros', 1),
(9, 'Auto-Elétrica', 1),
(10, 'Injeção Eletrônica', 1),
(11, 'Som', 1),
(12, 'Funilaria', 1),
(13, 'Pintura', 1),
(14, 'Polimento', 1);
");
$db->execute();
$db->free();
unset($db);

$user = new UserMapper();
$user->setFilter(_c($user->getEntity()->descricao, '!=', null));

echo it('instancia do usermapper criada')->expect($user)->toInstanceOf('UserMapper');

echo it('procura todos os registros não nulos da tabela')->expect($user->select())->toBeGreaterThan(0);
echo it('contagem dos registros da tabela')->expect($user->count())->toBe(14);
echo it('valor descricao do primeiro registro (selecionado)')->expect($user['descricao'])->toBe('Suspensão');
echo it('valor id do primeiro registro (selecionado)')->expect($user['id'])->toBe(1, false);

$a = $user->next();
echo it('next')->expect($a)->toBeOfType('array');
echo it('valor descricao do segundo registro (selecionado)')->expect($user['descricao'])->toBe('Alinhamento');

$user->last();

echo it('valor descricao do ultimo registro (selecionado)')->expect($user['descricao'])->toBe('Polimento');

$user->find(8);

echo it('valor descricao do registro id 8 (selecionado)')->expect($user['descricao'])->toBe('Vidros');

$user->setFilter(_c($user->getEntity()->descricao, '=', 'Vidros'));
$del = $user->delete();

echo it('deleta o registro "vidros"')->expect($del)->toBeOfType('integer')->also()->toBeGreaterThan(0);
echo it('contagem de registros deleta o registro "vidros"')->expect($user->count())->toBe(0);

$user->select();

echo it('select com o mesmo fitlro do delete')->expect($user->count())->toBe(0);

$user->setFilter(_c($user->getEntity()->descricao, '!=', null));
echo it('procura todos os registros não nulos da tabela')->expect($user->select())->toBeGreaterThan(0);
echo it('select com o filtro antigo (descricao != null)')->expect($user->count())->toBe(13);

$a = $user->insert();

echo it('tenta inserir sem ter colocado nenhum dado')->expect($a)->toBe(0);
echo it('tenta inserir sem ter colocado nenhum dado (count)')->expect($user->count())->toBe(13);

$user->nullset();
$user['descricao'] = 'Teste';
$user['ativo'] = 2;
$a = $user->insert();
//dump($user);

echo it('insere com registro atual')->expect($a)->notToBe(false)->also()->toBeOfType('integer')->also()->toBeGreaterThan(0);
echo it('insere com registro atual (count)')->expect($user->count())->toBe(14);

$a = $user->insert();

echo it('tenta inserir sem ter colocado nenhum dado de novo')->expect($a)->toBe(0);
echo it('tenta inserir sem ter colocado nenhum dado de novo (count)')->expect($user->count())->toBe(14);

echo it('id do registro novo')->expect($user['id'])->toBe(15, false);

for($i=0;$i<200;$i++) {
  $user->push(array(
     'descricao' => rand(),
     'ativo' => rand(),
  ));
}

echo it('adicionado +200 registros a fila (ainda nao muda o count)')->expect($user->count())->toBe(14);

$a = $user->insert();

echo it('insere com +200 registros')->expect($a)->notToBe(false)->also()->toBeOfType('integer')->also()->toBe(200);
echo it('insere com +200 registros (count) (agora muda o count)')->expect($user->count())->toBe(214);

echo it('id do registro atual (o registro atual não muda por conta do result)')->expect($user['id'])->toBe(15, false);
$user->last();
echo it('id do ultimo registro')->expect($user['id'])->toBe(215, false);

$user->first();
echo it('id do primeiro registro')->expect($user['id'])->toBe(1, false);
echo it('descricao do primeiro registro')->expect($user['descricao'])->toBe('Suspensão', false);

$user['descricao'] = 'TTT';
$user->setFilter(_c($user->id, '=', 1));
$a = $user->update();

echo it('faz update no id 1 para TTT')->expect($a)->notToBe(false)->also()->toBeOfType('integer')->also()->toBe(1);

$a = $user->update();

echo it('faz update no id 1 para TTT (de novo)')->expect($a)->notToBe(false)->also()->toBeOfType('integer')->also()->toBe(0);

echo it('campo ->"descricao" (DesCriCao)')->expect($user->DesCriCao)->toInstanceOf('SQLField');
echo it('valor ->"descricao" (será vazio pois o valor só pode ser recuperado por array)')->expect($user->DesCriCao->getValue())->toBe(null);
echo it('valor ["descricao"]')->expect($user['DesCriCao'])->toBe('TTT');

//dump($user);

echo '<hr>';
exit;
// ======================================================================================

$array = new FileMapper();

echo it('numero de elementos')->expect($array->count())->toBe(0);

$array->push();

echo it('numero de elementos depois do push vazio')->expect($array->count())->toBe(0);

$array['id'] = 1;
$array['campo1'] = 10;
$array->push();

echo it('numero de elementos depois do push com registro')->expect($array->count())->toBe(1);
echo it('valor do campo1 depois do push com registro')->expect($array['campo1'])->toBe(10);
echo it('nome do campo1 usando letras maiusculas (CaMpO1)')->expect($array->CaMpO1)->toBe('campo1');
echo it('valor do campo2 (nao existe)')->expect($array['campo2'])->toBe(null);

$array->unshift();

echo it('numero de elementos depois do unshift com registro anterior')->expect($array->count())->toBe(2);
echo it('valor do campo1 depois do unshift com registro')->expect($array['campo1'])->toBe(10);

$array['campo1'] += 20;

echo it('valor do campo1 somado ele mesmo += 20')->expect($array['campo1'])->toBe(30);

$array['campo1']++;

echo it('valor do campo1 incrementado ++')->expect($array['campo1'])->notToBe(31);

$array->find(1);

echo it('valor do campo1 ser o primeiro campo id = 1')->expect($array['campo1'])->notToBe(30);
echo it('valor do campo1 ser o primeiro campo id = 1')->expect($array['campo1'])->toBe(10);

$result = $array->remove();

echo it('valor do resultado após deletar')->expect($result)->toBe(true);
echo it('valor do campo1 após deletar')->expect($array['campo1'])->toBe(null);

echo it('deletar vazio')->expect($array->remove())->toBe(false);
echo it('deletar não existente')->expect($array->remove(999))->toBe(false);
echo it('deletar outro id 1')->expect($array->remove(1))->toBe(true);

echo it('numero de elementos depois de deletar os dois id 1')->expect($array->count())->toBe(0);

$i = 11;
for($j=0;$j<5;$j++)
$array->push(array(
    //'id' => $i++,
    'nome' => mt_rand()
));

echo it('apos inserir 5 registros')->expect($array->count())->toBe(5);
echo it('o ponteiro interno estar no ultimo registro (sem get)')->expect($array['id'])->toBe(null);
$array->get();
echo it('o ponteiro interno estar no ultimo registro (apos get)')->expect($array['id'])->toBe(5);

echo it('busca pelo id 2')->expect($array->find(2))->toBe(1);
echo it('busca pelo id 2 valor')->expect($array['id'])->toBe(2);

echo it('busca pelo id 4')->expect($array->find(4))->toBe(3);
echo it('busca pelo id 4 valor')->expect($array['id'])->toBe(4);

echo it('busca pelo id 9999')->expect($array->find(9999))->toBe(false);
echo it('busca pelo id 9999 valor')->expect($array['id'])->toBe(null);

$array->unshift(array(
    'id' => 90,
    'nome' => 'joao'
));
$array->setPointer('nome');

echo it('adicionar um registro com nome "joao" (90), definir o id como nome e buscar por "joao"')->expect($array->find('joao'))->notToBe(false);
