<?php

class knjdb_result{
  public $knjdb;
  public $driver;
  public $result;
  
  function __construct(knjdb $knjdb, $driver, $result){
    $this->knjdb = $knjdb;
    $this->driver = $driver;
    $this->result = $result;
  }
  
  function fetch(){
    return $this->driver->fetch($this->result);
  }
  
  function free(){
    return $this->driver->free($this->result);
  }
}