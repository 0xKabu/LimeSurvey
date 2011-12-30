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
* survey
*
* @package LimeSurvey
* @author  The LimeSurvey Project team
* @copyright 2011
* @version $Id: survey.php 11349 2011-11-09 21:49:00Z tpartner $
* @access public
*/
class SurveyAction extends Survey_Common_Action {

    private $template_data;

    /**
	* Base function
	*
	* This functions receives the form data from listsurveys and executes
	* according mass actions, like survey deletions, etc.
    * Only superadmins are allowed to do this!
	*
    * @access public
    * @return void
    */
   public function run($sa = '', $surveyid = 0)
   {
        if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] != 1)
        {
            die();
        }

        $this->template_data = array();

   		if (empty($sa)) $sa = null;

		if ($sa == 'view')
			$this->route('view', array('surveyid', 'gid', 'qid'));
		elseif ($sa == 'newsurvey' || isset($_GET['newsurvey']))
			$this->route('newsurvey', array());
		elseif ($sa == 'view' || isset($_GET['view']))
			$this->view($_GET['view']);
		elseif ($sa == 'insert' || isset($_GET['insert']))
			$this->route('insert', array());
		elseif ($sa == 'importsurveyresources')
			$this->route('importsurveyresources', array());
		elseif ($sa == 'copy' || isset($_GET['copy']))
			$this->route('copy', array());
        elseif ($sa == 'activate')
            $this->route('activate', array('surveyid'));
		elseif ($sa == 'listsurveys')
			$this->route('listsurveys', array());
		elseif ($sa == 'ajaxgetusers')
			$this->route('ajaxgetusers', array());
        elseif ($sa == 'getSurveys_json')
			$this->route('getSurveys_json', array());
        elseif ($sa == 'edit_json')
			$this->route('edit_json', array());
		elseif ($sa == 'ajaxowneredit')
			$this->route('ajaxowneredit', array('newowner', 'surveyid'));
		elseif ($sa == 'deactivate')
			$this->route('deactivate', array('surveyid'));
		elseif ($sa == 'confirmdelete' || $sa == 'delete')
			$this->route('delete', array('surveyid', 'sa'));
		elseif ($sa == 'editlocalsettings' || isset($_GET['editlocalsettings']))
			$this->route('editlocalsettings', array('surveyid'));
		elseif ($sa == 'editsurveysettings')
			$this->route('editsurveysettings', array('surveyid'));
		elseif ($sa == 'getUrlParamsJSON')
			$this->route('getUrlParamsJSON', array('surveyid'));
		elseif ($sa == 'expire')
			$this->route('expire', array('surveyid'));
   		return;
    }

    /**
    * This function prepares the view for a new survey
    *
    */
    function newsurvey()
    {
        if(!bHasGlobalPermission('USER_RIGHT_CREATE_SURVEY'))
        {
            die();
        }

        $this->_registerScriptFiles();

        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu();;
        Yii::app()->loadHelper('surveytranslator');
        $clang = Yii::app()->lang;

        $esrow = $this->_fetchSurveyInfo('newsurvey');
        $dateformatdetails=getDateFormatData(Yii::app()->session['dateformat']);
        Yii::app()->loadHelper('admin/htmleditor');

        $data = $this->_generalTabNewSurvey();
        $data['esrow'] = $esrow;
        $data = array_merge($data,$this->_tabPresentationNavigation($esrow));
        $data = array_merge($data,$this->_tabPublicationAccess($esrow));
        $data = array_merge($data,$this->_tabNotificationDataManagement($esrow));
        $data = array_merge($data,$this->_tabTokens($esrow));
		$arrayed_data['data']=$data;
        $this->getController()->render('/admin/survey/newSurvey_view',$arrayed_data);
        $this->getController()->_loadEndScripts();
        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));
    }

    /**
    * This function prepares the view for editing a survey
    *
    */
    function editsurveysettings($surveyid)
    {
        $surveyid = (int)$surveyid;
        if (is_null($surveyid) || !$surveyid)
        {
            die();
        }

        if(!bHasSurveyPermission($surveyid,'surveysettings','read') && !bHasGlobalPermission('USER_RIGHT_CREATE_SURVEY'))
        {
            die();
        }

        $this->_registerScriptFiles();

        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu($surveyid);;
        self::_surveybar($surveyid);

        //Yii::app()->loadHelper('text');
        Yii::app()->loadHelper('surveytranslator');
        $clang = $this->getController()->lang;

        $esrow = array();
        $editsurvey = '';
        $esrow = self::_fetchSurveyInfo('editsurvey',$surveyid);
		//$esrow = $esrow[0]->tableSchema->columns;
        $data['esrow']=$esrow;

        $data = array_merge($data, $this->_generalTabEditSurvey($surveyid,$esrow));
        $data = array_merge($data, $this->_tabPresentationNavigation($esrow));
        $data = array_merge($data, $this->_tabPublicationAccess($esrow));
        $data = array_merge($data, $this->_tabNotificationDataManagement($esrow));
        $data = array_merge($data, $this->_tabTokens($esrow));
        $data = array_merge($data, $this->_tabPanelIntegration($esrow));
        $data = array_merge($data, $this->_tabResourceManagement($surveyid));

        //echo $editsurvey;
        //$this->load->model('questions_model');
        $oResult = Questions::model()->getQuestionsWithSubQuestions($surveyid, $esrow['language'],"({{questions}}.type = 'T'  OR  {{questions}}.type = 'Q'  OR  {{questions}}.type = 'T' OR {{questions}}.type = 'S')");

        $data['questions'] = $oResult;
        $data['display'] = $editsurvey;
        $data['action'] = "editsurveysettings";
		$data['data'] = $data;

        $this->getController()->render('/admin/survey/editSurvey_view',$data);
        $this->getController()->_loadEndScripts();
        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));
    }


    /**
    * survey::importsurveyresources()
    * Function responsible to import survey resources.
    * @access public
    * @return void
    */
    function importsurveyresources()
    {
        $clang = $this->getController()->lang;
        $action = CHttpRequest::getPost('action');
        $surveyid = CHttpRequest::getPost('sid');

        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu($surveyid);
        self::_surveybar( $surveyid, NULL );
        self::_surveysummary($surveyid, $action);

        $this->template_data = array(
            'surveyid' => $surveyid,
            'clang' => $clang
        );

        if ($action == "importsurveyresources" && $surveyid)
        {
            $br = CHtml::tag('br', array(), NULL, FALSE);

            if ( Yii::app()->getConfig('demoMode') )
            {
                $error_content = $clang->gT("Demo Mode Only: Uploading file is disabled in this system.");
                $importsurveyresourcesoutput .= $this->_returnWarningHtml($error_content, $surveyid);

                echo($importsurveyresourcesoutput);
                return;
            }

            //require("classes/phpzip/phpzip.inc.php");
            Yii::app()->loadLibrary('admin/Phpzip');

            $zipfile = $_FILES['the_file']['tmp_name'];
            $z = new PHPZip();

            // Create temporary directory
            // If dangerous content is unzipped
            // then no one will know the path
            $extractdir = self::_tempdir(Yii::app()->getConfig('tempdir'));
            $basedestdir = Yii::app()->getConfig('publicdir')."/upload/surveys";
            $destdir = $basedestdir."/$surveyid/";

            if ( !is_writeable($basedestdir) )
            {
                $error_content = sprintf($clang->gT("Incorrect permissions in your %s folder."), $basedestdir);
                $importsurveyresourcesoutput .= $this->_returnWarningHtml($error_content, $surveyid);

                echo($importsurveyresourcesoutput);
                return;
            }

            if (!is_dir($destdir))
            {
                mkdir($destdir);
            }

            $aImportedFilesInfo=null;
            $aErrorFilesInfo=null;

            if (is_file($zipfile))
            {
                $importsurveyresourcesoutput .= CHtml::tag('div', array('class'=>'successheader'), $clang->gT("Success")) . $br;
                $importsurveyresourcesoutput .= $clang->gT("File upload succeeded.").str_repeat($br, 2);
                $importsurveyresourcesoutput .= $clang->gT("Reading file...").str_repeat($br, 2);

                if ($z->extract($extractdir,$zipfile) != 'OK')
                {
                    $error_content = $clang->gT("This file is not a valid ZIP file $CI. Import failed.");
                    $importsurveyresourcesoutput .= $this->_returnWarningHtml($error_content, $surveyid);
                    echo($importsurveyresourcesoutput);
                    return;
                }

                // now read tempdir and copy authorized files only
                $dh = opendir($extractdir);
                while($direntry = readdir($dh))
                {
                    if ( ($direntry!=".") && ($direntry!="..") )
                    {
                        if (is_file($extractdir."/".$direntry))
                        { // is  a file
                            $extfile = substr(strrchr($direntry, '.'),1);
                            if  (!(stripos(','.$allowedresourcesuploads.',',','.$extfile.',') === false))
                            { //Extension allowed
                                if (!copy($extractdir."/".$direntry, $destdir.$direntry))
                                {
                                    $aErrorFilesInfo[] = Array(
                                        "filename" => $direntry,
                                        "status" => $clang->gT("Copy failed")
                                    );
                                    unlink($extractdir."/".$direntry);

                                }
                                else
                                {
                                    $aImportedFilesInfo[]=Array(
                                        "filename" => $direntry,
                                        "status" => $clang->gT("OK")
                                    );
                                    unlink($extractdir."/".$direntry);
                                }
                            }

                            else
                            { // Extension forbidden
                                $aErrorFilesInfo[]=Array(
                                    "filename" => $direntry,
                                    "status" => $clang->gT("Error")." (".$clang->gT("Forbidden Extension").")"
                                );
                                unlink($extractdir."/".$direntry);
                            }
                        } // end if is_file
                    } // end if ! . or ..
                } // end while read dir


                //Delete the temporary file
                unlink($zipfile);
                //Delete temporary folder
                rmdir($extractdir);

                // display summary
                $okfiles = 0;
                $errfiles= 0;
                $ErrorListHeader = "";
                $ImportListHeader = "";
                if (is_null($aErrorFilesInfo) && !is_null($aImportedFilesInfo))
                {
                    $status = $clang->gT("Success");
                    $statusClass = 'successheader';
                    $okfiles = count($aImportedFilesInfo);
                    $ImportListHeader .= $this->_makeErrorMsg($clang->gT("Imported Files List"));
                }
                elseif (is_null($aErrorFilesInfo) && is_null($aImportedFilesInfo))
                {
                    $errors = array(
                        $clang->gT("This ZIP $CI contains no valid Resources files. Import failed."),
                        $clang->gT("Remember that we do not support subdirectories in ZIP $CIs.")
                    );

                    $this->_returnWarningHtml($errors, $surveyid);

                    echo($importsurveyresourcesoutput);
                    return;

                }
                elseif (!is_null($aErrorFilesInfo) && !is_null($aImportedFilesInfo))
                {
                    $status = $clang->gT("Partial");
                    $statusClass = 'partialheader';
                    $okfiles = count($aImportedFilesInfo);
                    $errfiles = count($aErrorFilesInfo);
                    $ErrorListHeader .= $this->_makeErrorMsg($clang->gT("Error Files List"));
                    $ImportListHeader .= $this->_makeErrorMsg($clang->gT("Imported Files List"));
                }
                else
                {
                    $status = $clang->gT("Error");
                    $statusClass = 'warningheader';
                    $errfiles = count($aErrorFilesInfo);
                    $ErrorListHeader .= $this->_makeErrorMsg($clang->gT("Imported Files List"));
                }

                $this->template_data['statusClass'] = $statusClass;
                $this->template_data['status'] = $status;
                $this->template_data['okfiles'] = $okfiles;
                $this->template_data['errfiles'] = $errfiles;
                $this->template_data['ImportListHeader'] = $ImportListHeader;

                foreach ($aImportedFilesInfo as $entry)
                {
                    $importsurveyresourcesoutput .= CHtml::tag('li', array(), $clang->gT("File").': '.$entry["filename"]);
                }
                if (!is_null($aImportedFilesInfo))
                {
                    $importsurveyresourcesoutput .= CHtml::closeTag('ul').$br;
                }
                $importsurveyresourcesoutput .= $ErrorListHeader;

                foreach ($aErrorFilesInfo as $entry)
                {
                    $li_content = $clang->gT("File").': '.$entry['filename'].' ('.$entry['status'].')';
                    $importsurveyresourcesoutput .= CHtml::tag('li', array(), $li_content);
                }

                if (!is_null($aErrorFilesInfo))
                {
                    $importsurveyresourcesoutput .= CHtml::closeTag('ul').$br;
                }
            }
            else
            {
                $error_content = sprintf ($clang->gT("An error occurred uploading your file. This may be caused by incorrect permissions in your %s folder."), $basedestdir);
                $importsurveyresourcesoutput .= $this->_returnWarningHtml($error_content, $surveyid);

                show_error($importsurveyresourcesoutput);
                return;
            }

            $this->template_data['additional_content'] = $importsurveyresourcesoutput;

            $this->getController()->render('/admin/survey/importSurveyResources_view', $this->template_data);
        }

        $this->getController()->_loadEndScripts();


        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));

    }

    //---------------------
    // Comes from http://fr2.php.net/tempnam
    function _tempdir($dir, $prefix='', $mode=0700)
    {
        if (substr($dir, -1) != PATH_SEPARATOR) $dir .= PATH_SEPARATOR;

        do
        {
            $path = $dir.$prefix.mt_rand(0, 9999999);
        } while (!mkdir($path, $mode));

        return $path;
    }

    function showsyntaxerrors($surveyid)
    {
        $surveyid = (int)$surveyid;

        if (is_null($surveyid) || !$surveyid)
        {
            die();
        }

        if(!bHasSurveyPermission($surveyid, 'surveysettings', 'read') && !bHasGlobalPermission('USER_RIGHT_CREATE_SURVEY'))
        {
            die();
        }

        $this->_registerScriptFiles();

        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu($surveyid);;
        $this->getController()->_surveybar($surveyid);

        $data['errors'] = LimeExpressionManager::GetSyntaxErrors();

        Yii::app()->render('admin/survey/showSyntaxErrors_view',$data);
        $this->getController()->_loadEndScripts();
        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));
    }

    function resetsyntaxerrorlog($surveyid)
    {
        $surveyid=(int)$surveyid;
        if (is_null($surveyid) || !$surveyid)
        {
            die();
        }

        if(!bHasSurveyPermission($surveyid,'surveysettings','read') && !bHasGlobalPermission('USER_RIGHT_CREATE_SURVEY'))
        {
            die();
        }

        $this->_registerScriptFiles();

        /*$this->getController()->_js_admin_includes(Yii::app()->getConfig('generalscripts').'admin/surveysettings.js');
        $this->getController()->_js_admin_includes(Yii::app()->getConfig('generalscripts').'jquery/jqGrid/js/i18n/grid.locale-en.js');
        $this->getController()->_js_admin_includes(Yii::app()->getConfig('generalscripts').'jquery/jqGrid/js/jquery.jqGrid.min.js');
        $this->getController()->_js_admin_includes(Yii::app()->getConfig('generalscripts').'jquery/jquery.json.min.js');
        $this->getController()->_css_admin_includes(Yii::app()->getConfig('styleurl')."admin/default/superfish.css");
        $this->getController()->_css_admin_includes(Yii::app()->getConfig('generalscripts')."jquery/jqGrid/css/ui.jqgrid.css");*/
        self::_getAdminHeader();
        self::_showadminmenu($surveyid);
        self::_surveybar($surveyid);

        LimeExpressionManager::ResetSyntaxErrorLog();

        $this->load->view('admin/survey/resetSyntaxErrorLog_view');
        self::_loadEndScripts();
        self::_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));
    }

    /**
    * surveyaction::view()
    * Load complete view of survey properties and actions specified by $surveyid
    * @param mixed $surveyid
    * @param mixed $gid
    * @param mixed $qid
    * @return
    */
    function view($surveyid,$gid=null,$qid=null)
    {
        $surveyid = sanitize_int($surveyid);
        if(isset($gid)) $gid = sanitize_int($gid);
        if(isset($qid)) $qid = sanitize_int($qid);

        // show till question menubar.
        if (!is_null($qid))
        {
            $css_admin_includes[] = Yii::app()->getConfig('styleurl')."admin/default/superfish.css";
            Yii::app()->setConfig("css_admin_includes", $css_admin_includes);

            $this->getController()->_getAdminHeader();
            $this->getController()->_showadminmenu($surveyid);

            self::_surveybar($surveyid, $gid);
            self::_surveysummary($surveyid, "viewquestion");
            self::_questiongroupbar($surveyid, $gid, $qid, "viewquestion");
            self::_questionbar($surveyid, $gid, $qid,"viewquestion");

            $this->getController()->_loadEndScripts();
            $this->getController()->_getAdminFooter("http://docs.limesurvey.org", Yii::app()->lang->gT("LimeSurvey online manual"));

        }
        else
        {
            //show till questiongroup menu bar.
            if (!is_null($gid))
            {
                $css_admin_includes[] = Yii::app()->getConfig('styleurl')."admin/default/superfish.css";
                Yii::app()->setConfig("css_admin_includes", $css_admin_includes);

                $this->getController()->_getAdminHeader();
                $this->getController()->_showadminmenu($surveyid);

                self::_surveybar($surveyid, $gid);
                self::_surveysummary($surveyid, "viewgroup");
                self::_questiongroupbar($surveyid, $gid, $qid, "viewgroup");

                $this->getController()->_loadEndScripts();
                $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));

            }
            else
            {
                //show till survey menu bar.
                if(bHasSurveyPermission($surveyid,'survey','read'))
                {
                    $css_admin_includes[] = Yii::app()->getConfig('styleurl')."admin/default/superfish.css";
                    Yii::app()->setConfig("css_admin_includes", $css_admin_includes);

                    $this->getController()->_getAdminHeader();
                    $this->getController()->_showadminmenu($surveyid);

                    self::_surveybar($surveyid);
                    self::_surveysummary($surveyid);

                    $this->getController()->_loadEndScripts();
                    $this->getController()->_getAdminFooter("http://docs.limesurvey.org", Yii::app()->lang->gT("LimeSurvey online manual"));
                }
            }
        }

    }

    /**
    * survey::deactivate()
    * Function responsible to deactivate a survey.
    * @param mixed $surveyid
    * @return
    */
    function deactivate($surveyid = null)
    {
        $surveyid = sanitize_int($surveyid);
        $css_admin_includes[] = Yii::app()->getConfig('styleurl')."admin/default/superfish.css";
        Yii::app()->setConfig("css_admin_includes", $css_admin_includes);

        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu($surveyid);

        //$postsid=returnglobal('sid');
        if (!empty($_POST['sid']))
        {
            $postsid = CHttpRequest::getPost('sid');
        }
        else
        {
            $postsid = $surveyid;
        }
        $clang = $this->getController()->lang;
        $date = date('YmdHis'); //'Hi' adds 24hours+minutes to name to allow multiple deactiviations in a day

        if (!isset($_POST['ok']) || !$_POST['ok'])
        {
            $data['clang'] = $clang;
            $data['surveyid'] = $surveyid;
            $data['date'] = $date;
            $data['dbprefix'] = Yii::app()->db->tablePrefix;
            $data['step1'] = true;

            $this->_surveybar($surveyid);
            $this->getController()->render('/admin/survey/deactivateSurvey_view',$data);
        }
        else
        {
            //See if there is a tokens table for this survey
            if (Yii::app()->db->schema->getTable("{{tokens_{$postsid}}}"))
            {
                if (Yii::app()->db->getDriverName() == 'postgre')
                {
	                $deactivateresult = Yii::app()->db->createCommand()->renameTable($toldtable.'_tid_seq',$tnewtable.'_tid_seq');
	                $setsequence = "ALTER TABLE {{{$tnewtable}}} ALTER COLUMN tid SET DEFAULT nextval('{{{$tnewtable}}}_tid_seq'::regclass);";
	                $deactivateresult = Yii::app()->db->createCommand($setsequence)->query();
	                $setidx = "ALTER INDEX {{{$toldtable}}}_idx RENAME TO {{{$tnewtable}}}_idx;";
	                $deactivateresult = Yii::app()->db->createCommand($setidx)->query();
                }

                $toldtable="{{tokens_{$postsid}}}";
                $tnewtable="{{old_tokens_{$postsid}_{$date}}}";

                $tdeactivateresult = Yii::app()->db->createCommand()->renameTable($toldtable, $tnewtable);

                $data['tnewtable'] = $tnewtable;
                $data['toldtable'] = $toldtable;
            }

            // IF there are any records in the saved_control table related to this survey, they have to be deleted
            $result = Saved_control::model()->deleteSomeRecords( array('sid'=>$postsid) ); //Yii::app()->db->createCommand($query)->query();
            $oldtable = "{{survey_{$postsid}}}";
            $newtable = "{{old_survey_{$postsid}_{$date}}}";

            //Update the auto_increment value from the table before renaming
            $new_autonumber_start=0;
            $query = "SELECT id FROM $oldtable ORDER BY id desc LIMIT 1";
            $result = Yii::app()->db->createCommand($query)->query();
            if ($result->getRowCount() > 0)
            {
                foreach ($result->readAll() as $row)
                {
                    if (strlen($row['id']) > 12) //Handle very large autonumbers (like those using IP prefixes)
                    {
                        $part1 = substr($row['id'], 0, 12);
                        $part2len = strlen($row['id'])-12;
                        $part2 = sprintf("%0{$part2len}d", substr($row['id'], 12, strlen($row['id'])-12)+1);
                        $new_autonumber_start = "{$part1}{$part2}";
                    }
                    else
                    {
                        $new_autonumber_start=$row['id']+1;
                    }
                }
            }

            $condn = array('sid' => $surveyid);
            $insertdata = array('autonumber_start' => $new_autonumber_start);

        	$survey = Survey::model()->findByAttributes($condn);
        	$survey->autonumber_start = $new_autonumber_start;
        	$survey->save();
            if (Yii::app()->db->getDrivername()=='postgre')
            {
	            $deactivateresult = Yii::app()->db->createCommand()->renameTable($oldtable.'_id_seq',$newtable.'_id_seq');
                $setsequence = "ALTER TABLE $newtable ALTER COLUMN id SET DEFAULT nextval('{$newtable}_id_seq'::regclass);";
	            $deactivateresult = Yii::app()->db->createCommand($setsequence)->execute();
            }

            $deactivateresult = Yii::app()->db->createCommand()->renameTable($oldtable, $newtable);

            $insertdata = array('active' => 'N');
        	$survey->active = 'N';
        	$survey->save();

            //$pquery = "SELECT savetimings FROM {{surveys}} WHERE sid={$postsid}";
            $prow = Survey::model()->getSomeRecords('savetimings', array('sid'=>$postsid), TRUE);
            if ($prow['savetimings'] == "Y")
            {
                $oldtable = "{{survey_{$postsid}_timings}}";
                $newtable = "{{old_survey_{$postsid}_timings_{$date}}}";

                $deactivateresult2 = Yii::app()->db->createCommand()->renameTable($oldtable, $newtable);
                $deactivateresult = ($deactivateresult && $deactivateresult2);
            }

            $data['clang'] = $clang;
            $data['surveyid'] = $surveyid;
            $data['newtable'] = $newtable;

            $this->_surveybar($surveyid);
            $this->getController()->render('/admin/survey/deactivateSurvey_view',$data);

        }

        $this->getController()->_loadEndScripts();

        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));
    }

    /**
    * survey::activate()
    * Function responsible to activate survey.
    * @param mixed $surveyid
    * @return
    */
    function activate($iSurveyID)
    {
        $iSurveyID = (int)$iSurveyID;

        $css_admin_includes[] = Yii::app()->getConfig('styleurl')."admin/default/superfish.css";
        Yii::app()->getConfig("css_admin_includes", $css_admin_includes);

        $this->template_data['aSurveysettings'] = getSurveyInfo($iSurveyID);

        // Die if this is not possible
        if (!isset($this->template_data['aSurveysettings']['active']) || $this->template_data['aSurveysettings']['active']=='Y') die();

        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu($iSurveyID);
        //$this->getController()->_surveybar($iSurveyID);

        $qtypes = getqtypelist('', 'array');
        Yii::app()->loadHelper("admin/activate");

        if (!isset($_POST['ok']) || !$_POST['ok'])
        {
            if (isset($_GET['fixnumbering']) && $_GET['fixnumbering'])
            {
                fixNumbering($_GET['fixnumbering']);
            }

            // Check consistency for groups and questions
            $failedgroupcheck = checkGroup($iSurveyID);
            $failedcheck = checkQuestions($iSurveyID, $iSurveyID, $qtypes);

            $this->template_data['clang'] = $this->getController()->lang;
            $this->template_data['surveyid'] =  $iSurveyID;
            $this->template_data['$failedcheck'] = $failedcheck;
            $this->template_data['failedgroupcheck'] = $failedgroupcheck;
            $this->template_data['aSurveysettings'] = getSurveyInfo($iSurveyID);

            $this->getController()->render("/admin/survey/activateSurvey_view", $this->template_data);
            //IF ANY OF THE CHECKS FAILED, PRESENT THIS SCREEN
        }
        else
        {
            $survey = Survey::model()->findByAttributes( array('sid' => $iSurveyID) );
            if(!is_null($survey))
            {
                $survey->anonymized = CHttpRequest::getPost('anonymized');
                $survey->datestamp = CHttpRequest::getPost('datestamp');
                $survey->ipaddr = CHttpRequest::getPost('ipaddr');
                $survey->refurl = CHttpRequest::getPost('refurl');
                $survey->savetimings = CHttpRequest::getPost('savetimings');
                $survey->save();
            }

            $activateoutput = activateSurvey($iSurveyID);
            $displaydata['display'] = $activateoutput;

            $this->getController()->render('/survey_view',$displaydata);
        }

         $this->getController()->_loadEndScripts();
         $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));
    }


    /**
    * survey::ajaxgetusers()
    * This get the userlist in surveylist screen.
    * @return void
    */
    function ajaxgetusers()
    {
        header('Content-type: application/json');

        //$query = "SELECT users_name, uid FROM {{users}}";
        //$result =Yii::app()->db->createCommand($query)->query();

        $result = User::model()->getSomeRecords(array('users_name', 'uid'));

        $aUsers = array();
        if(count($result) > 0) {
            foreach($result as $rows)
                $aUsers[] = array($rows['uid'], $rows['users_name']);
        }

        $ajaxoutput = json_encode($aUsers) . "\n";
        echo $ajaxoutput;
    }

    /**
    * survey::ajaxowneredit()
    * This function change the owner of a survey.
    * @param mixed $newowner
    * @param mixed $surveyid
    * @return void
    */
    function ajaxowneredit($newowner, $surveyid)
    {
        header('Content-type: application/json');

        $intNewOwner = sanitize_int($newowner);
        $intSurveyId = sanitize_int($surveyid);
        $owner_id = Yii::app()->session['loginID'];

        $query_condition = array('and', "sid='$intSurveyId'");
        if ( !bHasGlobalPermission("USER_RIGHT_SUPERADMIN") )
            $update_condition[] = "owner_id='$owner_id'"; //$query .=" AND owner_id=$owner_id";

        $result = Survey::model()->updateSurvey(array('owner_id'=>$intNewOwner), $query_conditions);

        $query = "SELECT b.users_name FROM {{surveys}} as a"
        ." INNER JOIN  {{users}} as b ON a.owner_id = b.uid WHERE sid=$intSurveyId AND owner_id=$intNewOwner;";
        $result = Yii::app()->db->createCommand($query)->query();

        $intRecordCount = $result->readAll();

        $aUsers = array(
            'record_count' => $intRecordCount,
        );

        if($result->getRowCount() > 0) {
            foreach($result->readAll() as $rows)
                $aUsers['newowner'] = $rows['users_name'];
        }
        $ajaxoutput = json_encode($aUsers) . "\n";

        echo $ajaxoutput;
    }


	/**
	 * survey::listsurveys()
	 * Function that load list of surveys and it's few quick properties.
	 * @return
	 */
	function listsurveys()
	{
        $clang = $this->getController()->lang;
        $data['clang'] = $clang;
        $this->getController()->_js_admin_includes( Yii::app()->getConfig('generalscripts')."jquery/jqGrid/js/i18n/grid.locale-en.js");
        $this->getController()->_js_admin_includes( Yii::app()->getConfig('generalscripts')."jquery/jqGrid/js/jquery.jqGrid.min.js");
        $this->getController()->_js_admin_includes( Yii::app()->getConfig('generalscripts')."jquery/jquery.coookie.js");
        $this->getController()->_js_admin_includes(Yii::app()->baseUrl."/scripts/admin/listsurvey.js");
        $css_admin_includes[] = Yii::app()->getConfig('generalscripts')."jquery/css/jquery.multiselect.css";
        $css_admin_includes[] = Yii::app()->getConfig('generalscripts')."jquery/css/jquery.multiselect.filter.css";
        $css_admin_includes[] = Yii::app()->getConfig('styleurl')."admin/default/displayParticipants.css";
        $css_admin_includes[] = Yii::app()->getConfig('generalscripts')."jquery/jqGrid/css/ui.jqgrid.css";
        $css_admin_includes[] = Yii::app()->getConfig('generalscripts')."jquery/jqGrid/css/jquery.ui.datepicker.css";
        Yii::app()->setConfig("css_admin_includes", $css_admin_includes);
		Yii::app()->loadHelper('surveytranslator');

		$this->getController()->_getAdminHeader();
		$this->getController()->_showadminmenu(false);
        
        if (Yii::app()->session['USER_RIGHT_SUPERADMIN'] != 1)
		{
			$data['issuperadmin'] = false;
		}
		else
		{
			$data['issuperadmin'] = true;
		}

		$this->getController()->render('/admin/survey/listSurveys_view',$data);
		$this->getController()->_loadEndScripts();
		$this->getController()->_getAdminFooter("http://docs.limesurvey.org", $clang->gT("LimeSurvey online manual"));
    }
    
    /**
     * survey::getSurveys_json()
     * @return
     */
    function getSurveys_json()
    {
		$this->getController()->loadHelper('surveytranslator');
        $clang = $this->getController()->lang;
        $dateformatdetails = getDateFormatData(Yii::app()->session['dateformat']);
        
        $page = CHttpRequest::getPost('page');
        $limit = CHttpRequest::getPost('rows');
        
        $surveys = Survey::loadSurveys( Yii::app()->session['USER_RIGHT_SUPERADMIN'])->readAll();
        $aSurveyEntries->page = $page;
        $aSurveyEntries->records =count($surveys);
        $aSurveyEntries->total =ceil($aSurveyEntries->records / $limit );
        for($i=0,$j=($page-1)*$limit; $i<$limit && $j<$aSurveyEntries->records ; $i++, $j++)
        {
            $aSurveyEntry=array();
            $rows=$surveys[$j];
            // Set status
            if ($rows['active']=="Y" && $rows['expires']!='' && $rows['expires'] < date_shift(date("Y-m-d H:i:s"), "Y-m-d", Yii::app()->getConfig('timeadjust')))
            {
                $aSurveyEntry[] = '<!--a--><img src="'.Yii::app()->getConfig('imageurl').'/expired.png" alt="'.$clang->gT("This survey is active but expired.").'" />';
            }
            elseif ($rows['active']=="Y" && $rows['startdate']!='' && $rows['startdate'] > date_shift(date("Y-m-d H:i:s"), "Y-m-d", Yii::app()->getConfig('timeadjust')))
            {
                $aSurveyEntry[] = '<!--b--><img src="'.Yii::app()->getConfig('imageurl').'"/notyetstarted.png" alt="'.$clang->gT("This survey is active but has a start date.").'" />';
            }
            elseif ($rows['active']=="Y")
            {
                if (bHasSurveyPermission($rows['sid'],'surveyactivation','update'))
                {
                    $aSurveyEntry[] = '<!--c--><a href="'.$this->getController()->createUrl('admin/survey/deactivate/'.$rows['sid']).'"><img src="'.Yii::app()->getConfig('imageurl').'/active.png" alt="'.$clang->gT("This survey is active - click here to stop this survey.").'"/></a>';
                } else {
                    $aSurveyEntry[] = '<!--d--><img src="'.Yii::app()->getConfig('imageurl').'/active.png" alt="'.$clang->gT("This survey is currently active.").'" />';
                }
            }
            else
            {
				$condition = "sid={$rows['sid']} AND language='".$rows['language']."'";
				$questionsCountResult = Questions::model()->getSomeRecords('qid', $condition);

                if ( count($questionsCountResult) && bHasSurveyPermission($rows['sid'],'surveyactivation','update') )
                {
                    $aSurveyEntry[] = '<!--e--><a href="'.$this->getController()->createUrl('admin/survey/sa/activate/surveyid/'.$rows['sid']).'"><img src="'.Yii::app()->getConfig('imageurl').'/inactive.png" title="" alt="'.$clang->gT("This survey is currently not active - click here to activate this survey.").'" /></a>';
                } else {
                    $aSurveyEntry[] = '<!--f--><img src="'.Yii::app()->getConfig('imageurl').'/inactive.png" title="'.$clang->gT("This survey is currently not active.").'" alt="'.$clang->gT("This survey is currently not active.").'" />';                   
				}
            }
            
            //Set SID
            $aSurveyEntry[] = $rows['sid'];
            '<a href="'.$this->getController()->createUrl("/admin/survey/sa/view/surveyid/".$rows['sid']).'">'.$rows['sid'].'</a>';
            
            //Set Title
            $aSurveyEntry[] = '<!--'.$rows['surveyls_title'].'--><a href="'.$this->getController()->createUrl("/admin/survey/sa/view/surveyid/".$rows['sid']).'">'.$rows['surveyls_title'].'</a>';
            
            //Set Date
            Yii::import('application.libraries.Date_Time_Converter', true);
            $datetimeobj = new Date_Time_Converter(array($rows['datecreated'], "Y-m-d H:i:s"));
            $aSurveyEntry[] = '<!--'.$rows['datecreated'].'-->'.$datetimeobj->convert($dateformatdetails['phpdate']);

            //Set Owner
            $aSurveyEntry[] = $rows['users_name'].' (<a href="#" class="ownername_edit" translate_to="'.$clang->gT('Edit').'" id="ownername_edit_'.$rows['sid'].'">Edit</a>)';
            
            //Set Access
            if (Yii::app()->db->schema->getTable('{{tokens_'.$rows['sid'] . '}}'))
            {
                $aSurveyEntry[] = $clang->gT("Closed");
            }
            else
            {
                $aSurveyEntry[] = $clang->gT("Open");
            }
            
            //Set Anonymous
            if($rows['anonymized'] == "Y")
            {
                $aSurveyEntry[]=$clang->gT("Yes") ;
            }
            else
            {
                $aSurveyEntry[]=$clang->gT("No") ;
            }
            
            //Set Responses
            if($rows['active'] == "Y")
            {
                $gnquery = "SELECT COUNT(id) AS countofid FROM {{survey_".$rows['sid']."}} WHERE submitdate IS NULL";
                $partial = Yii::app()->db->createCommand($gnquery)->query()->read();
                $gnquery = "SELECT COUNT(id) AS countofid FROM {{survey_".$rows['sid'] . "}}";
                $all = Yii::app()->db->createCommand($gnquery)->query()->read();

            
                $aSurveyEntry[]=$all['countofid']-$partial['countofid'];
                $aSurveyEntry[]=$partial['countofid'];
                $aSurveyEntry[]=$all['countofid'];

                
				$aSurveyEntry['viewurl'] = $this->getController()->createUrl("/admin/survey/sa/view/surveyid/".$rows['sid']);
                if (Yii::app()->db->schema->getTable("{{tokens_".$rows['sid'] . "}}"))
				{
                    $tokencountquery = "SELECT COUNT(tid) AS countoftid FROM {{tokens_".$rows['sid'] . "}}";
                    $tokens = Yii::app()->db->createCommand($tokencountquery)->query()->read();
                    $tokencompletedquery = "SELECT COUNT(tid) AS countoftid FROM {{tokens_".$rows['sid']."}} WHERE completed!='N'";
                    $tokenscompleted = Yii::app()->db->createCommand($tokencompletedquery)->query()->read();
                    
                    $aSurveyEntry[]=$tokens['countoftid'];
                    $aSurveyEntry[]=($tokens['countoftid']==0)?0:round($tokenscompleted['countoftid']/$tokens['countoftid']*100,1);
                } else {
                    $aSurveyEntry[]=$aSurveyEntry[]='';
                }
            } else {
                $aSurveyEntry[]=$aSurveyEntry[]=$aSurveyEntry[]=$aSurveyEntry[]=$aSurveyEntry[]='';
            }
            $aSurveyEntries->rows[$i]['id']=$rows['sid'];
            $aSurveyEntries->rows[$i]['cell']=$aSurveyEntry;
        }
        
        echo ls_json_encode($aSurveyEntries);
	}

    /**
    * survey::delete()
    * Function responsible to delete a survey.
    * @return
    */
    function delete($surveyid, $sa = 'confirmdelete')
    {
        $css_admin_includes[] = Yii::app()->getConfig('styleurl')."admin/default/superfish.css";
        Yii::app()->setConfig("css_admin_includes", $css_admin_includes);
        $this->getController()->_getAdminHeader();

        $this->template_data['surveyid'] = $iSurveyId = $surveyid;

        if ($sa == 'confirmdelete')
        {
            $this->getController()->_showadminmenu($iSurveyId);
            self::_surveybar($iSurveyId);
        }

        if(bHasSurveyPermission($iSurveyId, 'survey', 'delete'))
        {
            $this->template_data['clang'] = $this->getController()->lang;

            if ($sa == 'delete')
            {
				$this->template_data['issuperadmin'] = Yii::app()->session['USER_RIGHT_SUPERADMIN'] == true;
                self::_deleteSurvey($iSurveyId);
                $this->getController()->_showadminmenu(false);
            }
            elseif ($sa == 'confirmdelete')
            {
				$this->getController()->render('/admin/survey/deleteSurvey_view', $this->template_data);
			}
        }
        else
        {
            $finaldata['display'] = access_denied("editsurvey", $iSurveyId);
            $this->getController()->render('/survey_view', $finaldata);
        }

        $this->getController()->_loadEndScripts();
        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", Yii::app()->lang->gT("LimeSurvey online manual"));
    }

    /**
    * survey::editlocalesettings()
    * Load editing of local settings of a survey screen.
    * @param mixed $surveyid
    * @return
    */
    function editlocalsettings($surveyid)
    {
        $surveyid = sanitize_int($surveyid);

        $clang = $this->getController()->lang;

        $css_admin_includes[] = Yii::app()->getConfig('styleurl')."/admin/default/superfish.css";
        Yii::app()->setConfig("css_admin_includes", $css_admin_includes);

        $this->getController()->_js_admin_includes($this->getController()->createUrl('scripts/admin/surveysettings.js'));
        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu($surveyid);;
        self::_surveybar($surveyid);

        if(bHasSurveyPermission($surveyid,'surveylocale','read'))
        {
        	$editsurvey = '';
            $grplangs = GetAdditionalLanguagesFromSurveyID($surveyid);
            $baselang = GetBaseLanguageFromSurveyID($surveyid);
            array_unshift($grplangs,$baselang);

            Yii::app()->loadHelper("admin/htmleditor");

			PrepareEditorScript(true, $this->getController());

            $i = 0;
            foreach ($grplangs as $grouplang)
            {
                // this one is created to get the right default texts fo each language
                Yii::app()->loadHelper('database');
                Yii::app()->loadHelper('surveytranslator');
                $bplang = $this->getController()->lang; //new lang($grouplang);

                $esquery = "SELECT * FROM ".Yii::app()->db->tablePrefix."surveys_languagesettings WHERE surveyls_survey_id=$surveyid and surveyls_language='$grouplang'";
                $esresult = db_execute_assoc($esquery); //Checked
                $esrow = $esresult->read();

                $tab_title[$i] = getLanguageNameFromCode($esrow['surveyls_language'],false);

                if ($esrow['surveyls_language'] == GetBaseLanguageFromSurveyID($surveyid))
                    $tab_title[$i]  .= '('.$clang->gT("Base Language").')';

                $esrow = array_map('htmlspecialchars', $esrow);
                $this->template_data['clang'] = $clang;
                $this->template_data['esrow'] = $esrow;
                $this->template_data['surveyid'] = $surveyid;
                $this->template_data['action'] = "editsurveylocalesettings";

                $tab_content[$i] = $this->getController()->render('/admin/survey/editLocalSettings_view', $this->template_data, true);

                $i++;
            }

            $editsurvey .= CHtml::openTag('ul');
            foreach($tab_title as $i=>$eachtitle) {
                $a_link = CHtml::link($eachtitle, "#edittxtele$i");
                $editsurvey .= CHtml::tag('li', array('style'=>'clear:none;'), $a_link);
            }
            $editsurvey .= CHtml::closeTag('ul');

            foreach ($tab_content as $i=>$eachcontent){
                $editsurvey .= CHtml::tag('div', array('id'=>'edittxtele'.$i), $eachcontent);
            }
            $editsurvey .= CHtml::closeTag('div');

            $this->template_data['has_permissions'] = bHasSurveyPermission($surveyid, 'surveylocale', 'update');
            $this->template_data['surveyls_language'] = $esrow["surveyls_language"];
            $this->template_data['additional_content'] = $editsurvey;

            $this->getController()->render('/admin/survey/editLocalSettings_main_view', $this->template_data);

        }
        else
        {
            //include("access_denied.php");
            $finaldata['display'] = access_denied("editsurvey",$surveyid);
            $this->getController()->render('/survey_view',$finaldata);
        }

        $this->getController()->_loadEndScripts();
        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));
    }

    /**
    * survey::copy()
    * Function responsible to import/copy a survey based on $action.
    * @return
    */
    function copy()
    {
        $importsurvey = "";
        $action = $_POST['action'];
        @$surveyid = $_POST['sid'];

        if ($action == "importsurvey" || $action == "copysurvey")
        {
            if ( @$_POST['copysurveytranslinksfields'] == "on"  || @$_POST['translinksfields'] == "on")
            {
                $sTransLinks = true;
            }
            $clang = $this->getController()->lang;

            // Start the HTML
            if ($action == 'importsurvey')
            {
                $aData['sHeader']=$clang->gT("Import survey data");
                $aData['sSummaryHeader']=$clang->gT("Survey structure import summary");
                $importingfrom = "http";
            }
            elseif($action == 'copysurvey')
            {
                $aData['sHeader']=$clang->gT("Copy survey");
                $aData['sSummaryHeader']=$clang->gT("Survey copy summary");
            }
            // Start traitment and messagebox
            $aData['bFailed']=false; // Put a var for continue

            if ($action == 'importsurvey')
            {

                $the_full_file_path = Yii::app()->getConfig('tempdir') . DIRECTORY_SEPARATOR . $_FILES['the_file']['name'];
                if (!@move_uploaded_file($_FILES['the_file']['tmp_name'], $the_full_file_path))
                {
                    $aData['sErrorMessage'] = sprintf ($clang->gT("An error occurred uploading your file. This may be caused by incorrect permissions in your %s folder."), Yii::app()->getConfig('tempdir'));
                    $aData['bFailed']=true;
                }
                else
                {
                    $sFullFilepath=$the_full_file_path;
                    $aPathInfo = pathinfo($sFullFilepath);
                    if (isset($aPathInfo['extension']))
                    {
                        $sExtension = $aPathInfo['extension'];
                    }
                    else
                    {
                        $sExtension = "";
                    }
                }

                if (!$aData['bFailed'] && (strtolower($sExtension)!='csv' && strtolower($sExtension)!='lss' && strtolower($sExtension)!='zip'))
                {
                    $aData['sErrorMessage'] = $clang->gT("Import failed. You specified an invalid file type.");
                    $aData['bFailed']=true;
                }
            }
            elseif ($action == 'copysurvey')
            {
                $surveyid = sanitize_int($_POST['copysurveylist']);
                $exclude = array();

                if (get_magic_quotes_gpc()) {$sNewSurveyName = stripslashes($_POST['copysurveyname']);}
                else{
                    $sNewSurveyName = CHttpRequest::getPost('copysurveyname');
                }

                if (CHttpRequest::getPost('copysurveyexcludequotas') == "on")
                {
                    $exclude['quotas'] = true;
                }
                if (CHttpRequest::getPost('copysurveyexcludeanswers') == "on")
                {
                    $exclude['answers'] = true;
                }
                if (CHttpRequest::getPost('copysurveyresetconditions') == "on")
                {
                    $exclude['conditions'] = true;
                }

                if (!$surveyid)
                {
                    $aData['sErrorMessage'] = $clang->gT("No survey ID has been provided. Cannot copy survey");
                    $aData['bFailed']=true;
                }

                Yii::app()->loadHelper('export');
                $copysurveydata = survey_getXMLData($surveyid,$exclude);
            }

            // Now, we have the survey : start importing
            Yii::app()->loadHelper('admin/import');

            if ($action == 'importsurvey' && !$aData['bFailed'])
            {

                if (isset($sExtension) && strtolower($sExtension) == 'csv')
                {
                    $aImportResults = CSVImportSurvey($sFullFilepath, null, (isset($_POST['translinksfields'])));
                }
                elseif (isset($sExtension) && strtolower($sExtension) == 'lss')
                {
                    $aImportResults = XMLImportSurvey($sFullFilepath, null, null, null,(isset($_POST['translinksfields'])));
                }
                elseif (isset($sExtension) && strtolower($sExtension) == 'zip')  // Import a survey archive
                {
                    $this->load->library("admin/pclzip/pclzip", array('p_zipname' => $sFullFilepath));
                    $aFiles = $this->pclzip->listContent();

                    if ($this->pclzip->extract(PCLZIP_OPT_PATH, Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR, PCLZIP_OPT_BY_EREG, '/(lss|lsr|lsi|lst)$/')== 0) {
                        unset($this->pclzip);
                    }
                    // Step 1 - import the LSS file and activate the survey
                    foreach ($aFiles as $aFile)
                    {
                        if (pathinfo($aFile['filename'], PATHINFO_EXTENSION)=='lss')
                        {
                            //Import the LSS file
                            $aImportResults = XMLImportSurvey(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename'], null, null, null, true);
                            // Activate the survey
                            Yii::app()->loadHelper("admin/activate");
                            $activateoutput = activateSurvey($aImportResults['newsid']);
                            unlink(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename']);
                            break;
                        }
                    }
                    // Step 2 - import the responses file
                    foreach ($aFiles as $aFile)
                    {
                        if (pathinfo($aFile['filename'], PATHINFO_EXTENSION)=='lsr')
                        {
                            //Import the LSS file
                            $aResponseImportResults=XMLImportResponses(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename'],$aImportResults['newsid'],$aImportResults['FieldReMap']);
                            $aImportResults=array_merge($aResponseImportResults,$aImportResults);
                            unlink(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename']);
                            break;
                        }
                    }
                    // Step 3 - import the tokens file - if exists
                    foreach ($aFiles as $aFile)
                    {
                        if (pathinfo($aFile['filename'], PATHINFO_EXTENSION)=='lst')
                        {
                            Yii::app()->loadHelper("admin/token");
                            if (createTokenTable($aImportResults['newsid'])) $aTokenCreateResults = array('tokentablecreated'=>true);
                            $aImportResults=array_merge($aTokenCreateResults,$aImportResults);
                            $aTokenImportResults = XMLImportTokens(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename'],$aImportResults['newsid']);
                            $aImportResults=array_merge($aTokenImportResults,$aImportResults);
                            unlink(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename']);
                            break;
                        }
                    }
                    // Step 4 - import the timings file - if exists
                    foreach ($aFiles as $aFile)
                    {
                        if (pathinfo($aFile['filename'], PATHINFO_EXTENSION)=='lsi' && tableExists("survey_{$aImportResults['newsid']}_timings"))
                        {
                            $aTimingsImportResults = XMLImportTimings(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename'],$aImportResults['newsid'],$aImportResults['FieldReMap']);
                            $aImportResults=array_merge($aTimingsImportResults,$aImportResults);
                            unlink(Yii::app()->getConfig('tempdir').DIRECTORY_SEPARATOR.$aFile['filename']);
                            break;
                        }
                    }
                }
                else
                {
                    $importerror = true;
                }
            }
            elseif ($action == 'copysurvey' && empty($importerror) || !$importerror)
            {
                $aImportResults=XMLImportSurvey('',$copysurveydata,$sNewSurveyName);
            }
            else
            {
                $importerror=true;
            }
            if ($action == 'importsurvey')
            {
                unlink($sFullFilepath);
            }

            $aData['action'] = $action;
            $aData['sLink'] = $this->getController()->createUrl('admin/survey/view/'.$aImportResults['newsid']);
            $aData['aImportResults'] = $aImportResults;
			$aData['clang'] = $this->getController()->lang;
        }

        $this->getController()->_getAdminHeader();
        $this->getController()->_showadminmenu();;
        $this->getController()->render('/admin/survey/importSurvey_view',$aData);
        $this->getController()->_getAdminFooter("http://docs.limesurvey.org", $this->getController()->lang->gT("LimeSurvey online manual"));

    }

    /**
    * survey::_fetchSurveyInfo()
    * Load survey information based on $action.
    * @param mixed $action
    * @param mixed $surveyid
    * @return
    */
    function _fetchSurveyInfo($action,$surveyid=null)
    {
        if(isset($surveyid)) $surveyid = sanitize_int($surveyid);

        if ($action == 'newsurvey')
        {
            $esrow['active']                   = 'N';
            $esrow['allowjumps']               = 'N';
            $esrow['format']                   = 'G'; //Group-by-group mode
            $esrow['template']                 = Yii::app()->getConfig('defaulttemplate');
            $esrow['allowsave']                = 'Y';
            $esrow['allowprev']                = 'N';
            $esrow['nokeyboard']               = 'N';
            $esrow['printanswers']             = 'N';
            $esrow['publicstatistics']         = 'N';
            $esrow['publicgraphs']             = 'N';
            $esrow['public']                   = 'Y';
            $esrow['autoredirect']             = 'N';
            $esrow['tokenlength']              = 15;
            $esrow['allowregister']            = 'N';
            $esrow['usecookie']                = 'N';
            $esrow['usecaptcha']               = 'D';
            $esrow['htmlemail']                = 'Y';
            $esrow['sendconfirmation']         = 'Y';
            $esrow['emailnotificationto']      = '';
            $esrow['anonymized']               = 'N';
            $esrow['datestamp']                = 'N';
            $esrow['ipaddr']                   = 'N';
            $esrow['refurl']                   = 'N';
            $esrow['tokenanswerspersistence']  = 'N';
            $esrow['alloweditaftercompletion'] = 'N';
            $esrow['assesments']               = 'N';
            $esrow['startdate']                = '';
            $esrow['savetimings']              = 'N';
            $esrow['expires']                  = '';
            $esrow['showqnumcode']             = 'X';
            $esrow['showwelcome']              = 'Y';
            $esrow['emailresponseto']          = '';
            $esrow['assessments']              = 'N';
            $esrow['navigationdelay']          = 0;
        }
        elseif ($action == 'editsurvey')
        {
            $condition = array('sid' => $surveyid);
            $esresult = Survey::model()->getOneRecord( array('sid'=>$surveyid) );
            if ($esresult) {
                $esrow = $esresult;
            }

        }

        return $esrow;
    }

    /**
    * survey::_generalTabNewSurvey()
    * Load "General" tab of new survey screen.
    * @return
    */
    function _generalTabNewSurvey()
    {
        global $siteadminname,$siteadminemail;
        $clang = $this->getController()->lang;

        $condition = array('users_name' => Yii::app()->session['user']);
        $fieldstoselect = array('full_name', 'email');

        //Use the current user details for the default administrator name and email for this survey
        $owner = User::model()->getSomeRecords($fieldstoselect, $condition);
        //Degrade gracefully to $siteadmin details if anything is missing.

        if (empty($owner['full_name']))
            $owner['full_name'] = $siteadminname;
        if (empty($owner['email']))
            $owner['email'] = $siteadminemail;

        //Bounce setting by default to global if it set globally
        Yii::app()->loadHelper('globalsettings');

        if (getGlobalSetting('bounceaccounttype')!='off'){
            $owner['bounce_email'] = getGlobalSetting('siteadminbounce');
        } else {
            $owner['bounce_email'] = $owner['email'];
        }

        $data['action'] = "newsurvey";
        $data['clang'] = $clang;
        $data['owner'] = $owner;

        return $data;
    }

    /**
    * survey::_generalTabEditSurvey()
    * Load "General" tab of edit survey screen.
    * @param mixed $surveyid
    * @param mixed $esrow
    * @return
    */
    function _generalTabEditSurvey($surveyid,$esrow)
    {
        global $siteadminname,$siteadminemail;
        $clang = $this->getController()->lang;
        $data['action'] = "editsurveysettings";
        $data['clang'] = $clang;
        $data['esrow'] = $esrow;
        $data['surveyid'] = $surveyid;
        return $data;
    }

    /**
    * survey::_tabPresentationNavigation()
    * Load "Presentation & navigation" tab.
    * @param mixed $esrow
    * @return
    */
    function _tabPresentationNavigation($esrow)
    {
        $clang = $this->getController()->lang;
        global $showXquestions,$showgroupinfo,$showqnumcode;

        Yii::app()->loadHelper('globalsettings');

        $shownoanswer = getGlobalSetting('shownoanswer')?getGlobalSetting('shownoanswer'):'Y';

        $data['clang'] = $clang;
        $data['esrow'] = $esrow;
        $data['shownoanswer'] = $shownoanswer;
        $data['showXquestions'] = $showXquestions;
        $data['showgroupinfo'] = $showgroupinfo;
        $data['showqnumcode'] = $showqnumcode;
        return $data;

    }

    /**
    * survey::_tabPublicationAccess()
    * Load "Publication * access control" tab.
    * @param mixed $esrow
    * @return
    */
    function _tabPublicationAccess($esrow)
    {
        $clang = $this->getController()->lang;
        $dateformatdetails=getDateFormatData(Yii::app()->session['dateformat']);
        $startdate='';
        if ($esrow['startdate']) {
            $items = array($esrow["startdate"] ,"Y-m-d H:i:s"); // $dateformatdetails['phpdate']
            Yii::app()->loadLibrary('Date_Time_Converter');
            $datetimeobj = new date_time_converter($items) ; //new Date_Time_Converter($esrow['startdate'] , "Y-m-d H:i:s");
            $startdate=$datetimeobj->convert("d.m.Y H:i");//$datetimeobj->convert($dateformatdetails['phpdate'].' H:i');
        }

        $expires='';
        if ($esrow['expires']) {
            $items = array($esrow['expires'] ,"Y-m-d H:i:s");

            Yii::app()->loadLibrary('Date_Time_Converter');
            $datetimeobj = new date_time_converter($items) ; //new Date_Time_Converter($esrow['expires'] , "Y-m-d H:i:s");
            $expires=$datetimeobj->convert("d.m.Y H:i");
        }
        $data['clang'] = $clang;
        $data['esrow'] = $esrow;
        $data['startdate'] = $startdate;
        $data['expires'] = $expires;
        return $data;
    }

    /**
    * survey::_tabNotificationDataManagement()
    * Load "Notification & data management" tab.
    * @param mixed $esrow
    * @return
    */
    function _tabNotificationDataManagement($esrow)
    {
        $clang = $this->getController()->lang;

        $data['clang'] = $clang;
        $data['esrow'] = $esrow;

        return $data;

    }

    /**
    * survey::_tabTokens()
    * Load "Tokens" tab.
    * @param mixed $esrow
    * @return
    */
    function _tabTokens($esrow)
    {
        $clang = $this->getController()->lang;

        $data['clang'] = $clang;
        $data['esrow'] = $esrow;

        return $data;
    }

    function _tabPanelIntegration($esrow)
    {
        $data=array();
        return $data;
    }

    /**
    * survey::_tabImport()
    * Load "Import" tab.
    * @param mixed $surveyid
    * @return
    */
    function _tabImport()
    {
        $data=array();
        return $data;
    }

    /**
    * survey::_tabCopy()
    * Load "Copy" tab.
    * @param mixed $surveyid
    * @return
    */
    function _tabCopy()
    {
        $data=array();
        return $data;
    }

    /**
    * survey::_tabResourceManagement()
    * Load "Resources" tab.
    * @param mixed $surveyid
    * @return
    */
    function _tabResourceManagement($surveyid)
    {
        $clang = $this->getController()->lang;

        global $sCKEditorURL;

        // TAB Uploaded Resources Management
        $ZIPimportAction = " onclick='if (validatefilename(this.form,\"".$clang->gT('Please select a file to import!','js')."\")) { this.form.submit();}'";
        if (!function_exists("zip_open")) {
            $ZIPimportAction = " onclick='alert(\"".$clang->gT("zip library not supported by PHP, Import ZIP Disabled","js")."\");'";
        }

        $disabledIfNoResources = '';
        if (hasResources($surveyid, 'survey') === false) {
            $disabledIfNoResources = " disabled='disabled'";
        }
        $data['clang'] = $clang;
        //$data['esrow'] = $esrow;
        $data['ZIPimportAction'] = $ZIPimportAction;
        $data['disabledIfNoResources'] = $disabledIfNoResources;
        $dqata['sCKEditorURL'] = $sCKEditorURL;

        return $data;

    }

    function expire($iSurveyID)
    {
        $iSurveyID = (int)$iSurveyID;
        if(!bHasSurveyPermission($iSurveyID,'surveysettings','update'))
        {
            die();
        }
        $clang = $this->getController()->lang;
        Yii::app()->session['flashmessage'] = $clang->gT("The survey was successfully expired by setting an expiration date in the survey settings.");
        $this->_expireSurvey($iSurveyID);
        $dExpirationdate=date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", Yii::app()->getConfig('timeadjust'));
        $dExpirationdate=date_shift($dExpirationdate, "Y-m-d H:i:s", '-1 day');
        Survey::model()->updateSurvey(array('expires'=>$dExpirationdate),
        'sid= \''.$iSurveyID.'\'');
        $this->getController()->redirect($this->getController()->createUrl('admin/survey/sa/view/surveyid/'.$iSurveyID));

    }

    /**
    * Expires a survey
    *
    * @param mixed $iSurveyID The survey ID
    * @return False if not successful
    */
    function _expireSurvey($iSurveyID)
    {
        $dExpirationdate=date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", Yii::app()->getConfig('timeadjust'));
        $dExpirationdate=date_shift($dExpirationdate, "Y-m-d H:i:s", '-1 day');
        return Survey::model()->updateSurvey(array('expires'=>$dExpirationdate), 'sid=\''.$iSurveyID.'\'');
    }



    function getUrlParamsJSON($iSurveyID)
    {
        $iSurveyID = (int)$iSurveyID;
		Yii::app()->loadHelper('database');
		$oResult = db_execute_assoc("select '' as act, up.*,q.title, sq.title as sqtitle, q.question, sq.question as sqquestion from {Yii::app()->db->tablePrefix}survey_url_parameters up
                            left join {Yii::app()->db->tablePrefix}questions q on q.qid=up.targetqid
                            left join {Yii::app()->db->tablePrefix}questions sq on q.qid=up.targetqid
                            where up.sid={$iSurveyID}");
        $i=0;

        foreach ($oResult->readAll() as $oRow)
        {
            $data->rows[$i]['id']=$oRow['id'];
            $oRow['title']= $oRow['title'].': '.ellipsize(FlattenText($oRow['question'],false,true),43,.70);

            if ($oRow['sqquestion']!='')
            {
                echo (' - '.ellipsize(FlattenText($oRow['sqquestion'],false,true),30,.75));
            }
            unset($oRow['sqquestion']);
            unset($oRow['sqtitle']);
            unset($oRow['question']);

            $data->rows[$i]['cell']=array_values($oRow);
            $i++;
        }

        $data->page = 1;
        $data->records = $oResult->getRowCount();
        $data->total = 1;

        echo ls_json_encode($data);
    }

    /**
    * This private function deletes a survey
    * Important: If you change this function also change the remotecontrol XMLRPC function
    *
    * @param mixed $iSurveyID  The survey ID to delete
    */
    function _deleteSurvey($iSurveyID)
    {
		Survey::model()->deleteByPk($iSurveyID);
        rmdirr(Yii::app()->getConfig("uploaddir").'/surveys/'.$iSurveyID);
    }

    private function _makeErrorMsg( $message, $use_brs = TRUE )
    {
        $br = CHtml::tag('br', array(), '', FALSE);
        $u_tag = CHtml::tag('u', array(), $message.':');
        $strong_tag = CHtml::tag('strong', array(), $tags['u']);

        if( $use_br )
            return $br . $strong_tag . $br;
        else
            return $strong_tag;
    }

    /**
    * Generates error message html and returns it
    *
    * @param mixed $content
    * @param string $back_url
    * @return html
    */
    private function _returnWarningHtml($content, $surveyid)
    {
        $clang = Yii::app()->lang;

        // Generate <br> tag using yii's CHtml helper
        $br = CHtml::tag('br', array(), '', FALSE);

        $error_msg_html = CHtml::tag('div', array('class'=>'header ui-widget-header'), $clang->gT("Import survey resources"));
        $error_msg_html = CHtml::tag('div', array('class'=>'messagebox ui-corner-all'), '', FALSE);

        // Generate the warning html using the CHtml helper again
        $error_msg_html .= CHtml::tag('div', array('class'=>'warningheader'), $clang->gT("Error")).$br;
        $error_msg_html .= $this->prepErrorMsgs($content);
        $error_msg_html .= CHtml::submitButton($clang->gT("Back"),  array(
            'onclick' => "window.open('".$this->getController()->createUrl('admin/survey/editsurveysettings/'.$surveyid). "', '_top')"
        ));
        $error_msg_html .= CHtml::closeTag('div');

        return $error_msg_html;
    }

    /**
    * Prepares error messages string and returns it
    * ( used by _returnWarningBlock function )
    *
    * @param mixed $content
    * @return string
    */
    private function _prepErrorMsgs( $content )
    {
        $br_tags = str_repeat(CHtml::tag('br', array(), '', FALSE), 2);

        if( is_array($content) )
        {
            $html_content = '';

            foreach($content as $msg)
            {
                $html_content .= $msg . $br_tags;
            }

            return $html_content;
        }
        else
            return $content;
    }

    /**
    * Executes registerScriptFile for all needed script/style files
    *
    * @param array $files
    * @return void
    */
    private function _registerScriptFiles( $files = array() )
    {
        if( empty($files) )
        {
            $generalscripts_path = Yii::app()->getConfig('generalscripts');
            $styleurl = Yii::app()->getConfig('styleurl');

            $files = array(
                $generalscripts_path.'admin/surveysettings.js',
                $generalscripts_path.'jquery/jqGrid/js/i18n/grid.locale-en.js',
                $generalscripts_path.'jquery/jqGrid/js/jquery.jqGrid.min.js',
                $generalscripts_path.'jquery/jquery.json.min.js',
                $generalscripts_path.'jquery/jqGrid/css/ui.jqgrid.css',
                $styleurl.'admin/default/superfish.css',
            );
        }

        foreach($files as $file)
        {
            Yii::app()->clientscript->registerScriptFile($file);
        }
    }

    /**
    * Saves the new survey after the creation screen is submitted
    *
    * @param $iSurveyID  The survey id to be used for the new survey. If already taken a new random one will be used.
    */
    function insert($iSurveyID=null)
    {
        if (Yii::app()->session['USER_RIGHT_CREATE_SURVEY'])
        {
            // Check if survey title was set
            if (!$_POST['surveyls_title'])
            {
                Yii::app()->session['flashmessage']=$clang->gT("Survey could not be created because it did not have a title");
                redirect($this->getController()->createUrl('admin'));
                return;
            }

            // Check if template may be used
            $sTemplate = $_POST['template'];
            if(!$sTemplate || (Yii::app()->session['USER_RIGHT_SUPERADMIN'] != 1 && Yii::app()->session['USER_RIGHT_MANAGE_TEMPLATE'] != 1 && !hasTemplateManageRights(Yii::app()->session['loginID'], $_POST['template'])))
            {
                $sTemplate = "default";
            }

            Yii::app()->loadHelper("surveytranslator");


            // If start date supplied convert it to the right format
            $aDateFormatData=getDateFormatData($_SESSION['dateformat']);
            $sStartDate = $_POST['startdate'];
            if (trim($sStartDate)!='')
            {
                $this->load->library('Date_Time_Converter',array($sStartDate , $aDateFormatData['phpdate'].' H:i:s'));
                $sStartDate=$this->date_time_converter->convert("Y-m-d H:i:s");
            }

            // If expiry date supplied convert it to the right format
            $sExpiryDate = $_POST['expires'];
            if (trim($sExpiryDate)!='')
            {
                $this->load->library('Date_Time_Converter',array($sExpiryDate , $aDateFormatData['phpdate'].' H:i:s'));
                $sExpiryDate=$this->date_time_converter->convert("Y-m-d H:i:s");
            }

            // Insert base settings into surveys table
            $aInsertData=array(
                'expires'                   =>  $sExpiryDate,
                'startdate'                 =>  $sStartDate,
                'template'                  =>  $sTemplate,
                'owner_id'                  =>  Yii::app()->session['loginID'],
                'admin'                     =>  $_POST['admin'],
                'active'                    =>  'N',
                'adminemail'                =>  $_POST['adminemail'],
                'bounce_email'              =>  $_POST['bounce_email'],
                'anonymized'                =>  $_POST['anonymized'],
                'faxto'                     =>  $_POST['faxto'],
                'format'                    =>  $_POST['format'],
                'savetimings'               =>  $_POST['savetimings'],
                'language'                  =>  $_POST['language'],
                'datestamp'                 =>  $_POST['datestamp'],
                'ipaddr'                    =>  $_POST['ipaddr'],
                'refurl'                    =>  $_POST['refurl'],
                'usecookie'                 =>  $_POST['usecookie'],
                'emailnotificationto'       =>  $_POST['emailnotificationto'],
                'allowregister'             =>  $_POST['allowregister'],
                'allowsave'                 =>  $_POST['allowsave'],
                'navigationdelay'           =>  $_POST['navigationdelay'],
                'autoredirect'              =>  $_POST['autoredirect'],
                'showXquestions'            =>  $_POST['showXquestions'],
                'showgroupinfo'             =>  $_POST['showgroupinfo'],
                'showqnumcode'              =>  $_POST['showqnumcode'],
                'shownoanswer'              =>  $_POST['shownoanswer'],
                'showwelcome'               =>  $_POST['showwelcome'],
                'allowprev'                 =>  $_POST['allowprev'],
                'allowjumps'                =>  $_POST['allowjumps'],
                'nokeyboard'                =>  $_POST['nokeyboard'],
                'showprogress'              =>  $_POST['showprogress'],
                'printanswers'              =>  $_POST['printanswers'],
                'listpublic'                =>  $_POST['public'],
                'htmlemail'                 =>  $_POST['htmlemail'],
                'sendconfirmation'          =>  $_POST['sendconfirmation'],
                'tokenanswerspersistence'   =>  $_POST['tokenanswerspersistence'],
                'alloweditaftercompletion'  =>  $_POST['alloweditaftercompletion'],
                'usecaptcha'                =>  $_POST['usecaptcha'],
                'publicstatistics'          =>  $_POST['publicstatistics'],
                'publicgraphs'              =>  $_POST['publicgraphs'],
                'assessments'               =>  $_POST['assessments'],
                'emailresponseto'           =>  $_POST['emailresponseto'],
                'tokenlength'               =>  $_POST['tokenlength']
            );

            if (!is_null($iSurveyID))
            {
                $aInsertData['wishSID']=$iSurveyID;
            }

            $iNewSurveyid=Survey::model()->insertNewSurvey($aInsertData);
            if (!$iNewSurveyid) die('Survey could not be created.');

            // Prepare locale data for surveys_language_settings table
            $sTitle = $_POST['surveyls_title'];
            $sDescription = $_POST['description'];
            $sWelcome = $_POST['welcome'];
            $sURLDescription = $_POST['urldescrip'];
            if (Yii::app()->getConfig('filterxsshtml'))
            {
				//$p = new CHtmlPurifier();
				//$p->options = array('URI.AllowedSchemes'=>array('http' => true,  'https' => true));
                //$sTitle=$p->purify($sTitle);
                //$sDescription=$p->purify($sDescription);
                //$sWelcome=$p->purify($sWelcome);
                //$sURLDescription=$p->purify($sURLDescription);
            }
            $sTitle = html_entity_decode($sTitle, ENT_QUOTES, "UTF-8");
            $sDescription = html_entity_decode($sDescription, ENT_QUOTES, "UTF-8");
            $sWelcome = html_entity_decode($sWelcome, ENT_QUOTES, "UTF-8");
            $sURLDescription = html_entity_decode($sURLDescription, ENT_QUOTES, "UTF-8");

            // Fix bug with FCKEditor saving strange BR types
            $sTitle = fix_FCKeditor_text($sTitle);
            $sDescription = fix_FCKeditor_text($sDescription);
            $sWelcome = fix_FCKeditor_text($sWelcome);

            // Load default email templates for the chosen language
            $oLanguage = new Limesurvey_lang(array($_POST['language']));
            $aDefaultTexts=aTemplateDefaultTexts($oLanguage,'unescaped');
            unset($oLanguage);

            if ($_POST['htmlemail'] && $_POST['htmlemail'] == "Y")
            {
                $bIsHTMLEmail = true;
                $aDefaultTexts['admin_detailed_notification']=$aDefaultTexts['admin_detailed_notification_css'].conditional_nl2br($aDefaultTexts['admin_detailed_notification'],$bIsHTMLEmail,'unescaped');
            }
            else
            {
                $bIsHTMLEmail = false;
            }

            // Insert base language into surveys_language_settings table

            $aInsertData=array( 'surveyls_survey_id'=>$iNewSurveyid,
                'surveyls_title'                =>  $sTitle,
                'surveyls_description'          =>  $sDescription,
                'surveyls_welcometext'          =>  $sWelcome,
                'surveyls_language'             =>  $_POST['language'],
                'surveyls_urldescription'       =>  $_POST['urldescrip'],
                'surveyls_endtext'              =>  $_POST['endtext'],
                'surveyls_url'                  =>  $_POST['url'],
                'surveyls_email_invite_subj'    =>  $aDefaultTexts['invitation_subject'],
                'surveyls_email_invite'         =>  conditional_nl2br($aDefaultTexts['invitation'],$bIsHTMLEmail,'unescaped'),
                'surveyls_email_remind_subj'    =>  $aDefaultTexts['reminder_subject'],
                'surveyls_email_remind'         =>  conditional_nl2br($aDefaultTexts['reminder'],$bIsHTMLEmail,'unescaped'),
                'surveyls_email_confirm_subj'   =>  $aDefaultTexts['confirmation_subject'],
                'surveyls_email_confirm'        =>  conditional_nl2br($aDefaultTexts['confirmation'],$bIsHTMLEmail,'unescaped'),
                'surveyls_email_register_subj'  =>  $aDefaultTexts['registration_subject'],
                'surveyls_email_register'       =>  conditional_nl2br($aDefaultTexts['registration'],$bIsHTMLEmail,'unescaped'),
                'email_admin_notification_subj' =>  $aDefaultTexts['admin_notification_subject'],
                'email_admin_notification'      =>  conditional_nl2br($aDefaultTexts['admin_notification'],$bIsHTMLEmail,'unescaped'),
                'email_admin_responses_subj'    =>  $aDefaultTexts['admin_detailed_notification_subject'],
                'email_admin_responses'         =>  $aDefaultTexts['admin_detailed_notification'],
                'surveyls_dateformat'           =>  (int) $_POST['dateformat'],
                'surveyls_numberformat'         =>  (int) $_POST['numberformat']
            );

			$langsettings = new Surveys_languagesettings;
            $langsettings->insertNewSurvey($aInsertData);

            Yii::app()->session['flashmessage']=$this->getController()->lang->gT("Survey was successfully added.");

            // Update survey permissions
            Survey_permissions::model()->giveAllSurveyPermissions(Yii::app()->session['loginID'],$iNewSurveyid);

            $this->getController()->redirect($this->getController()->createUrl('admin/survey/sa/view/surveyid/'.$iNewSurveyid));
        }

    }
}
