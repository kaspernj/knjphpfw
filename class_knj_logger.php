<?php
/**
 * This class handels logging to a screen and to files.
 */
class knj_logger
{
    private $log_file = array();
    private $log_screen = array();
    private $backtrace = false;
    private $loggers = array();

    const debug = 1;		//Log-messages which should be used for debugging.
    const notice = 2;		//Notices - if something mysterious is going on.
    const program = 3;	//Program - programlogs. If someone is trying to delete something, that he cant, or he is just doing something with the program.
    const warning = 4;	//Warnings - systemerrors.

    /**
     * Adds outputs to the logger.
     */
    function setOutput($options)
    {
        foreach ($options as $value) {
            if (!$value["level"]) {
                $value["level"] = knj_logger::warning;
            }

            if (!$value["show"]) {
                $value["show"] = array(
                    "file" => false,
                    "line" => false,
                    "function" => false
                );
            }

            if ($value["type"] == "file") {
                if (!file_exists($value["filename"])) {
                    file_put_contents($value["filename"], "[" . date("d/m Y H:i") . "] Log created...\n");
                }

                if (!is_writeable($value["filename"])) {
                    throw new Exception("The log-file is not writeable (" . $value["filename"] . ")");
                }

                $value["fp"] = fopen($value["filename"], "a");

                $this->loggers[] = $value;
            } elseif ($value["type"] == "screen") {
                $this->loggers[] = $value;
            } else {
                throw new Exception("Unsupported log-mode: " . $key);
            }
        }
    }

    /**
     * Sets wherever a backtrace should be generated (can take up a lot of memory, if you log a lot).
     */
    function setBackTrace($value)
    {
        $this->backtrace = $value;
    }

    /**
     * Logs a message.
     */
    function log($msg, $level = knj_logger::warning)
    {
        if ($this->backtrace) {
            $debug = debug_backtrace();
        }

        //log to files.
        foreach ($this->loggers as $logopt) {
            $logthis = false;
            if (is_array($logopt["showonly"])) {
                foreach ($logopt["showonly"] as $value) {
                    if ($value == $level) {
                        $logthis = true;
                    }
                }
            } elseif ($level >= $logopt["level"]) {
                $logthis = true;
            }

            if (is_array($logopt["dontshow"])) {
                foreach ($logopt["dontshow"] as $value) {
                    if ($value == $level) {
                        $logthis = false;
                    }
                }
            }

            if ($logthis) {
                $realmsg = "";
                if ($logopt["show"]["date"]) {
                    $realmsg .= "[" . date("d/m/Y H:i") . "] ";
                }
                if ($logopt["show"]["file"] && $debug) {
                    $realmsg .= basename($debug[0]["file"]);
                }
                if ($logopt["show"]["line"]) {
                    if ($logopt["show"]["file"]) {
                        $realmsg .= ":";
                    }

                    if ($debug) {
                        $realmsg .= $debug[0]["line"];
                    }
                }
                if ($logopt["show"]["function"] && $debug) {
                    if ($logopt["show"]["file"] || $logopt["show"]["line"]) {
                        $realmsg .= "->";
                    }

                    $realmsg .= $debug[1]["function"] . "()";
                }
                if ($logopt["show"]["file"] || $logopt["show"]["line"] || $logopt["show"]["function"]) {
                    $realmsg .= ": ";
                }
                $realmsg .= $msg . "\n";

                if ($logopt["type"] == "file") {
                    fwrite($logopt["fp"], $realmsg);
                } elseif ($logopt["type"] == "screen") {
                    echo $realmsg;
                } else {
                    throw new Exception("Unsupported log-mode: " . $logopt["type"]);
                }
            }
        }

        if ($debug) {
            unset($debug);
        }

        unset($realmsg);
    }
}

