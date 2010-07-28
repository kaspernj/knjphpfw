<?php
	class dkcvr_cvrnotfound extends exception{}
	
	class dkcvr{
		private $http;
		
		function __construct(){
			require_once("knj/class_knj_httpbrowser.php");
			$this->http = new knj_httpbrowser();
			$this->http->connect("cvr.dk");
		}
		
		function getByCVR($cvr){
			$html = $this->http->getAddr("Site/Forms/PublicService/DisplayCompany.aspx?cvrnr=" . $cvr);
			
			if (!preg_match_all("/<td class=\"fieldname\" valign=\"top\">\s*([\S ]*):\s*<\/td>\s*<td class=\"fieldvalue\" valign=\"top\">\s*(.*)(<\/td>|\n)/", $html, $matches)){
				throw new Exception("Could not match any information.");
			}
			
			$arr_replace = array(
				"<br/>" => "\n",
				"</td>" => ""
			);
			
			if (!preg_match("/<div class=\"titletext\">\s*([\s\S]+)<\/div>/U", $html, $match)){
				throw new Exception("Could not match title.");
			}
			
			$title = trim($match[1]);
			$title = preg_replace("/[ ][ ]+/", " ", $title);
			$info = array(
				"Name" => $title
			);
			
			if (!$title){
				throw new dkcvr_cvrnotfound("The CVR-number was not found.");
			}
			
			foreach($matches[1] AS $key => $title){
				$write = true;
				$value = trim(strtr($matches[2][$key], $arr_replace));
				
				if ($title == "Statstidende meddelelser"){
					$write = false;
				}
				
				if ($title == "Adresse"){
					if (preg_match("/([0-9]{4}) (.*)/", $value, $match)){
						$info["AddressZIP"] = trim($match[1]);
						$info["AddressCity"] = trim($match[2]);
					}
					
					if (preg_match("/^(.+) ([0-9]+[\s\S]+)\n/U", $value, $match)){
						$match[2] = preg_replace("/\s*<br \/>True\s*/", "", $match[2]);
						
						$info["Address"] = $match[1];
						
						$no = htmlspecialchars_decode($match[2]);
						$no = strtr($no, array(
								"<br />" => ""
						));
						$no = preg_replace("/\s+/", " ", $no);
						
						$info["AddressNo"] = $no;
					}
					
					$title = "AddressFull";
				}
				
				if ($title == "Telefon"){
					$title = "PhoneNo";
				}
				
				if ($write == true){
					$info[$title] = $value;
				}
			}
			
			return $info;
		}
	}
?>