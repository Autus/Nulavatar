<?php
/***************************************************************************
 *                              nulavatar.php
 *                            -------------------
 *   Version            : 1.3.1
 *   began              : Sunday, May 29th, 2004
 *   released           : Friday, July 28th, 2006 (v1.3.1)
 *   email              : nuladion@gmail.com
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   Copyright (C) 2003/2004  Guido Kessels (aka Nuladion)
 *
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version 2
 *   of the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   http://www.gnu.org/copyleft/gpl.html
 *
 ***************************************************************************/

//
// Let's set the root dir for phpBB
//
define('IN_PHPBB', true);

$phpbb_root_path = './';
include($phpbb_root_path . 'extension.inc');
include($phpbb_root_path . 'common.' . $phpEx);

// [START] Fix for register_globals! Thanks alot Mav! ^_^
if(is_array($_GET))
{
extract($_GET, EXTR_PREFIX_SAME, "get");
}
if(is_array($_POST))
{
extract($_POST, EXTR_PREFIX_SAME, "post");
}
// [END] Fix for register_globals!

$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);

//
//check if logged in
//
if( !$userdata['session_logged_in'] )
{
	header('Location: ' . append_sid("login.$phpEx?redirect=nulavatar.php", true));
}
//end check

   $template->set_filenames(array( 
      'body' => 'nulavatar_body.tpl') 
   );

//
// Start MOD Code
//

//
// [START] General Variables
//

$sqlconfig = "SELECT * FROM ".$table_prefix."nulavatar_settings WHERE config_name = 'avatar_height' ";
if ( !($resultconfig = $db->sql_query($sqlconfig)) )
{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }
$config_row = mysql_fetch_array($resultconfig); 
$height = $config_row['config_value'];

$sqlconfig = "SELECT * FROM ".$table_prefix."nulavatar_settings WHERE config_name = 'avatar_width' ";
if ( !($resultconfig = $db->sql_query($sqlconfig)) )
{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }
$config_row = mysql_fetch_array($resultconfig); 
$width = $config_row['config_value'];

// Get RPG System
$sqlconfig = "SELECT * FROM ".$table_prefix."nulavatar_settings WHERE config_name = 'rpgsystem' ";
if ( !($resultconfig = $db->sql_query($sqlconfig)) )
{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }
$config_row = mysql_fetch_array($resultconfig); 
$rpgsystem = $config_row['config_value'];

// Get Image Type
$sqlconfig = "SELECT * FROM ".$table_prefix."nulavatar_settings WHERE config_name = 'imagetype' ";
if ( !($resultconfig = $db->sql_query($sqlconfig)) )
{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }
$config_row = mysql_fetch_array($resultconfig); 
$imagetype = $config_row['config_value'];
$imagecreate = "imagecreatefrom".$imagetype;

// Get Display Mode
$sqlconfig = "SELECT * FROM ".$table_prefix."nulavatar_settings WHERE config_name = 'display' ";
if ( !($resultconfig = $db->sql_query($sqlconfig)) )
{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }
$config_row = mysql_fetch_array($resultconfig); 
$show_shelves = $config_row['config_value'];

// Get items per shelf
$sqlconfig = "SELECT * FROM ".$table_prefix."nulavatar_settings WHERE config_name = 'eamountofitems' ";
if ( !($resultconfig = $db->sql_query($sqlconfig)) )
{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }
$config_row = mysql_fetch_array($resultconfig); 
$items_per_shelf = $config_row['config_value'];

$file = "nulavatar.php";
$sprites_path = "images/nulavatar";
$chars_path = "images/nulavatar_avatars";

$mod_title = $lang['nulavatar_character_editor'];
$page_title = $userdata['username'].$lang['nulavatar_character_sprite'];
//
// [END] General Variables
//

