<?php

/**
 * Sistema de ijfisdojfs
 */

require 'core/bootstrap.php';

Core::uses('SavUsuario', 'model/testes');
Core::uses('SavGrupo', 'model/testes');
Core::uses('Teste', 'model/testes');

$teste = new Teste();
$savu = new SavUsuario();
$savu->id->setAlias('id_u');
$savu->alias->setAlias('alias_u');
$savu->setFields($savu->id, $savu->alias);
$savg = new SavGrupo();
$savg->id->setAlias('id_g');
$savg->alias->setAlias('alias_g');
$savg->setFields($savg->id, $savg->alias);

$tree = new Join('RIGHT', $savg, 
          new Join('RIGHT', $savu, $teste, 
                  new SQLExpression('AND', 
                          new SQLCriteria($teste->foreign_key, '=', $savu->id), 
                          new SQLCriteria($teste->model, '=', 'SAV_USUARIO'))
                  ),
          new SQLExpression('AND', 
                  new SQLCriteria($teste->foreign_key, '=', $savg->id), 
                  new SQLCriteria($teste->model, '=', 'SAV_GRUPO')
          )
        );
//$tree->addBehavior('Collection');
//$tree->addBehavior('Tree');


if ($_GET['grupo']) {
  $sav_g = new SavGrupo();
  $sav_g['alias'] = $_GET['alias'];
  $sav_g->addNode(empty($_GET['parent']) ? null : (int)$_GET['parent']);
  $sav_g->setFilter(new SQLCriteria($sav_g->alias, '=', $_GET['alias']));
  $sav_g->select();
  
  $teste->setFilter(new SQLCriteria($teste->foreign_key, '=', (int)$_GET['parent']), new SQLCriteria($teste->model, '=', 'SAV_GRUPO'));
  if ($teste->select()) {
    $parent_id = $teste['id'];
  } else {
    $parent_id = null;
  }
  $teste->setFilter(null);
  $teste->Mapper->clearResult();
  $teste['id'] = null; // HACK
  $teste['foreign_key'] = $sav_g['id'];
  $teste['model'] = 'SAV_GRUPO';
  $teste['alias'] = $_GET['alias'];
  $teste->addNode($parent_id);
  header('Location: ./testeunitario_1.php');
  exit;
}

if ($_GET['usuario']) {
  $sav_g = new SavUsuario();
  $sav_g['alias'] = $_GET['alias'];
  $sav_g['sav_grupo_id'] = (int)$_GET['parent'];
  $sav_g->insert();
  $sav_g->setFilter(new SQLCriteria($sav_g->alias, '=', $_GET['alias']));
  $sav_g->select();
  
  $teste->setFilter(new SQLCriteria($teste->foreign_key, '=', (int)$_GET['parent']), new SQLCriteria($teste->model, '=', 'SAV_GRUPO'));
  if ($teste->select()) {
    $parent_id = $teste['id'];
  } else {
    $parent_id = null;
  }
  $teste->setFilter(null);
  $teste->Mapper->clearResult();
  $teste['id'] = null; // HACK
  $teste['foreign_key'] = $sav_g['id'];
  $teste['model'] = 'SAV_USUARIO';
  $teste['alias'] = $_GET['alias'];
  $teste->addNode($parent_id);
  header('Location: ./testeunitario_1.php');
  exit;
}

if ($_GET['del']=='u') {
  $sav_g = new SavUsuario();
  $sav_g->setFilter(new SQLCriteria($sav_g->id, '=', (int)$_GET['id']));
  $sav_g->delete();
  
  $teste->setFilter(new SQLCriteria($teste->foreign_key, '=', (int)$_GET['id']), new SQLCriteria($teste->model, '=', 'SAV_USUARIO'));
  $teste->removeNodes(false);
  header('Location: ./testeunitario_1.php');
  exit;
}

if ($_GET['del']=='g') {
  $sav_g = new SavGrupo();
  $sav_g->setFilter(new SQLCriteria($sav_g->id, '=', (int)$_GET['id']));
  $sav_g->removeNodes(false);
  
  $teste->setFilter(new SQLCriteria($teste->foreign_key, '=', (int)$_GET['id']), new SQLCriteria($teste->model, '=', 'SAV_GRUPO'));
  $teste->removeNodes(false);
  header('Location: ./testeunitario_1.php');
  exit;
}

