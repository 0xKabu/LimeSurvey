<div class="header ui-widget-header"><?php $clang->eT("Survey Quota");?>: <?php $clang->eT("Add Answer");?></div><br />
	<div class="messagebox ui-corner-all" style="width: 600px">
		<form action="<?php echo $this->createUrl("/admin/quotas/new_answer/surveyid/$iSurveyId/subaction/new_answer_two");?>" method="post">
			<table class="addquotaanswer" border="0" cellpadding="0" cellspacing="0" bgcolor="#F8F8FF">
				<thead>
				<tr>
				  <th class="header ui-widget-header"  colspan="2"><?php echo sprintf($clang->gt("New Answer for Quota '%s'"), $quota_name);?></th>
				</tr>
				</thead>
				<tbody>
				<tr class="evenrow">
					<td align="center">&nbsp;</td>
					<td align="center">&nbsp;</td>
				</tr>
				<tr class="evenrow">
					<td width="30%" align="center" valign="top"><strong><?php $clang->eT("Select Question");?>:</strong></td>
					<td align="left">
						<select name="quota_qid" size="15">
	<?php foreach ($newanswer_result as $questionlisting) { ?>
        <option value="<?php echo $questionlisting['qid'];?>"><?php echo $questionlisting['title'];?>: <?php echo strip_tags(substr($questionlisting['question'],0,40));?></option>
    <?php } ?>

						</select>
					</td>
				</tr>
				<tr align="left" class="evenrow">
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr align="left" class="evenrow">
					<td>&nbsp;</td>
					<td>
						<input name="submit" type="submit" class="submit" value="<?php $clang->eT("Next");?>" />
						<input type="hidden" name="sid" value="'.$iSurveyId.'" />
						<input type="hidden" name="action" value="quotas" />
						<input type="hidden" name="subaction" value="new_answer_two" />
						<input type="hidden" name="quota_id" value="<?php echo sanitize_int($_POST['quota_id']);?>" />
					</td>
				</tr>
				</tbody>
			</table><br />
		</form>
	</div>
