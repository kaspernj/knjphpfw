<?
	/** Outputs the HTML for drawing a box with content. */
	function DrawBoxT($title, $width = "100%", $args = null){
		if (!$args["skip_htmlparse"]){
			$title = htmlspecialchars($title);
		}

		?>
			<table style="width: <?=$width?>; text-align: left;" cellspacing="0" cellpadding="0">
				<tr>
					<td style="width: 100%; padding: 4px; font-size: 14px; border-bottom: 1px solid #FBECA7; font-weight: bold;"><?=$title?></td>
				</tr>
				<tr>
					<td style="padding: 2px; padding-left: 8px; padding-right: 8px; border-bottom: 1px solid #FBECA7; width: 100%;">
		<?
	}

	/** Outputs the HTML for drawing a box with content. */
	function DrawBoxB(){
		?>
					</td>
				</tr>
			</table>
		<?
	}

	/** Outputs the HTML for drawing the top of a page. */
	function DrawSiteT($in_title){
		?>
			<div style="font-size: 18px;"><span style="color: #609FD7;">&bull;</span> <?=$in_title?></div>
			<div style="padding-top: 6px; padding-left: 2px;">
		<?
	}

	/** Outputs the HTML for drawing the bottom of a page. */
	function DrawSiteB(){
		?>
			</div>
		<?
	}

