<?php

class knj_table_writer
{
    function __construct($args)
    {
        $this->args = $args;

        if (!$this->args["filepath"]) {
            throw new exception("No filepath was given.");
        }

        if ($this->args["format"] == "csv") {
            $this->fp = fopen($this->args["filepath"], "w");
        } elseif ($this->args["format"] == "excel5" or $this->args["format"] == "excel2007") {
            $this->objPHPExcel = new PHPExcel();

            if ($this->args["creator"]) {
                $this->objPHPExcel->getProperties()->setCreator($this->args["creator"]);
            }

            if ($this->args["last_modified_by"]) {
                $this->objPHPExcel->getProperties()->setLastModifiedBy($this->args["last_modified_by"]);
            }

            if ($this->args["title"]) {
                $this->objPHPExcel->getProperties()->setTitle($this->args["title"]);
            }

            if ($this->args["subject"]) {
                $this->objPHPExcel->getProperties()->setSubject($this->args["subject"]);
            }

            if ($this->args["descr"]) {
                $this->objPHPExcel->getProperties()->setDescription($this->args["descr"]);
            }

            $this->objPHPExcel->setActiveSheetIndex(0);
            $this->sheet = $this->objPHPExcel->getActiveSheet();

            $this->colarr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
            $this->linec = 0;

            if ($this->args["date_format"]) {
                $this->date_format_excel = strtr($this->args["date_format"], array(
                "d" => "dd",
                "m" => "mm",
                "Y" => "yyyy",
                "y" => "yy",
                "-" => "\\-"
                ));
            }
        } else {
            throw new exception("Unknown format: " . $this->args["format"]);
        }
    }

    function write_row($arr)
    {
        if ($this->args["format"] == "csv") {
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    if ($value["type"] == "decimal") {
                        $arr[$key] = number_format($value["value"], $this->args["amount_decimals"], $this->args["amount_dsep"], $this->args["amount_tsep"]);
                    } elseif ($value["type"] == "date") {
                        if (!$this->args["date_format"]) {
                            throw new exception("Date-value given but no date-format in arguments.");
                        }

                        $arr[$key] = date($this->args["date_format"], $value["value"]);
                    } else {
                        throw new exception("Unknown type: " . $value["type"]);
                    }
                }
            }

            $line = knj_csv::arr_to_csv($arr, $this->args["expl"], $this->args["surr"]) . "\n";

            if ($this->args["encoding"] == "iso8859-1") {
                $line = utf8_decode($line);
            }

            fwrite($this->fp, $line);
        } elseif ($this->args["format"] == "excel5" or $this->args["format"] == "excel2007") {
            $col_no = 0;
            $this->linec++;

            foreach ($arr as $setval) {
                $letter = $this->colarr[$col_no];
                if (!$letter) {
                    throw new exception(sprintf("Letter could not be found for '%s'.", $letter));
                }

                $colval = $letter . $this->linec;

                if (is_array($setval)) {
                    if ($setval["type"] == "decimal") {
                        $this->sheet->setCellValue($colval, $setval["value"]);
                        $this->sheet->getStyle($colval)->getNumberFormat()->setFormatCode("#,##0.00");
                    } elseif ($setval["type"] == "date") {
                        if (!$this->args["date_format"]) {
                        throw new exception("Date-value given but no date-format in arguments.");
                        }

                        /** NOTE: PHPExcel apparently substracts one day - this fixes it... */
                        $setval["value"] = strtotime("+1 day", $setval["value"]);

                        $this->sheet->setCellValue($colval, PHPExcel_Shared_Date::PHPToExcel($setval["value"]));
                        $this->sheet->getStyle($colval)->getNumberFormat()->setFormatCode($this->date_format_excel);
                    } else {
                        throw new exception("Unknown type: " . $value["type"]);
                    }
                } else {
                    $this->sheet->setCellValue($colval, $setval);
                }

                $col_no++;
            }
        } else {
            throw new exception("Unknown format: " . $this->args["format"]);
        }
    }

    function close()
    {
        if ($this->args["format"] == "csv") {
            fclose($this->fp);
        } elseif ($this->args["format"] == "excel5") {
            $objWriter = new PHPExcel_Writer_Excel5($this->objPHPExcel);
            $objWriter->save($this->args["filepath"]);
        } elseif ($this->args["format"] == "excel2007") {
            $objWriter = new PHPExcel_Writer_Excel2007($this->objPHPExcel);
            $objWriter->save($this->args["filepath"]);
        } else {
            throw new exception("Unknown format: " . $this->args["format"]);
        }
    }

    function ext()
    {
        if ($this->args["format"] == "csv") {
            return "csv";
        } elseif ($this->args["format"] == "excel5") {
            return "xls";
        } elseif ($this->args["format"] == "excel2007") {
            return "xlsx";
        } else {
            throw new exception("Unknown format: " . $this->args["format"]);
        }
    }

    function ftype()
    {
        if ($this->args["format"] == "csv") {
            return "text/csv";
        } elseif ($this->args["format"] == "excel5" or $this->args["format"] == "excel2007") {
            return "application/ms-excel";
        } else {
            throw new exception("Unknown format: " . $this->args["format"]);
        }
    }
}

