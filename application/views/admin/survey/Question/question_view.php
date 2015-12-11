<div class="side-body">
	<h3><?php eT('Question summary'); ?></h3>
	<div class="row">
		<div class="col-lg-12 content-right">

<table  id='questiondetails' <?php echo $qshowstyle; ?>><tr><td><strong>
            <?php eT("Code:"); ?></strong></td>
        <td><?php echo $qrrow['title']; ?>
            <?php if ($qrrow['type'] != "X")
                {
                    if ($qrrow['mandatory'] == "Y") { ?>
                    : (<i><?php eT("Mandatory Question"); ?></i>)
                    <?php }
                    else { ?>
                    : (<i><?php eT("Optional Question"); ?></i>)
                    <?php }
            } ?>
        </td></tr>
    <tr><td><strong>
            <?php eT("Question:"); ?></strong></td><td>
            <?php
                templatereplace($qrrow['question'],array(),$aReplacementData,'Unspecified', false ,$qid);
                echo viewHelper::stripTagsEM(LimeExpressionManager::GetLastPrettyPrintExpression());
        ?></td></tr>
    <tr><td><strong>
            <?php eT("Help:"); ?></strong></td><td>
            <?php
                if (trim($qrrow['help'])!=''){
                    templatereplace($qrrow['help'],array(),$aReplacementData,'Unspecified', false ,$qid);
                    echo viewHelper::stripTagsEM(LimeExpressionManager::GetLastPrettyPrintExpression());
            } ?>
        </td></tr>
    <?php if ($qrrow['preg'])
        { ?>
        <tr ><td><strong>
                <?php eT("Validation:"); ?></strong></td><td><?php echo htmlspecialchars($qrrow['preg']); ?>
            </td></tr>
        <?php } ?>

    <tr><td><strong>
            <?php eT("Type:"); ?></strong></td><td><?php echo $qtypes[$qrrow['type']]['description']; ?>
        </td></tr>
    <?php if ($qct == 0 && $qtypes[$qrrow['type']]['answerscales'] >0)
        { ?>
        <tr ><td></td><td>
                <span class='statusentryhighlight'>
                    <?php eT("Warning"); ?>: <a href='<?php echo $this->createUrl("admin/questions/sa/answeroptions/surveyid/$surveyid/gid/$gid/qid/$qid"); ?>'><?php eT("You need to add answer options to this question"); ?>
                        <span class="icon-answers text-success" title='<?php eT("Edit answer options for this question"); ?>'></span>
                    </a>
                </span></td></tr>
        <?php }


        if($sqct == 0 && $qtypes[$qrrow['type']]['subquestions'] >0)
        { ?>
        <tr ><td></td><td>
                <span class='statusentryhighlight'>
                    <?php eT("Warning"); ?>: <a href='<?php echo $this->createUrl("admin/questions/sa/subquestions/surveyid/$surveyid/gid/$gid/qid/$qid"); ?>'><?php eT("You need to add subquestions to this question"); ?>
                        <span class="icon-subquestions text-success" title='<?php eT("Edit subquestions for this question"); ?>' ></span>
                            </a></span></td></tr>
        <?php }

        if ($qrrow['type'] == "M" or $qrrow['type'] == "P")
        { ?>
        <tr>
            <td><strong>
                <?php eT("Option 'Other':"); ?></strong></td>
            <td>
                <?php if ($qrrow['other'] == "Y") { ?>
                    <?php eT("Yes"); ?>
                    <?php } else
                    { ?>
                    <?php eT("No"); ?>

                    <?php } ?>
            </td></tr>
        <?php }
        if (isset($qrrow['mandatory']) and ($qrrow['type'] != "X") and ($qrrow['type'] != "|"))
        { ?>
        <tr>
            <td><strong>
                <?php eT("Mandatory:"); ?></strong></td>
            <td>
                <?php if ($qrrow['mandatory'] == "Y") { ?>
                    <?php eT("Yes"); ?>
                    <?php } else
                    { ?>
                    <?php eT("No"); ?>

                    <?php } ?>
            </td>
        </tr>
        <?php } ?>
    <?php if (trim($qrrow['relevance']) != '') { ?>
        <tr>
            <td><?php eT("Relevance equation:"); ?></td>
            <td>
                <?php
                    LimeExpressionManager::ProcessString("{" . $qrrow['relevance'] . "}", $qid);    // tests Relevance equation so can pretty-print it
                    echo LimeExpressionManager::GetLastPrettyPrintExpression();
                ?>
            </td>
        </tr>
        <?php } ?>
    <?php
        $sCurrentCategory='';
        foreach ($advancedsettings as $aAdvancedSetting)
        { ?>
        <tr>
            <td><?php echo $aAdvancedSetting['caption'];?>:</td>
            <td><?php
                    if ($aAdvancedSetting['i18n']==false)  echo htmlspecialchars($aAdvancedSetting['value']); else echo htmlspecialchars($aAdvancedSetting[$baselang]['value'])?>
            </td>
        </tr>
        <?php } ?>
</table>

    <!-- Quick Actions -->
    <h3 id="survey-action-title"><?php eT('Survey quick actions'); ?></h3>
    <div class="row welcome survey-action">
        <div class="col-lg-12 content-right">
            <!-- create new question in this group -->
            <div class="col-lg-3">
                <div class="panel panel-primary panel-clickable" id="pannel-1" aria-data-url="<?php echo $this->createUrl('admin/questions/sa/newquestion/surveyid/'.$surveyid.'/gid/'.$gid); ?>">
                    <div class="panel-heading">
                        <h4 class="panel-title"><?php eT("Add new question to group");?></h4>
                    </div>
                    <div class="panel-body">
                        <a  href="<?php echo $this->createUrl('admin/questions/sa/newquestion/surveyid/'.$surveyid.'/gid/'.$gid); ?>" >
                            <span class="icon-add text-success"  style="font-size: 3em;"></span>
                        </a>
                        <p> <a href="<?php echo $this->createUrl('admin/questions/sa/newquestion/surveyid/'.$surveyid.'/gid/'.$gid); ?>">
                                <?php eT("Add new question to group");?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
		</div>
	</div>
</div>
