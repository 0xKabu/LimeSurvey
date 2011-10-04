<?php
/*
 * LimeSurvey
 * Copyright (C) 2011 The LimeSurvey Project Team / Carsten Schmitz
 * All rights reserved.
 * License: GNU/GPL License v2 or later, see LICENSE.php
 * LimeSurvey is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 *
 *	$Id$
 */
class Admin_Controller extends LSCI_Controller {
	function __construct()
	{
		parent::__construct();
		self::_init();

	}

	/**
	 * Load Controller
	 */
	function _init()
	{

		//if ($sourcefrom == "admin")
		//{
		//    require_once($homedir.'/admin_functions.php');
		//}
        $updatelastcheck = '';

		//Admin menus and standards
		//IF THIS IS AN ADMIN SCRIPT, RUN THE SESSIONCONTROL SCRIPT
	    //include($homedir."/sessioncontrol.php");
	    self::_sessioncontrol();

		//SET LANGUAGE DIRECTORY
		// Check if the DB is up to date

		If (tableExists('surveys'))
		{
		    $usrow = getGlobalSetting('DBVersion');
		    if (intval($usrow)<$this->config->item('dbversionnumber') && $this->router->class != "update" && $this->router->class != "authentication") {
				redirect('/admin/update/db', 'refresh');
		    }
		}

	    $langdir=$this->config->item("publicurl")."/locale/".$this->session->userdata('adminlang')."/help";
	    $langdirlocal=$this->config->item("rootdir")."/locale/".$this->session->userdata('adminlang')."/help";

	    if (!is_dir($langdirlocal))  // is_dir only works on local dirs
	    {
	        $langdir=$this->config->item("publicurl")."/locale/en/help"; //default to english if there is no matching language dir
	    }


		if ($this->config->item('buildnumber') != "" && $this->config->item('updatecheckperiod')>0 && $updatelastcheck<date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", "-".$this->config->item('updatecheckperiod')." days"))
		{

            updatecheck();
		}

		// Reset FileManagerContext
		$this->session->unset_userdata("FileManagerContext");

		if (!$this->config->item("surveyid")) {$this->config->set_item("surveyid", returnglobal('sid'));}         //SurveyID
		if (!$this->config->item("ugid")) {$this->config->set_item("ugid", returnglobal('ugid'));}                //Usergroup-ID
		if (!$this->config->item("gid")) {$this->config->set_item("gid", returnglobal('gid'));}                   //GroupID
		if (!$this->config->item("qid")) {$this->config->set_item("qid", returnglobal('qid'));}                   //QuestionID
		if (!$this->config->item("lid")) {$this->config->set_item("lid", returnglobal('lid'));}                   //LabelID
		if (!$this->config->item("code")) {$this->config->set_item("code", returnglobal('code'));}                // ??
		if (!$this->config->item("action")) {$this->config->set_item("action", returnglobal('action'));}          //Desired action
		if (!$this->config->item("subaction")) {$this->config->set_item("subaction", returnglobal('subaction'));} //Desired subaction
		if (!$this->config->item("editedaction")) {$this->config->set_item("editedaction", returnglobal('editedaction'));} // for html editor integration

		/*
		if (!isset($surveyid)) {$surveyid=returnglobal('sid');}         //SurveyID
		if (!isset($ugid)) {$ugid=returnglobal('ugid');}                //Usergroup-ID
		if (!isset($gid)) {$gid=returnglobal('gid');}                   //GroupID
		if (!isset($qid)) {$qid=returnglobal('qid');}                   //QuestionID
		if (!isset($lid)) {$lid=returnglobal('lid');}                   //LabelID
		if (!isset($code)) {$code=returnglobal('code');}                // ??
		if (!isset($action)) {$action=returnglobal('action');}          //Desired action
		if (!isset($subaction)) {$subaction=returnglobal('subaction');} //Desired subaction
		if (!isset($editedaction)) {$editedaction=returnglobal('editedaction');} // for html editor integration
		*/

		if ($this->router->class != "update" && $this->router->method != "db") self::_logincheck();

		/*if ( $action == 'CSRFwarn')
		{
		    include('access_denied.php');
		}

		if ( $action == 'FakeGET')
		{
		    include('access_denied.php');
		}*/

	}

	/**
	 * Load and set session vars
	 */
	function _sessioncontrol()
	{
		//Session is initialized by CodeIgniter

		//LANGUAGE ISSUES
		// if changelang is called from the login page, then there is no userId
		//  ==> thus we just change the login form lang: no user profile update
		// if changelang is called from another form (after login) then update user lang
		// when a loginlang is specified at login time, the user profile is updated in usercontrol.php
		//if (returnglobal('action') == "savepersonalsettings" && (!isset($login) || !$login ))
		//{
		//    $_SESSION['adminlang']=returnglobal('lang');
		//}
		//elseif (!isset($_SESSION['adminlang']) || $_SESSION['adminlang']=='' )
		//{
		//    $_SESSION['adminlang']=$defaultlang;
		//}

		if (!$this->session->userdata("adminlang") || $this->session->userdata("adminlang")=='')
		{
			$this->session->set_userdata("adminlang",$this->config->item("defaultlang"));
		}

		// Construct the language class, and set the language.
		//if (isset($_REQUEST['rootdir'])) {die('You cannot start this script directly');}
		//require_once($rootdir.'/classes/core/language.php');
		//$clang = new limesurvey_lang($_SESSION['adminlang']);

        $this->load->library('Limesurvey_lang',array("langcode"=>$this->session->userdata("adminlang")));
		//Load with $clang = $CI->limesurvey_lang;

		if($this->session->userdata("loginID")) {self::_GetSessionUserRights($this->session->userdata("loginID"));}

		// check that requests that modify the DB are using POST
		// and not GET requests
		// Not Applicable for CodeIgniter

		//CSRF Protection
		/*if ($_SERVER['REQUEST_METHOD'] == 'POST' &&
		returnglobal('action') != 'login' &&
		returnglobal('action') != 'forgotpass' &&
		returnglobal('action') != 'ajaxquestionattributes' &&
		returnglobal('action') != '')
		{
		    if (returnglobal('checksessionbypost') != $_SESSION['checksessionpost'])
		    {
		        error_log("LimeSurvey ERROR while checking POST session- Probable CSRF attack Received=".returnglobal('checksessionbypost')." / Expected= ".$_SESSION['checksessionpost']." for action=".returnglobal('action')." .");
		        $subaction='';
		        if (isset($_POST['action'])) {unset($_POST['action']);}
		        if (isset($_REQUEST['action'])) {unset($_REQUEST['action']);}
		        if (isset($_POST['subaction'])) {unset($_POST['subaction']);}
		        if (isset($_REQUEST['subaction'])) {unset($_REQUEST['subaction']);}
		        $_POST['action'] = 'CSRFwarn';
		        $_REQUEST['action'] = 'CSRFwarn';
		        $action='CSRFwarn';
		        //include("access_denied.php");
		    }
		}*/


	}

	/**
	 * Authentication checks
	 */
	function _logincheck()
	{
    //    DebugBreak();
		if(!$this->session->userdata("loginID") && $this->router->class != "authentication")
		{
            $this->session->set_userdata('redirect_after_login',$this->uri->uri_string());
			redirect('/admin/authentication/login', 'refresh');
		}

	}

	/**
	* Prints Admin Header
	*/
	function _getAdminHeader($meta=false, $return = false)
	{
		if (!$this->session->userdata("adminlang") || $this->session->userdata("adminlang")=='')
		{
			$this->session->set_userdata("adminlang",$this->config->item("defaultlang"));
		}

		$data['adminlang']=$this->session->userdata("adminlang");
		//$data['admin'] = getLanguageRTL;
		$data['test'] = "t";
		$data['languageRTL']="";
		$data['styleRTL']="";
		$this->load->helper("surveytranslator");
		if (getLanguageRTL($this->session->userdata("adminlang")))
	    {
	        $data['languageRTL'] = " dir=\"rtl\" ";
			$data['styleRTL']="<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/".$this->config->item("admintheme")."/adminstyle-rtl.css\" />\n";
	    }

   		$data['meta']="";
        if ($meta)
	    {
	        $data['meta']=$meta;
	    }

		$data['baseurl']=base_url();
		$data['datepickerlang']="";
	    if ($this->session->userdata("adminlang")!='en')
	    {
	        $data['datepickerlang'] = "<script type=\"text/javascript\" src=\"".$data['baseurl']."scripts/jquery/locale/jquery.ui.datepicker-".$this->session->userdata("adminlang").".js\"></script>\n";
	    }
		$data['sitename'] = $this->config->item("sitename");
		$data['admintheme'] = $this->config->item("admintheme");


		if($this->config->item("css_admin_includes"))
	    {
	    	$data['css_admin_includes'] = array_unique($this->config->item("css_admin_includes"));
	    }

		$data['firebug'] = use_firebug();

	    if ($this->session->userdata('dateformat'))
	    {
	        $data['formatdata']=getDateFormatData($this->session->userdata('dateformat'));
	    }

	    // Prepare flashmessage
	    if ($this->session->userdata('flashmessage') && $this->session->userdata('flashmessage')!='')
	    {
	        //unset($_SESSION['flashmessage']);
			$data['flashmessage'] = $this->session->userdata('flashmessage');
			$this->session->unset_userdata('flashmessage');
	    }
	   	return $this->load->view("admin/super/header",$data, $return);
	}

	/**
	 * Prints Admin Footer
	 */
	function _getAdminFooter($url, $explanation, $return = false)
	{
		$clang = $this->limesurvey_lang;
		$data['clang'] = $clang;

		$data['versionnumber'] = $this->config->item("versionnumber");

		$data['buildtext']="";
		if($this->config->item("buildnumber")!="") {
			$data['buildtext']="Build ".$this->config->item("buildnumber");
		}

	    //If user is not logged in, don't print the version number information in the footer.
	    $data['versiontitle']=$clang->gT('Version');
	    if(!$this->session->userdata('loginID'))
	    {
	        $data['versionnumber']="";
	        $data['versiontitle']="";
			$data['buildtext']="";
	    }

		$data['imageurl']= $this->config->item("imageurl");
		$data['url']=$url;

	    if($this->config->item("js_admin_includes"))
	    {
	    	$data['js_admin_includes'] = array_unique($this->config->item("js_admin_includes"));
	    }

		return $this->load->view("admin/super/footer",$data, $return);
	}

	/**
	 * Set Session User Rights
	 */
	function _GetSessionUserRights($loginID)
	{
	    //$squery = "SELECT create_survey, configurator, create_user, delete_user, superadmin, manage_template, manage_label FROM {$dbprefix}users WHERE uid=$loginID";
	    $this->load->model("Users_model");
		$query = $this->Users_model->getSomeRecords(array("create_survey, configurator, create_user, delete_user, superadmin,participant_panel, manage_template, manage_label"),array("uid"=>$loginID));
		//$sresult = db_execute_assoc($squery); //Checked
	    //if ($sresult->RecordCount()>0)
	    if($query->num_rows() > 0)
	    {
	        //$fields = $sresult->FetchRow();
			$fields = $query->row_array();
            $this->session->set_userdata('USER_RIGHT_SUPERADMIN', $fields['superadmin']);
	        $this->session->set_userdata('USER_RIGHT_CREATE_SURVEY', $fields['create_survey']);
            $this->session->set_userdata('USER_RIGHT_PARTICIPANT_PANEL', $fields['participant_panel']);
	        $this->session->set_userdata('USER_RIGHT_CONFIGURATOR', $fields['configurator']);
            $this->session->set_userdata('USER_RIGHT_CREATE_USER', $fields['create_user']);
	        $this->session->set_userdata('USER_RIGHT_DELETE_USER', $fields['delete_user']);
	        $this->session->set_userdata('USER_RIGHT_MANAGE_TEMPLATE', $fields['manage_template']);
	        $this->session->set_userdata('USER_RIGHT_MANAGE_LABEL', $fields['manage_label']);
	    }

	    // SuperAdmins
	    // * original superadmin with uid=1 unless manually changed and defined
	    //   in config-defaults.php
	    // * or any user having USER_RIGHT_SUPERADMIN right

	    // Let's check if I am the Initial SuperAdmin
	    //$adminquery = "SELECT uid FROM {$dbprefix}users WHERE parent_id=0";
	    //$adminresult = db_select_limit_assoc($adminquery, 1);
	    $query = $this->Users_model->getSomeRecords(array("uid"),array("parent_id"=>0));
	    //$row=$adminresult->FetchRow();
	    $row=$query->row_array();
	    if($row['uid'] == $this->session->userdata('loginID'))
	    {
	        $initialSuperadmin=true;
	    }
	    else
	    {
	        $initialSuperadmin=false;
	    }

	    if ( $initialSuperadmin === true)
	    {
	        $this->session->set_userdata('USER_RIGHT_SUPERADMIN', 1);
	        $this->session->set_userdata('USER_RIGHT_INITIALSUPERADMIN', 1);
	    }
	    else
	    {
	        $this->session->set_userdata('USER_RIGHT_INITIALSUPERADMIN', 0);
	    }
	}

	function _showMessageBox($title,$message,$class="header ui-widget-header")
	{
		$data['title']=$title;
		$data['message']=$message;
		$data['class']=$class;

		//self::_getAdminHeader();
		//self::_showadminmenu();
		$this->load->view('admin/super/messagebox', $data);
		//self::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
	}

	/**
	 * _showadminmenu() function returns html text for the administration button bar
	 *
	 * @global string $homedir
	 * @global string $scriptname
	 * @global string $surveyid
	 * @global string $setfont
	 * @global string $imageurl
	 * @return string $adminmenu
	 */
	function _showadminmenu($surveyid=false)
	{
	    global $homedir, $scriptname, $setfont, $imageurl, $debug, $action, $updateavailable, $updatebuild, $updateversion, $updatelastcheck, $databasetype;

		$clang=$this->limesurvey_lang;
		$data['clang']=$this->limesurvey_lang;

	    if  ($this->session->userdata('pw_notify') && $this->config->item("debug")<2)  {
			$this->session->set_userdata('flashmessage',$clang->gT("Warning: You are still using the default password ('password'). Please change your password and re-login again."));
		}

		$data['showupdate'] = ($this->session->userdata('USER_RIGHT_SUPERADMIN') == 1 && $this->config->item("updatelastcheck")>0 && $this->config->item("updateavailable")==1);
		$data['updateversion'] = $this->config->item("updateversion");
		$data['updatebuild'] = $this->config->item("updatebuild");
		$data['surveyid'] = $surveyid;

	    $this->load->view("admin/super/adminmenu",$data);

	}

	function _css_admin_includes($include)
	{
		$css_admin_includes = $this->config->item("css_admin_includes");
		$css_admin_includes[] = $include;
		$this->config->set_item("css_admin_includes", $css_admin_includes);
	}

    function _js_admin_includes($include)
    {
        $js_admin_includes = $this->config->item("js_admin_includes");
        $js_admin_includes[] = $include;
        $this->config->set_item("js_admin_includes", $js_admin_includes);
    }


    function _loadEndScripts()
    {
        if (!$this->session->userdata('metaHeader')) {
            $this->session->set_userdata('metaHeader','');
        }
        //$adminoutput = getAdminHeader($_SESSION['metaHeader']).$adminoutput;  // All future output is written into this and then outputted at the end of file
        $this->session->unset_userdata('metaHeader');
        //$adminoutput.= "</div>\n";
        if(!$this->session->userdata('checksessionpost'))
        {
            $this->session->set_userdata('checksessionpost','');
            //$_SESSION['checksessionpost'] = '';
        }
        $data['checksessionpost'] = $this->session->userdata('checksessionpost');
        return $this->load->view('admin/endScripts_view',$data);

    }

}