<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
   * LimeSurvey
   * Copyright (C) 2007 The LimeSurvey Project Team / Carsten Schmitz
   * All rights reserved.
   * License: GNU/GPL License v2 or later, see LICENSE.php
   * LimeSurvey is free software. This version may have been modified pursuant
   * to the GNU General Public License, and as distributed it includes or
   * is derivative of works licensed under the GNU General Public License or
   * other free or open source software licenses.
   * See COPYRIGHT.php for copyright notices and details.
   *
   *	$Id: common_helper.php 11335 2011-11-08 12:06:48Z c_schmitz $
   *	Files Purpose: lots of common functions
*/

class Groups extends CActiveRecord
{
	/**
	 * Returns the static model of Settings table
	 *
	 * @static
	 * @access public
	 * @return CActiveRecord
	 */
	public static function model()
	{
		return parent::model(__CLASS__);
	}

	/**
	 * Returns the setting's table name to be used by the model
	 *
	 * @access public
	 * @return string
	 */
	public function tableName()
	{
		return '{{groups}}';
	}

	/**
	 * Returns the primary key of this table
	 *
	 * @access public
	 * @return string
	 */
	public function primaryKey()
	{
		return 'gid';
	}

	function updateGroupOrder($sid,$lang,$position=0)
    {
		$data=Yii::app()->db->createCommand()->select('gid')->where(array('and','sid='.$sid,'language="'.$lang.'"'))->order('group_order, group_name ASC')->from('{{groups}}')->query();

        foreach($data->readAll() as $row)
        {
            Yii::app()->db->createCommand()->update($this->tableName(),array('group_order' => $position),'gid='.$row['gid']);
            $position++;
		}
    }

	function update($data, $condition=FALSE)
    {

        return Yii::app()->db->createCommand()->update('{{groups}}', $data, $condition);

    }

	public function insertRecords($data)
    {
        $group = new self;
		foreach ($data as $k => $v)
			$group->$k = $v;
		return $group->save();
    }

    function getGroups($surveyid) {
        $language = GetBaseLanguageFromSurveyID($surveyid);
		return Yii::app()->db->createCommand()
			->select(array('gid', 'group_name'))
			->from($this->tableName())
			->where(array('and', 'sid='.$surveyid, 'language=:language'))
			->order('group_order asc')
			->bindParam(":language", $language, PDO::PARAM_STR)
			->query()->readAll();
    }
}
?>
