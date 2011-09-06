<?php

class NotFoundExc extends exception{
	
}

class DBConnExc extends exception{
	
}

class NoAccessExc extends exception{
	
}

function thrownew($msg, $exc = "Exception"){
	throw new $exc($msg);
}

