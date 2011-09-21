<?
	/** This class contains functions for handelig documents. */
	class knj_doc{
		/** Converts a Word-content (or Word-file) into plain-text. */
		static function wordtext($cont, $type = null){
			require_once "knj/functions_knj_os.php";
			$catdoc_status = knj_os::checkCMD("catdoc");
			if (!$catdoc_status){
				throw new Exception("catdoc could not be found on this system.");
			}
			
			if (!$type){
				if (file_exists($cont)){
					$type = "file";
				}elseif(strlen($cont) >= 200){
					$type = "content";
				}else{
					throw new Exception("Could not register the type.");
				}
			}
			
			if ($type == "file"){
				$filename = $cont;
			}elseif($type = "content"){
				$filename = "/tmp/knj_documents_catdoc_" . time() . ".doc";
				$status = file_put_contents($filename, $cont);
				if (!$status){
					throw new Exception("Could not write temp-document to: " . $filename);
				}
			}
			
			$doc = knj_os::shellCMD($catdoc_status[filepath] . " " . $filename);
			return $doc[result];
		}
	}

