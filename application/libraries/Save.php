<?php
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
 *
//Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

Redesigned 7/25/2006 - swales

1.  Save Feature (// --> START NEW FEATURE - SAVE)

How it used to work
-------------------
1. The old save method would save answers to the "survey_x" table only when the submit button was clicked.
2. If "allow saves" was turned on then answers were temporarily recorded in the "saved" table.

Why change this feature?
------------------------
If a user did not complete a survey, ALL their answers were lost since no submit (database insert) was performed.


Save Feature redesign
---------------------
Benefits
Partial survey answers are saved (provided at least Next/Prev/Last/Submit/Save so far clicked at least once).

Details.
1. The answers are saved in the "survey_x" table only.  The "saved" table is no longer used.
2. The "saved_control" table has new column (srid) that points to the "survey_x" record it corresponds to.
3. Answers are saved every time you move between pages (Next,Prev,Last,Submit, or Save so far).
4. Only the fields modified on the page are updated. A new hidden field "modfields" store which fields have changed. - REVERTED
5. Answered are reloaded from the database after the save so that if some other answers were modified by someone else
the updates would be picked up for the current page.  There is still an issue if two people modify the same
answer at the same time.. the 'last one to save' wins.
6. The survey_x datestamp field is updated every time the record is updated.
7. Template can now contain {DATESTAMP} to show the last modified date/time.
8. A new field 'submitdate' has been added to the survey_x table and is written when the submit button is clicked.
9. Save So Far now displays on Submit page. This allows the user one last chance to create a saved_control record so they
can return later.

Notes
-----
1. A new column SRID has been added to saved_control.
2. saved table no longer exists.
*/

class Save {

    function run($args) {
        extract($args);

        //First, save the posted data to session
        //Doing this ensures that answers on the current page are saved as well.
        //CONVERT POSTED ANSWERS TO SESSION VARIABLES
        if (isset($_POST['fieldnames']) && $_POST['fieldnames'])
        {
            $postedfieldnames=explode("|", $_POST['fieldnames']);

            // Remove invalid fieldnames from fieldnames array
            for($x=count($postedfieldnames)-1;$x>=0;$x--)
            {
                if (!isset($_SESSION['fieldmap'][$postedfieldnames[$x]]))
                {
                    $this->array_remval($postedfieldnames[$x],$postedfieldnames);
                }

            }
            $_POST['fieldnames']=implode("|",$postedfieldnames);


            foreach ($postedfieldnames as $pf)
            {
                if (isset($_POST[$pf])) {$_SESSION[$pf] = $_POST[$pf];}
                if (!isset($_POST[$pf])) {$_SESSION[$pf] = "";}
            }
        }
        //CHECK FOR TIMER QUESTIONS TO SAVE TIME REMAINING
        if (isset($_POST['timerquestion']))
        {
            $_SESSION[$_POST['timerquestion']]=sanitize_float($_POST[$_POST['timerquestion']]);
        }
        //CHECK FOR RELEVANCE RESULTS
        $aIrrelevant = array();
        $aRelevance = array();
        foreach ($_POST as $key=>$value) {
            if (preg_match('/^relevance\d+$/',$key)) {
                $aRelevance[substr($key,9)] = $value;
                if ($value!='1') {
                    if (isset($_POST[$key . 'codes'])) {
                        $aIrrelevant = array_merge($aIrrelevant,explode('|',$_POST[$key . 'codes']));
                    }
                }
            }
        }
        $_SESSION['irrelevantCodes'] = array_unique($aIrrelevant);
        if (!isset($_SESSION['relevanceStatus'])) {
            $_SESSION['relevanceStatus'] = array();
        }
        foreach ($aRelevance as $key=>$value)
        {
            $_SESSION['relevanceStatus'][$key] = $value;
        }

        //Check to see if we should set a submitdate or not
        // this depends on the move, and on quesitons checks
        if (isset($move) && $move == "movesubmit")
        {
            $backok=null;
            // TMSW Conditions->Relevance:  EM will check mandatories
            $notanswered=addtoarray_single(checkmandatorys($move,$backok),checkconditionalmandatorys($move,$backok));
            // TMSW Conditions->Relevance:  EM will validate most/all of input
            $notvalidated=aCheckInput($surveyid, $move,$backok);
            $filenotvalidated = checkUploadedFileValidity($surveyid, $move, $backok);

            if ( (!is_array($notanswered) || count($notanswered)==0) && (!is_array($notvalidated) || count($notvalidated)==0) && (!is_array($filenotvalidated) || count($filenotvalidated) == 0))
            {
                $bFinalizeThisAnswer = true;
            }
            else
            {
                $bFinalizeThisAnswer = false;
            }
        }
        else
        {
            $bFinalizeThisAnswer = false;
        }

        // SAVE if on page with questions or on submit page
        if (isset($postedfieldnames) || (isset($move) && $move == "movesubmit") )
        {
            if ($thissurvey['active'] == "Y")
            {
                $bQuotaMatched=false;
                $aQuotas=check_quota('return',$surveyid);
                if ($aQuotas !== false)
                {
                    if ($aQuotas!=false)
                    {
                        foreach ($aQuotas as $aQuota)
                        {
                            if (isset($aQuota['status']) && $aQuota['status']=='matched') $bQuotaMatched=true;
                        }
                    }
                }
                if ($bQuotaMatched) $bFinalizeThisAnswer=false;
            }

            if ($thissurvey['active'] == "Y" && !isset($_SESSION['finished'])) 	// Only save if active and the survey wasn't already submitted
            {
                // SAVE DATA TO SURVEY_X RECORD
                $subquery = self::createinsertquery($surveyid, $move, $thissurvey, $thisstep, $postedfieldnames, $bFinalizeThisAnswer);
                if ($subquery)
                {
                    if ($result=db_execute_assoc($subquery))  // Checked
                    {
                        if (substr($subquery,0,6)=='INSERT')
                        {
                            $tempID=Yii::app()->db->getLastInsertID(); // Find out id immediately if inserted
                            $_SESSION['srid'] = $tempID;
                            $saved_id = $tempID;
                        }
                        if ($bFinalizeThisAnswer === true)
                        {
                            db_execute_assoc("DELETE FROM {{saved_control}} where srid=".$_SESSION['srid'].' and sid='.$surveyid);   // Checked
                        }
                    }
                    else
                    {
                        echo submitfailed();
                    }
                }
                if ($bQuotaMatched)
                {
                    check_quota('enforce',$surveyid);
                }
            }
            elseif (isset($move))
            {
                // This else block is only there to take care of date conversion if the survey is not active - otherwise this is done in creatInsertQuery
                $fieldmap=createFieldMap($surveyid,'full'); //Creates a list of the legitimate questions for this survey
                $inserts=array_unique($_SESSION['insertarray']);
                foreach ($inserts as $value)
                {
                    //Work out if the field actually exists in this survey
                    unset($fieldexists);
                    if (isset($fieldmap[$value])) $fieldexists = $fieldmap[$value];
                    //Iterate through possible responses
                    if (isset($_SESSION[$value]) && isset($fieldexists) && $_SESSION[$value]!='')
                    {
                        if ($fieldexists['type']=='D' && isset($_POST[$value]))  // convert the date to the right DB Format
                        {
                            $qidattributes = getQuestionAttributeValues($fieldexists['qid']);
                            $dateformatdetails = aGetDateFormatDataForQid($qidattributes, $thissurvey);
                            //		                    $datetimeobj = new Date_Time_Converter($_SESSION[$value], $dateformatdetails['phpdate']);
                            $_SESSION[$value] = date("Y-m-d H:i:s", strtotime($_SESSION[$value]));
                        }
                    }
                }


            }
            if ($thissurvey['savetimings']=="Y" && $thissurvey['active'] == "Y")
            {
                $this->set_answer_time();
            }
        }

        // CREATE SAVED CONTROL RECORD USING SAVE FORM INFORMATION
        if (isset($_POST['saveprompt']))  //Value submitted when clicking on 'Save Now' button on SAVE FORM
        {
            if ($thissurvey['active'] == "Y") 	// Only save if active
            {
                $flashmessage=savedcontrol();
                if (isset($errormsg) && $errormsg != "")
                {
                    showsaveform();
                }
            }
            else
            {
                $_SESSION['scid'] = 0;		// If not active set to a dummy value to save form does not continue to show.
            }
        }

        // DISPLAY SAVE FORM
        // Displays even if not active just to show how it would look when active (for testing purposes)
        // Show 'SAVE FORM' only when click the 'Save so far' button the first time
        if ($thissurvey['allowsave'] == "Y"  && isset($_POST['saveall']) && !isset($_SESSION['scid']))
        {
            if($thissurvey['tokenanswerspersistence'] != 'Y' || !tableExists('tokens_'.$surveyid))
            {
                $this->showsaveform();
            }
            else
            {
                $flashmessage = $this->savedsilent();
            };
        }
        elseif ($thissurvey['allowsave'] == "Y"  && isset($_POST['saveall']) && isset($_SESSION['scid']) )   //update the saved step only
        {
            db_execute_assoc("update {{saved_control}} set saved_thisstep=".$thisstep." where scid=".$_SESSION['scid']);  // Checked
        }

    }

    function showsaveform()
    {
        //Show 'SAVE FORM' only when click the 'Save so far' button the first time, or when duplicate is found on SAVE FORM.
        global $thistpl, $errormsg, $thissurvey, $surveyid, $clang, $clienttoken, $thisstep;
        sendcacheheaders();
        doHeader();
        foreach(file("$thistpl/startpage.pstpl") as $op)
        {
            echo templatereplace($op);
        }
        echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n"
        ."\t<script type='text/javascript'>\n"
        ."\t<!--\n"
        ."function checkconditions(value, name, type)\n"
        ."\t{\n"
        ."\t}\n"
        ."\t//-->\n"
        ."\t</script>\n\n";

        echo "<form method='post' action='".Yii::app()->getController()->createUrl("survey")."'>\n";
        //PRESENT OPTIONS SCREEN
        if (isset($errormsg) && $errormsg != "")
        {
            $errormsg .= "<p>".$clang->gT("Please try again.")."</p>";
        }
        foreach(file("$thistpl/save.pstpl") as $op)
        {
            echo templatereplace($op);
        }
        //END
        echo "<input type='hidden' name='sid' value='$surveyid' />\n";
        echo "<input type='hidden' name='thisstep' value='",$thisstep,"' />\n";
        echo "<input type='hidden' name='token' value='",$clienttoken,"' />\n";
        echo "<input type='hidden' name='saveprompt' value='Y' />\n";
        echo "</form>";

        foreach(file("$thistpl/endpage.pstpl") as $op)
        {
            echo templatereplace($op);
        }
        echo "</html>\n";
        exit;
    }



    function savedcontrol()
    {
        //This data will be saved to the "saved_control" table with one row per response.
        // - a unique "saved_id" value (autoincremented)
        // - the "sid" for this survey
        // - the "srid" for the survey_x row id
        // - "saved_thisstep" which is the step the user is up to in this survey
        // - "saved_ip" which is the ip address of the submitter
        // - "saved_date" which is the date ofthe saved response
        // - an "identifier" which is like a username
        // - a "password"
        // - "fieldname" which is the fieldname of the saved response
        // - "value" which is the value of the response
        //We start by generating the first 5 values which are consistent for all rows.

        global $surveyid, $thissurvey, $errormsg, $publicurl, $sitename, $timeadjust, $clang, $clienttoken, $thisstep;

        //Check that the required fields have been completed.
        $errormsg="";
        if (!isset($_POST['savename']) || !$_POST['savename']) {$errormsg.=$clang->gT("You must supply a name for this saved session.")."<br />\n";}
        if (!isset($_POST['savepass']) || !$_POST['savepass']) {$errormsg.=$clang->gT("You must supply a password for this saved session.")."<br />\n";}
        if ((isset($_POST['savepass']) && !isset($_POST['savepass2'])) || $_POST['savepass'] != $_POST['savepass2'])
        {$errormsg.=$clang->gT("Your passwords do not match.")."<br />\n";}
        // if security question asnwer is incorrect
        if (function_exists("ImageCreate") && captcha_enabled('saveandloadscreen',$thissurvey['usecaptcha']))
        {
            if (!isset($_POST['loadsecurity']) ||
            !isset($_SESSION['secanswer']) ||
            $_POST['loadsecurity'] != $_SESSION['secanswer'])
            {
                $errormsg .= $clang->gT("The answer to the security question is incorrect.")."<br />\n";
            }
        }

        if ($errormsg)
        {
            return;
        }
        //All the fields are correct. Now make sure there's not already a matching saved item
        $query = "SELECT COUNT(*) FROM {{saved_control}}\n"
        ."WHERE sid=$surveyid\n"
        ."AND identifier=".$_POST['savename'];
        $result = db_execute_num($query) or safe_die("Error checking for duplicates!<br />$query<br />");   // Checked
        list($count) = $result->read();
        if ($count > 0)
        {
            $errormsg.=$clang->gT("This name has already been used for this survey. You must use a unique save name.")."<br />\n";
            return;
        }
        else
        {
            //INSERT BLANK RECORD INTO "survey_x" if one doesn't already exist
            if (!isset($_SESSION['srid']))
            {
                $today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);
                $sdata = array("datestamp"=>$today,
                "ipaddr"=>get_current_ip_address(),
                "startlanguage"=>$_SESSION['s_lang'],
                "refurl"=>getenv("HTTP_REFERER"));
                if (db_execute_assoc($CI->db->insert_string($thissurvey['tablename'], $sdata)))    // Checked
                {
                    $srid = Yii::app()->db->getLastInsertID();
                    $_SESSION['srid'] = $srid;
                }
                else
                {
                    safe_die("Unable to insert record into survey table.<br /><br />");
                }
            }
            //CREATE ENTRY INTO "saved_control"
            $today = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);
            $scdata = array("sid"=>$surveyid,
            "srid"=>$_SESSION['srid'],
            "identifier"=>$_POST['savename'], // Binding does escape , so no quoting/escaping necessary
            "access_code"=>md5($_POST['savepass']),
            "email"=>$_POST['saveemail'],
            "ip"=>get_current_ip_address(),
            "refurl"=>getenv("HTTP_REFERER"),
            "saved_thisstep"=>$thisstep,
            "status"=>"S",
            "saved_date"=>$today);


            if (db_execute_assoc($CI->db->insert_string("{{saved_control}}", $scdata)))   // Checked
            {
                $scid = Yii::app()->db->getLastInsertID();
                $_SESSION['scid'] = $scid;
            }
            else
            {
                safe_die("Unable to insert record into saved_control table.<br /><br />");
            }

            $_SESSION['holdname']=$_POST['savename']; //Session variable used to load answers every page. Unsafe - so it has to be taken care of on output
            $_SESSION['holdpass']=$_POST['savepass']; //Session variable used to load answers every page.  Unsafe - so it has to be taken care of on output

            //Email if needed
            if (isset($_POST['saveemail']) )
            {
                if (validate_email($_POST['saveemail']))
                {
                    $subject=$clang->gT("Saved Survey Details") . " - " . $thissurvey['name'];
                    $message=$clang->gT("Thank you for saving your survey in progress.  The following details can be used to return to this survey and continue where you left off.  Please keep this e-mail for your reference - we cannot retrieve the password for you.","unescaped");
                    $message.="\n\n".$thissurvey['name']."\n\n";
                    $message.=$clang->gT("Name","unescaped").": ".$_POST['savename']."\n";
                    $message.=$clang->gT("Password","unescaped").": ".$_POST['savepass']."\n\n";
                    $message.=$clang->gT("Reload your survey by clicking on the following link (or pasting it into your browser):","unescaped").":\n";
                    $message.=$publicurl."/index.php?sid=$surveyid&loadall=reload&scid=".$scid."&loadname=".urlencode($_POST['savename'])."&loadpass=".urlencode($_POST['savepass']);

                    if ($clienttoken){$message.="&token=".$clienttoken;}
                    $from="{$thissurvey['adminname']} <{$thissurvey['adminemail']}>";
                    if (SendEmailMessage($message, $subject, $_POST['saveemail'], $from, $sitename, false, getBounceEmail($surveyid)))
                    {
                        $emailsent="Y";
                    }
                    else
                    {
                        echo "Error: Email failed, this may indicate a PHP Mail Setup problem on your server. Your survey details have still been saved, however you will not get an email with the details. You should note the \"name\" and \"password\" you just used for future reference.";
                    }
                }
            }
            return  $clang->gT('Your survey was successfully saved.');
        }
    }

    /**
    * savesilent() saves survey responses when the "Resume later" button
    * is press but has no interaction. i.e. it does not ask for email,
    * username or password or capture.
    *
    * @return string confirming successful save.
    */
    function savedsilent()
    {
        global $surveyid, $thissurvey, $errormsg, $publicurl, $sitename, $timeadjust, $clang, $clienttoken, $thisstep, $modrewrite;
        submitanswer();
        // Prepare email
        $tokenentryquery = 'SELECT * from {{tokens_'.$surveyid.'}} WHERE token=\''.sanitize_paranoid_string($clienttoken).'\';';
        $tokenentryresult = db_execute_assoc($tokenentryquery);
        $tokenentryarray = $tokenentryresult->read();

        $from = $thissurvey['adminname'].' <'.$thissurvey['adminemail'].'>';
        $to = $tokenentryarray['firstname'].' '.$tokenentryarray['lastname'].' <'.$tokenentryarray['email'].'>';
        $subject = $clang->gT("Saved Survey Details") . " - " . $thissurvey['name'];
        $message = $clang->gT("Thank you for saving your survey in progress. You can return to the survey at the same point you saved it at any time using the link from this or any previous email sent to regarding this survey.","unescaped")."\n\n";
        $message .= $clang->gT("Reload your survey by clicking on the following link (or pasting it into your browser):","unescaped").":\n";
        $language = $tokenentryarray['language'];

        if($modrewrite)
        {
            $message .= "\n\n$publicurl/$surveyid/lang-$language/tk-$clienttoken";
        }
        else
        {
            $message .= "\n\n$publicurl/index.php?lang=$language&sid=$surveyid&token=$clienttoken";
        };
        if (SendEmailMessage($message, $subject, $to, $from, $sitename, false, getBounceEmail($surveyid)))
        {
            $emailsent="Y";
        }
        else
        {
            echo "Error: Email failed, this may indicate a PHP Mail Setup problem on your server. Your survey details have still been saved, however you will not get an email with the details. You should note the \"name\" and \"password\" you just used for future reference.";
        };
        return  $clang->gT('Your survey was successfully saved.');
    }


    //FUNCTIONS USED WHEN SUBMITTING RESULTS:
    function createinsertquery($surveyid, $move, $thissurvey, $thisstep, $postedfieldnames, $bFinalizeThisAnswer)
    {



        global $thistpl;

        $clang = Yii::app()->lang;
        $timeadjust = Yii::app()->getConfig("timeadjust");
        $deletenonvalues = Yii::app()->getConfig("deletenonvalues");

        $fieldmap=createFieldMap($surveyid); //Creates a list of the legitimate questions for this survey

        if (isset($_SESSION['insertarray']) && is_array($_SESSION['insertarray']))
        {
            $inserts=array_unique($_SESSION['insertarray']);

            $colnames_hidden=Array();
            // Add irrelevant columns to list of hidden fields
            if (isset($_SESSION['irrelevantCodes'])) {
                $colnames_hidden = array_merge($colnames_hidden,$_SESSION['irrelevantCodes']);
            }
            foreach ($inserts as $value)
            {
                //Work out if the field actually exists in this survey
                $fieldexists = '';
                if (isset($fieldmap[$value])) $fieldexists = $fieldmap[$value];
                //Iterate through possible responses
                if (isset($_SESSION[$value]) && !empty($fieldexists))
                {
                    //Only create column name and data entry if there is actually data!
                    $colnames[]=$value;
                    //If deletenonvalues is ON, delete any values that shouldn't exist
                    // we only do this at stubmit time so that we don't delete default values
                    // morover, doing this only once reduces the perfomance impact
                    // Never blank out result for Equation question type, even though it is hidden

                    if ($move == "movesubmit" && $deletenonvalues==1 && $fieldexists['type']!='*' && !checkconfield($value))
                    {
                        $values[]='NULL';
                        $colnames_hidden[]=$value;
                    }
                    elseif (($_SESSION[$value]=='' && $fieldexists['type']=='D')||($_SESSION[$value]=='' && $fieldexists['type']=='K')||($_SESSION[$value]=='' && $fieldexists['type']=='N'))
                    {
                        // most databases do not allow to insert an empty value into a datefield,
                        // therefore if no date was chosen in a date question the insert value has to be NULL
                        $values[]='NULL';
                    }

                    else if ($fieldexists['type']=='|' && strpos($fieldexists['fieldname'], "_filecount") === false)
                        {
                            $fieldname = $fieldexists['fieldname'];
                            $target = Yii::app()->getConfig("uploaddir")."/surveys/". $thissurvey['sid'] ."/files/";

                            $json = $_SESSION[$value];
                            $phparray = json_decode(stripslashes($json));

                            // if the files have not been saved already,
                            // move the files from tmp to the files folder

                            $tmp = Yii::app()->getConfig('tempdir').'/upload/';
                            if (!is_null($phparray) && count($phparray) > 0)
                            {
                                // Move the (unmoved, temp) files from temp to files directory.
                                // Check all possible file uploads
                                for ($i = 0; $i < count($phparray); $i++)
                                {
                                    if (file_exists($tmp.$phparray[$i]->filename))
                                    {
                                        $sDestinationFileName='fu_'.sRandomChars(15);
                                        if (!rename($tmp . $phparray[$i]->filename, $target . $sDestinationFileName))
                                            echo "Error moving file to its destination";
                                        $phparray[$i]->filename=$sDestinationFileName;
                                    }
                            }
                            $_SESSION[$value] = json_encode($phparray);
                        }
                        $values[] = $_SESSION[$value];
                        // filename is changed from undefined to a random value
                        // update uses $_POST for saving responses
                        $_POST[$value] = $_SESSION[$value];
                    }

                    else
                    {
                        // Empty the 'Other' field if a value other than '-oth-' was set for the main field (prevent invalid other values being saved - for example if Javascript fails to hide the 'Other' input field)
                        if ($fieldexists['type']=='!' && $fieldmap[$value]['aid']=='other' && isset($_POST[substr($value,0,strlen($value)-5)]) && $_POST[substr($value,0,strlen($value)-5)]!='-oth-')
                        {
                            $_SESSION[$value]='';
                        }

                        elseif ($fieldexists['type']=='N' || $fieldexists['type']=='K') //sanitize numerical fields
                        {
                            $_SESSION[$value]=sanitize_float($_SESSION[$value]);

                        }
                        elseif ($fieldexists['type']=='D' && is_array($postedfieldnames) && in_array($value,$postedfieldnames))
                        {
                            // convert the date to the right DB Format but only if it was posted
                            $qidattributes = getQuestionAttributeValues($fieldexists['qid']);
                            $dateformatdetails = aGetDateFormatDataForQid($qidattributes, $thissurvey);
                            $datetimeobj = new Date_Time_Converter($_SESSION[$value], $dateformatdetails['phpdate']);
                            $_SESSION[$value] = date("Y-m-d H:i:s", strtotime($_SESSION[$value]));
                        }
                        $values[]=date("Y-m-d H:i:s", strtotime($_SESSION[$value]));
                    }
                }
            }

            if ($thissurvey['datestamp'] == "Y")
            {
                $_SESSION['datestamp']=date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);

            }


            // First compute the submitdate
            if ($thissurvey['anonymized'] =="Y" && $thissurvey['datestamp'] =="N")
            {
                // In case of anonymized responses survey with no datestamp
                // then the the answer submutdate gets a conventional timestamp
                // 1st Jan 1980
                $mysubmitdate = date("Y-m-d H:i:s",mktime(0,0,0,1,1,1980));
            }
            else
            {
                $mysubmitdate = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);
            }

            // CHECK TO SEE IF ROW ALREADY EXISTS
            // srid (=Survey Record ID ) is set when the there were already answers saved for that survey
            if (!isset($_SESSION['srid']))
            {
                //Prepare row insertion


                if (!isset($colnames) || !is_array($colnames)) //If something went horribly wrong - ie: none of the insertarray fields exist for this survey, crash out
                {
                    echo submitfailed();
                    exit;
                }

                // INSERT NEW ROW
                $query = "INSERT INTO ".$thissurvey['tablename']."\n"
                ."(".implode(', ', array_map('db_quote_id',$colnames));
                $query .= ",lastpage";
                if ($thissurvey['datestamp'] == "Y")
                {
                    $query .= ",datestamp";
                    $query .= ",startdate";
                }
                if ($thissurvey['ipaddr'] == "Y")
                {
                    $query .= ",ipaddr";
                }
                $query .= ",startlanguage";
                if ($thissurvey['refurl'] == "Y")
                {
                    $query .= ",refurl";
                }
                if ($bFinalizeThisAnswer === true && ($thissurvey['format'] != "A"))
                {
                    $query .= ",submitdate";
                }
                $query .=") ";
                $query .="VALUES ('".implode("', '", $values);
                $query .= "',".($thisstep+1);
                if ($thissurvey['datestamp'] == "Y")
                {
                    $query .= ", '".$_SESSION['datestamp']."'";
                    $query .= ", '".$_SESSION['datestamp']."'";
                }
                if ($thissurvey['ipaddr'] == "Y")
                {
                    $query .= ", '".get_current_ip_address()."'";
                }
                $query .= ", '".$_SESSION['s_lang']."'";
                if ($thissurvey['refurl'] == "Y")
                {
                    $query .= ", '".$_SESSION['refurl']."'";
                }
                if ($bFinalizeThisAnswer === true && ($thissurvey['format'] != "A"))
                {
                    // is if a ALL-IN-ONE survey, we don't set the submit date before the data is validated
                    $query .= ", '".$mysubmitdate."'";
                }
                $query .=")";
            }
            else
            {  // UPDATE EXISTING ROW
                // Updates only the MODIFIED fields posted on current page.
                if (isset($postedfieldnames) && $postedfieldnames)
                {
                    $query = "UPDATE {$thissurvey['tablename']} SET ";
                    $query .= " lastpage = '".$thisstep."',";
                    if ($thissurvey['datestamp'] == "Y")
                    {
                        $query .= " datestamp = '".$_SESSION['datestamp']."',";
                    }
                    if ($thissurvey['ipaddr'] == "Y")
                    {
                        $query .= " ipaddr = '".get_current_ip_address()."',";
                    }
                    // is if a ALL-IN-ONE survey, we don't set the submit date before the data is validated
                    if ($bFinalizeThisAnswer === true && ($thissurvey['format'] != "A"))
                    {
                        $query .= " submitdate = '".$mysubmitdate."', ";
                    }
                    // Resets fields hidden due to conditions
                    // TMSW Conditions->Relevance:  EM will deal with NULLing irrelevant fields (as opposed to hidden ones)
                    if ($deletenonvalues == 1)
                    {
                        $hiddenfields=array_unique(array_values($colnames_hidden));
                        foreach ($hiddenfields as $hiddenfield)
                        {
                            //$fieldinfo = arraySearchByKey($hiddenfield, $fieldmap, "fieldname", 1);
                            //if ($fieldinfo['type']=='D' || $fieldinfo['type']=='N' || $fieldinfo['type']=='K')
                            //{
                            $query .= db_quote_id($hiddenfield)." = NULL,";
                            //}
                            //else
                            //{
                            //	$query .= $hiddenfield." = '',";
                            //}
                        }
                    }
                    else
                    {
                        $hiddenfields=Array();
                    }

                    $fields=$postedfieldnames;
                    $fields=array_unique($fields);
                    $fields=array_diff($fields,$hiddenfields); // Do not take fields that are hidden
                    foreach ($fields as $field)
                    {
                        if(!empty($field))
                        {
                            $fieldinfo = $fieldmap[$field];
                            if (!isset($_POST[$field])) {$_POST[$field]='';}
                            //fixed numerical question fields. They have to be NULL instead of '' to avoid database errors
                            if (($_POST[$field]=='' && $fieldinfo['type']=='D') || ($_POST[$field]=='' && $fieldinfo['type']=='N') || ($_POST[$field]=='' && $fieldinfo['type']=='K'))
                            {
                                $query .= db_quote_id($field)." = NULL,";
                            }
                            else
                            {
                                // Empty the 'Other' field if a value other than '-oth-' was set for the main field (prevent invalid other values being saved - for example if Javascript fails to hide the 'Other' input field)
                                if ($fieldinfo['type']=='!' && $fieldmap[$field]['aid']=='other' && $_POST[substr($field,0,strlen($field)-5)]!='-oth-') //sanitize Other fields
                                {
                                    $qfield="''";
                                }
                                elseif ($fieldinfo['type']=='N' || $fieldinfo['type']=='K') //sanitize numerical fields
                                {
                                    $qfield=sanitize_float($_POST[$field]);
                                }
                                elseif ($fieldinfo['type']=='D')  // convert the date to the right DB Format
                                {
                                    $qidattributes = getQuestionAttributeValues($fieldinfo['qid']);
                                    $dateformatdetails = aGetDateFormatDataForQid($qidattributes, $thissurvey);
                                    // $datetimeobj = new Date_Time_Converter($_POST[$field], $dateformatdetails['phpdate']);
                                    $qfield = date("Y-m-d H:i:s", strtotime($_POST[$field]));
                                }
                                else
                                {
                                    $qfield = $_POST[$field];
                                }
                                $query .= db_quote_id($field)." = ".$qfield.",";
                            }
                        }
                    }


                    $query .= "WHERE id=" . $_SESSION['srid'];
                    $query = str_replace(",WHERE", " WHERE", $query);   // remove comma before WHERE clause
                }
                else
                {
                    $query = "";
                    if ($bFinalizeThisAnswer === true)
                    {
                        $query = "UPDATE {$thissurvey['tablename']} SET ";
                        $query .= " submitdate = '".$mysubmitdate ."'";
                        $query .= " WHERE id=" . $_SESSION['srid'];
                    }
                }
            }
            return $query;

        }
        else
        {
            sendcacheheaders();
            doHeader();
            foreach(file("$thistpl/startpage.pstpl") as $op)
            {
                echo templatereplace($op);
            }
            echo "<br /><center><font face='verdana' size='2'><font color='red'><strong>".$clang->gT("Error")."</strong></font><br /><br />\n";
            echo $clang->gT("Cannot submit results - there are none to submit.")."<br /><br />\n";
            echo "<font size='1'>".$clang->gT("This error can occur if you have already submitted your responses and pressed 'refresh' on your browser. In this case, your responses have already been saved.")."<br /><br />".$clang->gT("If you receive this message in the middle of completing a survey, you should choose '<- BACK' on your browser and then refresh/reload the previous page. While you will lose answers from the last page all your others will still exist. This problem can occur if the webserver is suffering from overload or excessive use. We apologise for this problem.")."<br />\n";
            echo "</font></center><br /><br />";
            exit;
        }
    }

    // submitanswer sets the submitdate
    // Only used by question.php and group.php if next pages
    // should not display due to conditions and generally used by survey.php
    // In this case all answers have already been updated by save.php
    // but movesubmit status was only set after calling save.php
    // ==> thus we need to update submitdate here.
    function submitanswer($surveyid, $thissurvey, $move)
    {
        global $thissurvey;
        global $surveyid, $move;
        $CI = &get_instance();
        $clang = Yii::app()->lang;
        $timeadjust = Yii::app()->getConfig('timeadjust');

        if ($thissurvey['anonymized'] =="Y" && $thissurvey['datestamp'] =="N")
        {
            // In case of anonymized responses survey with no datestamp
            // then the the answer submitdate gets a conventional timestamp
            // 1st Jan 1980
            $mysubmitdate = date("Y-m-d H:i:s",mktime(0,0,0,1,1,1980));
        }
        else
        {
            $mysubmitdate = date_shift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", $timeadjust);
        }

        $query = "";
        if (isset($move) && ($move == "movesubmit") && ($thissurvey['active'] == "Y"))
        {
            if (!isset($_SESSION['srid']))
            { //due to conditions no answer was displayed and yet we must submit
                $query=createinsertquery();
                if ($result=db_execute_assoc($query))
                {
                    $tempID = Yii::app()->db->getLastInsertID(); //Insert_ID($thissurvey['tablename'],"id");
                    $_SESSION['srid'] = $tempID;
                }
            }
            $query = "UPDATE {$thissurvey['tablename']} SET ";
            $query .= " submitdate = '".date("Y-m-d H:i:s", strtotime($mysubmitdate)."'");
            $query .= " WHERE id=" . $_SESSION['srid'];
        }

        $result=db_execute_assoc($query);    // Checked
        return $result;
    }

    function array_remval($val, &$arr)
    {
        $array_remval = $arr;
        for($x=0;$x<count($array_remval)-1;$x++)
        {
            $i=array_search($val,$array_remval);
            if($i===false)return false;
            $array_remval=array_merge(array_slice($array_remval, 0,$i), array_slice($array_remval, $i+1));
        }
        return $array_remval;
    }

    /**
    * This functions saves the answer time for question/group and whole survey.
    * [ It compares current time with the time in $_POST['start_time'] ]
    * The times are saved in table: {prefix}{surveytable}_timings
    * @return void
    */
    function set_answer_time()
    {
        $CI = &get_instance();
        global $thissurvey;
        if (isset($_POST['lastanswer']))
        {
            $setField = $_POST['lastanswer'];
        }
        $passedTime = round(microtime(true) - $_POST['start_time'],2);

        if(!isset($setField))
            $setField = $_POST['lastgroup'];
        if(!isset($setField)){ //we show the whole survey on one page - we don't have to save time for group/question
            if(Yii::app()->db->getLastInsertID() > 0){	// means that the last operation was INSERT
                $query = "INSERT INTO ".$thissurvey['tablename']."_timings" ." ("
                ."id, interviewtime)"
                ." VALUES (" .$_SESSION['srid'] ."," .$passedTime .")";
            }else{	// UPDATE
                $query = "UPDATE {$thissurvey['tablename']}_timings SET "
                ."interviewtime = interviewtime" ." + " .$passedTime
                ." WHERE id = " .$_SESSION['srid'];
            }
            db_execute_assoc($query);
            return;
        }

        $setField .= "time";
        //saving the times
        if(Yii::app()->db->getLastInsertID() > 0){	// means that the last operation was INSERT
            $query = "INSERT INTO ".$thissurvey['tablename']."_timings" ." ("
            ."id, interviewtime, " .$setField .")"
            ." VALUES (" .$_SESSION['srid'] ."," .$passedTime ."," .$passedTime.")";
        }else{	// UPDATE
            $query = "UPDATE {$thissurvey['tablename']}_timings SET "
            ."interviewtime = interviewtime" ." + " .$passedTime .","
            .$setField." = ".$setField." + ".$passedTime
            ." WHERE id = " .$_SESSION['srid'];
        }
        db_execute_assoc($query);
    }
}
