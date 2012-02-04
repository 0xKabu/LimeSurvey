<table width='100%' border='0' cellpadding='0' cellspacing='0'><tr><td>
        <div class='menubar'>
            <div class='menubar-title ui-widget-header'>
                <strong><?php $clang->eT("Conditions designer");?>:</strong>
            </div>
            <div class='menubar-main'>
                <div class='menubar-left'>
                    <a href="<?php echo $this->createUrl("/admin/survey/view/$surveyid$extraGetParams"); ?>" title='"<?php $clang->eTview("Return to survey administration");?>'>
                        <img name='HomeButton' src='<?php echo $imageurl;?>/home.png' alt='<?php $clang->eT("Return to survey administration");?>' /></a>
                    <img src='<?php echo $imageurl;?>/blank.gif' alt='' width='11' />
                    <img src='<?php echo $imageurl;?>/seperator.gif' alt='' />
                    <a href="<?php echo $this->createUrl("/admin/conditions/index/subaction/conditions/surveyid/$surveyid/gid/$gid/qid/$qid"); ?>" title='<?php $clang->eTview("Show conditions for this question");?>' >
                        <img name='SummaryButton' src='<?php echo $imageurl;?>/summary.png' alt='<?php $clang->eT("Show conditions for this question");?>' /></a>
                    <img src='<?php echo $imageurl;?>/seperator.gif' alt='' />
                    <a href="<?php echo $this->createUrl("admin/conditions/index/subaction/editconditionsform/surveyid/$surveyid/gid/$gid/qid/$qid"); ?>" title='<?php $clang->eTview("Add and edit conditions");?>' >
                        <img name='ConditionAddButton' src='<?php echo $imageurl;?>/conditions_add.png' alt='"<?php $clang->eT("Add and edit conditions");?>' /></a>
                    <a href="<?php echo $this->createUrl("admin/conditions/index/subaction/copyconditionsform/surveyid/$surveyid/gid/$gid/qid/$qid"); ?>" title='<?php $clang->eTview("Copy conditions");?>' >
                        <img name='ConditionCopyButton' src='<?php echo $imageurl;?>/conditions_copy.png' alt='<?php $clang->eT("Copy conditions");?>' /></a>

                </div><div class='menubar-right'>
                    <img width="11" alt="" src="<?php echo $imageurl;?>/blank.gif"/>
                    <label for='questionNav'><?php $clang->eT("Questions");?>:</label>
                    <select id='questionNav' onchange="window.open(this.options[this.selectedIndex].value,'_top')"><?php echo $quesitonNavOptions;?></select>
                    <img hspace="0" border="0" alt="" src="<?php echo $imageurl;?>/seperator.gif"/>
                    <a href="http://docs.limesurvey.org" target='_blank' title="<?php $clang->eTview("LimeSurvey online manual");?>">
                        <img src='<?php echo $imageurl;?>/showhelp.png' name='ShowHelp' title='' alt='<?php $clang->eT("LimeSurvey online manual");?>' /></a>
                </div></div></div>
        <p style='margin: 0pt; font-size: 1px; line-height: 1px; height: 1px;'> </p>
    </td></tr>

<?php echo $conditionsoutput_action_error;?>

<tr><td align='center'>
        <?php echo $javascriptpre;?>
    </td></tr>