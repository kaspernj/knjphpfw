<?
	class knj_xml{
		public $tags;
		public $beguns;

		/**
		 * The constructor.
		*/
		function __construct($filecont){
			global $knj_xml;

			$this->xmlcount = 0;

			if (!$knj_xml[xmlobject]){
				$xml_parser = xml_parser_create();
			}else{
				$xml_parser = $knj_xml[xmlobject];
			}

			xml_set_element_handler($xml_parser, array($this, "xmlStart"), array($this, "xmlEnd"));
			xml_set_character_data_handler($xml_parser, array($this, "xmlData"));

			xml_parse($xml_parser, $filecont);
			xml_parser_free($xml_parser);
		}

		/**
		 * A new XML-element has been read.
		*/
		function xmlStart($parser, $name, $attrs){
			$this->beguns[] = $name;

			$eval = "\$this->tags";
			foreach($this->beguns AS $value){
				$eval .= "['" . $value . "']";
			}

			$eval .= " = array();";
			eval($eval);
		}

		/**
		 * A XML-element has been ended.
		*/
		function xmlEnd($parser, $name){
			$temp = array_reverse($this->beguns);
			$newarr = $temp;
			foreach($this->beguns AS $key => $value){
				unset($newarr[$key]);

				if ($value == $name){
					break;
				}
			}

			$this->beguns = array_reverse($newarr);
		}

		/**
		 * Some data for the XML-element has been read.
		*/
		function xmlData($parser, $handler){
			$eval = "\$this->tags";
			foreach($this->beguns AS $value){
				$eval .= "['" . $value . "']";
			}

			$eval .= " = '" . $handler . "';";
			eval($eval);
		}

		/**
		 * Returns a XML-content as an array.
		*/
		static function asArray($filecont){
			$knj_xml = new knj_xml($filecont);
			$tags = $knj_xml->tags;
			unset($knj_xml);

			return $tags;
		}
	}

