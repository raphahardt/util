<?php

namespace App\controller\home;

use Djck\Core;
use App\controller\AppController;
use App\model\AppModel;
use App\view\AppView;
use Djck\upload\Uploader;

Core::uses('AppController', 'App\controller');
Core::uses('AppModel', 'App\model');
Core::uses('AppView', 'App\view');

Core::uses('Uploader', 'Djck\upload');


class AAA extends AppModel {
  
  protected $permanent_delete = false;
  protected $log = false;
  
  public function __construct() {
    
    /*$table = new SQLTable('fm_veiculos_modelos', 'u');
    $table->addField('id');
    $table->addField('id_marca');
    $table->addField('descricao');
    $table->addField('ativo');*/
    $table = new SQLTable('cn_series', 'u');
    $table->addField('id');
    $table->addField('titulo');
    $table->addField('urlkey');
    $table->addField('classif');
    $table->addField('sinopse');
    $table->addField('genero');
    
    $mapper = new DbcMapper();
    $mapper->setEntity($table);
    
    $this->setMapper($mapper);
    
    $this->addBehavior('Single');
    
    return parent::__construct();
  }
  
}

/**
 * Description of HomeController
 *
 * @author usuario
 */
class HomeController extends AppController {
  
  function executeTeste() {
    
    $s = $this->Request->query['s'];
    $data = array();
    
    $a = new AAA();
    $a->addBehavior('Collection');
    $a->setFilter(new SQLCriteria($a->titulo, 'like', "%$s%"));
    $a->setOrderBy($a->titulo);
    if ($a->select()) {
      foreach ($a as $_a) {
        $data[] = array(
            'titulo' => htmlspecialchars($_a['titulo']),
            'autor' => htmlspecialchars($_a['autores']),
            'url' => $_a['urlkey'],
            'img' => SITE_FULL_URL.'/static/150/series/'.$_a['urlkey'].'/arq_thumb.jpg',
            'classif' => $_a['classif'],
            'sinopse' => substr($_a['sinopse'], 0, 90).'...',
            'genero' => explode(';', $_a['genero']),
            'tokens' => explode(' ', $_a['titulo'])
        );
      }
    }
    
    $this->Response->type('json');
    echo json($data);
  }
  
  function executeIndex(AppView $View) {
    
    /*$this->session['sess'.uniqid()] = uniqid();
    Core::dump();
    echo '<pre style="background:#fcc; padding:20px;">';
    foreach(glob('C:\\session\\*') as $f) {
      echo $f.'<br>';
    }
    echo '</pre>';*/
    
    //FIXME
    //Core::uses('Arquivo', 'model/testes');
    
    //$arq = new Arquivo();
    //$arq->select();
    
    //Core::dump();
    
    //Core::uses('AppView', 'view');
    $View = new AppView('home/index_4.tpl');
    $View->assign('folder', $_GET['folder']);
    $View->render();
    
    /*echo SITE_DOMAIN;
    echo $this->request->referer().'<br>';
    echo 'index';
    //echo '<img src="imagem.jpg" />';
    echo '<a href="add/">add</a>';
    $this->session['teste'] = 'aaa';
    echo '<pre style="background:#ddd">';
    //var_dump($this->session['teste']);
    
    Core::uses('SessionModel', 'model/session');
    
    $s = new SessionModel();
    //$s->setFilterValues('0-d19e796e25333eae1b58258bfa5c4ef7-2130706433-5');
    //$s->setFilterValues('gnfudignfduigd');
    $s->select();
    var_dump($s['sid']);
    
    echo '</pre>';*/
  }
  
  function executeProcess_upload() {
    
    // FIXME
    //Core::uses('Arquivo', 'model/testes');
    
    //sleep(1);
    $arquivo = new Arquivo();
    
    if ($ordfiles = $this->Request->data['ordem_file']) {
      $ret_array = array();
      foreach ($ordfiles as $i => $ordfile) {
        if ($arquivo->find($ordfile) !== false) {
          $arquivo->select();

          $arquivo['ord'] = (int)$this->Request->data['ordem'][$i];
          $ret = $arquivo->update();
          $ret_array[] = array($ordfile => $ret);
        }
      }
      //file_put_contents(DJCK.DS.'config-'.$ordfile.'.txt', $this->request->data['ordem']);
      $this->Response->disableCache();
      echo json($ret_array);
      return;
    }
    
    /*Core::uses('FtpUpload', 'core/upload');
    
    dump($_FILES['f']);
    
    $ftp = new FtpUpload();
    $ftp->setFile($_FILES['f']);
    $ftp->setDestinationFolder('/furacao/testeupload-sistema13');
    
    if ($ftp->beginTransfer()) {
      $ftp->transfer();
      
      $ftp->endTransfer();
    }
    
    return;*/
    
    
    $upload = new Uploader();
    $upload->setModel($arquivo);
    $upload->addImageVersion('thumbnail', array(
        // Uncomment the following to use a defined directory for the thumbnails
        // instead of a subdirectory based on the version identifier.
        // Make sure that this directory doesn't allow execution of files if you
        // don't pose any restrictions on the type of uploaded files, e.g. by
        // copying the .htaccess file from the files directory for Apache:
        //'upload_dir' => dirname($this->get_server_var('SCRIPT_FILENAME')).'/thumb/',
        //'upload_url' => $this->get_full_url().'/thumb/',
        // Uncomment the following to force the max
        // dimensions and e.g. create square thumbnails:
        //'crop' => true,
        'max_width' => 250,
        'max_height' => 350
    ));
    $upload->setDestinationFolder($_GET['folder']);
    $upload->setData('data', rand(5, 423));
    //$upload->setData('ord', 0);
    $upload->process();
    
  }
  
  function executeFavicon() {
    $favicon = DJCK.DS.'favicon.ico';
    
    $this->Response->cache(filemtime($favicon), '+4 years');
    $this->Response->type('ico');
    $modified = $this->Response->checkNotModified($this->Request);
    
    if (!$modified) {
      readfile($favicon);
    }
  }
  
}