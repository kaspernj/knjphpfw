<?php
/**
 * TODO
 *
 * PHP version 5
 *
 * @category framework
 * @package  knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  MIT http://www.opensource.org/licenses/mit-license.php
 * @link     https://github.com/kaspernj/knjphpfw
 */

require_once "knj/knjdb/interfaces/class_knjdb_driver_rows.php";

/**
 * TODO
 *
 * @category framework
 * @package  knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  MIT http://www.opensource.org/licenses/mit-license.php
 * @link     https://github.com/kaspernj/knjphpfw
 */
class knjdb_mysqli_rows implements knjdb_driver_rows
{

	/**
	 * TODO
	 *
	 * @param object $knjdb TODO
	 */
	function __construct(knjdb $knjdb)
	{
		$this->knjdb = $knjdb;
		$this->driver = $this->knjdb->conn;
	}

	/**
	 * TODO
	 *
	 * @param object $row TODO
	 *
	 * @return
	 */
	function getObInsertSQL(knjdb_row $row)
	{
		$data = $row->getAsArray();
		$table = $row->getTable();

		return $this->getArrInsertSQL($table->get("name"), $data);
	}

	/**
	 * TODO
	 *
	 * @param string $tablename TODO
	 * @param array  $data      TODO
	 *
	 * @return string
	 */
	function getArrInsertSQL($tablename, $data)
	{
		if (!is_array($data)) {
			throw new Exception("This function only accepts an array.");
		}

		$sql = "INSERT INTO " .$this->driver->sep_table .$tablename
		.$this->driver->sep_table ." (";

		$count = 0;
		foreach ($data as $key => $value) {
			if ($count > 0) {
				$sql      .= ", ";
				$sql_vals .= ", ";
			}

			$sql .= $this->driver->sep_col .$key .$this->driver->sep_col;
			$sql_vals .= $this->driver->sep_val .$this->knjdb->sql($value)
			.$this->driver->sep_val;

			$count++;
		}

		$sql .= ") VALUES (" .$sql_vals .");";

		return $sql;
	}
}

