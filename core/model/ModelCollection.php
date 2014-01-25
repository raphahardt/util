<?php

namespace Djck\model;

use Djck\Core;

Core::uses('ModelRegister', 'Djck\model');

class ModelCollection extends ModelRegister implements \ArrayAccess, \Countable, \SeekableIterator {

  public $filters = array();
  public $orders = array();

  protected $data_collection;

  public function __construct(Model $Model, $filters) {

    $this->filters = $filters;
    $this->orders = $Model->orders;

    $this->data_collection = new \Djck\types\StorageArray();

    parent::__construct($Model);
  }

  public function setCollection($data) {
    if (!is_array($data) && !($data instanceof \Iterator)) {
      throw new \Exception('Os dados do collection precisam ser um array/iterator');
    }
    if ($data instanceof \Djck\types\StorageArray) {
      $this->data_collection = $data;
    } else {
      $this->data_collection->clean();
      if (count($data) > 0) {
        foreach ($data as $row) {
          $this->data_collection->push($row);
        }
      }
    }
  }

  public function count() {
    return $this->data_collection->count();
  }

  public function current() {
    $data = $this->data_collection->current();
    if (isset($data['data'])) {
      $data = $data['data'];
    }
    return $data;
  }

  public function key() {
    return $this->data_collection->key();
  }

  public function next() {
    return $this->data_collection->next();
  }

  public function offsetExists($offset) {
    if (!is_numeric($offset)) {
      return parent::offsetExists($offset);
    }
    if (isset($this->data_collection[$offset]['data'])) {
      return isset($this->data_collection[$offset]['data']);
    }
    return isset($this->data_collection[$offset]);
  }

  public function offsetGet($offset) {
    if (!is_numeric($offset)) {
      return parent::offsetGet($offset);
    }
    if (isset($this->data_collection[$offset]['data'])) {
      return $this->data_collection[$offset]['data'];
    }
    return $this->data_collection[$offset];
  }

  public function offsetSet($offset, $value) {
    if (!is_numeric($offset)) {
      parent::offsetSet($offset, $value);
      return;
    }
    throw new \Exception('$collection[] = val não suportado');
  }

  public function offsetUnset($offset) {
    if (!is_numeric($offset)) {
      parent::offsetUnset($offset);
      return;
    }
    throw new \Exception('unset($collection[key]) não suportado');
  }

  public function rewind() {
    $this->data_collection->rewind();
  }

  public function seek($position) {
    $this->data_collection->seek($position);
  }

  public function valid() {
    return $this->data_collection->valid();
  }

}