//
// [START] Save it! :)
//
if($action == "save")
{
	// Check if Save action is called from Nulavatar page.
	// This should prevent users from calling NA from an other page to gain access to images they do not have.
	if($_SERVER['HTTP_REFERER'] != 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']) {
		message_die(GENERAL_MESSAGE, $lang['nulavatar_invalidpage']);
	}

	// Check if user has an Avatar yet! If not, create one!
	$sql = "SELECT * FROM ".$table_prefix."nulavatar_userchars WHERE user = '".$userdata['user_id']."' ";
	if ( !($result = $db->sql_query($sql)) )
	{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }

	$tot_users = mysql_num_rows($result);

	if($tot_users < 1)
	{
		$sql2 = "SELECT * FROM ".$table_prefix."nulavatar_layers ORDER BY position DESC";
		if ( !($result2 = $db->sql_query($sql2)) )
		{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }

		$tot_layers = mysql_num_rows($result2);

		$insert_values = '';
		$insert_layers = '';

		for( $i = 0; $i < $tot_layers; $i++ )
		{
				$row2 = mysql_fetch_array($result2); 
				$insert_layers .= ','.$row2['name']; 
				$insert_values .= ",'' ";
		}

		// Create character!
		$sql3 = "INSERT INTO ".$table_prefix."nulavatar_userchars (user".$insert_layers.") VALUES ('".$userdata['user_id']."'".$insert_values.")";
		if ( !($result3 = $db->sql_query($sql3)) )
		{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }
	}

	// Now that they got a character, insert the selected images into the db!
	$sql = "SELECT * FROM ".$table_prefix."nulavatar_layers ORDER BY position DESC";
	if ( !($result = $db->sql_query($sql)) )
	{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }

	$tot_layers = mysql_num_rows($result);

	$insert_values = '';
	$insert_layers = '';
	$dontshow = '';
	$no_show_list = '';
	$dont_show_these_layers = '';

	for( $i = 0; $i < $tot_layers; $i++ )
	{
			$row = mysql_fetch_array($result); 

			$insert_layers .= ','.$row2['name']; 

			if($insert_values != "")
			{ $insert_values .= ', '; }

			$insert_values .= $row['name']." = '".$HTTP_POST_VARS[$row['name']]."'";

			//
			// [START] Check for DONTSHOW layers :)
			//
			$IMsql = "SELECT * FROM ".$table_prefix."nulavatar_images WHERE image = '".$HTTP_POST_VARS[$row['name']]."' ";
			if ( !($IMresult = $db->sql_query($IMsql)) )
			{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }

			$IMrow = mysql_fetch_array($IMresult); 

			if(($IMrow['dontshowlayer'] != '0') && ($IMrow['dontshowlayer'] != ''))
			{
				// Image has a NOSHOW layer! --- Need to check the name of the layer!
				$ILsql = "SELECT name FROM ".$table_prefix."nulavatar_layers WHERE id = '".$IMrow['dontshowlayer']."' ";
				if ( !($ILresult = $db->sql_query($ILsql)) )
				{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }
				$ILrow = mysql_fetch_array($ILresult); 

				$dontshow .= '
					<tr>
						<td class="row1" align="center"><span class="gen">&nbsp;'.$IMrow['name'].'&nbsp;</span></td>
						<td class="row1" align="center"><span class="gen">&nbsp;'.$row['name'].'&nbsp;</span></td>
						<td class="row1" align="center"><span class="gen">&nbsp;'.$ILrow['name'].'&nbsp;</span></td>
					</tr>
				';

				$dont_show_these_layers .= 'Q'.$IMrow['dontshowlayer'].'M';
			}

			if (substr_count($dont_show_these_layers,'Q'.$row['id'].'M') > 0)
			{
				$no_show_list .= '<input type="hidden" name="'.$row['name'].'" value="spacer.gif">';
			}
			else
			{
				$no_show_list .= '<input type="hidden" name="'.$row['name'].'" value="'.$HTTP_POST_VARS[$row['name']].'">';
			}
			//
			// [END] Check for DONTSHOW layers :)
			//
	}

	if(($dontshow != '') && ($pass_check != "yes"))
	{
		$useaction = '
			<center><br />
			<table border="0" cellspacing="0" cellpadding="0" width="95%">
			<tr>
				<th class="catHead">&nbsp;'.$lang['nulavatar_noshow_warning_header'].'&nbsp;</th>
			</tr>
			<tr>
				<td class="row2" align="center"><span class="gen"><br />'.$lang['nulavatar_noshow_warning'].'<br /><br />
					<table border="0" cellspacing="0" cellpadding="0" width="70%">
						<tr>
							<th class="catHead">&nbsp;'.$lang['nulavatar_this_image'].'&nbsp;</th>
							<th class="catHead">&nbsp;'.$lang['nulavatar_at_layer'].'&nbsp;</th>
							<th class="catHead">&nbsp;'.$lang['nulavatar_will_hide_this_layer'].'&nbsp;</th>
						</tr>
						'.$dontshow.'
						<form method="post" action="">
						<tr>
							<td class="row2" align="center" colspan=3><span class="gen"><br />'.$no_show_list.'<input type="hidden" name="action" value="save"><input type="hidden" name="pass_check" value="yes"><input type="submit" value="'.$lang['nulavatar_proceed'].'" class="mainoption"></span></td>
						</tr>
						</form>
						<form method="post" action="">
						<tr>
							<td class="row2" align="center" colspan=3><span class="gen"><input type="hidden" name="action" value=""><input type="submit" value="'.$lang['nulavatar_go_back'].'" class="mainoption"></span></td>
						</tr>
						</form>
					</table>
				<br />
				</span></td>
			</tr>
			</table>
			<br />
			</center>
		';
	}
	else
	{

	// Update character!
	$sql = "UPDATE ".$table_prefix."nulavatar_userchars SET ".$insert_values." WHERE user = '".$userdata['user_id']."' ";
	if ( !($result = $db->sql_query($sql)) )
	{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }

	//
	// [START] At last, create the PNG image!
	//
	$tot_width = $width;
	$tot_height = $height;

	// Get all layers in the correct order!
	$Lsql = "SELECT * FROM `".$table_prefix."nulavatar_layers` ORDER BY position ASC";
	if ( !($Lresult = $db->sql_query($Lsql)) )
	{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }

	$tot_layers = mysql_num_rows($Lresult);

	$nulavatar = false;

	for( $iii = 0; $iii < $tot_layers; $iii++ )
	{
			$Lrow = mysql_fetch_array($Lresult); 
			if($HTTP_POST_VARS[$Lrow['name']] != 'spacer.gif')
			{
				if($nulavatar == false)
				{
					$sprite_1 = @$imagecreate($phpbb_root_path.$sprites_path.'/'.$HTTP_POST_VARS[$Lrow['name']]);
					$nulavatar = true;
				}
				else
				{
					$image = @$imagecreate($phpbb_root_path.$sprites_path.'/'.$HTTP_POST_VARS[$Lrow['name']]);
					@imagecopy ($sprite_1, $image, 0, 0, 0, 0, $tot_width, $tot_height);
					@ImageDestroy($image);
				}
			}
	}

	$save = $userdata['user_id'];
	imagepng($sprite_1, $phpbb_root_path . $chars_path . '/' . $save . '.png');
	imagedestroy($sprite_1);
	//
	// [END] At last, create the PNG image!
	//

	$useaction = "
		<center>
		<table width=\"60%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
			<tr>
				<td class=\"row1\" align=\"center\"><span class=\"gen\">
					<br /><b>".$lang['nulavatar_sprite_created_succesfully']."</b><br />&nbsp;
				</td>
			</tr>
			<tr>
				<td class=\"row1\" align=\"center\"><span class=\"gen\">
				<form method=\"post\" action=\"\">
					<input type=\"hidden\" name=\"action\" value=\"\"><input type=\"submit\" value=\"".$lang['nulavatar_go_back']."\" class=\"mainoption\">
				</form>
				</td>
			</tr>
		</table>
		<br />
		</center>
	";

	}
}
//
// [END] Save it! :)
//

