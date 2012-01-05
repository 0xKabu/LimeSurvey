<script src="<?php echo Yii::app()->getConfig('generalscripts')."jquery/jquery.js";?>" type="text/javascript"></script>
<script src="<?php echo Yii::app()->getConfig('generalscripts')."jquery/jquery-ui.js"; ?>" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->getConfig('generalscripts')."jquery/css/start/jquery-ui.css"; ?>" />
<title><?php $clang->eT("Blacklist control"); ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->baseUrl."templates/default/template.css"; ?>" />
<style type="text/css" media="aural tty">
    progress-graph .zero, progress-graph .graph, progress-graph .cent { display: none; }
</style>
<script src="<?php echo $this->createUrl("/templates/default/template.js"); ?>" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $this->createUrl("'templates/default/favicon.ico"); ?>" />
<script src="<?php echo Yii::app()->getConfig('generalscripts')."scripts/survey_runtime.js"; ?>" type="text/javascript"></script>

<body class="default lang-en groupbygroup">
<div class="outerframe">
<table class="innerframe">
<tr>
<td>
<table class="survey-header-table">
    <tr>
        <td class="survey-description">
            <br />
            <h2><?php $clang->eT('Blacklist control'); ?></h2>
            <p></p>
        </td>
    </tr>
    <tr>
        <td class="graph">
        </td>
    </tr>
    <tr>
        <td class="language-changer">
        </td>
    </tr>
</table>
<div id='wrapper'>
    <p id='tokenmessage'>
        <?php
            if($global == 1)
            {
                if($is_participant && $is_updated)
                {
                    if($blacklist == 'Y')
                    {
                        $clang->eT("You have successfully blacklisted from any survey on this server");
                    }
                    else if($blacklist == 'N')
                        {
                            $clang->eT("You have successfully un-blacklisted from any survey on this server");
                        }
                }
                else if($is_participant)
                    {
                        if($blacklist == 'Y')
                        {
                            $clang->eT("You have already been blacklisted from any survey on this server");
                        }
                        else if($blacklist == 'N')
                            {
                                $clang->eT("You have already been un-blacklisted from any survey on this server");
                            }

                }
                else if(!$is_survey)
                    {
                        $clang->eT("Survey is no longer active");
                    }
                    else
                    {
                        $clang->eT("The URL you are trying to use is either modified, or you have been removed from this server");
                }

            }
            else if($local == 1)
                {
                    if($is_participant && $is_updated)
                    {
                        if($blacklist == 'Y')
                        {
                            $clang->eT("You have successfully blacklisted from this survey");
                        }
                        else if($blacklist == 'N')
                            {
                                $clang->eT("You have successfully un-blacklisted from this survey");
                            }
                }
                else if($is_participant)
                    {
                        if($blacklist == 'Y')
                        {
                            $clang->eT("You have already been blacklisted from this survey");
                        }
                        else if($blacklist == 'N')
                            {
                                $clang->eT("You have already been un-blacklisted from this survey");
                            }

                }
                else
                {
                    $clang->eT("The URL you are trying to use is either modified, or you have been removed from this server");

                }

            }
            else
            {
                $clang->eT("You have successfully blacklisted from this survey");
            }
        ?>
    </p>
</div>
