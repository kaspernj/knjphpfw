<?
	require_once "knj/functions_knj_extensions.php";
	if (!knj_dl("gd")){
		die("Could not load the GD-extension.\n");
	}

	/** This class allows the user the fairly easy make a tray-icon showing a percentage. */
	class TrayPercent extends GtkStatusIcon{
		private $pixbuf;		//The last pixbuf used.
		private $height;

		/** The constructor of TrayPercent. */
		function __construct(){
			parent::__construct();
			$this->height = 48;
		}

		/** Sets a new percentage in the tray. */
		function setPercent($percs){
			$spacing = 2;
			$spacing_bottom = 5;

			$width = ($spacing * 2) + (count($percs) * 7) + (count($percs) * 2);

			$img = ImageCreateTrueColor($width, $this->height);
			$transcolor = ImageColorTransparent($img);
			ImageFill($img, 0, 0, $transcolor);

			$color_border = ImageColorAllocate($img, 1, 1, 1);;

			$x = $spacing;
			foreach($percs AS $perc){
				if ($perc[color] == "green"){
					$color_fill = ImageColorAllocate($img, 10, 253, 16);
				}elseif($perc[color] == "yellow"){
					$color_fill = ImageColorAllocate($img, 187, 253, 10);
				}else{
					$color_fill = ImageColorAllocate($img, 1, 1, 1);
				}

				if ($perc[value] > 1){
					$perc[value] = 1;
				}

				if ($perc[value] <= 0.9){
					$perc[value] += 0.08;
				}

				if ($perc[value] >= 0.09){
					$count_to = round(($this->height - ($spacing_bottom * 2)) * ($perc[value]));
					$count = 0;

					while($count <= $count_to){
						$y = $this->height - $count - $spacing_bottom;

						if ($count == $count_to || $count == 0){
							$color_use = &$color_border;
						}else{
							$color_use = &$color_fill;
						}

						ImageSetPixel($img, $x, $y, $color_border);
						ImageSetPixel($img, $x + 1, $y, $color_use);
						ImageSetPixel($img, $x + 2, $y, $color_use);
						ImageSetPixel($img, $x + 3, $y, $color_use);
						ImageSetPixel($img, $x + 4, $y, $color_use);
						ImageSetPixel($img, $x + 5, $y, $color_use);
						ImageSetPixel($img, $x + 6, $y, $color_border);

						$count++;
					}
				}

				$x += 11;

				if ($perc[text]){
					ImageString($img, 7, $x, $spacing, $perc[text], $color_border);
					$x += 25;
					break;
				}
			}

			$pixbuf = GdkPixBuf::new_from_gd($img);
			$this->set_from_pixbuf($pixbuf);

			ImageDestroy($img);
		}
	}

