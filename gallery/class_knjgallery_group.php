<?
	class knj_gallery_group extends knjdb_row{
		private $knj_gallery;
		public $dbconn;
		private $path;
		private $id;
		private $get_called = false;

		/** The constructor for the individual group. */
		function __construct(knj_gallery $knj_gallery, $id, $data = null){
			$this->knj_gallery = $knj_gallery;
			$this->dbconn = $knj_gallery->getDBConn();
			$this->path = $this->knj_gallery->getPath();

			$this->id = $id;

			parent::__construct($this->dbconn, "groups", $id, $data);
		}

		/** Returns the knj_gallery-object that this group was spawned from. */
		function getKNJGallery(){
			return $this->knj_gallery;
		}

		/** Returns all the pictures for this group. */
		function getPictures(){
			$this->get_called = true;

			$pictures = array();
			$f_gp = $this->dbconn->select("pictures", array("group_id" => $this->id), array("orderby" => "id"));
			while($d_gp = $f_gp->fetch()){
				$pictures[] = $this->getPicture($d_gp["id"], $d_gp);
			}

			return $pictures;
		}

		/** Returns a picture by its ID. */
		function getPicture($id, $data = null){
			if (!$this->picture[$id]){
				require_once("knj/gallery/class_knjgallery_picture.php");
				$this->pictures[$id] = new knj_gallery_picture($this, $id, $data);
			}

			return $this->pictures[$id];
		}

		function getGroupPicture(){
			$pics = $this->getPictures();
			foreach($pics AS $pic){
				return $pic;
			}

			return false;
		}

		/** Returns all the persons in this group. */
		function getPersons(){
			$return = array();
			$f_gp = $this->dbconn->query("
				SELECT
					persons.*,
					COUNT(pictures.id) AS pics_count

				FROM
					pictures_personlinks,
					pictures,
					persons

				WHERE
					pictures.group_id = '" . sql($this->get("id")) . "' AND
					pictures_personlinks.picture_id = pictures.id AND
					persons.id = pictures_personlinks.person_id

				GROUP BY
					persons.id

				ORDER BY
					persons.name
			");
			while($d_gp = $f_gp->fetch()){
				$pics_count = $d_gp["pics_count"];
				unset($d_gp["pics_count"]);

				$return[] = array(
					"pictures_count" => $pics_count,
					"person" => $this->knj_gallery->getPerson($d_gp["id"], $d_gp)
				);
			}

			return $return;
		}

		/** Count all the pictures in the group. */
		function countPictures(){
			if ($this->get_called){
				return count($this->pictures);
			}

			$d_cpics = $this->dbconn->query("SELECT COUNT(*) AS count FROM pictures WHERE group_id = '" . sql($this->id) . "'")->fetch();
			return $d_cpics["count"];
		}

		/** Returns the HTML for a link to this group. */
		function getHTML(){
			return "<a href=\"?show=views_createandgoto&choice=fromgroup&group_id=" . $this->get("id") . "\">" . htmlspecialchars($this->get("title")) . "</a>";
		}

		/** Returns a view for this group (creates one if a valid one does not exist. */
		function getView(){
			//Check if a active view already exists.
			$d_gview = $this->dbconn->selectsingle("views", array("group_id" => $this->get("id"), "person_id" => 0, "active" => "yes"), array("limit" => 1));
			if ($d_gview){
				return $this->knj_gallery->getView($d_gview["id"], $d_gview);
			}

			//Create a new view with all the pictures from this group.
			require_once("knj/gallery/class_knjgallery_view.php");
			$view = knj_gallery_view::createNew($this->knj_gallery);
			$view->setData(array("group_id" => $this->get("id")));

			$pictures = $this->getPictures();
			$sort_no = 1;

			foreach($pictures AS $picture){
				$view->addPicture($picture, $sort_no);
				$sort_no++;
			}

			return $view;
		}
	}
?>