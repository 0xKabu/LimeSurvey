<div class='header ui-widget-header'><?php $clang->eT("Import survey resources"); ?></div>\n";
	<div class='messagebox ui-corner-all'>
		<strong><?php $clang->eT("Imported Resources for"); ?>" SID:</strong> <?php echo $surveyid; ?><br /><br />
        <div class="<?php echo $statusClass; ?>">
        	<?php echo $status; ?>
        </div>
        <br />
        <strong>
        	<u><?php $clang->eT("Resources Import Summary"); ?></u>
        </strong>
        <br />
        <?php $clang->eT("Total Imported files"); ?>: <?php echo $okfiles; ?><br />
        <?php $clang->eT("Total Errors"); ?>: <?php echo $errfiles; ?><br />
        <?php echo $additional_content; ?>
    	<input type='submit' value='<?php $clang->eT("Back"); ?>' onclick="window.open('<?php echo $this->createUrl('admin/survey/sa/editsurveysettings/surveyid/'.$surveyid); ?>', '_top')" />\n";
	</div>
