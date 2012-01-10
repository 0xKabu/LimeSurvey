<div id='resources'>
    <form enctype='multipart/form-data'  class='form30' id='importsurveyresources' name='importsurveyresources' action='<?php echo $this->createUrl('admin/survey/importsurveyresources/'); ?>' method='post' onsubmit='return validatefilename(this,"<?php $clang->eT('Please select a file to import!', 'js'); ?>");'>
        <input type='hidden' name='surveyid' value='<?php echo $surveyid; ?>' />
        <input type='hidden' name='action' value='importsurveyresources' />
        <ul>
            <li><label>&nbsp;</label>
                <input type='button' onclick='window.open("<?php echo $this->createUrl("admin/kcfinder/index/load/browse"); ?>", "_blank")' value="<?php $clang->eT("Browse Uploaded Resources"); ?>" /></li>
            <li><label>&nbsp;</label>
                <input type='button' onclick='window.open("<?php echo $this->createUrl("admin/export/resources/export/survey/surveyid/$surveyid/"); ?>", "_blank")' value="<?php $clang->eT("Export Resources As ZIP Archive"); ?>" <?php echo $disabledIfNoResources; ?> /></li>
            <li><label for='the_file'><?php $clang->eT("Select ZIP File:"); ?></label>
                <input id='the_file' name='the_file' type='file' size='50' /></li>
            <li><label>&nbsp;</label>
                <input type='button' value='<?php $clang->eT("Import Resources ZIP Archive"); ?>' <?php echo $ZIPimportAction; ?> /></li>
        </ul>
    </form>
</div>