<?php

/**
 * Sistema de ijfisdojfs
 */

require 'core/bootstrap.php';

Core::uses('Teste', 'model/testes');

$tree = new Teste();
//$tree->addBehavior('Collection');
$tree->addBehavior('Tree');


if ($_GET['add']) {
  $tree['alias'] = 'node'.dechex(rand(2097152,16777215-32656));
  $tree->addNode($_GET['id'] === 'NULL' ? null : (int)$_GET['id']);
  header('Location: ./testeunitario.php');
  exit;
}

if ($_GET['del']) {
  $tree->removeNode((int)$_GET['id'], (bool)($_GET['del'] == 2));
  header('Location: ./testeunitario.php');
  exit;
}

if ($_GET['dellvl']) {
  $tree->setFilter(new SQLCriteria($tree->lvel, '=', (int)$_GET['dellvl']));
  $tree->removeNodes(isset($_GET['all']));
  header('Location: ./testeunitario.php');
  exit;
}

if ($_GET['delalias']) {
  $tree->setFilter(new SQLCriteria($tree->alias, 'like', '%'.$_GET['delalias'].'%'));
  $tree->removeNodes(isset($_GET['all']));
  header('Location: ./testeunitario.php');
  exit;
}

if ($_GET['move']==2) {
  $tree->moveNode((int)$_GET['idsrc'], $_GET['iddst'] === 'NULL' ? null : (int)$_GET['iddst']);
  header('Location: ./testeunitario.php');
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

echo '<form style="margin:0" method="get" action="./testeunitario.php">deletar <input type="submit" name="all" value="todos"/>/<input type="submit" name="notall" value="soh os nos"/> do nivel <input type="text" name="dellvl" value="1"/></form>';
echo '<form style="margin:0" method="get" action="./testeunitario.php">deletar <input type="submit" name="all" value="todos"/>/<input type="submit" name="notall" value="soh os nos"/> que tiverem no alias <input type="text" name="delalias" value=""/></form>';

$filhos_index = array();
echo $_GET['move']==1 ? '<a href="./testeunitario.php?move=2&idsrc='.$_GET['id'].'&iddst=NULL">mover aqui</a>' : '<a href="./testeunitario.php?add=1&id=NULL">add</a><br>';
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
  
  echo '<div style="top: '.($node['lvel']*120).'px;width:'.$width[ $node['id'] ].'%; left:'.$left[ $node['id'] ].'%;position:absolute"><div style="background: #'.substr($node['alias'], 4).';" class="node">'.
          //str_repeat(' &gt;', $node['lvel']).
          '['.$node['id'].'] '.
          '<span class="red lft">('.$node['lft'].')</span> '.
          $node['alias'].
          '<span class="red rght">('.$node['rght'].')</span> '.
          '<span class="blue p">('.($node['parent_id']?:'root').')</span> '.
          '<span class="blue h">('.($node['height']).')</span> '.
          '<span class="blue lvl">('.($node['lvel']).')</span> '.
          '<div class="opt">'.
          ($_GET['move']==1 ?
            ($_GET['id'] != $node['id'] ?
            '<a href="./testeunitario.php?move=2&idsrc='.$_GET['id'].'&iddst='.$node['id'].'">mover aqui</a> | ' :
            '')
          :
          '<a href="./testeunitario.php?add=1&id='.$node['id'].'">add</a> | '.
          '<a href="./testeunitario.php?del=1&id='.$node['id'].'">del</a> | '.
          '<a href="./testeunitario.php?del=2&id='.$node['id'].'">del all</a> | '.
          '<a href="./testeunitario.php?move=1&id='.$node['id'].'">move</a> | '
          ).
          '</div></div></div>';
  $filhos_index[ $node['parent_id'] ]++;
  
  
}

finish();