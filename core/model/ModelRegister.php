<?php

namespace Djck\model;

use Djck\system\AbstractObject;

class ModelRegister extends AbstractObject implements \ArrayAccess {

  public $columns = array();
  public $from = 0;
  public $to = 0;
  public $limit = 0;
  public $orders = array();

  protected $data = array();

  public $dirty = false;
  public $dirty_columns = array();
  public $persisted = false;
  public $deleted = false;

  public function __construct(Model $Model) {

    $this->columns = $Model->columns;
    $this->from = $Model->from;
    $this->to = $Model->to;
    $this->limit = $Model->limit;

    parent::__construct();
  }

  public function offsetExists($offset) {
    return isset($this->data[$offset]);
  }

  public function offsetGet($offset) {
    return $this->data[$offset];
  }

  public function offsetSet($offset, $value) {
    $this->dirty = true;
    $this->dirty_columns[ $offset ] = $offset;
    $this->data[$offset] = $value;
  }

  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }

  // deprecated: usar model->delete(reg) em vez de reg->delete()
  /*public function delete() {
    $this->dirty = true;
    $this->deleted = true;
  }*/

  public function setData($data) {
    $this->data = $data;
  }

  public function getData() {
    return $this->data;
  }

  public function getUpdatedColumns() {
    return array_values($this->dirty_columns);
  }

  public function isDirty() {
    return (bool)$this->dirty;
  }

  public function isPersisted() {
    return (bool)$this->persisted;
  }

  public function isDeleted() {
    return (bool)$this->deleted;
  }

  public function setPersisted() {
    $this->persisted = true;
  }

  public function setDirty($dirty_columns = array()) {
    $this->dirty = true;
    if (!empty($dirty_columns)) {
      foreach ($dirty_columns as $col) {
        $this->dirty_columns[$col] = $col;
      }
    }
  }

  public function setPristine() {
    $this->dirty = false;
    $this->dirty_columns = array();
  }

  public function setDeleted() {
    $this->deleted = true;
  }

}
