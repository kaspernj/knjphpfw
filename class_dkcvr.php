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
class dkcvr_cvrnotfound extends exception
{
}

/**
 * TODO
 *
 * @category Framework
 * @package  Knjphpfw
 * @author   Kasper Johansen <kaspernj@gmail.com>
 * @license  Public domain http://en.wikipedia.org/wiki/Public_domain
 * @link     https://github.com/kaspernj/knjphpfw
 */
class dkcvr
{
	private $_http;

	/**
	 * TODO
	 */
	function __construct()
	{
		include_once "knj/class_knj_httpbrowser.php";
		$this->_http = new knj_httpbrowser();
		$this->_http->connect("cvr.dk");
	}

	/**
	 * TODO
	 *
	 * @param string $cvr TODO
	 *
	 * @return TODO
	 */
	function getByCVR($cvr)
	{
		$html = $this->_http->getAddr(
			"Site/Forms/PublicService/DisplayCompany.aspx?cvrnr=" .$cvr
		);

		$result = preg_match_all(
			'<td class="fieldname" valign="top">\s*([\S ]*):\s*<\/td>\s*<td class="fieldvalue" valign="top">\s*(.*)(<\/td>|\n)/u',
			$html,
			$matches
		);
		if (!$result) {
			throw new Exception("Could not match any information.");
		}

		$arr_replace = array(
			"<br/>" => "\n",
			"</td>" => ""
		);

		$result = preg_match(
			'<div class="titletext">\s*([\s\S]+)<\/div>/U',
			$html,
			$match
		);
		if (!$result) {
			throw new Exception("Could not match title.");
		}

		$title = trim($match[1]);
		$title = preg_replace("/[ ][ ]+/", " ", $title);
		$info = array(
			"Name" => $title
		);

		if (!$title) {
			throw new dkcvr_cvrnotfound("The CVR-number was not found.");
		}

		foreach ($matches[1] AS $key => $title) {
			$write = true;
			$value = trim(strtr($matches[2][$key], $arr_replace));

			if ($title == "Statstidende meddelelser") {
				$write = false;
			}

			if ($title == "Adresse") {
				if (preg_match("/([0-9]{4}) (.*)/", $value, $match)) {
					$info["AddressZIP"] = trim($match[1]);
					$info["AddressCity"] = trim($match[2]);
				}

				if (preg_match("/^(.+) ([0-9]+[\s\S]+)\n/U", $value, $match)) {
					$match[2] = preg_replace("/\s*<br \/>True\s*/", "", $match[2]);

					$info["Address"] = $match[1];

					$no = htmlspecialchars_decode($match[2]);
					$data = array(
						"<br />" => ""
					);
					$no = strtr($no, $data);
					$no = preg_replace("/\s+/", " ", $no);

					$info["AddressNo"] = $no;
				}

				$title = "AddressFull";
			}

			if ($title == "Telefon") {
				$title = "PhoneNo";
			}

			if ($write == true) {
				$info[$title] = $value;
			}
		}

		return $info;
	}
}

