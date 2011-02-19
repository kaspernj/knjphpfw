<?
	$config_fn = "image_config.php";
	
	if (file_exists($config_fn)){
		require_once($config_fn);
	}
	
	if (!$_GET["type"]){
		$type = "png";
	}else{
		$type = $_GET["type"];
	}
	
	if ($_GET["force"]){
		$force = true;
	}
	
	$id = md5($_GET["picture"] . "_" . $_GET["smartsize"] . "_" . $_GET["width"] . "_" . $_GET["height"] . "_" . $_GET["edgesize"] . "_" . $_GET["edgeborder"] . "_" . $_GET["quality"] . "_" . $_GET["type"] . "_" . $_GET["equaldim"]);
	$cache_fn = $image_config["tmpimagesdir"] . "/" . $id;
	
	/** NOTE: If the user have cached the picture - just let him get a header instead of the actual picture to save trafic. */
	$if_modified_since = preg_replace("/;.*$/", "", $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
	
	if ($image_config["tmpimagesdir"] && file_exists($cache_fn)){
		$mtime = filemtime($cache_fn);
		$mtime_pic = filemtime($_GET["picture"]);
		
		if ($mtime != $mtime_pic){
			unlink($cache_fn);
			$mtime = $mtime_pic;
			$force = true;
		}
	}else{
		if (file_exists($_GET["picture"])){
			$mtime = filemtime($_GET["picture"]);
		}
	}
	
	if ($mtime && !$force){
		$gmdate_mod = gmdate("D, d M Y H:i:s", $mtime) . " GMT";
		if ($if_modified_since == $gmdate_mod){
			header("HTTP/1.0 304 Not Modified");
			exit();
		}
	}
	
	/** NOTE: If the picture has been saved in the cache-dir, just send the user that file instead of generating it to save CPU performance. */
	if ($image_config["tmpimagesdir"] && !$force && file_exists($cache_fn)){
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
		header("Content-Type: image/" . $type);
		readfile($cache_fn);
		exit();
	}
	
	/** NOTE: The user does not have the picture, and it has not been cached - generate it. */
	require_once("knj/functions_knj_picture.php");
	$image = ImageOpen($_GET["picture"]);
	if (!$image){
		if (!file_exists($_GET["picture"])){
			throw new exception("Picture does not exist.");
		}
		
		throw new exception("Could not open picture: \"" . $_GET["picture"] . "\".");
	}
	
	if (!$_GET["bgcolor"]){
		$bgcolor = "#ffffff";
	}else{
		$bgcolor = $_GET["bgcolor"];
	}
	
	if ($_GET["smartsize"]){
		$image = ImageSmartSize($image, $_GET["smartsize"]);
	}
	
	if ($_GET["width"] || $_GET["height"]){
		$image = ImageSmartSize($image, array(
			"width" => $_GET["width"],
			"height" => $_GET["height"]
		));
	}
	
	if ($_GET["equaldim"]){
		$image = ImageEqualSizes(array(
			"image" => $image,
			"color" => ImageHTMLColor($image, $bgcolor)
		));
	}
	
	if ($_GET["padding"]){
		$origx = ImageSX($image);
		
		if ($_GET["paddingorigsize"]){
			$image = ImagePadding(array(
				"image" => $image,
				"color" => ImageHTMLColor($image, $bgcolor),
				"padding" => $_GET["padding"],
				"keep_size" => true
			));
		}else{
			$image = ImagePadding(array(
				"image" => $image,
				"color" => ImageHTMLColor($image, $bgcolor),
				"padding" => $_GET["padding"]
			));
		}
	}
	
	if ($_GET["edgesize"]){
		$args = array(
			"htmltranscolor" => "#ff00a8"
		);
		if ($_GET["edgeborder"]){
			$args["border"] = $_GET["edgeborder"];
		}
		$image = ImageRoundEdges($image, $_GET["edgesize"], $args);
	}
	
	if ($_GET["quality"]){
		$quality = $_GET["quality"];
	}else{
		$quality = 85;
	}
	
	if (!$image or !is_resource($image)){
		die("Something went wrong.");
	}
	
	if ($image_config["tmpimagesdir"] && $cache_fn){
		ImageOut($image, $type, $quality, $cache_fn);
		if (!touch($cache_fn, filemtime($_GET["picture"]))){
			throw new exception(_("Could not touch file."));
		}
		
		header("Content-Type: image/" . $type);
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");
		readfile($cache_fn);
	}else{
		ImageOut($image, $type, $quality, null);
	}
?>