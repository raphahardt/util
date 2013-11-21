<?php

Core::uses('AppController', 'controller');

/**
 * Description of HomeController
 *
 * @author usuario
 */
class ErrorController extends AppController {
  
  function index() {
    echo $this->request->referer();
  }
  
}