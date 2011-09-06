<?php
	class knj_gallery_picture extends knjdb_row{
		private $knj_gallery_group;
		private $knj_gallery;
		public $dbconn;

		/** The constructor of the class. */
		function __construct(knj_gallery_group $knj_gallery_group, $id, $data = null){
			$this->knj_gallery_group = $knj_gallery_group;
			$this->knj_gallery = $knj_gallery_group->getKNJGallery();
			$this->dbconn = $this->knj_gallery->getDBConn();

			parent::__construct($this->dbconn, "pictures", $id, $data);
		}

		function getViews(){
			$return = array();
			$f_gviews = $this->dbconn->query("
				SELECT
					views.*

				FROM
					views_picturelinks,
					views

				WHERE
					views_picturelinks.picture_id = '" . sql($this->get("id")) . "' AND
					views.id = views_picturelinks.view_id
			");
			while($d_gviews = $f_gviews->fetch()){
				$return[] = $this->knj_gallery->getView($d_gviews["id"], $d_gviews);
			}

			return $return;
		}

		/** Returns the url for the picture, which can be used for clients. */
		function getURL($args = array()){
			$url = $this->knj_gallery->getURL() . "/picture.php?id=" . $this->get("id");

			foreach($args AS $key => $value){
				if ($key == "smartsize"){
					$url .= "&smartsize=" . $value;
				}else{
					throw new Exception("Invalid argument: " . $key);
				}
			}

			return $url;
		}

		/** Returns the full path of the picture. */
		function getPath(){
			return $this->knj_gallery->getPath() . "/pictures/" . $this->get("id") . ".jpg";
		}

		/** Returns the group of this picture. */
		function getGroup(){
			return $this->knj_gallery_group;
		}

		/** Returns the size of the picture based on a wished size. */
		function getSmartSize($size){
			$imagedata = getImageSize($this->getPath());

			if ($imagedata[0] >= $imagedata[1]){
				$width = $size;
			}else{
				$height = $size;
			}

			if ($width){
				$w = $width;
				$h = round($imagedata[1] / ($imagedata[0] / $width));
			}

			if ($height){
				$w = round($imagedata[0] / ($imagedata[1] / $height));
				$h = $height;
			}

			return array(
				"width" => $w,
				"height" => $h
			);
		}

		/** Returns an array of persons who are registered on the picture. */
		function getPersons(){
			$return = array();
			$f_gp = $this->dbconn->query("
				SELECT
					persons.*,
					pictures_personlinks.x_cord,
					pictures_personlinks.y_cord,
					pictures_personlinks.id AS link_id

				FROM
					pictures_personlinks,
					persons

				WHERE
					pictures_personlinks.picture_id = '" . sql($this->get("id")) . "' AND
					persons.id = pictures_personlinks.person_id
			");
			while($d_gp = $f_gp->fetch()){
				$x_cord = $d_gp["x_cord"];
				$y_cord = $d_gp["y_cord"];
				$link_id = $d_gp["link_id"];
				unset($d_gp["x_cord"], $d_gp["y_cord"], $d_gp["link_id"]);

				$person = $this->knj_gallery->getPerson($d_gp["id"], $d_gp);
				$return[] = array(
					"id" => $link_id,
					"person" => $person,
					"x_cord" => $x_cord,
					"y_cord" => $y_cord
				);
			}

			return $return;
		}

		/** Returns the previous picture. */
		function getPrevPic(){
			$d_gprev = $this->dbconn->query("
				SELECT
					*

				FROM
					pictures

				WHERE
					group_id = '" . sql($this->get("group_id")) . "' AND
					id < '" . sql($this->get("id")) . "'

				ORDER BY
					id DESC

				LIMIT
					1
			")->fetch();

			if ($d_gprev){
				return $this->knj_gallery_group->getPicture($d_gprev["id"], $d_gprev);
			}else{
				return false;
			}
		}

		/** Returns the next picture. */
		function getNextPic(){
			$d_gnext = $this->dbconn->query("
				SELECT
					*

				FROM
					pictures

				WHERE
					group_id = '" . sql($this->get("group_id")) . "' AND
					id > '" . sql($this->get("id")) . "'

				ORDER BY
					id

				LIMIT
					1
			")->fetch();

			if ($d_gnext){
				return $this->knj_gallery_group->getPicture($d_gnext["id"], $d_gnext);
			}else{
				return false;
			}
		}

		/** Adds a person to the picture. */
		function addPerson(knj_gallery_person $person, $x, $y, $args = array()){
			foreach($args AS $key => $value){
				if ($key == "user"){
					$user_id = $value["id"];
				}else{
					throw new Exception("Invalid argument: \"" . $key . "\".");
				}
			}

			$this->dbconn->insert("pictures_personlinks", array(
					"picture_id" => $this->get("id"),
					"person_id" => $person->get("id"),
					"x_cord" => $x,
					"y_cord" => $y,
					"date_saved" => date("Y-m-d H:i:s"),
					"user_id_saved" => $user_id
				)
			);

			return true;
		}
	}
?>