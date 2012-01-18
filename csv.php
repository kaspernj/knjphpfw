<?php
/**
 * TODO
 *
 * PHP version 5
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */

/**
 * TODO
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class knj_csv
{
	/**
	 * TODO
	 *
	 * @param array  $arr  TODO
	 * @param string $del  TODO
	 * @param string $encl TODO
	 *
	 * @return string TODO
	 */
	static function arr_to_csv($arr, $del, $encl)
	{
		$str = "";
		foreach ($arr as $value) {
			$value_safe = str_replace($del, "", $value);
			$value_safe = str_replace($encl, "", $value);

			if (strlen($str) > 0) {
				$str .= $del;
			}

			$str .= $encl .$value_safe .$encl;
		}

		return $str;
	}

	/**
	 * TODO
	 *
	 * @param array $args TODO
	 */
	function __construct($args)
	{
		if (!is_array($args)) {
			throw new exception(_("Invalid arguments."));
		}

		$this->args = $args;
		$this->read_size = 4096 * 4;
		$this->del = ";";
		$this->encl = "\"";
		$this->lines_count = 0;

		if (!array_key_exists("nl", $this->args)) {
			$this->args["nl"] = "\n";
		}

		$this->fp = fopen($args["path"], "r");
		if (!$this->fp) {
			throw new exception(_("Path could not be opened in read mode."));
		}
	}

	/**
	 * TODO
	 *
	 * @return array TODO
	 */
	function line()
	{
		$this->lines_count++;
		$this->line = fgets($this->fp, $this->read_size);

		$arr = array();
		$this->col_count = 0;
		while (($data = $this->line_new()) !== false) {
			$this->col_count++;

			if ($this->args["utf8_encode"]) {
				$arr[] = knj_strings::utf8force($data);
			} else {
				$arr[] = $data;
			}
		}

		return $arr;
	}

	/**
	 * TODO
	 *
	 * @return string TODO
	 */
	function line_new()
	{
		if (strlen($this->line) <= 0) {
			return false;
		}

		$char = substr($this->line, 0, 1);

		if ($char == $this->encl) {
			$this->line = substr($this->line, 1);

			while (true) {
				$next_found = $this->encl . $this->del;
				$next = strpos($this->line, $next_found);
				if ($next !== false) {
					break;
				}

				$next_found = $this->encl . $this->args["nl"];
				$next = strpos($this->line, $next_found);
				if ($next !== false) {
					break;
				}

				if ($this->args["multiple_lines"] && !feof($this->fp)) {
					$this->lines_count++;
					$this->line .= fgets($this->fp, $this->read_size);
					continue;
				}

				$next_found = $this->encl;
				$next = strpos($this->line, $next_found);
				if (feof($this->fp) && $next !== false) {
					break;
				}

				$msg = _("Could not find the next enclosure on line %s.");
				throw new exception(sprintf($msg, $this->lines_count));
			}

			$data = substr($this->line, 0, $next);
			$this->line = substr($this->line, $next + strlen($next_found));

			return $data;
		} elseif ($char == $this->del) {
			$this->line = substr($this->line, 1);
			return "";
		}

		$pos_del = strpos($this->line, $this->del);
		if ($pos_del === false) {
			$data = $this->line;
			$this->line = "";
			return $data;
		}

		$data = substr($this->line, 0, $pos_del);
		$this->line = substr($this->line, $pos_del + 1);
		return $data;
	}
}

