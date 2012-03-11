<div class='header ui-widget-header'>
	<?php $clang->eT("Import responses from a deactivated survey table"); ?>
</div>
	<form id='importresponses' class='form30' method='post'>
		<ul>
			<li>
				<label for='spansurveyid'><?php $clang->eT("Target survey ID:"); ?></label>
		 		<span id='spansurveyid'><?php echo $surveyid; ?><input type='hidden' value='$surveyid' name='sid'></span>
			</li>
		<li>
			<label for='oldtable'>
		 		<?php $clang->eT("Source table:"); ?>
		 	</label>
	 		<select name='oldtable'>
				<?php echo $optionElements; ?>
			</select>
		</li>
		<li>
			<label for='importtimings'>
				<?php $clang->eT("Import also timings (if exist):"); ?>
			</label>
			<select name='importtimings' >
				<option value='Y' selected='selected'><?php $clang->eT("Yes"); ?></option>
				<option value='N'><?php $clang->eT("No"); ?></option>
			</select>
		</li>
		</ul>
		<p>
			<input type='submit' value='<?php $clang->eT("Import Responses"); ?>' onclick='return confirm("<?php $clang->eT("Are you sure?","js"); ?>")'>&nbsp;
			<input type='hidden' name='subaction' value='import'><br /><br />
			<div class='messagebox ui-corner-all'><div class='warningheader'><?php echo $clang->gT("Warning").'</div>'.$clang->gT("You can import all old responses with the same amount of columns as in your active survey. YOU have to make sure, that this responses corresponds to the questions in your active survey."); ?></div>
		</p>
	</form>
<br />
