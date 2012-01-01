<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * LimeSurvey
 * Copyright (C) 2007-2011 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 *	$Id: Admin_Controller.php 11256 2011-10-25 13:52:18Z c_schmitz $
 */

/**
 * This functions checks if the databaseversion in the settings table is the same one as required
 * If not then the necessary upgrade procedures are run
 */
function CheckForDBUpgrades($subaction = null)
{
	$clang = Yii::app()->getController()->lang;
	$dbversionnumber = Yii::app()->getConfig('dbversionnumber');
    $currentDBVersion=GetGlobalSetting('DBVersion');
	$dbprefix = Yii::app()->db->tablePrefix;
	$usertemplaterootdir = Yii::app()->getConfig('usertemplaterootdir');
	$standardtemplaterootdir = Yii::app()->getConfig('standardtemplaterootdir');
    if (intval($dbversionnumber)>intval($currentDBVersion))
    {
        if(isset($subaction) && $subaction=="continue")
        {
        	echo "<div style='width:90%; padding:1% 10%;background-color:#eee;'>";
            $upgradedbtype=Yii::app()->db->getDriverName();
            if ($upgradedbtype=='mysqli') $upgradedbtype='mysql';
            Yii::app()->loadHelper('update/upgrade-'.$upgradedbtype);
            Yii::app()->loadHelper('update/upgrade-all');
            db_upgrade_all(intval($currentDBVersion));
            db_upgrade(intval($currentDBVersion));
            Yii::app()->db->createCommand()->update($dbprefix.'settings_global', array('stg_value' => intval($dbversionnumber)), 'stg_name = \'DBVersion\'');
            echo "<br />".sprintf($clang->gT("Database has been successfully upgraded to version %s"),$dbversionnumber);
			echo "<br /><a href='".Yii::app()->getController()->createUrl("admin")."'>".$clang->gT("Back to main menu")."</a></div>";
        }
        else {
            ShowDBUpgradeNotice();
        }
    }
}

function ShowDBUpgradeNotice() {
    //$error=false;
	$clang = Yii::app()->lang;
	//$sitename = Yii::app()->getConfig('sitename');
	echo '<div class="messagebox">';
    echo "<div class='header'>".$clang->gT('Database upgrade').'</div><p>';
    $clang->eT('Please verify the following information before continuing with the database upgrade:').'<ul>';
    echo "<li><b>" .$clang->gT('Database type') . ":</b> " . Yii::app()->db->getDriverName() . "</li>";
    echo "<li><b>" .$clang->gT('Database name') . ":</b> " . getDBConnectionStringProperty('dbname') . "</li>";
    echo "<li><b>" .$clang->gT('Table prefix') . ":</b> " . Yii::app()->db->tablePrefix . "</li>";
    echo "<li><b>" .$clang->gT('Site name') . ":</b> " . Yii::app()->getConfig("sitename") . "</li>";
    echo "<li><b>" .$clang->gT('Root URL') . ":</b> " . Yii::app()->getController()->createUrl('') . "</li>";
    echo '</ul>';
    echo "<br />";
    echo "<a href='".Yii::app()->getController()->createUrl("admin/update/db/continue")."'>" . $clang->gT('Click here to continue') . "</a>";
    echo "<br />";
	echo '</div>';
}

function getDBConnectionStringProperty($szProperty)
{
    // Yii doesn't give us a good way to get the database name
    preg_match('/'.$szProperty.'=([^;]*)/', Yii::app()->db->getSchema()->getDbConnection()->connectionString, $aMatches);
    if ( count($aMatches) === 0 ) {
        return null;
    }
    return $aMatches[1];
}
