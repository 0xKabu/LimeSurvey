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

class Answers extends CActiveRecord
{
	/**
	 * Returns the static model of Settings table
	 *
	 * @static
	 * @access public
     * @param string $class
	 * @return CActiveRecord
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	/**
	 * Returns the setting's table name to be used by the model
	 *
	 * @access public
	 * @return string
	 */
	public function tableName()
	{
		return '{{answers}}';
	}

	/**
	 * Returns the primary key of this table
	 *
	 * @access public
	 * @return array
	 */
	public function primaryKey()
	{
		return array('qid', 'code');
	}

    function getAnswers($qid)
    {
		return Yii::app()->db->createCommand()
			->select()
			->from(self::tableName())
			->where(array('and', 'qid='.$qid))
			->order('code asc')
			->query();
    }

    function getAnswerCode($qid, $code, $lang)
    {
		return Yii::app()->db->createCommand()
			->select(array('code', 'answer'))
			->from(self::tableName())
			->where(array('and', 'qid='.$qid, 'code="'.$code.'"', 'scale_id=0', 'language="'.$lang.'"'))
			->query();
    }

	public function oldNewInsertansTags($newsid,$oldsid)
	{
		$sql = "SELECT a.qid, a.language, a.code, a.answer from {{answers}} as a INNER JOIN {{questions}} as b ON a.qid=b.qid WHERE b.sid=".$newsid." AND a.answer LIKE '%{INSERTANS:".$oldsid."X%'";
    	return Yii::app()->db->createCommand($sql)->query();
	}

	public function updateRecord($data, $condition=FALSE)
    {
        return Yii::app()->db->createCommand()->update(self::tableName(), $data, $condition ? $condition : '');
    }

	function insertRecords($data)
    {
        $ans = new self;
		foreach ($data as $k => $v)
			$ans->$k = $v;
		return $ans->save();
    }

    /**
     * Updates sort order of answers inside a question
     *
     * @static
     * @access public
     * @param int $qid
     * @param string $lang
     * @return void
     */
    public static function updateSortOrder($qid, $lang)
    {
        $data = self::model()->findAllByAttributes(array('qid' => $qid, 'language' => $lang), array('order' => 'sortorder asc'));
        $position = 0;

        foreach ($data as $row)
        {
            $row->sortorder = $position++;
            $row->save();
        }
    }

    public function getAnswerQuery($surveyid, $lang, $return_query = TRUE)
    {
		$query = Yii::app()->db->createCommand();
		$query->select("{{answers}}.*, {{questions}}.gid");
		$query->from("{{answers}}, {{questions}}");
		$query->where("{{questions}}.sid = '{$surveyid}' AND {{questions}}.qid = {{answers}}.qid AND {{questions}}.language = {{answers}}.language AND {{questions}}.language = '{$lang}'");
		$query->order('qid, code, sortorder');
		return ( $return_query ) ? $query->queryAll() : $query;
    }

    function getAllRecords($condition, $order=FALSE)
    {
        $command=Yii::app()->db->createCommand()->select('*')->from($this->tableName())->where($condition);
        if ($order != FALSE)
        {
            $command->order($order);
        }
        return $command->query();
    }

    public function getQuestionsForStatistics($fields, $condition, $orderby)
    {
        return Yii::app()->db->createCommand()
        ->select($fields)
        ->from(self::tableName())
        ->where($condition)
        ->order($orderby)
        ->queryAll();
    }
}
?>
