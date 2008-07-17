<?php
//Copyright (C) ATL Telecom Ltd 2008 Nick Lewis
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

isset($_REQUEST['action'])?$action = $_REQUEST['action']:$action='';

//the item we are currently displaying
isset($_REQUEST['itemid'])?$itemid=$_REQUEST['itemid']:$itemid='';

$dispnum = "usersets"; //used for switch on config.php

//if submitting form, update database
if(isset($_POST['action'])) {
	switch ($action) {
		case "add":
			usersets_add($_POST);
			needreload();
			redirect_standard();
		break;
		case "delete":
			usersets_del($itemid);
			needreload();
			redirect_standard();
		break;
		case "edit":
			usersets_edit($itemid,$_POST);
			needreload();
			redirect_standard('itemid');
		break;
	}
}

//get list of permitted user sets
$usersetss = usersets_list();
?>

</div> <!-- end content div so we can display rnav properly-->


<!-- right side menu -->
<div class="rnav"><ul>
    <li><a <?php echo ($itemid=='' ? "id=\"current\"":'') ?> href="config.php?display=<?php echo urlencode($dispnum)?>"><?php echo _("Add Permitted User Set")?></a></li>
<?php
if (isset($usersetss)) {
	foreach ($usersetss as $usersets) {
		echo "<li><a ".($itemid==$usersets['usersets_id'] ? "id=\"current\"":"")." href=\"config.php?display=".urlencode($dispnum)."&amp;itemid=".urlencode($usersets['usersets_id'])."\">{$usersets['description']}</a></li>";
	}
}
?>
</ul></div>

<div class="content">
<?php
if ($action == 'delete') {
	echo '<br /><h3>'._("Permitted User Set ").' '.$itemid.' '._("deleted").'!</h3>';
} else {
	if ($itemid){
		//get details for this permitted user set
		$thisItem = usersets_get($itemid);
	}

	$delURL = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delete';
	$delButton = "
			<form name=delete action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
				<input type=\"hidden\" name=\"display\" value=\"{$dispnum}\" />
				<input type=\"hidden\" name=\"itemid\" value=\"{$itemid}\" />
				<input type=\"hidden\" name=\"action\" value=\"delete\" />
				<input type=submit value=\""._("Delete User Set")."\" />
			</form>";

?>

	<h2><?php echo ($itemid ? _("Permitted User Set:")." ". $itemid : _("Add Permitted User Set")); ?></h2>

	<p><?php echo ($itemid ? '&nbsp;' : _("User Sets are used to manage lists of permitted users that can be given access to restricted features such as Outbound Routes.")); ?></p>

<?php		if ($itemid){  echo $delButton; 	} ?>

<form name="edit" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return edit_onsubmit();">
	<input type="hidden" name="display" value="<?php echo $dispnum?>" />
	<input type="hidden" name="action" value="<?php echo ($itemid ? 'edit' : 'add') ?>" />
	<input type="hidden" name="deptname" value="<?php echo $_SESSION["AMP_user"]->_deptname ?>" />

<?php		if ($itemid){ ?>
		<input type="hidden" name="account" value="<?php echo $itemid; ?>" />
<?php		}?>


	<table>
	<tr><td colspan="2"><div class="h5"><?php echo ($itemid ? _("Edit User Set") : _("New User Set")) ?><hr /></div></td></tr>

	<tr>
		<td><?php echo _("User Set Description:")?></td>
		<td><input type="text" size=23 name="description" value="<?php echo (isset($thisItem['description']) ? $thisItem['description'] : ''); ?>" /></td>
	</tr>
<tr><td>&nbsp;</td></tr>
			<tr>
				<td valign="top"><a href="#" class="info"><?php echo _("Trusted user list")?>:<span><br /><?php echo _("List of extensions that are permitted without requiring the user to enter their voicemail password.")?><br /><br /></span></a></td>
				<td valign="top">
					<?php $rows = 10; ?>
					<textarea id="trustlist" cols="20" rows="<?php  echo $rows ?>" name="trustlist"><?php echo(isset($thisItem['trustlist']) ? $thisItem['trustlist'] : '');?></textarea><br />
					<input type="submit" style="font-size:10px;" value="<?php echo _("Clean &amp; Remove duplicates")?>" />

				</td>
			</tr>
<tr><td>&nbsp;</td></tr>
			<tr>
				<td valign="top"><a href="#" class="info"><?php echo _("Authenticated user list")?>:<span><br /><?php echo _("List of extensions that are permitted once the user has entered their voicemail password.")?><br /><br /></span></a></td>
				<td valign="top">
					<?php $rows = 10; ?>
					<textarea id="authlist" cols="20" rows="<?php  echo $rows ?>" name="authlist"><?php echo(isset($thisItem['authlist']) ? $thisItem['authlist'] : '');?></textarea><br />
					<input type="submit" style="font-size:10px;" value="<?php echo _("Clean &amp; Remove duplicates")?>" />
				</td>
			</tr>
<tr><td>&nbsp;</td></tr>
	<tr>
		<td colspan="2"><br /><h6><input name="submit" type="submit" value="<?php echo _("Submit Changes")?>" /></h6></td>
	</tr>

	</table>
<script type="text/javascript">
<!--

var theForm = document.edit;
theForm.description.focus();

function edit_onsubmit() {

	defaultEmptyOK = false;
	if (!isAlphanumeric(theForm.description.value))
		return warnInvalid(theForm.description, "Please enter a valid Description");

	return true;
}


-->
</script>


	</form>
<?php
} //end if action == delete
?>
