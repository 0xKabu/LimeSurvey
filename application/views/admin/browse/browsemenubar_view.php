<div class='menubar'>
    <div class='menubar-title ui-widget-header'>
        <strong><?php echo $title; ?></strong>: (<?php echo $thissurvey['surveyls_title']; ?>)
    </div>
    <div class='menubar-main'>
        <div class='menubar-left'>
            <a href='<?php echo $this->createUrl("admin/survey/view/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Return to survey administration"); ?>">
                <img src='<?php echo $sImageURL; ?>home.png' title='' alt='<?php $clang->eT("Return to survey administration"); ?>' /></a>
            <img src='<?php echo $sImageURL; ?>blank.gif' alt='' width='11' />
            <img src='<?php echo $sImageURL; ?>seperator.gif' alt='' />

            <?php if (hasSurveyPermission($surveyid, 'responses', 'read'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/browse/index/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Show summary information"); ?>">
                    <img src='<?php echo $sImageURL; ?>summary.png' title='' alt='<?php $clang->eT("Show summary information"); ?>' /></a>
                <?php if (count(Survey::model()->findByPk($surveyid)->additionalLanguages) == 0)
                    { ?>
                    <a href='<?php echo $this->createUrl("admin/browse/index/surveyid/$surveyid/all"); ?>' title="<?php $clang->eTview("Display Responses"); ?>">
                        <img src='<?php echo $sImageURL; ?>document.png' title='' alt='<?php $clang->eT("Display Responses"); ?>' /></a>
                    <?php }
                    else
                    { ?>
                    <a href="<?php echo $this->createUrl("admin/browse/index/surveyid/$surveyid/all"); ?>" accesskey='b' id='browseresponses' title="<?php $clang->eTview("Display Responses"); ?>" >
                        <img src='<?php echo $sImageURL; ?>document.png' alt='<?php $clang->eT("Display Responses"); ?>' /></a>

                    <div class="langpopup" id="browselangpopup"><?php $clang->eT("Please select a language:"); ?><ul>
                            <?php foreach ($tmp_survlangs as $tmp_lang)
                                { ?>
                                <li><a href="<?php echo $this->createUrl("admin/browse/index/surveyid/$surveyid/all/start/0/limit/50/order/asc/browselang/$tmp_lang"); ?>" accesskey='b'><?php echo getLanguageNameFromCode($tmp_lang, false); ?></a></li>
                                <?php } ?>
                        </ul></div>
                    <?php } ?>
                <a href='<?php echo $this->createUrl("admin/browse/index/surveyid/$surveyid/all/start/0/limit/50/order/desc"); ?>' title="<?php $clang->eTview("Display Last 50 Responses"); ?>" >
                    <img src='<?php echo $sImageURL; ?>viewlast.png' alt='<?php $clang->eT("Display Last 50 Responses"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'create'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/dataentry/view/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Dataentry Screen for Survey"); ?>" >
                    <img src='<?php echo $sImageURL; ?>dataentry.png' alt='<?php $clang->eT("Dataentry Screen for Survey"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'statistics', 'read'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/statistics/index/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Get statistics from these responses"); ?>">
                    <img src='<?php echo $sImageURL; ?>statistics.png' alt='<?php $clang->eT("Get statistics from these responses"); ?>' /></a>
                <?php if ($thissurvey['savetimings'] == "Y")
                    { ?>
                    <a href='<?php echo $this->createUrl("admin/browse/index/surveyid/$surveyid/subaction/time"); ?>' title="<?php $clang->eTview("Get time statistics from these responses"); ?>" >
                        <img src='<?php echo $sImageURL; ?>timeStatistics.png' alt='<?php $clang->eT("Get time statistics from these responses"); ?>' /></a>
                    <?php }
            } ?>
            <img src='<?php echo $sImageURL; ?>seperator.gif' alt='' />
            <?php if (hasSurveyPermission($surveyid, 'responses', 'export'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/export/exportresults/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Export results to application"); ?>">
                    <img src='<?php echo $sImageURL; ?>export.png' alt='<?php $clang->eT("Export results to application"); ?>' /></a>

                <a href='<?php echo $this->createUrl("admin/export/exportspss/sid/$surveyid"); ?>' title="<?php $clang->eTview("Export results to a SPSS/PASW command file"); ?>">
                    <img src='<?php echo $sImageURL; ?>exportspss.png' alt="<?php $clang->eT("Export results to a SPSS/PASW command file"); ?>" /></a>

                <a href='<?php echo $this->createUrl("admin/export/exportr/sid/$surveyid"); ?>' title="<?php $clang->eTview("Export results to a R data file"); ?>" >
                    <img src='<?php echo $sImageURL; ?>exportr.png' alt='<?php $clang->eT("Export results to a R data file"); ?>' /></a>
                <?php
                }
                if (hasSurveyPermission($surveyid, 'responses', 'create'))
                {
                ?>
                <a href='<?php echo $this->createUrl("admin/dataentry/import/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Import responses from a deactivated survey table"); ?>">
                    <img src='<?php echo $sImageURL; ?>importold.png' alt='<?php $clang->eT("Import responses from a deactivated survey table"); ?>' /></a>
                <?php } ?>
            <img src='<?php echo $sImageURL; ?>seperator.gif' alt='' />

            <?php if (hasSurveyPermission($surveyid, 'responses', 'read'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/saved/view/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("View Saved but not submitted Responses"); ?>" >
                    <img src='<?php echo $sImageURL; ?>saved.png' title='' alt='<?php $clang->eT("View Saved but not submitted Responses"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'import'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/dataentry/vvimport/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Import a VV survey file"); ?>" >
                    <img src='<?php echo $sImageURL; ?>importvv.png' alt='<?php $clang->eT("Import a VV survey file"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'export'))
                { ?>
                <a href='<?php echo $this->createUrl("admin/export/vvexport/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Export a VV survey file"); ?>" >
                    <img src='<?php echo $sImageURL; ?>exportvv.png' title='' alt='<?php $clang->eT("Export a VV survey file"); ?>' /></a>
                <?php }
                if (hasSurveyPermission($surveyid, 'responses', 'delete') && $thissurvey['anonymized'] == 'N' && $thissurvey['tokenanswerspersistence'] == 'Y')
                { ?>
                <a href='<?php echo $this->createUrl("admin/iteratesurvey/surveyid/$surveyid"); ?>' title="<?php $clang->eTview("Iterate survey"); ?>" >
                    <img src='<?php echo $sImageURL; ?>iterate.png' title='' alt='<?php $clang->eT("Iterate survey"); ?>' /></a>
                <?php } ?>
        </div>
    </div>
</div>