if ($_GET['move']==2) {
  $tree->moveNode((int)$_GET['idsrc'], $_GET['iddst'] === 'NULL' ? null : (int)$_GET['iddst']);
  header('Location: ./testeunitario_1.php');
  exit;
}


$tree->setFilter(null);
$tree->setOrderBy($tree->lft, 'asc');
$tree->select();

echo '<style>.red,.blue{font-size:.9em;color:red;background:rgba(255,255,255,.5);width:20px;display:inline-block;text-align:center}.blue{color:blue}.node{border:1px solid #ccc; height:80px;font-size:12px; width:85%; margin:12px auto; display:block;position:relative;background:#fff;overflow:hidden;transition:min-width ease .2s}'
. '.node .lft,.node .rght{position:absolute;top:40%;font-size:12px}.node .lft{left:0}.node .rght{right:0}.node .opt{position:absolute;left:0;bottom:0;white-space:nowrap}.node:hover {min-width: 300px; z-index:9999;box-shadow:4px 4px 5px #ccc}</style>';

$filhos = array();
$width = array();
$left = array();

foreach ($tree as $node) {
  if (!isset($filhos[ $node['parent_id'] ])) {
    $filhos[ $node['parent_id'] ] = 0;
  }
  $filhos[ $node['parent_id'] ]++;
}

echo '<form style="margin:0" method="get" action="./testeunitario_1.php">adicionar <input type="submit" name="usuario" value="usuario"/>/<input type="submit" name="grupo" value="grupo"/> com nome <input type="text" name="alias" value=""/>, filho do grupo <input type="text" name="parent" value=""/></form>';

$filhos_index = array();
echo $_GET['move']==1 ? '<a href="./testeunitario_1.php?move=2&idsrc='.$_GET['id'].'&iddst=NULL">mover aqui</a>' : '<a href="./testeunitario_1.php?add=1&id=NULL">add</a><br>';
foreach ($tree as $node) {
  $filhos_index[ $node['id'] ] = 0;
  if (!isset($filhos_index[ $node['parent_id'] ])) {
    $filhos_index[ $node['parent_id'] ] = 0;
  }
  if (!isset($width[ $node['parent_id'] ])) {
    $width[ $node['parent_id'] ] = 100;
  }
  if (!isset($left[ $node['parent_id'] ])) {
    $left[ $node['parent_id'] ] = 0;
  }
  $width[ $node['id'] ] = ($width[ $node['parent_id'] ]/$filhos[ $node['parent_id'] ]);
  $left[ $node['id'] ] = $left[ $node['parent_id'] ] + $width[ $node['id'] ] * $filhos_index[ $node['parent_id']];
  
  echo '<div style="top: '.($node['lvel']*120).'px;width:'.$width[ $node['id'] ].'%; left:'.$left[ $node['id'] ].'%;position:absolute"><div style="background: #'.($node['model'] == 'SAV_USUARIO' ? 'dcc' : 'cdc').';" class="node">'.
          //str_repeat(' &gt;', $node['lvel']).
          '[U:'.$node['id_u'].' / G:'.$node['id_g'].' / '.$node['id'].'] '.
          '<span class="red lft">('.$node['lft'].')</span> '.
          $node['alias_u'].
          $node['alias_g'].
          '<span class="red rght">('.$node['rght'].')</span> '.
          '<span class="blue p">('.($node['parent_id']?:'root').')</span> '.
          '<span class="blue h">('.($node['height']).')</span> '.
          '<span class="blue lvl">('.($node['lvel']).')</span> '.
          '<div class="opt">'.
          ($_GET['move']==1 ?
            ($_GET['id'] != $node['id'] ?
            '<a href="./testeunitario_1.php?move=2&idsrc='.$_GET['id'].'&iddst='.$node['id'].'">mover aqui</a> | ' :
            '')
          :
          '<a href="./testeunitario_1.php?del=u&id='.$node['id_u'].'">del u</a> | '.
          '<a href="./testeunitario_1.php?del=g&id='.$node['id_g'].'">del g</a> | '.
          '<a href="./testeunitario_1.php?move=1&id='.$node['id'].'">move</a> | '
          ).
          '</div></div></div>';
  $filhos_index[ $node['parent_id'] ]++;
  
  
}

finish();