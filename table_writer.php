<?

class knj_table_writer{
	function __construct($args){
		$this->args = $args;
		
		if (!$this->args["filepath"]){
			throw new exception("No filepath was given.");
		}
		
		if ($this->args["format"] == "csv"){
			$this->fp = fopen($this->args["filepath"], "w");
		}elseif($this->args["format"] == "excel5" or $this->args["format"] == "excel2007"){
			$this->objPHPExcel = new PHPExcel();
			
			if ($this->args["creator"]){
				$this->objPHPExcel->getProperties()->setCreator($this->args["creator"]);
			}
			
			if ($this->args["last_modified_by"]){
				$this->objPHPExcel->getProperties()->setLastModifiedBy($this->args["last_modified_by"]);
			}
			
			if ($this->args["title"]){
				$this->objPHPExcel->getProperties()->setTitle($this->args["title"]);
			}
			
			if ($this->args["subject"]){
				$this->objPHPExcel->getProperties()->setSubject($this->args["subject"]);
			}
			
			if ($this->args["descr"]){
				$this->objPHPExcel->getProperties()->setDescription($this->args["descr"]);
			}
			
			$this->objPHPExcel->setActiveSheetIndex(0);
			$this->sheet = $this->objPHPExcel->getActiveSheet();
			
			$this->colarr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
			$this->linec = 0;
		}else{
			throw new exception("Unknown format: " . $this->args["format"]);
		}
	}
	
	function write_row($arr){
		if ($this->args["format"] == "csv"){
			$line = knj_csv::arr_to_csv($arr, $this->args["expl"], $this->args["surr"]) . "\n";
			
			if ($this->args["encoding"] == "iso8859-1"){
				$line = utf8_decode($line);
			}
			
			fwrite($this->fp, $line);
		}elseif($this->args["format"] == "excel5" or $this->args["format"] == "excel2007"){
			$col_no = 0;
			$this->linec++;
			
			foreach($arr as $setval){
				$letter = $this->colarr[$col_no];
				if (!$letter){
					throw new exception(sprintf("Letter could not be found for '%s'.", $letter));
				}
				
				$colval = $letter . $this->linec;
				$this->sheet->setCellValue($colval, $setval);
				$col_no++;
			}
		}else{
			throw new exception("Unknown format: " . $this->args["format"]);
		}
	}
	
	function close(){
		if ($this->args["format"] == "csv"){
			fclose($this->fp);
		}elseif($this->args["format"] == "excel5"){
			$objWriter = new PHPExcel_Writer_Excel5($this->objPHPExcel);
			$objWriter->save($this->args["filepath"]);
		}elseif($this->args["format"] == "excel2007"){
			$objWriter = new PHPExcel_Writer_Excel2007($this->objPHPExcel);
			$objWriter->save($this->args["filepath"]);
		}else{
			throw new exception("Unknown format: " . $this->args["format"]);
		}
	}
	
	function ext(){
		if ($this->args["format"] == "csv"){
			return "csv";
		}elseif($this->args["format"] == "excel5"){
			return "xls";
		}elseif($this->args["format"] == "excel2007"){
			return "xlsx";
		}else{
			throw new exception("Unknown format: " . $this->args["format"]);
		}
	}
	
	function ftype(){
		if ($this->args["format"] == "csv"){
			return "text/csv";
		}elseif($this->args["format"] == "excel5" or $this->args["format"] == "excel2007"){
			return "application/ms-excel";
		}else{
			throw new exception("Unknown format: " . $this->args["format"]);
		}
	}
}