//
// [START] Get Layers + Images!
//
if($action == "")
{
	$sql = "SELECT * FROM ".$table_prefix."nulavatar_layers ORDER BY position DESC";
	if ( !($result = $db->sql_query($sql)) )
	{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br><br>".mysql_error()); }

	$tot_layers = mysql_num_rows($result);
	$preview_img = '';
	$zindex = $tot_layers;

	$items_shelf = '<table cellpadding="0" cellspacing="0" border="0">';
	$items_shelf_layers = '';

	// Prevent users from accessing NulAvatar if there aren't any layers yet!
	if($tot_layers < 1)
	{ message_die(GENERAL_MESSAGE, $lang['nulavatar_nolayers']); }

	for( $i = 0; $i < $tot_layers; $i++ )
	{
		$row = mysql_fetch_array($result); 

		$imagename = "preview".$i;

		$compulsive = $lang['nulavatar_No'];
		$no_image_text = '';

		if($row['compulsive'] == 1) { 
			$compulsive = $lang['nulavatar_Yes']; 
		} else {
			$no_image_text .= '<span class="gensmall"><br /><a href="#hook'.$imagename.'" name="hook'.$imagename.'" onClick="document.images[\''.$imagename.'\'].src = \''.$phpbb_root_path.$sprites_path.'/spacer.gif\'; document.nulavatarlayers.'.$row['name'].'.value=\'spacer.gif\'">'.$lang['nulavatar_no_image'].'</a></span>';
		}

		$items_shelf .= '<tr><td valign="top" align="right">';
		$items_shelf_count = 1;

		//
		// [START] Get images for this layer!
		//
		$Usql = "SELECT ".$row['name']." FROM ".$table_prefix."nulavatar_userchars WHERE user = '".$userdata['user_id']."' ";
		if ( !($Uresult = $db->sql_query($Usql)) )
		{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }

		$Urow = mysql_fetch_array($Uresult);

		$sql2 = "SELECT * FROM ".$table_prefix."nulavatar_images WHERE layer = '".$row['id']."' ORDER BY name ASC";
		if ( !($result2 = $db->sql_query($sql2)) )
		{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }

		$i_list = '';
		$firstimage = '';

		if($row['compulsive'] == 0)
		{ $i_list = '<option value="spacer.gif">' . $lang['nulavatar_dont_use_nolayer'] . '</option>'; $firstimage = "spacer.gif"; }

		for( $z = 0; $z < mysql_num_rows($result2); $z++ )
		{
			$row2 = mysql_fetch_array($result2); 

			$checked = '';
			if($Urow[$row['name']] == $row2['image'])
			{ $checked = "SELECTED";}

			// check for item, if itemneeded is set to Yes(=1)!
			if($row2['itemneeded'] != "")
			{
				// MOOGIES MOD
				//    Thanks to theanimewizard for fixing a problem in this code! ^^
				if($rpgsystem == "moogies")
				{
					$UsqlM = "SELECT user_items FROM ".USERS_TABLE." WHERE user_id = '".$userdata['user_id']."' ";
					if ( !($UresultM = $db->sql_query($UsqlM)) )
					{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); }
					$UrowM = mysql_fetch_array($UresultM); 
	
					if (substr_count($UrowM['user_items'],"ß".$row2['itemneeded']."Þ") > 0)
					{
						$i_list .= '<option value="' . $row2['image'] . '" '.$checked.'>' . $row2['name'] . '</option>'; 
	
						if(($firstimage == '') OR ($checked != ''))
						{ $firstimage = $row2['image']; }

						$items_shelf .= '<img src="'.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'" onClick="document.images[\''.$imagename.'\'].src = \''.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'\'; document.nulavatarlayers.'.$row['name'].'.value=\''.$row2['image'].'\'">';
					}
				}
				// ADVANCED DUNGEONS & RABBITS
				//    Thanks to Seteo-Bloke for this code!
				if($rpgsystem == "adr")
				{
					$UsqlA = "SELECT * FROM phpbb_adr_shops_items 
						WHERE item_owner_id = '".$userdata['user_id']."' 
						AND item_in_shop = 0 
						AND item_name = '".$row2['itemneeded']."'
					"; 
					if( !($UresultA = $db->sql_query($UsqlA)) ) 
					{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); } 
					$UrowA = mysql_fetch_array($UresultA); 
					
					if ( $UrowA['item_name'] == $row2['itemneeded'] ) 
					{ 
						$i_list .= '<option value="' . $row2['image'] . '" '.$checked.'>' . $row2['name'] . '</option>'; 
					
						if(($firstimage == '') OR ($checked != '')) 
						{ $firstimage = $row2['image']; } 

						$items_shelf .= '<img src="'.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'" onClick="document.images[\''.$imagename.'\'].src = \''.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'\'; document.nulavatarlayers.'.$row['name'].'.value=\''.$row2['image'].'\'">';
					} 
				}
				// SHOP MOD v3.0.0
				if($rpgsystem == "shop3")
				{
					$UsqlS = "SELECT * FROM " . USER_ITEMS_TABLE . "
						WHERE user_id = '".$userdata['user_id']."' 
						AND item_name = '".$row2['itemneeded']."'
					"; 
					if( !($UresultS = $db->sql_query($UsqlS)) ) 
					{ message_die(GENERAL_MESSAGE, "<b>Fatal Error!</b><br />".mysql_error()); } 
					$UrowS = mysql_fetch_array($UresultS); 
					
					if ( $UrowS['item_name'] == $row2['itemneeded'] ) 
					{ 
						$i_list .= '<option value="' . $row2['image'] . '" '.$checked.'>' . $row2['name'] . '</option>'; 
					
						if(($firstimage == '') OR ($checked != '')) 
						{ $firstimage = $row2['image']; } 

						$items_shelf .= '<img src="'.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'" onClick="document.images[\''.$imagename.'\'].src = \''.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'\'; document.nulavatarlayers.'.$row['name'].'.value=\''.$row2['image'].'\'">';
					} 
				}
			}
			else
			{
				$i_list .= '<option value="' . $row2['image'] . '" '.$checked.'>' . $row2['name'] . '</option>'; 

				if(($firstimage == '') OR ($checked != ''))
				{ $firstimage = $row2['image']; }

				$items_shelf .= '<img src="'.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'" onClick="document.images[\''.$imagename.'\'].src = \''.$phpbb_root_path.$sprites_path.'/'.$row2['image'].'\'; document.nulavatarlayers.'.$row['name'].'.value=\''.$row2['image'].'\'">';
			}

			if($items_shelf_count >= $items_per_shelf) { $items_shelf .= '<br />'; $items_shelf_count = 0;}
			$items_shelf_count++;
		}
		//
		// [END] Get images for this layer!
		//

		if($show_shelves == 1) {
			$items_shelf .= '<td width="100" valign="top" class="row1"><span class="gen"><b>'.str_replace("_"," ",$row['name']).'</b></span>'.$no_image_text.'</td></tr>';
			$items_shelf_layers .= '<input type="hidden" name="'.$row['name'].'" value="'.$firstimage.'">';
		} else {
			$list .= '
				<tr>
					<td class="row2" align="center"><span class="gen">'.str_replace("_"," ",$row['name']).'</span></td>
					<td class="row2" align="center"><span class="gen">'.$compulsive.'</span></td>
					<td class="row2" align="left"><span class="gen"><select name="'.$row['name'].'" onChange="document.images[\''.$imagename.'\'].src = \''.$phpbb_root_path.$sprites_path.'/\'+ this.value;">'.$i_list.'</select></td>
				</tr>
			';
		}

		$preview_img .= '
			<div style="position:absolute;width:'.$width.'px;height:'.$height.'px;z-index:'.$zindex.'; padding: 0px; margin: 0px;">
				<img width="'.$width.'" height="'.$height.'" src="'.$phpbb_root_path.$sprites_path.'/'.$firstimage.'" name="'.$imagename.'">
			</div>
		';
		$zindex = $zindex - 1;
	}

	$spritegen = '
		<tr>
			<th class="catHead">&nbsp;'.$lang['nulavatar_name'].'&nbsp;</th>
			<th class="catHead">&nbsp;'.$lang['nulavatar_compulsive'].'&nbsp;</th>
			<th class="catHead">&nbsp;'.$lang['nulavatar_image'].'&nbsp;</th>
		</tr>
	'.$list;

	$button = '<input type="submit" value=" '.$lang['nulavatar_go'].' " class="mainoption"><input type="hidden" name="action" value="save"><input type="hidden" name="tot_layers" value="'.$tot_layers.'">';

	$choose_items = $spritegen;
	$choose_row = 3;
	$buttonrow = 2;
	if($show_shelves == 1) { $choose_items = $items_shelf_layers.$items_shelf; $choose_row = 2; $buttonrow = 1; }

	$useaction = "
		<center>
		<b>".$userdata['username'].$lang['nulavatar_character_sprite']."</b><br />
		<form method=\"post\" action=\"\" name=\"nulavatarlayers\">
		<table width=\"60%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
		<tr>
			<td valign=\"top\" width=\"".$width."\" height=\"".$height."\" class=\"row1\">
				".$preview_img."
				<img src=\"".$phpbb_root_path.$sprites_path."/spacer.gif\" width=\"".$width."\">
			</td>
			<td valign=\"top\" width=\"100\" class=\"row1\">
				&nbsp;
			</td>
			<td valign=\"top\" align=\"right\">
				<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
				".$choose_items."
				</table>
			</td>
			</tr>
				<td class=\"row1\" colspan=\"3\" align=\"center\"><span class=\"gen\">".$button."</span></td>
			</tr>
			</form>
		</table>
		<br />
		</center>
	";

}
//
// [END] Get Layers + Images!
//

//
// [START] Output
//
$useaction = "
	<tr>
	     <td class=\"row1\" height=\"100%\">
		<span class=\"gen\">
			".$useaction."
		</span>
	     </td>
	</tr>";
//
// [END] Output
//

//
// General thingies
//
   $location = " -> <a href=\"".$file."\" class=\"nav\">NulAvatar</a>";

   $template->assign_vars(array( 
		'OUTPUT' => $useaction,
		'LOCATION' => $location,
		'TITLE' => $mod_title
   )); 
   $template->assign_block_vars('', array());

//
// Start output of page
//
include($phpbb_root_path . 'includes/page_header.' . $phpEx);

//
// Generate the page
//
$template->pparse('body');

include($phpbb_root_path . 'includes/page_tail.' . $phpEx);

?>