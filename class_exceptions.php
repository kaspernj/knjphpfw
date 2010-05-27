<?php
	class NotFoundExc extends Exception{
		
	}
	
	class DBConnExc extends Exception{
		
	}
	
	function thrownew($msg, $exc = "Exception"){
		throw new $exc($msg);
	}
?>