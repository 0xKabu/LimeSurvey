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
* $Id$
*
*/

/**
* Authentication Controller
*
* This controller performs authentication
*
* @package		LimeSurvey
* @subpackage	Backend
*/
class Authentication extends Admin_Controller {

    /**
    * Constructor
    */
    function __construct()
    {
        parent::__construct();
    }

    /**
    * Default Controller Action
    */
    function index()
    {
        redirect('/admin', 'refresh');
    }

    /**
    * Show login screen and parse login data
    */
    function login()
    {
        if(!$this->session->userdata("loginID"))
        {
            $sIp = $this->session->userdata("ip_address");
            $this->load->model("failed_login_attempts_model");
            $this->failed_login_attempts_model->cleanOutOldAttempts();
            $bCannotLogin=$this->failed_login_attempts_model->isLockedOut($sIp);

            if (!$bCannotLogin)
            {
                if($this->input->post('action'))
                {
                    $clang = $this->limesurvey_lang;

                    $data=self::_doLogin($this->input->post("user"), $this->input->post('password'));
                    if (isset($data['errormsg']))
                    {
                        parent::_getAdminHeader();
                        $this->load->view('admin/authentication/error', $data);
                        parent::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));

                    }
                    else
                    {
                        $loginsummary = "<br />".sprintf($clang->gT("Welcome %s!"),$this->session->userdata('full_name'))."<br />&nbsp;";
                        if ($this->session->userdata('redirect_after_login') && strpos($this->session->userdata('redirect_after_login'), "logout") === FALSE)
                        {
                            $this->session->set_userdata('metaHeader',"<meta http-equiv=\"refresh\""
                            . " content=\"1;URL=".site_url($this->session->userdata('redirect_after_login'))."\" />");
                            $loginsummary = "<p><font size='1'><i>".$clang->gT("Reloading screen. Please wait.")."</i></font>\n";
                            $this->session->unset_userdata('redirect_after_login');
                        }
                        self::_GetSessionUserRights($this->session->userdata('loginID'));
                        $this->session->set_userdata("just_logged_in",true);
                        $this->session->set_userdata('loginsummary',$loginsummary);
                        redirect(site_url('/admin'));
                    }

                }
                else
                {
                    self::_showLoginForm();
                }
            }
            else
            {
                // wrong or unknown username
                $data['errormsg']="";
                $data['maxattempts']=sprintf($this->limesurvey_lang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($this->config->item("timeOutTime")/60))."<br />";
                $data['clang']=$this->limesurvey_lang;

                parent::_getAdminHeader();
                $this->load->view('admin/authentication/error', $data);
                parent::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
            }
        }
        else
        {
            redirect('/admin', 'refresh');
        }
    }

    /**
    * Logout user
    */
    function logout()
    {
        killSession();
        self::_showLoginForm('<p>'.$this->limesurvey_lang->gT("Logout successful."));
    }

    /**
    * Forgot Password screen
    */
    function forgotpassword()
    {
        $clang = $this->limesurvey_lang;
        if(!$this->input->post("action"))
        {
            $data['clang'] = $this->limesurvey_lang;
            parent::_getAdminHeader();
            $this->load->view('admin/authentication/forgotpassword', $data);
            parent::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
        }
        else
        {
            $postuser = $this->input->post("user");
            $emailaddr = $this->input->post("email");
            //$query = "SELECT users_name, password, uid FROM ".db_table_name('users')." WHERE users_name=".$connect->qstr($postuser)." AND email=".$connect->qstr($emailaddr);
            //$result = db_select_limit_assoc($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());  // Checked
            $this->load->model("Users_model");
            $query = $this->Users_model->getSomeRecords(array("users_name, password, uid"),array("users_name"=>$postuser,"email"=>$emailaddr));

            if ($query->num_rows()  < 1)
            {
                // wrong or unknown username and/or email
                $data['errormsg']=$this->limesurvey_lang->gT("User name and/or email not found!");
                $data['maxattempts']="";
                $data['clang']=$this->limesurvey_lang;

                parent::_getAdminHeader();
                $this->load->view('admin/authentication/error', $data);
                parent::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));

            }
            else
            {
                //$fields = $result->FetchRow();
                $fields = $query->row_array();

                // send Mail
                $new_pass = createPassword();
                $body = sprintf($clang->gT("Your user data for accessing %s"),$this->config->item("sitename")). "<br />\n";;
                $body .= $clang->gT("Username") . ": " . $fields['users_name'] . "<br />\n";
                $body .= $clang->gT("New password") . ": " . $new_pass . "<br />\n";

                $this->load->config("email");
                $subject = $clang->gT("User data","unescaped");
                $to = $emailaddr;
                $from = $this->config->item("siteadminemail");
                $sitename = $this->config->item("siteadminname");

                if(SendEmailMessage($body, $subject, $to, $from, $this->config->item("sitename"), false,$this->config->item("siteadminbounce")))
                {
                    //$query = "UPDATE ".db_table_name('users')." SET password='".SHA256::hashing($new_pass)."' WHERE uid={$fields['uid']}";
                    //$connect->Execute($query); //Checked
                    $this->Users_model->updatePassword($fields['uid'], $this->sha256->hashing($new_pass));

                    $data['clang'] = $clang;
                    $data['message'] = "<br />".$clang->gT("Username").": {$fields['users_name']}<br />".$clang->gT("Email").": {$emailaddr}<br />
                    <br />".$clang->gT("An email with your login data was sent to you.");
                    parent::_getAdminHeader();
                    $this->load->view('admin/authentication/message', $data);
                    parent::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
                }
                else
                {
                    $tmp = str_replace("{NAME}", "<strong>".$fields['users_name']."</strong>", $clang->gT("Email to {NAME} ({EMAIL}) failed."));
                    $data['clang'] = $clang;
                    $data['message'] = "<br />".str_replace("{EMAIL}", $emailaddr, $tmp) . "<br />";
                    parent::_getAdminHeader();
                    $this->load->view('admin/authentication/message', $data);
                    parent::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));
                }
            }
        }

    }

    /**
    * Show login screen
    * @param optional message
    */
    function _showLoginForm($logoutsummary="")
    {
        $data['clang'] = $this->limesurvey_lang;

        if ($logoutsummary=="")
        {
            $data['summary'] = $this->limesurvey_lang->gT("You have to login first.");
        }
        else
        {
            $data['summary'] = $logoutsummary;
        }

        parent::_getAdminHeader();
        $this->load->view('admin/authentication/login', $data);
        parent::_getAdminFooter("http://docs.limesurvey.org", $this->limesurvey_lang->gT("LimeSurvey online manual"));

    }

    /**
    * Do the actual login work
    * Note: This function is replicated in parts in remotecontrol.php controller - if you change this don't forget to make according changes there, too
    * @param string $sUsername The username to login with
    * @param string $sPassword The password to login with
    */
    function _doLogin( $sUsername, $sPassword)
    {

        $clang = $this->limesurvey_lang;
        $sUsername = sanitize_user($sUsername);
        $this->load->library('admin/sha256','sha256');
        $post_hash = $this->sha256->hashing($sPassword);

        $this->load->model("Users_model");

        $query = $this->Users_model->getAllRecords(array("users_name"=>$sUsername, 'password'=>$post_hash));

        if ($query->num_rows() < 1)
        {
            $this->load->model("failed_login_attempts_model");
            $query = $this->failed_login_attempts_model->addAttempt($this->input->ip_address());

            if ($query)
            {
                // wrong or unknown username
                $data['errormsg']=$clang->gT("Incorrect username and/or password!");
                $data['maxattempts']="";
                if ($this->failed_login_attempts_model->isLockedOut($this->input->ip_address()))
                {
                    $data['maxattempts']=sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($this->config->item("timeOutTime")/60))."<br />";
                }
                $data['clang']=$clang;
                return $data;
            }
        }
        else
        {
            // Anmeldung ERFOLGREICH
            $fields = $query->row_array(); //$result->FetchRow();

            // Check if the user has changed his default password
            if (strtolower($this->input->post('password'))=='password')
            {
                $this->session->set_userdata('pw_notify',true);
                $this->session->set_userdata('flashmessage',$clang->gT("Warning: You are still using the default password ('password'). Please change your password and re-login again."));
            }
            else
            {
                $this->session->set_userdata('pw_notify',false);
            }

            $session_data = array(
            'loginID' => intval($fields['uid']),
            'user' => $fields['users_name'],
            'full_name' => $fields['full_name'],
            'full_name' => $fields['full_name'],
            'htmleditormode' => $fields['htmleditormode'],
            'templateeditormode' => $fields['templateeditormode'],
            'questionselectormode' => $fields['questionselectormode'],
            'dateformat' => $fields['dateformat'],
            // Compute a checksession random number to test POSTs
            'checksessionpost' => sRandomChars(10)
            );
            $this->session->set_userdata($session_data);

            $postloginlang=sanitize_languagecode($this->input->post('loginlang'));
            if (isset($postloginlang) && $postloginlang!='default')
            {
                $this->session->set_userdata('adminlang',$postloginlang);
                $this->limesurvey_lang->limesurvey_lang(array("langcode"=>$postloginlang));
                $clang = $this->limesurvey_lang;
                $this->Users_model->updateLang($this->session->userdata("loginID"),$postloginlang);
            }
            else
            {
                $this->session->set_userdata('adminlang',$fields['lang']);
                $this->load->library('Limesurvey_lang',array("langcode"=>$fields['lang']));
                $clang = $this->limesurvey_lang;
            }
            return true;

        }
    }
}