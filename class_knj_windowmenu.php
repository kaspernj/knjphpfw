<?
	//useage
	/*
		$windowmenu = new knj_WindowMenu(array(
			"Filer" =>
				array(
					"Test1" => array(
						"Test3" => "test3",
						"Test4" => "test4"
					),
					"Test2" => "test2"
				)
			),
			array($this, "WindowmenuClicked")
		);
		
		$box->pack_start($windowmenu->menu, false, false);
	*/
	
	class knj_WindowMenu{
		function __construct($menus, $connect){
			$this->connect = $connect;
			$this->menu = new GtkMenuBar();
			$this->GenerateMenus($this->menu, $menus);
		}
		
		private function GenerateMenus($menu, $menus){
			foreach($menus AS $key => $value){
				$newmenu = new GtkMenuItem($key);
				
				if (is_array($value)){
					$submenu = new GtkMenu();
					$submenu->set_size_request(135, -1);
					$this->GenerateMenus($submenu, $value);
					
					$newmenu->set_submenu($submenu);
				}else{
					$newmenu->connect("activate", array($this, "MenuClicked"), $value);
				}
				
				$menu->append($newmenu);
			}
		}
		
		function MenuClicked($object, $mode){
			if (is_array($this->connect)){
				$eval = "\$this->connect[0]->" . $this->connect[1] . "('" . $mode . "');";
				eval($eval);
			}else{
				$eval = $this->connect . "('" . $mode . "')";
				eval($eval);
			}
		}
	}

