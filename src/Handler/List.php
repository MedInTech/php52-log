<?php

class MedInTech_Log_Handler_List implements MedInTech_Log_Handler_IList
{
  public function __construct(array $list = array())
  {
    foreach ($list as $handler) {
      if (!($handler instanceof MedInTech_Log_Handler_Interface)) {
        throw new InvalidArgumentException("All elements must implements Log_IHandler");
      }
      $this->push($handler);
    }
  }

  private $list = array();
  public function current() { return current($this->list); }
  public function next() { next($this->list); }
  public function key() { return key($this->list); }
  public function valid() { return false !== current($this->list); }
  public function rewind() { reset($this->list); }

  public function push(MedInTech_Log_Handler_Interface $handler) { array_push($this->list, $handler); }
  public function unshift(MedInTech_Log_Handler_Interface $handler) { array_unshift($this->list, $handler); }
  public function pop() { return array_pop($this->list); }
  public function shift() { array_shift($this->list); }
  public function isEmpty() { return $this->getLength() === 0; }
  public function getLength() { return count($this->list); }
}
