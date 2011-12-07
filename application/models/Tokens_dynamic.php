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

class Tokens_dynamic extends CActiveRecord
{
	protected static $sid = 0;

	/**
	 * Sets the survey ID for the next model
	 *
	 * @static
	 * @access public
	 * @param int $sid
	 * @return void
	 */
	public static function sid($sid)
	{
		self::$sid = (int) $sid;
	}

	/**
	 * Returns the static model of Settings table
	 *
	 * @static
	 * @access public
	 * @param int $surveyid
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
		return '{{tokens_' . self::$sid . '}}';
	}

	/**
	 * Returns the primary key of this table
	 *
	 * @access public
	 * @return string
	 */
	public function primaryKey()
	{
		return 'id';
	}

	/**
	 * Returnvs a summary of this table
	 *
	 * @access public
	 * @return array
	 */
	public function summary()
	{
		$sid = self::$sid;

		$tksq = "SELECT count(tid) FROM {{tokens_$sid}}";
		$tksr = Yii::app()->db->createCommand($tksq)->query();
		$tkr = $tksr->read();
		$tkcount = $tkr["count(tid)"];
		$data['tkcount']=$tkcount;

		$tksq = "SELECT count(*) FROM {{tokens_$sid}} WHERE token IS NULL OR token=''";
		$tksr = Yii::app()->db->createCommand($tksq)->query();
		$tkr = $tksr->read();
		$data['query1'] = $tkr["count(*)"]." / $tkcount";

		$tksq = "SELECT count(*) FROM {{tokens_$sid}} WHERE (sent!='N' and sent<>'')";
		$tksr = Yii::app()->db->createCommand($tksq)->query();
		$tkr = $tksr->read();
		$data['query2'] = $tkr["count(*)"]." / $tkcount";

		$tksq = "SELECT count(*) FROM {{tokens_$sid}} WHERE emailstatus = 'optOut'";
		$tksr = Yii::app()->db->createCommand($tksq)->query();
		$tkr = $tksr->read();
		$data['query3'] = $tkr["count(*)"]." / $tkcount";

		$tksq = "SELECT count(*) FROM {{tokens_$sid}} WHERE (completed!='N' and completed<>'')";
		$tksr = Yii::app()->db->createCommand($tksq)->query();
		$tkr = $tksr->read();
		$data['query4'] = $tkr["count(*)"]." / $tkcount";

		return $data;
	}

	public function totalRecords($iSurveyID)
    {
        $tksq = "SELECT count(tid) FROM {{tokens_{$iSurveyID}}}";
        $tksr = Yii::app()->db->createCommand($tksq)->query();
        $tkr = $tksr->read();
        return $tkr["count(tid)"];
}

	public function ctquery($iSurveyID,$SQLemailstatuscondition,$tokenid=false,$tokenids=false)
    {
        $ctquery = "SELECT * FROM {{tokens_{$iSurveyID}}} WHERE ((completed ='N') or (completed='')) AND ((sent ='N') or (sent='')) AND token !='' AND email != '' $SQLemailstatuscondition";

        if ($tokenid) {$ctquery .= " AND tid='{$tokenid}'";}
        if ($tokenids) {$ctquery .= " AND tid IN ('".implode("', '", $tokenids)."')";}

        return Yii::app()->db->createCommand($ctquery)->query();
    }

    public function emquery($iSurveyID,$SQLemailstatuscondition,$maxemails,$tokenid=false,$tokenids=false)
    {
        $emquery = "SELECT * FROM {{tokens_{$iSurveyID}}} WHERE ((completed ='N') or (completed='')) AND ((sent ='N') or (sent='')) AND token !='' AND email != '' $SQLemailstatuscondition";

        if ($tokenid) {$emquery .= " and tid='{$tokenid}'";}
        if ($tokenids) {$emquery .= " AND tid IN ('".implode("', '", $tokenids)."')";}
        Yii::app()->loadHelper("database");
        return db_select_limit_assoc($emquery,$maxemails);
    }

    function insertToken($iSurveyID, $data)
    {
		self::sid($iSurveyID);
		return Yii::app()->db->createCommand()->insert(self::tableName(), $data);
    }
function updateToken($tid,$newtoken)
    {
        return Yii::app()->db->createCommand('UPDATE ' . $this->tableName() . ' SET token=\'' . $newtoken . '\' WHERE tid=' . $tid)->execute();
    }
    function selectEmptyTokens($iSurveyID)
    {
        return Yii::app()->db->createCommand("SELECT tid FROM ".$this->tableName()." WHERE token IS NULL OR token=''")->queryAll();
    }
    function createTokens($iSurveyID)
    {
        //get token length from survey settings
        $tlresult = Survey::model()->getSomeRecords("tokenlength",array("sid"=>$iSurveyID));
        $tlrow = $tlresult;
        // an alternative way to get tokenlength...  told to me by GautamGupta1:  :)
        //$tokenlength = Yii::app()->db->createCommand()->select('tokenlength')->from('{{surveys}}')->where('sid='.$surveyid)->query()->readColumn(0);
        $iTokenLength = $tlrow[0]['tokenlength'];
        

        //if tokenlength is not set or there are other problems use the default value (15)
        if(!isset($iTokenLength) || $iTokenLength == '')
        {
            $iTokenLength = 15;
        }
        $tablename = $this->tableName();
		$ntresult = Yii::app()->db->createCommand()->select('token')->from($tablename)->queryAll();
        // select all existing tokens
        //old code that i did with the code above :)
        //$ntresult = $this->getSomeRecords(array("token"),$iSurveyID,FALSE,"token");
        foreach ($ntresult as $tkrow)
        {
            $existingtokens[$tkrow['token']]=null;
        }
        $newtokencount = 0;
        $tkresult = $this->selectEmptyTokens($iSurveyID);
        foreach ($tkresult as $tkrow)
        {
            $bIsValidToken = false;
            while ($bIsValidToken == false)
            {
                $newtoken = sRandomChars($iTokenLength);
                if (!isset($existingtokens[$newtoken])) {
                    $bIsValidToken = true;
                    $existingtokens[$newtoken]=null;
                }
            }
            $itresult = $this->updateToken($tkrow['tid'],$newtoken);
            $newtokencount++;
        }
        return $newtokencount;

    }
    public function getSomeRecords($fields,$condition=FALSE)
    {
		$criteria = new CDbCriteria;

        if ($condition != FALSE)
        {	
		    foreach ($condition as $item => $value)
			{
				$criteria->addCondition($item.'="'.$value.'"');
			}
        }
		
		$data = $this->findAll($criteria);

        return $data;
    }
}
?>
