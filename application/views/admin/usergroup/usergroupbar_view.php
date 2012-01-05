<div class='menubar'>
    <div class='menubar-title ui-widget-header'>
    <strong><?php $clang->eT("User group"); ?></strong>
    <?php if($ugid && $grpresultcount > 0)
    {
        echo "{$grow['name']}";
    } ?>


    </div>
    <div class='menubar-main'>
    <div class='menubar-left'>
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/blank.gif' alt='' width='55' height='20' />
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/seperator.gif' alt='' />

    <?php if($ugid && $grpresultcount > 0)
    { ?>
        <a href="#" onclick="window.location='<?php echo $this->createUrl("admin/usergroups/mail/ugid/".$ugid); ?>'"
         title='<?php $clang->eTview("Mail to all Members"); ?>'>
        <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/invite.png' alt='<?php $clang->eT("Mail to all Members"); ?>' name='MailUserGroup' /></a>
    <?php }
    else
    { ?>
        <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/blank.gif' alt='' width='40' height='20' />
    <?php } ?>
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/blank.gif' alt='' width='78' height='20' />
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/seperator.gif' alt='' />

    <?php if($ugid && $grpresultcount > 0 &&
    Yii::app()->session['loginID'] == $grow['owner_id'])
    { ?>
        <a href="#" onclick="window.location='<?php echo $this->createUrl("admin/usergroups/edit/ugid/".$ugid); ?>'"
         title='<?php $clang->eTview("Edit current user group"); ?>'>
        <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/edit.png' alt='<?php $clang->eT("Edit current user group"); ?>' name='EditUserGroup' /></a>
    <?php }
    else
    { ?>
        <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/blank.gif' alt='' width='40' height='20' />
    <?php }

    if($ugid && $grpresultcount > 0 &&
    Yii::app()->session['loginID'] == $grow['owner_id'])
    { ?>

        <a href='#' onclick="if (confirm('<?php $clang->eT("Are you sure you want to delete this entry?","js"); ?>')) { <?php echo get2post($this->createUrl('admin/usergroups/delete')."?action=delusergroup&amp;ugid=$ugid"); ?>}"
         title='<?php $clang->eTview("Delete current user group"); ?>'>
        <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/delete.png' alt='<?php $clang->eT("Delete current user group"); ?>' name='DeleteUserGroup'  /></a>
    <?php }
    else
    { ?>
        <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/blank.gif' alt='' width='40' height='20' />
    <?php } ?>
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/blank.gif' alt='' width='92' height='20' />
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/seperator.gif' alt='' />
    </div>
    <div class='menubar-right'>
    <label for="ugid"><?php $clang->eT("User groups"); ?>:</label>  <select name='ugid' id='ugid' onchange="window.location=this.options[this.selectedIndex].value">
    <?php echo getusergrouplist($ugid,'optionlist'); ?>
    </select>
    <?php if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] == 1)
    { ?>
        <a href='<?php echo $this->createUrl("admin/usergroups/add"); ?>'
         title='<?php $clang->eTview("Add new user group"); ?>'>
        <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/add.png' alt='<?php $clang->eT("Add new user group"); ?>'
         onclick="window.location=''" /></a>
    <?php } ?>
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/seperator.gif' alt='' />
    <img src='<?php echo Yii::app()->getConfig('imageurl'); ?>/blank.gif' alt='' width='82' height='20' />
    </div></div>
    </div>
    <p style='margin:0;font-size:1px;line-height:1px;height:1px;'>&nbsp;</p>