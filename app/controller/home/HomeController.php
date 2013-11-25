<?php

Core::uses('AppController', 'controller');
Core::uses('AppView', 'view');

Core::import('Minify_Loader', 'plugin/min/lib/Minify/Loader.php'); // nome do arquivo não bate com nome da classe

Core::uses('Uploader', 'core/upload');

Core::uses('AppModel', 'model');

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
  
  function teste() {
    
    $s = $this->request->query['s'];
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
    
    $this->response->type('json');
    echo json($data);
  }
  
  function index(AppView $View) {
    
    /*$this->session['sess'.uniqid()] = uniqid();
    Core::dump();
    echo '<pre style="background:#fcc; padding:20px;">';
    foreach(glob('C:\\session\\*') as $f) {
      echo $f.'<br>';
    }
    echo '</pre>';*/
    echo 'kfpsdof';
    return;
    
    Core::uses('Arquivo', 'model/testes');
    
    $arq = new Arquivo();
    $arq->select();
    
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
  
  function process_upload() {
    
    Core::uses('Arquivo', 'model/testes');
    
    //sleep(1);
    $arquivo = new Arquivo();
    
    if ($ordfiles = $this->request->data['ordem_file']) {
      $ret_array = array();
      foreach ($ordfiles as $i => $ordfile) {
        if ($arquivo->find($ordfile) !== false) {
          $arquivo->select();

          $arquivo['ord'] = (int)$this->request->data['ordem'][$i];
          $ret = $arquivo->update();
          $ret_array[] = array($ordfile => $ret);
        }
      }
      //file_put_contents(DJCK.DS.'config-'.$ordfile.'.txt', $this->request->data['ordem']);
      $this->response->disableCache();
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
  
  function favicon() {
    $favicon = DJCK.DS.'favicon.ico';
    
    $this->response->cache(filemtime($favicon), '+4 years');
    $this->response->type('ico');
    $modified = $this->response->checkNotModified($this->request);
    
    if (!$modified) {
      readfile($favicon);
    }
  }
  
  function add() {
    //$this->response->cache(mktime(0,0,0, 6, 8, 2013), time()+5);
    //$this->response->checkNotModified($this->request);
    echo $this->request->referer().'<br>';
    echo '<a href="../">home</a>';
    echo env('HTTP_REFERER').'<br>';
    echo '<img src="../imagem.jpg" />';
    echo 'add22';
  }
  
  function edit() {
    echo 'edit #'.$this->request->params[':id'];
    print_r($this->request->params);
  }
  
  
  function imagem() {
    
    if ($this->request->referer() != SITE_FULL_URL.'/') {
      //$this->response->statusCode(404);
      // Create a 100*30 image
      $im = imagecreate(500, 30);

      // White background and blue text
      $bg = imagecolorallocate($im, 255, 255, 255);
      $textcolor = imagecolorallocate($im, 0, 0, 255);

      // Write the string at the top left
      imagestring($im, 3, 0, 0, $this->request->referer(), $textcolor);
      imagestring($im, 3, 0, 10, SITE_FULL_URL.'/', $textcolor);

      // Output the image
      $this->response->type('png');

      imagepng($im);
      imagedestroy($im);
      //echo 'erro';
      return;
    }
    
    $this->response->cache(mktime(0,0,0, 6, 8, 2013), '+1 day');
    $this->response->type('jpg');
    $modified = $this->response->checkNotModified($this->request);
    
    if (!$modified) {
      readfile(DJCK.DS.'123.jpg');
    }
  }
  
}