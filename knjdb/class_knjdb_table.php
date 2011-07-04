<?

class knjdb_table{
	public $knjdb;
	public $data;
	
	public $columns = array();
	public $columns_changed = true;
	
	public $indexes = array();
	public $indexes_changed = true;
	
	function __construct($knjdb, $data){
		$this->knjdb = $knjdb;
		$this->data = $data;
	}
	
	function rename($newname){
		$this->knjdb->tables()->renameTable($this, $newname);
		$this->data["name"] = $newname;
	}
	
	/** Returns a key from the row. */
	function get($key){
		if (!array_key_exists($key, $this->data)){
			throw new Exception("The key does not exist: \"" . $key . "\".");
		}
		
		return $this->data[$key];
	}
	
	function getIndexByName($name){
		if ($this->indexes[$name]){
			return $this->indexes[$name];
		}
		
		foreach($this->getIndexes() AS $index){
			if ($index->get("name") == $name){
				return $index;
			}
		}
		
		throw new Exception("Index not found: \"" . $name . "\" on table \"" . $this->get("name") . "\".");
	}
	
	/** Count the rows for a table. */
	function countRows(){
		$d_c = $this->knjdb->query("SELECT COUNT(*) AS count FROM " . $this->knjdb->conn->sep_table . $this->get("name") . $this->knjdb->conn->sep_table)->fetch();
		return $d_c["count"];
	}
	
	function addColumns($columns){
		$this->knjdb->columns()->addColumns($this, $columns);
	}
	
	function addIndex($cols, $name = null){
		if (!$name){
			$name = "table_" . $this->get("name") . "_cols";
			
			foreach($cols AS $col){
				$name .= "_" . $col->get("name");
			}
		}
		
		$this->knjdb->indexes()->addIndex($this, $cols, $name);
	}
	
	function removeIndex(knjdb_index $index){
		$this->knjdb->indexes()->removeIndex($this, $index);
	}
	
	function removeColumn(knjdb_column $col){
		$this->knjdb->columns()->removeColumn($this, $col);
	}
	
	function getColumns(){
		return $this->knjdb->columns()->getColumns($this);
	}
	
	function getColumn($name){
		if ($this->columns[$name]){
			return $this->columns[$name];
		}
		
		$cols = $this->getColumns();
		foreach($cols AS $col){
			if ($col->get("name") == $name){
				return $col;
			}
		}
		
		throw new Exception("The column was not found (" . $name . ").");
	}
	
	function getIndexes(){
		return $this->knjdb->indexes()->getIndexes($this);
	}
	
	function drop(){
		$this->knjdb->tables()->dropTable($this);
		unset($this->columns);
		unset($this->columns_changed);
	}
	
	function truncate(){
		$this->knjdb->tables()->truncateTable($this);
	}
	
	function optimize(){
		$this->knjdb->tables()->optimizeTable($this);
	}
}