<?php

Core::depends('Request');
Core::import('UploadHandler', 'plugin/blueimp-upload');

/**
 * Description of Upload
 *
 * @author usuario
 */

class Uploader extends UploadHandler {
  
  private $request;
  
  private $form_data = array();
  private $destination_folder = '';
  private $image_versions = array();
  
  /**
   *
   * @var Model 
   */
  private $Model;
  
  //private $additional_file_props = array();
  
  /*function setMinImageSize($minw, $minh) {
    
  }
  
  function setMaxImageSize($maxw, $maxh) {
    
  }*/
  
  function setModel(Model $model) {
    $this->Model = $model;
  }
  
  function setDestinationFolder($dest) {
    if (substr($dest, -1) !== '/') {
      $dest .= '/';
    }
    $this->destination_folder = $dest;
  }
  
  function addImageVersion($foldername, $options) {
    $this->image_versions[$foldername] = $options;
  }
  
  function setData($name, $value) {
    $this->form_data[$name] = $value;
  }
  
  function __construct($options = null, $initialize = true, $error_messages = null) {
    
    $this->request = new Request();
    
    // pega configuracoes globais
    $cfg = cfg('Upload');
    
    $options = array(
        // Enable to provide file downloads via GET requests to the PHP script:
        //     1. Set to 1 to download files via readfile method through PHP
        //     2. Set to 2 to send a X-Sendfile header for lighttpd/Apache
        //     3. Set to 3 to send a X-Accel-Redirect header for nginx
        // If set to 2 or 3, adjust the upload_url option to the base path of
        // the redirect parameter, e.g. '/files/'.
        'download_via_php' => false,
        
        'script_url' => $this->request->here(),
        'upload_dir' => PUBLIC_PATH.DS.'img'.DS,
        'upload_url' => $this->get_full_url().'/img/',
        //'delete_type' => 'POST'
    );
    
    if ($cfg['accept-file-types']) {
      $options['accept_file_types'] = '/'.str_replace('/', '\\/', $cfg['accept-file-types']).'/i';
    }
    if (isset($cfg['max-file-size'])) {
      $options['max_file_size'] = $cfg['max-file-size'];
    }
    if (isset($cfg['min-file-size'])) {
      $options['min_file_size'] = $cfg['min-file-size'];
    }
    
    parent::__construct($options, false, $error_messages);
  }
  
  protected function get_full_url() {
    return SITE_FULL_URL.'/public';
  }
  
  protected function trim_file_name($name, $type = null, $index = null, $content_range = null) {
    $name = parent::trim_file_name($name, $type, $index, $content_range);
    // deixa nome tudo em minuscula também e retira os acentos do arquivo
    $name = remove_accents($name);
    $name = strtolower($name);
    // retira caracteres especiais proibidos por alguns sistemas operacionais
    $special_chars = array('?', '[', ']', '/', "\\", '=', '<', '>', ':', ';', ',', 
        "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', chr(0));
    $name = str_replace($special_chars, '', $name);
    $name = preg_replace('/[\s-]+/', '-', $name);
    $name = trim($name, '.-_');
    
    return $name;
  }
  
  protected function upcount_name_callback($matches) {
    $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
    $ext = isset($matches[2]) ? $matches[2] : '';
    return '_' . $index . '' . $ext;
  }

  protected function upcount_name($name) {
    return preg_replace_callback(
            '/(?:(?:_([\d]+))?(\.[^.]+))?$/', array($this, 'upcount_name_callback'), $name, 1
    );
  }
  
  protected function set_additional_file_properties($file) {
    // adiciona as propriedades adicionais naturais
    parent::set_additional_file_properties($file);
    
    // e mais as do djck
    // deleta no mapper
    $Model =& $this->Model;
    $offset = $Model->find($file->name);
    if ($offset !== false) { // procura o arquivo
      $Model->select();

      $cols = $Model->getFields();
      foreach ($cols as $col) {
        if ($Model->is('Collection')) {
          $file->{$col} = $Model[$offset][$col];
        } else {
          $file->{$col} = $Model[$col];
        }
      }
    }
    //$file->ord = rand(1,800);
    //$file->ord = (int)(@file_get_contents(DJCK.DS.'config-'.$file->name.'.txt'));
  }
  
  protected function handle_form_data($file, $index) {
    // Handle form data, e.g. $_REQUEST['description'][$index]
    parent::handle_form_data($file, $index);
    
    // post
    /*foreach ($_POST as $name => $val) {
      $file->{$name} = $val[$index];
    }*/
    
    foreach ($this->form_data as $name => $val) {
      $file->{$name} = $val;
    }
  }
  
  protected function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null) {
    $file = parent::handle_file_upload($uploaded_file, $name, $size, $type, $error, $index, $content_range);
    if (empty($file->error)) {
      
      // insere no mapper
      $Model =& $this->Model;
      
      $Model['arq'] = $file->name;
      foreach ($this->form_data as $name => $val) {
        $Model[$name] = $val;
      }
      
      if ($Model->insert()) {
        //$file->id = $Model['id'];
        $file->added = true;
      }
    }
    return $file;
  }
  
  protected function get_file_objects($iteration_method = 'get_file_object') {
    $array = array();
    
    $Model =& $this->Model;
    $Model->addBehavior('Collection');
    $Model->select();
    $Model->Mapper->sort('ord');
    foreach ($Model as $reg) {
      $array[] = $reg['arq'];
    }
    
    return array_values(array_filter(array_map(
                            array($this, $iteration_method), $array
    )));
  }

  protected function remove_db($filename) {
    $return = false;
    // deleta no mapper
    $Model =& $this->Model;
    if ($Model->find($filename) !== false) { // procura o arquivo
      $Model->select();

      $return = $Model->delete();
    }
    return $return;
  }
  
  /*public function delete($print_response = true) {
    $response = parent::delete(false);
    //if ($response) {
      foreach ($response as $name => &$deleted) {
        if ($deleted) {
          // deleta no mapper
          $Model =& $this->Model;
          dump($Model->find($name));
          if ($Model->find($name) !== false) { // procura o arquivo
            $Model->select();

            $deleted = $Model->delete();
          }

        }
      }
      unset($deleted);
    //}
    return $this->generate_response($response, $print_response);
  }*/

  /*protected function add_db($file, $index) {
    $return = parent::add_db($file, $index);
    
    $Model =& $this->Model;
    
    
    //$return && $return = file_put_contents(DJCK.DS.'config-'.$file->name.'.txt', $file->ord.'='.$index);
    return $return;
  }
  
  protected function remove_db($filename) {
    $return = parent::remove_db($filename);
    $return && $return = @unlink(DJCK.DS.'config-'.$filename.'.txt');
    return $return;
  }*/

  protected function generate_response($content, $print_response = true) {
    parent::generate_response($content, true);
  }
  
  function process() {
    $this->options['upload_dir'] .= str_replace('/', DS, $this->destination_folder);
    $this->options['upload_url'] .= $this->destination_folder;
    
    if (!empty($this->image_versions)) {
      foreach ($this->image_versions as $k => $o) {
        $this->options['image_versions'][$k] = $o;
      }
    }
    
    $this->initialize();
  }
  
}