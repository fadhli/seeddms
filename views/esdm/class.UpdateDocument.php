<?php
/**
 * Implementation of UpdateDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for UpdateDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_UpdateDocument extends SeedDMS_Bootstrap_Style {

	function __takeOverButton($name, $users) { /* {{{ */
?>
	<span id="<?php echo $name; ?>_btn" style="cursor: pointer;" title="<?php printMLText("takeOver".$name); ?>"><i class="icon-arrow-left"></i></span>
<script>
$(document).ready( function() {
	$('#<?php echo $name; ?>_btn').click(function(ev){
		ev.preventDefault();
<?php
		foreach($users as $_id) {
			echo "$(\"#".$name." option[value='".$_id."']\").attr(\"selected\", \"selected\");\n";
		}
?>
		$("#<?php echo $name; ?>").trigger("chosen:updated");
	});
});
</script>
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$strictformcheck = $this->params['strictformcheck'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$workflowmode = $this->params['workflowmode'];
		$presetexpiration = $this->params['presetexpiration'];
		$documentid = $document->getId();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("update_document"));
?>

<script language="JavaScript">
function checkForm()
{
	msg = new Array();
<?php if($dropfolderdir) { ?>
	if (document.form1.userfile.value == "" && document.form1.dropfolderfileform1.value == "") msg.push("<?php printMLText("js_no_file");?>");
<?php } else { ?>
	if (document.form1.userfile.value == "") msg.push("<?php printMLText("js_no_file");?>");
<?php } ?>
<?php
	if ($strictformcheck) {
	?>
	if (document.form1.comment.value == "") msg.push("<?php printMLText("js_no_comment");?>");
<?php
	}
?>
	if (msg != "")
	{
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}
</script>

<?php
		if ($document->isLocked()) {

			$lockingUser = $document->getLockingUser();

			print "<div class=\"alert alert-warning\">";
			
			printMLText("update_locked_msg", array("username" => htmlspecialchars($lockingUser->getFullName()), "email" => $lockingUser->getEmail()));
			
			if ($lockingUser->getID() == $user->getID())
				printMLText("unlock_cause_locking_user");
			else if ($document->getAccessMode($user) == M_ALL)
				printMLText("unlock_cause_access_mode_all");
			else
			{
				printMLText("no_update_cause_locked");
				print "</div>";
				$this->htmlEndPage();
				exit;
			}

			print "</div>";
		}

		$latestContent = $document->getLatestContent();
		$reviewStatus = $latestContent->getReviewStatus();
		$approvalStatus = $latestContent->getApprovalStatus();
		if($workflowmode == 'advanced') {
			if($status = $latestContent->getStatus()) {
				if($status["status"] == S_IN_WORKFLOW) {
					$this->warningMsg("The current version of this document is in a workflow. This will be interrupted and cannot be completed if you upload a new version.");
				}
			}
		}

		$msg = getMLText("max_upload_size").": ".ini_get( "upload_max_filesize");
		$this->warningMsg($msg);
		$this->contentContainerStart();
?>

<form action="../op/op.UpdateDocument.php" enctype="multipart/form-data" method="post" name="form1" onsubmit="return checkForm();">
	<input type="hidden" name="documentid" value="<?php print $document->getID(); ?>">
	<table class="table-condensed">
	
		<tr>
			<td><?php printMLText("local_file");?>:</td>
			<td><!-- input type="File" name="userfile" size="60" -->
<?php
	$this->printFileChooser('userfile', false);
?>
			</td>
		</tr>
<?php if($dropfolderdir) { ?>
		<tr>
			<td><?php printMLText("dropfolder_file");?>:</td>
			<td><?php $this->printDropFolderChooser("form1");?></td>
		</tr>
<?php } ?>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td class="standardText">
				<textarea name="comment" rows="4" cols="80"></textarea>
			</td>
		</tr>
<?php
			if($presetexpiration) {
				if(!($expts = strtotime($presetexpiration)))
					$expts = time();
			} else {
				$expts = time();
			}
?>
		<tr>
			<td><?php printMLText("expires");?>:</td>
			<td class="standardText">
        <span class="input-append date span12" id="expirationdate" data-date="<?php echo date('d-m-Y', $expts); ?>" data-date-format="dd-mm-yyyy" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span3" size="16" name="expdate" type="text" value="<?php echo date('d-m-Y', $expts); ?>">
          <span class="add-on"><i class="icon-calendar"></i></span>
        </span><br />
        <label class="checkbox inline">
				  <input type="checkbox" name="expires" value="false"<?php if (!$document->expires()) print " checked";?>><?php printMLText("does_not_expire");?><br>
        </label>
			</td>
		</tr>
<?php
	$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_all));
	if($attrdefs) {
		foreach($attrdefs as $attrdef) {
?>
    <tr>
	    <td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
	    <td><?php $this->printAttributeEditField($attrdef, '') ?></td>
    </tr>
<?php
		}
	}
?>
		<tr>
			<td></td>
			<td><input type="submit" class="btn" value="<?php printMLText("update_document")?>"></td>
		</tr>
	</table>
</form>

<?php
		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
