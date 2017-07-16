<?php
/***************************************************************************
 *                            nulavatar_update.php
 *                           -----------------------
 *		                Update file
 *
 *		NulAvatar made and (c) by Guido "Nuladion" Kessels
 ***************************************************************************/

define('IN_PHPBB', true);
$phpbb_root_path='./';
include($phpbb_root_path.'extension.inc');
include($phpbb_root_path.'common.'.$phpEx);

//
// Start session management
//
$userdata = session_pagestart($user_ip, PAGE_INDEX);
init_userprefs($userdata);
//
// End session management
//

$file = "nulavatar_update_v130.php";
$na_version = "1.3.0";

if( !$userdata['session_logged_in'] )
{
	header('Location: ' . append_sid("login.$phpEx?redirect=".$file, true));
}

if( $userdata['user_level'] != ADMIN )
{
	message_die(GENERAL_MESSAGE, $lang['Not_Authorised']);
}

if( !strstr($dbms, "mysql") )
{
    if( !isset($_GET['bypass']) )
    {
        $message = 'This mod has only been tested on MySQL and may only work on MySQL.<br />';
        $message .= 'Click <a href="'.$file.'?bypass=true">here</a> to install anyways.';
        message_die(GENERAL_MESSAGE, $message);
    }
}

if($_GET['gdcheck'] != "ok")
{
$info = gd_info(); 
$keys = array_keys($info); 
$values = array_values($info); 

$checked = false;

echo "<b>NulAvatar ".$na_version." GDLibrary check!</b> <br />";
echo "<br />";

if(!$values[0]) {
	echo("
		You don't seem to have the <b>GD Library</b> installed on your server! <br />
		The GD Library <i>is</i> required to use NulAvatar!<br />
		<br />
		Click <a href=\"".$file."?gdcheck=ok\">here</a> if you want to update NulAvatar anyway (not recommended)
	");
	$checked = true;
}
if(!$values[7]) {
	if(!$values[4]) {
	echo("
		You have the GD Library installed, but don't seem to have <b>PNG Support</b> enabled on your server! <br />
		The GD Library with PNG Support <i>is</i> required to use NulAvatar!<br />
		<br />
		Click <a href=\"".$file."?gdcheck=ok\">here</a> if you want to update NulAvatar anyway (not recommended)
	");
	$checked = true;
	}
	else {
	echo("
		You have the GD Library installed, but don't seem to have <b>PNG Support</b> enabled on your server! <br />
		However, you seem to have GIF Read Support enabled!<br />
		Make sure you set the NulAvatar 'Imagetype' to 'GIF' in the Settings Admin panel, and use GIF images instead of PNG!
		<br />
		Click <a href=\"".$file."?gdcheck=ok\">here</a> to update NulAvatar
	");
	$checked = true;
	}
}
if(($values[4]) && ($values[7])) {
	echo("
		You have the GD Library installed with PNG Support and GIF Read Support enabled!<br />
		This means you can use both PNG and GIF images with NulAvatar! <br />
		Make sure to correctly change the NulAvatar 'Imagetype' in the Settings Admin panel if you switch from one image format to another! <br />
		<br />
		Click <a href=\"".$file."?gdcheck=ok\">here</a> to update NulAvatar
	");
	$checked = true;
}

if($checked == false)
{
	echo("
		The script failed to check your GD Library status...<br />
		If you think you have the GD Library installed with 'PNG Support' and/or 'GIF Read Support' enabled,
		you can still install NulAvatar. If you're not sure, I recommend you contact your host first! <br />
		<br />
		Click <a href=\"".$file."?gdcheck=ok\">here</a> to update NulAvatar
	");
}

}
else
{

echo "<html>\n";
echo "<body>\n";

$sql = array();
$dat = array();

echo "<b>NulAvatar ".$na_version." Updater!</b> <br /><br />";

// Check if xxx_nulmods already exist!
echo "Check if ".$table_prefix."nulmods table exists...";
$check = mysql_query("SELECT * FROM ".$table_prefix."nulmods LIMIT 0,1");
if($check) { 
	// Table exists! -- Check if NulAvatar entry exists yet!
	echo "<b><font color=\"007700\">YES</font></b><br />\n";
	echo "Check for NulAvatar entry...";
	$check2 = mysql_query("SELECT * FROM ".$table_prefix."nulmods WHERE title='NulAvatar' LIMIT 0,1");
	$count2= mysql_num_rows($check2);
	if($count2 > 0) { 
		// Found! -- Update NA version number!
		echo "<b>found!</b> ";
		$dat[] = "Updating version number";
		$sql[] = "UPDATE `".$table_prefix."nulmods` SET version = '".$na_version."' WHERE title = 'NulAvatar'";
	} else {
		// Not found! -- Insert NulAvatar to nulmods table!
		echo "<b>not found!</b> ";
		$dat[] = "Inserting NulAvatar info";
		$sql[] = "INSERT INTO `".$table_prefix."nulmods` (title,version) VALUES ('NulAvatar','".$na_version."')";
	}
}
else {
	// Table doesn't exist! -- Create it and add NulAvatar info!
	echo "<b><font color=\"orange\">NO</font></b><br />\n";
	$dat[] = "Creating ".$table_prefix."nulmods table";
	$sql[] = "CREATE TABLE `".$table_prefix."nulmods` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `title` MEDIUMTEXT NOT NULL,
	  `version` MEDIUMTEXT NOT NULL,
	  PRIMARY KEY  (`id`)
	)";
	
	$dat[] = "Inserting NulAvatar info";
	$sql[] = "INSERT INTO `".$table_prefix."nulmods` (title,version) VALUES ('NulAvatar','".$na_version."')";
}
$dat[] = "Updating ".$table_prefix."nulavatar_settings table";
$sql[] = "UPDATE `".$table_prefix."nulavatar_settings` SET config_radio_choices = 'moogies,shop3,adr' WHERE config_name = 'rpgsystem' ";

$sql_count = count($sql);

for($i = 0; $i < $sql_count; $i++) {
	echo "" . $dat[$i];
	flush();

	if ( !$db->sql_query($sql[$i]) )
	{
		$errored = true;
		$error = $db->sql_error();
		echo "... <b><font color=\"FF0000\">FAILED</font></b><BR />Error Message:<i>" . $error['message'] . "</i><br />\n";
	}
	else
	{
		echo "... <b><font color=\"007700\">COMPLETED</font></b><br />\n";
	}
}

echo "<br />\n<br />\n";

// Do CHMOD
$filename = $phpbb_root_path.'images/nulavatar_avatars/';
echo "Checking if NulAvatar is able to write to <b>".$filename."</b>... ";
if (file_exists($filename))
{
	if (!is_writable($filename))
	{
		echo "<font color=\"orange\"><b>NO</b></font><br />\n Attempting to CHMOD <b>".$filename."</b> to 0777...";
		if (!chmod($filename, 0777))
		{
			echo "<br />Could not CHMOD <b>".$filename."</b> to 0777! Please do it manually! (required for NulAvatar to save your avatars!)<br />\n";
		}
		else
		{
			echo "The folder <b>".$filename."</b> was succesfully CHMODded to 0777!<br />\n";
		}
	}
	else
	{
		echo "<font color=\"007700\"><b>YES</b></font><br />\n";
	}
}
else
{
	echo "<font color=\"FF0000\"><b>NO</b></font><br />\n Make sure the folder exists!<br />\n";
}

echo "<br />\n<br />\n";

if( $errored ) {
    $message = "The update was <b>not</b> successful. Please try again!<br />If the problem persists, please post in the NulAvatar thread!";
}
else {
    $message = "<b>NulAvatar has been updated succesfully!</b><br />Make sure to delete this update file!<br /><b>Enjoy!</b>";
}

echo "\n<br />\n<b>Finished!</b><br />\n";
echo $message . "<br />\n";
echo "</body>\n";
echo "</html>\n";
exit();

// End GD Check
}

?>