<?

/** Handels error on FComputer's website. */
function knj_error_reporter_error_handeler($errno, $errmsg, $filename, $linenum, $vars, $args = null){
	if ($errno == E_NOTICE || $errno == E_STRICT){
		//Do not log notices and strict-errors.
		return null;
	}
	
	$errortype = array (  
		E_ERROR => 'Error',  
		E_WARNING => 'Warning',  
		E_PARSE => 'Parsing Error',  
		E_NOTICE => 'Notice',  
		E_CORE_ERROR => 'Core Error',  
		E_CORE_WARNING => 'Core Warning',  
		E_COMPILE_ERROR => 'Compile Error',  
		E_COMPILE_WARNING => 'Compile Warning',  
		E_USER_ERROR => 'User Error',  
		E_USER_WARNING => 'User Warning',  
		E_USER_NOTICE => 'User Notice',  
		E_STRICT => 'Runtime Notice'  
	);
	
	if (!$args["hideerror"]){
		echo $errortype[$errno] . ": " . utf8_encode($errmsg) . " in " . $filename . " on line " . $linenum . ".\n";
	}
	
	$backtrace = debug_backtrace();
	$trace = "";
	foreach($backtrace AS $key => $value){
		$trace .= "#" . $key . " " . $value["file"] . "(" . $value["line"] . "): " . $value["function"] . "()\n";
	}
	
	$mail_body = "An error occurred on the website at " .date("d/m Y - H:i") . ".\n\n";
	
	$mail_body .= "*URL*:\nhttp://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\n\n";
	
	$mail_body .= "*File*:\n" . $filename . ":" . $linenum . "\n\n";
	
	$mail_body .= "*Error text*:\n" . utf8_encode($errmsg) . "\n\n";
	$mail_body .= "*Trace*:\n" . utf8_encode($trace) . "\n\n";
	
	$mail_body .= knj_error_reporter_getData();
	
	knj_error_reporter_email($mail_body);
}

function knj_error_reporter_getData(){
	$mail_body = "";
	
	$mail_body .= "*Client IP*:\n";
	$mail_body .= $_SERVER["REMOTE_ADDR"] . "\n\n";
	
	$mail_body .= "*Client user-agent*:\n";
	$mail_body .= $_SERVER["HTTP_USER_AGENT"] . "\n\n";
	
	$mail_body .= "*Post-data*\n";
	if (count($_POST) <= 0){
		$mail_body .= "No post-data.";
	}else{
		$mail_body .= print_r($_POST, true);
	}
	
	$mail_body .= "\n\n";
	
	$mail_body .= "*Get-data*\n";
	if (count($_GET) <= 0){
		$mail_body .= "No get-data.";
	}else{
		$mail_body .= print_r($_GET, true);
	}
	
	$mail_body .= "\n\n";
	
	$mail_body .= "*Server-data*\n";
	$mail_body .= print_r($_SERVER, true);
	
	return $mail_body;
}

function knj_error_reporter_exception_handler($exc){
	$mail_body = "An exception occurred on the website at " .date("d/m Y - H:i") . ".\n\n";
	
	$mail_body .= "*URL*:\nhttp://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\n\n";
	
	$mail_body .= "*File*:\n" . $exc->getFile() . ":" . $exc->getLine() . "\n\n";
	
	$mail_body .= "*Exception text*:\n" . $exc->getMessage() . "\n\n";
	$mail_body .= "*Trace*:\n" . $exc->getTraceAsString() . "\n\n";
	
	$mail_body .= knj_error_reporter_getData();
	
	knj_error_reporter_email($mail_body);
	
	echo "Uncaught exception '" . get_class($exc) . "' with message '" . $exc->getMessage() . "'\n\n" . $exc->getTraceAsString() . "\n\n";
}

function knj_error_reporter_email($msg){
	global $knj_error_reporter;
	
	$mail_headers = "";
	if ($knj_error_reporter["email_from"]){
		$mail_headers .= "From: " . $knj_error_reporter["email_from"] . "\r\n";
	}
	
	$mail_headers .= "Content-Type: text/plain; charset=UTF-8; format=flowed";
	
	if (count($knj_error_reporter["emails"]) > 0){
		if ($knj_error_reporter["email_title"]){
			$title = $knj_error_reporter["email_title"];
		}else{
			$title = "Error reported by knj's error reporter";
		}
		
		foreach($knj_error_reporter["emails"] AS $email){
			mail($email, $title, $msg, $mail_headers);
		}
	}
}


function knj_error_reporter_activate($args = array()){
	global $knj_error_reporter;
	
	foreach($args AS $key => $value){
		if ($key == "emails"){
			foreach($value AS $email){
				$knj_error_reporter["emails"][] = $email;
			}
		}elseif($key == "email"){
			$knj_error_reporter["emails"][] = $value;
		}elseif($key == "email_title" || $key == "email_from" || $key == "ignore_javabots" || $key == "ignore_bots"){
			$knj_error_reporter[$key] = $value;
		}else{
			throw new Exception("Invalid key: \"" . $key . "\".");
		}
	}
	
	require_once "knj/web.php";
	
	$activate = true;
	if (
		(
			(array_key_exists("ignore_javabots", $knj_error_reporter) and $knj_error_reporter["ignore_javabots"]) or
			(array_key_exists("ignore_bots", $knj_error_reporter) and $knj_error_reporter["ignore_bots"])
		) and (
			array_key_exists("HTTP_USER_AGENT", $_SERVER) and
			preg_match("/Java\/[0-9\.]+/i", $_SERVER["HTTP_USER_AGENT"], $match)
		)
	){
		$activate = false;
	}elseif($knj_error_reporter["ignore_bots"] && knj_browser::getOS() == "bot"){
		$activate = false;
	}
	
	if ($activate){
		set_exception_handler("knj_error_reporter_exception_handler");
		set_error_handler("knj_error_reporter_error_handeler");
		error_reporting(E_ALL ^ E_NOTICE);
		register_shutdown_function("knj_error_shutdown");
	}
}

function knj_error_reporter_deactivate(){
	global $knj_error_reporter;
	$knj_error_reporter = null;
	
	restore_error_handler();
	restore_exception_handler();
}

function knj_error_shutdown(){
	if (function_exists("error_get_last")){
		$error = error_get_last();
		
		if ($error["type"] == E_USER_ERROR || $error["type"] == E_CORE_ERROR || $error["type"] == E_COMPILE_ERROR || $error["type"] == E_ERROR){
			knj_error_reporter_error_handeler($error["type"], $error["message"], $error["file"], $error["line"], null, array("hideerror" => true));
		}
	}
}
