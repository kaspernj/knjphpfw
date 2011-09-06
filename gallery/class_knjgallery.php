<?
	/** This class handels groups and pictures for a gallery. */
	class knj_gallery{
		private $path;
		private $url;
		private $groups;
		private $dbconn;

		/** Returns the path, where the framework is installed. */
		function getPath(){
			return $this->path;
		}

		/** Returns the URL, where the administration-site can be accessed. */
		function getUrl(){
			return $this->url;
		}

		/** Returns the DBConn-object - the database used. */
		function getDBConn(){
			return $this->dbconn;
		}

		/** Deletes a object from the gallery. */
		function deleteOb($ob){
			$class = strtolower(get_class($ob));

			if ($class == "knj_gallery_picture"){
				$path = $ob->getPath();
				if (file_exists($path)){
					if (!unlink($path)){
						throw new Exception("Could not delete the picture.");
					}
				}

				$this->dbconn->delete("views_picturelinks", array("picture_id" => $ob->get("id")));
				$this->dbconn->delete("pictures_personlinks", array("picture_id" => $ob->get("id")));
				$this->dbconn->delete("pictures", array("id" => $ob->get("id")));

				foreach($ob->getViews() AS $view){
					$view->reloadPics();
				}
			}elseif($class == "knj_gallery_group"){
				$f_gviews = $this->dbconn->select("views", array("group_id" => $ob->get("id")));
				while($d_gviews = $f_gviews->fetch()){
					$view = $this->getView($d_gviews["id"], $d_gviews);
					$view->setData(array("active" => "no"));
				}

				$pics = $ob->getPictures();
				foreach($pics AS $pic){
					$this->deleteOb($pic);
				}

				$this->dbconn->delete("groups", array("id" => $ob->get("id")));
			}elseif($class == "knj_gallery_view"){
				$this->dbconn->delete("views_picturelinks", array("view_id" => $ob->get("id")));
				$this->dbconn->delete("views", array("id" => $ob->get("id")));
			}elseif($class == "knjg_gallery_person"){
				$f_gviews = $this->dbconn->select("views", array("person_id" => $ob->get("id")));
				while($d_gviews = $f_gviews->fetch()){
					$view = $this->getView($d_gviews["id"], $d_gviews);
					$view->setData(array("active" => "no"));
				}

				$this->dbconn->delete("pictures_personlinks", array("person_id" => $ob->get("id")));
				$this->dbconn->delete("persons", array("id" => $ob->get("id")));
			}else{
				throw new Exception("Invalid class: \"" . $class . "\".");
			}
		}

		/** Sets options to be used. */
		function setOpts($arr){
			foreach($arr AS $key => $value){
				if ($key == "url" || $key == "path" || $key == "dbconn"){
					$this->$key = $value;
				}else{
					throw new Exception("Unknown argument: \"" . $key . "\".");
				}
			}
		}

		/** Returns all groups. */
		function getGroups(){
			$groups = array();
			$f_gg = $this->dbconn->select("groups", null, array("orderby" => "date_from DESC, title"));
			while($d_gg = $f_gg->fetch()){
				$groups[] = $this->getGroup($d_gg["id"], $d_gg);
			}

			return $groups;
		}

		/** Handels all the groups. */
		function getGroup($id, $data = null){
			if (!$this->path){
				throw new Exception("The path has not been set.");
			}

			if (!$this->groups[$id]){
				require_once("knj/gallery/class_knjgallery_group.php");
				$this->groups[$id] = new knj_gallery_group($this, $id, $data);
			}

			return $this->groups[$id];
		}

		/** Returns a picture-object from the ID. */
		function getPicture($id){
			$picd = $this->dbconn->getRow($id, "pictures");
			$group = $this->getGroup($picd->get("group_id"));
			$pic = $group->getPicture($picd->get("id"), $picd->getAsArray());

			return $pic;
		}

		/** Returns a person-object from the ID. */
		function getPerson($id, $data = null){
			if (!$this->persons_list[$id]){
				require_once("knj/gallery/class_knjgallery_person.php");
				$this->persons_list[$id] = new knj_gallery_person($this, $id, $data);
			}

			return $this->persons_list[$id];
		}

		/** Returns a view-object from the ID. */
		function getView($id, $data = null){
			if (!$this->views_list[$id]){
				require_once("knj/gallery/class_knjgallery_view.php");
				$this->views_list[$id] = new knj_gallery_view($this, $id, $data);
			}

			return $this->views_list[$id];
		}

		/** Returns the total number of pictures in an array. */
		function countPictures(){
			$d_cpics = $this->dbconn->query("SELECT COUNT(*) AS count FROM pictures")->fetch();

			return array(
				"count" => $d_cpics["count"]
			);
		}

		/** Returns the total number of groups in an array. */
		function countGroups(){
			$d_cpics = $this->dbconn->query("SELECT COUNT(*) AS count FROM groups")->fetch();

			return array(
				"count" => $d_cpics["count"]
			);
		}

		/** Returns the total number of persons in an array. */
		function countPersons(){
			$d_cpersons = $this->dbconn->query("SELECT COUNT(*) AS count FROM persons")->fetch();

			return array(
				"count" => $d_cpersons["count"]
			);
		}

		function getRandomPicture(){
			$rpic = $this->dbconn->query("SELECT id FROM pictures ORDER BY RAND() LIMIT 1")->fetch();
			if (!$rpic){
				return false;
			}

			return $this->getPicture($rpic["id"]);
		}
	}
?>