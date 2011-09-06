<?

class knjdb_async{
  function __construct($args = array()){
    $this->args = $args;
    $this->conn = $args["conn"];
    $this->conn->query("SET autocommit=0;");
  }

  function query($str){
    if ($this->query_ran){
      $this->read_res();
    }

    if ($this->conn->multi_query("START TRANSACTION; " . $str . "; COMMIT;") === false){
      throw new exception("Query failed: " . $this->conn->error);
    }

    $this->query_ran = true;
  }

  function read_res(){
    while($this->conn->next_result()){
      if ($res = $this->conn->store_result()){
        while($res->fetch_row()){
          //nothing.
        }
        $res->free();
      }
    }
  }
}

