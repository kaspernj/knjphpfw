<?php
	class knj_gallery_view extends knjdb_row{
		private $knjgallery;
		public $dbconn;
		
		/** The constructor. */
		function __construct($knjgallery, $id, $data = null){
			$this->knj_gallery = $knjgallery;
			$this->dbconn = $knjgallery->getDBConn();
			
			parent::__construct($this->dbconn, "views", $id, $data);
			
			if ($this->get("active") != "yes"){
				$this->reloadPics();
			}
		}
		
		/** Creates a view from a person and a group (every picture of the person in the group). */
		static function getFromPersonAndGroup(knj_gallery $knjgallery, knj_gallery_person $person, knj_gallery_group $group){
			$dbconn = $knjgallery->getDBConn();
			
			
			//Check if a group already exists.
			$d_gview = $dbconn->selectsingle("views", array("group_id" => $group->get("id"), "person_id" => $person->get("id")));
			if ($d_gview){
				$view = $knjgallery->getView($d_gview["id"], $d_gview);
				return $view;
			}
			
			
			//Create a new view.
			$arr = array(
				"person_id" => $person->get("id"),
				"group_id" => $group->get("id")
			);
			$view = knj_gallery_view::createNew($knjgallery, $arr);
			
			
			
			return $view;
		}
		
		/** Creates a new view and returns it. */
		static function createNew(knj_gallery $knjgallery, $arr_save = array()){
			$dbconn = $knjgallery->getDBConn();
			$arr = array(
				"date_added" => date("Y-m-d H:i:s")
			);
			foreach($arr_save AS $key => $value){
				$arr[$key] = $value;
			}
			$dbconn->insert("views", $arr);
			$view_id = $dbconn->getLastID();
			$view = $knjgallery->getView($view_id);
			return $view;
		}
		
		function addPicture(knj_gallery_picture $pic, $sort_no = "0"){
			$this->dbconn->delete("views_picturelinks", array("view_id" => $this->get("id"), "picture_id" => $pic->get("id")));
			$this->dbconn->insert("views_picturelinks", array(
					"view_id" => $this->get("id"),
					"picture_id" => $pic->get("id"),
					"sort_no" => $sort_no
				)
			);
			return true;
		}
		
		/** Returns the pictures from this view. */
		function getPictures(){
			$return = array();
			$f_gpics = $this->dbconn->query("
				SELECT
					pictures.*
				
				FROM
					views_picturelinks,
					pictures
				
				WHERE
					views_picturelinks.view_id = '" . sql($this->get("id")) . "' AND
					pictures.id = views_picturelinks.picture_id
				
				GROUP BY
					pictures.id
				
				ORDER BY
					views_picturelinks.sort_no,
					pictures.id
			");
			while($d_gpics = $f_gpics->fetch()){
				$return[] = $this->knj_gallery->getPicture($d_gpics["id"], $d_gpics);
			}
			
			return $return;
		}
		
		function getNextPic(knj_gallery_picture $pic){
			$d_gpic = $this->dbconn->selectsingle("views_picturelinks", array("view_id" => $this->get("id"), "picture_id" => $pic->get("id")), array("limit" => 1));
			if (!$d_gpic){
				throw new Exception("Could not find the picture in this view.");
			}
			
			$d_gviewpic = $this->dbconn->query("
				SELECT
					pictures.*
				
				FROM
					views_picturelinks,
					pictures
				
				WHERE
					views_picturelinks.view_id = '" . sql($this->get("id")) . "' AND
					pictures.id = views_picturelinks.picture_id AND
					views_picturelinks.sort_no > '" . sql($d_gpic["sort_no"]) . "'
				
				ORDER BY
					views_picturelinks.sort_no
				
				LIMIT
					1
			")->fetch();
			
			if (!$d_gviewpic){
				return false;
			}else{
				return $this->knj_gallery->getPicture($d_gviewpic["id"], $d_gviewpic);
			}
		}
		
		function getPrevPic(knj_gallery_picture $pic){
			$d_gpic = $this->dbconn->selectsingle("views_picturelinks", array("view_id" => $this->get("id"), "picture_id" => $pic->get("id")), array("limit" => 1));
			if (!$d_gpic){
				throw new Exception("Could not find the picture in this view.");
			}
			
			$d_gviewpic = $this->dbconn->query("
				SELECT
					pictures.*
				
				FROM
					views_picturelinks,
					pictures
				
				WHERE
					views_picturelinks.view_id = '" . sql($this->get("id")) . "' AND
					pictures.id = views_picturelinks.picture_id AND
					views_picturelinks.sort_no < '" . sql($d_gpic["sort_no"]) . "'
				
				ORDER BY
					views_picturelinks.sort_no DESC
				
				LIMIT
					1
			")->fetch();
			
			if (!$d_gviewpic){
				return false;
			}else{
				return $this->knj_gallery->getPicture($d_gviewpic["id"], $d_gviewpic);
			}
		}
		
		/** Returns the group. */
		function getGroup(){
			if ($this->get("group_id") <= 0){
				return false;
			}
			
			return $this->knj_gallery->getGroup($this->get("group_id"));
		}
		
		function getPerson(){
			if ($this->get("person_id") <= 0){
				return false;
			}
			
			return $this->knj_gallery->getPerson($this->get("person_id"));
		}
		
		/** Removes a picture from the view. */
		function delPic(knj_gallery_picture $pic){
			$this->dbconn->delete("views_picturelinks", array("view_id" => $this->get("id"), "picture_id" => $pic->get("id")));
			return true;
		}
		
		/** Removes all the pictures from this view. */
		function delAllPics(){
			$pics = $this->getPictures();
			foreach($pics AS $pic){
				$this->delPic($pic);
			}
		}
		
		/** Reloads all the pictures in the view (repairs it if broken or out of sync). */
		function reloadPics(){
			if ($this->get("group_id") > 0){
				$group = $this->getGroup();
			}
			
			if ($this->get("person_id") > 0){
				$person = $this->knj_gallery->getPerson($this->get("person_id"));
			}
			
			if ($group && $person){
				$sort_no = 1;
				$f_gpics = $this->dbconn->query("
					SELECT
						pictures.*
					
					FROM
						pictures,
						pictures_personlinks
					
					WHERE
						pictures.group_id = '" . sql($group->get("id")) . "' AND
						pictures_personlinks.person_id = '" . sql($person->get("id")) . "' AND
						pictures_personlinks.picture_id = pictures.id
					
					ORDER BY
						pictures.id
				");
				while($d_gpics = $f_gpics->fetch()){
					$picture = $this->knj_gallery->getPicture($d_gpics["id"], $d_gpics);
					$this->addPicture($picture, $sort_no);
					$sort_no++;
				}
			}elseif($group && !$person){
				$sort_no = 1;
				$f_gpics = $this->dbconn->select("pictures", array("group_id" => $group->get("id")), array("orderby" => "id"));
				while($d_gpics = $f_gpics->fetch()){
					$picture = $this->knj_gallery->getPicture($d_gpics["id"], $d_gpics);
					$this->addPicture($picture, $sort_no);
					$sort_no++;
				}
			}elseif($person && !$group){
				$sort_no = 1;
				$f_gpics = $this->dbconn->query("
					SELECT
						pictures.*
					
					FROM
						pictures,
						pictures_personlinks
					
					WHERE
						pictures_personlinks.person_id = '" . sql($person->get("id")) . "' AND
						pictures.id = pictures_personlinks.picture_id
					
					ORDER BY
						pictures.id
				");
				while($d_gpics = $f_gpics->fetch()){
					$picture = $this->knj_gallery->getPicture($d_gpics["id"], $d_gpics);
					$this->addPicture($picture, $sort_no);
					$sort_no++;
				}
			}else{
				return false;
			}
			
			$this->setData(array("active" => "yes"));
		}
	}

