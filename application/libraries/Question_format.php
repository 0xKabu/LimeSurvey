<?php
/**
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
 * $Id: survey.php 10158 2011-05-31 20:27:08Z tpartner $
 */

class Question_format {

 	function run($args) {

		global $surveyid, $thissurvey, $totalquestions, $token;

		extract($args);
		$CI =& get_instance();
		//$_SESSION = $CI->session->userdata;
		$dbprefix = $CI->db->dbprefix;
		$publicurl = base_url();
		$_POST = $CI->input->post();
		$allowmandbackwards = $CI->config->item("allowmandbackwards");


		//Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

		if (!isset($_SESSION['step'])) {$_SESSION['step']=0;}
		if (!isset($_SESSION['totalsteps'])) {$_SESSION['totalsteps']=0;}
		if (!isset($_SESSION['maxstep'])) {$_SESSION['maxstep']=0;}
		$_SESSION['prevstep']=$_SESSION['step'];

		//Move current step
		if (isset($move) && $move == "moveprev" && ($thissurvey['allowprev']=='Y' || $thissurvey['allowjumps']=='Y') && $_SESSION['step'] > 0) {$_SESSION['step'] = $thisstep-1;}
		if (isset($move) && $move == "movenext")
		{
		    if ($_SESSION['step']==$thisstep)
		        $_SESSION['step'] = $thisstep+1;
		}
		if (isset($move) && bIsNumericInt($move) && $thissurvey['allowjumps']=='Y')
		{
		    $move = (int)$move;
		    if ($move > 0 && (($move <= $_SESSION['step']) || (isset($_SESSION['maxstep']) && $move <= $_SESSION['maxstep'])))
		        $_SESSION['step'] = $move;
		}


		// We do not keep the participant session anymore when the same browser is used to answer a second time a survey (let's think of a library PC for instance).
		// Previously we used to keep the session and redirect the user to the
		// submit page.
		//if (isset($_SESSION['finished'])) {$move="movesubmit"; }

        // TODO - also check relevance
		//CHECK IF ALL MANDATORY QUESTIONS HAVE BEEN ANSWERED ############################################
		//First, see if we are moving backwards or doing a Save so far, and its OK not to check:
		if ($allowmandbackwards==1 && (
		    (isset($move) && ($move == "moveprev" || (is_int($move) && $_SESSION['prevstep'] == $_SESSION['maxstep']))) ||
		    (isset($_POST['saveall']) && $_POST['saveall'] == $clang->gT("Save your responses so far"))))
		{
		    $backok="Y";
		}
		else
		{
		    $backok="N";
		}

		//Now, we check mandatory questions if necessary
		//CHECK IF ALL CONDITIONAL MANDATORY QUESTIONS THAT APPLY HAVE BEEN ANSWERED
        // TMSW Conditions->Relevance:  EM will handle mandatories
		$notanswered=addtoarray_single(checkmandatorys($move,$backok),checkconditionalmandatorys($move,$backok));

		//CHECK INPUT
        // TMSW Conditions->Relevance:  EM will handle most/all validation
		$notvalidated=aCheckInput($surveyid, $move, $backok);

		// CHECK UPLOADED FILES
		$filenotvalidated = checkUploadedFileValidity($surveyid, $move, $backok);

        $redata = compact(array_keys(get_defined_vars()));
		//SEE IF $surveyid EXISTS ####################################################################
		if ($surveyExists <1)
		{
		    sendcacheheaders();
		    doHeader();
		    //SURVEY DOES NOT EXIST. POLITELY EXIT.
		    echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"),array(),$redata,'Question_format[89]');
		    echo "\t<center><br />\n";
		    echo "\t".$clang->gT("Sorry. There is no matching survey.")."<br /></center>&nbsp;\n";
		    echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"),array(),$redata,'Question_format[92]');
		    doFooter();
		    exit;
		}

		//RUN THIS IF THIS IS THE FIRST TIME
		if (!$_SESSION['step'])
		{
		    $totalquestions = buildsurveysession($surveyid);
		    if(isset($thissurvey['showwelcome']) && $thissurvey['showwelcome'] == 'N') {
		        //If explicitply set, hide the welcome screen
		        $_SESSION['step'] = 1;
		    } else {
		        display_first_page();
		        exit;
		    }
		}

		//******************************************************************************************************
		//PRESENT SURVEY
		//******************************************************************************************************

		//GET GROUP DETAILS

		if ($_SESSION['step'] == "0") {$currentquestion=$_SESSION['step'];}
		else {$currentquestion=$_SESSION['step']-1;}

		$ia=$_SESSION['fieldarray'][$currentquestion];

		$ia[]=$_SESSION['step'];

		list($newgroup, $gid, $groupname, $groupdescription, $gl)=self::_checkIfNewGroup($ia);

		// MANAGE CONDITIONAL QUESTIONS AND HIDDEN QUESTIONS
		$qidattributes=getQuestionAttributeValues($ia[0]);
		if ($qidattributes===false)  // Question was deleted
		{
		    $qidattributes['hidden']==1;    //Workaround to skip the question if it was deleted while the survey is running in test mode
		}
		$conditionforthisquestion=$ia[7];
		$questionsSkipped=0;

        LimeExpressionManager::StartProcessingPage($thissurvey['allowjumps']=='Y');
        LimeExpressionManager::StartProcessingGroup($gid,($thissurvey['anonymized']!="N"),$thissurvey['sid']);
        // Skip questions that are irrelevant
        // TMSW Conditions->Relevance:  LEM->GetNextRelevantSet() will handle navigation, saving / NULLing values along the way
        if (isset($qidattributes['relevance']))
        {
            $relevant = LimeExpressionManager::ProcessRelevance($qidattributes['relevance']);
//            $relevant = LimeExpressionManager::QuestionIsRelevant($ia[0]);
            if (!$relevant) {
                // TODO skip the question - need more elegant solution
                echo 'IRRELEVANT QUESTION!';
            }
        }

		while ($conditionforthisquestion == "Y" || $qidattributes['hidden']==1) //IF CONDITIONAL, CHECK IF CONDITIONS ARE MET; IF HIDDEN MOVE TO NEXT
		{
		    // this is a while, not an IF because if we skip the question we loop on the next question, see below
		    if (checkquestionfordisplay($ia[0]) === true && $qidattributes['hidden']==0)
		    {
		        // question will be displayed we set conditionforthisquestion to N here
		        // because it is used later to select style=display:'' for the question
		        $conditionforthisquestion="N";
		    }
		    else
		    {
		        $questionsSkipped++;
		        if ($_SESSION['prevstep'] <= $_SESSION['step'])
		        {
		            $currentquestion++;
		            if(isset($_SESSION['fieldarray'][$currentquestion]))
		            {
		                $ia=$_SESSION['fieldarray'][$currentquestion];
		            }
		            if ($_SESSION['step']>=$_SESSION['totalsteps'])
		            {
		                $move="movesubmit";
		                submitanswer(); // complete this answer (submitdate)
		                break;
		            }
		            $_SESSION['step']++;
		            foreach ($_SESSION['grouplist'] as $gl)
		            {
		                if ($gl[0] == $ia[5])
		                {
		                    $gid=$gl[0];
		                    $groupname=$gl[1];
		                    $groupdescription=$gl[2];
		                    if (auto_unescape($_POST['lastgroupname']) != strip_tags($groupname) && trim($groupdescription)!='') {$newgroup = "Y";} else {$newgroup == "N";}
		                }
		            }
		        }
		        else
		        {
                    if ($currentquestion > 0)
                    {
		            $currentquestion--; // if we reach -1, this means we must go back to first page
                        if(isset($_SESSION['fieldarray'][$currentquestion]))
		            {
                            $ia=$_SESSION['fieldarray'][$currentquestion];
                        }
		                $_SESSION['step']--;
		            }
                    else
		            {
		                $_SESSION['step']=0;
		                display_first_page();
		                exit;
		            }
		        }
		        // because we skip this question, we need to loop on the same condition 'check-block'
		        //  with the new question (we have overwritten $ia)
		        $conditionforthisquestion=$ia[7];
		        $qidattributes=getQuestionAttributeValues($ia[0]);
		    }
		} // End of while conditionforthisquestion=="Y"

		//Setup an inverted fieldnamesInfo for quick lookup of field answers.
		$aFieldnamesInfoInv = aArrayInvert($_SESSION['fieldnamesInfo']);
		if ($_SESSION['step'] > $_SESSION['maxstep'])
		{
		    $_SESSION['maxstep'] = $_SESSION['step'];
		}

		//SUBMIT
		if ((isset($move) && $move == "movesubmit")  && (!isset($notanswered) || !$notanswered)  && (!isset($notvalidated) || !$notvalidated ) && (!isset($filenotvalidated) || !$filenotvalidated))
		{
		    setcookie ("limesurvey_timers", "", time() - 3600);// remove the timers cookies
		    if ($thissurvey['refurl'] == "Y")
		    {
		        if (!in_array("refurl", $_SESSION['insertarray'])) //Only add this if it doesn't already exist
		        {
		            $_SESSION['insertarray'][] = "refurl";
		        }
		        //        $_SESSION['refurl'] = $_SESSION['refurl'];
		    }


		    //COMMIT CHANGES TO DATABASE
		    if ($thissurvey['active'] != "Y")
		    {
		        //if($thissurvey['printanswers'] != 'Y' && $thissurvey['usecookie'] != 'Y' && $tokensexist !=1)
		        if ($thissurvey['assessments']== "Y")
		        {
		            $assessments = doAssessment($surveyid);
		        }
		        $thissurvey['surveyls_url']=dTexts::run($thissurvey['surveyls_url']);

		        if($thissurvey['printanswers'] != 'Y')
		        {
		            killSession();
		        }

		        sendcacheheaders();
		        doHeader();

                $redata = compact(array_keys(get_defined_vars()));
		        echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"),array(),$redata,'Question_format[248]');

		        //Check for assessments
		        if ($thissurvey['assessments']== "Y" && $assessments)
		        {
		            echo templatereplace(file_get_contents("$thistpl/assessment.pstpl"),array(),$redata,'Question_format[253]');
		        }

		        $completed = $thissurvey['surveyls_endtext'];
		        $completed .= "<br /><strong><font size='2' color='red'>".$clang->gT("Did Not Save")."</font></strong><br /><br />\n\n";
		        $completed .= $clang->gT("Your survey responses have not been recorded. This survey is not yet active.")."<br /><br />\n";

		        if ($thissurvey['printanswers'] == 'Y')
		        {
		            // ClearAll link is only relevant for survey with printanswers enabled
		            // in other cases the session is cleared at submit time
		            $completed .= "<a href='{$publicurl}/index.php?sid=$surveyid&amp;move=clearall'>".$clang->gT("Clear Responses")."</a><br /><br />\n";
		        }

		    }
		    else //THE FOLLOWING DEALS WITH SUBMITTING ANSWERS AND COMPLETING AN ACTIVE SURVEY
		    {
		        if ($thissurvey['usecookie'] == "Y" && $tokensexist != 1) //don't use cookies if tokens are being used
		        {
		            $cookiename="PHPSID".returnglobal('sid')."STATUS";
		            setcookie("$cookiename", "COMPLETE", time() + 31536000); //Cookie will expire in 365 days
		        }

		        //Before doing the "templatereplace()" function, check the $thissurvey['url']
		        //field for limereplace stuff, and do transformations!
		        $thissurvey['surveyls_url']=dTexts::run($thissurvey['surveyls_url']);
		        $thissurvey['surveyls_url']=passthruReplace($thissurvey['surveyls_url'], $thissurvey);

                $redata = compact(array_keys(get_defined_vars()));
		        $content='';
		        $content .= templatereplace(file_get_contents("$thistpl/startpage.pstpl"),array(),$redata,'Question_format[283]');

		        //Check for assessments
		        if ($thissurvey['assessments']== "Y")
		        {
		            $assessments = doAssessment($surveyid);
		            if ($assessments)
		            {
                        $redata = compact(array_keys(get_defined_vars()));
		                $content .= templatereplace(file_get_contents("$thistpl/assessment.pstpl"),array(),$redata,'Question_format[292]');
		            }
		        }


		        if (trim(FlattenText($thissurvey['surveyls_endtext']))=='')
		        {
		            $completed = "<br /><span class='success'>".$clang->gT("Thank you!")."</span><br /><br />\n\n"
		            . $clang->gT("Your survey responses have been recorded.")."<br /><br />\n";
		        }
		        else
		        {
		            $completed = $thissurvey['surveyls_endtext'];
		        }

		        // Link to Print Answer Preview  **********
		        if ($thissurvey['printanswers']=='Y')
		        {
		            $completed .= "<br /><br />"
		            ."<a class='printlink' href='".site_url('printanswers/view/'.$surveyid)."' target='_blank'>"
		            .$clang->gT("Click here to print your answers.")
		            ."</a><br />\n";
		        }
		        //*****************************************

		        if ($thissurvey['publicstatistics']=='Y' && $thissurvey['printanswers']=='Y') {$completed .='<br />'.$clang->gT("or");}

		        // Link to Public statistics  **********
		        if ($thissurvey['publicstatistics']=='Y')
		        {
		            $completed .= "<br /><br />"
		            ."<a class='publicstatisticslink' href='statistics_user.php?sid=$surveyid' target='_blank'>"
		            .$clang->gT("View the statistics for this survey.")
		            ."</a><br />\n";
		        }
		        //*****************************************


		        //Update the token if needed and send a confirmation email
		        if (isset($clienttoken) && $clienttoken)
		        {
		            submittokens();
		        }

		        //Send notification to survey administrator
		        SendSubmitNotifications();

		        $_SESSION['finished']=true;
		        $_SESSION['sid']=$surveyid;

		        if (isset($thissurvey['autoredirect']) && $thissurvey['autoredirect'] == "Y" && $thissurvey['surveyls_url'])
		        {
		            //Automatically redirect the page to the "url" setting for the survey
		            $url = dTexts::run($thissurvey['surveyls_url']);
		            $url = passthruReplace($url, $thissurvey);
		            $url=str_replace("{SAVEDID}",$saved_id, $url);           // to activate the SAVEDID in the END URL
		            $url=str_replace("{TOKEN}",$clienttoken, $url);          // to activate the TOKEN in the END URL
		            $url=str_replace("{SID}", $surveyid, $url);              // to activate the SID in the END URL
		            $url=str_replace("{LANG}", $clang->getlangcode(), $url); // to activate the LANG in the END URL

		            header("Location: {$url}");
		        }

		        //if($thissurvey['printanswers'] != 'Y' && $thissurvey['usecookie'] != 'Y' && $tokensexist !=1)
		        if($thissurvey['printanswers'] != 'Y')
		        {
		            killSession();
		        }

		        doHeader();
		        if (isset($content)) {echo $content;}

		    }
            $redata = compact(array_keys(get_defined_vars()));
		    echo templatereplace(file_get_contents("$thistpl/completed.pstpl"),array(),$redata,'Question_format[366]');

		    echo "\n<br />\n";
		    echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"),array(),$redata,'Question_format[369]');
		    doFooter();
		    exit;
		}


		if ($questionsSkipped == 0 && $newgroup == "Y" && isset($move) && $move == "moveprev" && (isset($_POST['grpdesc']) && $_POST['grpdesc']=="Y")) //a small trick to manage moving backwards from a group description
		{
		    //This does not work properly in all instances.
		    $currentquestion++;
		    $ia=$_SESSION['fieldarray'][$currentquestion];
		    $_SESSION['step']++;
		}

		list($newgroup, $gid, $groupname, $groupdescription, $gl)=self::_checkIfNewGroup($ia);

		//Check if current page is for group description only
		$bIsGroupDescrPage = false;
		if ($newgroup == "Y" && trim($groupdescription)!='' &&
		    (isset($move) && $move != "moveprev" && !is_int($move)) &&
		    $_SESSION['maxstep'] == $_SESSION['step'])
		{
		    // This is a group description page
		    //  - $ia contains next question description,
		    //    but his question is not displayed, it is only used to know current group
		    //  - in this case answers' inputnames mustn't be added to filednames hidden input
		    $bIsGroupDescrPage = true;
		}



		//require_once("qanda.php");
		$CI->load->helper("qanda");
		setNoAnswerMode($thissurvey);
        // TMSW Conditions->Relevance:  EM will handle mandatories, so these arrays not needed
		$mandatorys=array();
		$mandatoryfns=array();
		$conmandatorys=array();
		$conmandatoryfns=array();
		$conditions=array();
		$inputnames=array();

		list($plus_qanda, $plus_inputnames)=retrieveAnswers($ia);
		if ($plus_qanda)
		{
		    $plus_qanda[] = $ia[4];
		    $plus_qanda[] = $ia[6]; // adds madatory identifyer for adding mandatory class to question wrapping div
		    $qanda[]=$plus_qanda;
		}
		if ($plus_inputnames && !$bIsGroupDescrPage)
		{
		    // Add answers' inputnames to $inputnames unless this is a group description page
		    $inputnames = addtoarray_single($inputnames, $plus_inputnames);
		}

		//Display the "mandatory" popup if necessary
		if (isset($notanswered) && $notanswered!=false)
		{
		    list($mandatorypopup, $popup)=mandatory_popup($ia, $notanswered);
		}

		//Display the "validation" popup if necessary
		if (isset($notvalidated))
		{
		    list($validationpopup, $vpopup)=validation_popup($ia, $notvalidated);
		}

		// Display the "file not valid" popup if necessary
		if (isset($filenotvalidated))
		{
		    list($filevalidationpopup, $fpopup) = file_validation_popup($ia, $filenotvalidated);
		}

		//Get list of mandatory questions
        // TMSW Conditions->Relevance:  not needed
		list($plusman, $pluscon)=create_mandatorylist($ia);
		if ($plusman !== null)
		{
		    list($plus_man, $plus_manfns)=$plusman;
		    $mandatorys=addtoarray_single($mandatorys, $plus_man);
		    $mandatoryfns=addtoarray_single($mandatoryfns, $plus_manfns);
		}
        // TMSW Conditions->Relevance:  not needed
		if ($pluscon !== null)
		{
		    list($plus_conman, $plus_conmanfns)=$pluscon;
		    $conmandatorys=addtoarray_single($conmandatorys, $plus_conman);
		    $conmandatoryfns=addtoarray_single($conmandatoryfns, $plus_conmanfns);
		}
		//Build an array containing the conditions that apply for this page
        // TMSW Conditions->Relevance:  not needed
		$plus_conditions=retrieveConditionInfo($ia); //Returns false if no conditions
		if ($plus_conditions)
		{
		    $conditions = addtoarray_single($conditions, $plus_conditions);
		}
		//------------------------END DEVELOPMENT OF QUESTION

		if ($thissurvey['showprogress'] == 'Y')
		{
		    $percentcomplete = makegraph($_SESSION['step'], $_SESSION['totalsteps']);
		}

		//READ TEMPLATES, INSERT DATA AND PRESENT PAGE
		sendcacheheaders();
		doHeader();

		if (isset($popup)) {echo $popup;}
		if (isset($vpopup)) {echo $vpopup;}
		if (isset($fpopup)) {echo $fpopup;}

        $redata = compact(array_keys(get_defined_vars()));
		echo templatereplace(file_get_contents("$thistpl/startpage.pstpl"),array(),$redata,'Question_format[477]');


		echo "\n<form method='post' action='".site_url("survey")."' id='limesurvey' name='limesurvey' autocomplete='off'>\n";
        echo sDefaultSubmitHandler();

        $redata = compact(array_keys(get_defined_vars()));
		//PUT LIST OF FIELDS INTO HIDDEN FORM ELEMENT
		echo "\n\n<!-- INPUT NAMES -->\n";
		echo "\t<input type='hidden' name='fieldnames' value='";
		echo implode("|", $inputnames);
		echo "' id='fieldnames'  />\n";
		echo "\n\n<!-- START THE SURVEY -->\n";
		echo templatereplace(file_get_contents("$thistpl/survey.pstpl"),array(),$redata,'Question_format[503]');

		if ($bIsGroupDescrPage)
		{
		    $presentinggroupdescription = "yes";
		    echo "\n\n<!-- START THE GROUP DESCRIPTION -->\n";
		    echo "\t<input type='hidden' name='grpdesc' value='Y' id='grpdesc' />\n";
		    echo templatereplace(file_get_contents("$thistpl/startgroup.pstpl"),array(),$redata,'Question_format[510]');
		    echo "\n";

		    //if ($groupdescription)
		    //{
		    echo templatereplace(file_get_contents("$thistpl/groupdescription.pstpl"),array(),$redata,'Question_format[515]');
		    //}
		    echo "\n";

		    echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n";
		    echo "\t<script type='text/javascript'>\n";
		    echo "\t<!--\n";
		    echo "function noop_checkconditions(value, name, type)\n";
		    echo "\t{\n";
		    echo "\t}\n";
		    echo "function checkconditions(value, name, type)\n";
		    echo "\t{\n";
		    echo "\t}\n";
		    echo "\t//-->\n";
		    echo "\t</script>\n\n";
		    echo "\n\n<!-- END THE GROUP -->\n";
		    echo templatereplace(file_get_contents("$thistpl/endgroup.pstpl"),array(),$redata,'Question_format[531]');
		    echo "\n";

		    $_SESSION['step']--;
		}
		else
		{
		    echo "\n\n<!-- START THE GROUP -->\n";
		    //	foreach(file("$thistpl/startgroup.pstpl") as $op)
		    //	{
		    //		echo "\t".templatereplace($op);
		    //	}
		    echo templatereplace(file_get_contents("$thistpl/startgroup.pstpl"),array(),$redata,'Question_format[543]');
		    echo "\n";

		    echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n";
		    echo "\t<script type='text/javascript'>\n";
		    echo "\t<!--\n";
		    echo "function noop_checkconditions(value, name, type)\n";
		    echo "\t{\n";
		    echo "\t}\n";
		    echo "function checkconditions(value, name, type)\n";
		    echo "\t{\n";
		    echo "\t}\n";
		    echo "\t//-->\n";
		    echo "\t</script>\n\n";

		    //Display the "mandatory" message on page if necessary
		    if (isset($showpopups) && $showpopups == 0 && isset($notanswered) && $notanswered == true)
		    {
		        echo "<p><span class='errormandatory'>" . $clang->gT("One or more mandatory questions have not been answered. You cannot proceed until these have been completed.") . "</span></p>";
		    }

		    //Display the "validation" message on page if necessary
		    if (isset($showpopups) && $showpopups == 0 && isset($notvalidated) && $notvalidated == true)
		    {
		        echo "<p><span class='errormandatory'>" . $clang->gT("One or more questions have not been answered in a valid manner. You cannot proceed until these answers are valid.") . "</span></p>";
		    }

		    // Display the File Validation message on page if necessary
		    if (isset($showpopups) && $showpopups == 0 && isset($filenotvalidated) && $filenotvalidated == true)
		    {
		        echo "<p><span class='errormandatory'>". $clang->gT("One or more uploaded files do not satisfy the criteria") . "</span></p>";
		    }


		    echo "\n\n<!-- PRESENT THE QUESTIONS -->\n";
		    if (is_array($qanda))
		    {
		        foreach ($qanda as $qa)
		        {
					echo "<input type='hidden' name='lastanswer' value='$qa[7]' id='lastanswer' />\n";
		            $q_class = question_class($qa[8]); // render question class (see common.php)

		            if ($qa[9] == 'Y')
		            {
		                $man_class = ' mandatory';
		            }
		            else
		            {
		                $man_class = '';
		            }

		            // Fixed by lemeur: can't rely on javascript checkconditions with
		            // question-by-question display to hide/show conditionnal questions
		            // as conditions are evaluated with php code
		            // Let's use result from previous condition eval instead
		            // (note there is only 1 question, $conditionforthisquestion is the result from
		            // condition eval in php)
		            //			if ($qa[3] != 'Y') {$n_q_display = '';} else { $n_q_display = ' style="display: none;"';}
                    //
                    // TMSW Conditions->Relevance:  EM will handle server and client-side control of question display.

		            if ($conditionforthisquestion != 'Y') {$n_q_display = '';} else { $n_q_display = ' style="display: none;"';}

		            $question= $qa[0];
		            //===================================================================
		            // The following four variables offer the templating system the
		            // capacity to fully control the HTML output for questions making the
		            // above echo redundant if desired.
		            $question['essentials'] = 'id="question'.$qa[4].'"'.$n_q_display;
		            $question['class'] = $q_class;
		            $question['man_class'] = $man_class;
		            $question['code'] = $qa[5];
		            $question['sgq']=$qa[7];
		            //===================================================================
		            $answer=$qa[1];
		            $help=$qa[2];

		            $question_template = file_get_contents($thistpl.'/question.pstpl');

                    $redata = compact(array_keys(get_defined_vars()));
		            if( preg_match( '/\{QUESTION_ESSENTIALS\}/' , $question_template ) === false || preg_match( '/\{QUESTION_CLASS\}/' , $question_template ) === false )
		            {
		                // if {QUESTION_ESSENTIALS} is present in the template but not {QUESTION_CLASS} remove it because you don't want id="" and display="" duplicated.
		                $question_template = str_replace( '{QUESTION_ESSENTIALS}' , '' , $question_template );
		                $question_template = str_replace( '{QUESTION_CLASS}' , '' , $question_template );
		                echo '
			<!-- NEW QUESTION -->
						<div id="question'.$qa[4].'" class="'.$q_class.$man_class.'"'.$n_q_display.'>
		';
		                echo templatereplace($question_template,array(),$redata,'Question_format[629]');
		                echo '
						</div>
		';
		            }
		            else
		            {
		                echo templatereplace($question_template,array(),$redata,'Question_format[636]');
		            };
		        }
		    }
            $redata = compact(array_keys(get_defined_vars()));
		    echo "\n\n<!-- END THE GROUP -->\n";
		    echo templatereplace(file_get_contents("$thistpl/endgroup.pstpl"),array(),$redata,'Question_format[642]');
		    echo "\n";
		}
        LimeExpressionManager::FinishProcessingGroup();
        LimeExpressionManager::FinishProcessingPage();
        echo LimeExpressionManager::GetRelevanceAndTailoringJavaScript();

		$navigator = surveymover();

        $redata = compact(array_keys(get_defined_vars()));
		echo "\n\n<!-- PRESENT THE NAVIGATOR -->\n";
		echo templatereplace(file_get_contents("$thistpl/navigator.pstpl"),array(),$redata,'Question_format[653]');
		echo "\n";

		if ($thissurvey['active'] != "Y")
		{
		    echo "<p style='text-align:center' class='error'>".$clang->gT("This survey is currently not active. You will not be able to save your responses.")."</p>\n";
		}

		echo "\n";

		if($thissurvey['allowjumps']=='Y' && !$bIsGroupDescrPage)
		{
		    echo "\n\n<!-- PRESENT THE INDEX -->\n";

		    $iLastGrp = null;

		    echo '<div id="index"><div class="container"><h2>' . $clang->gT("Question index") . '</h2>';
		    for($v = 0, $n = 0; $n != $_SESSION['maxstep']; ++$n)
		    {
		        $ia = $_SESSION['fieldarray'][$n];
		        $qidattributes=getQuestionAttributeValues($ia[0], $ia[4]);
		        if($qidattributes['hidden']==1 || !checkquestionfordisplay($ia[0]))
		            continue;

		        $sText = FlattenText($ia[3],true);
		        $bAnsw = bCheckQuestionForAnswer($ia[1], $aFieldnamesInfoInv);

		        if($iLastGrp != $ia[5])
		        {
		            $iLastGrp = $ia[5];
		            foreach ($_SESSION['grouplist'] as $gl)
		            {
		                if ($gl[0] == $iLastGrp)
		                {
		                    echo '<h3>' . htmlspecialchars(strip_tags($gl[1]),ENT_QUOTES,'UTF-8') . "</h3>";
		                    break;
		                }
		            }
		        }

		        ++$v;

		        $class = ($n == $_SESSION['step'] - 1? 'current': ($bAnsw? 'answer': 'missing'));
		        if($v % 2) $class .= " odd";

		        $s = $n + 1;
		        echo "<div class=\"row $class\" onclick=\"javascript:document.limesurvey.move.value = '$s'; document.limesurvey.submit();\"><span class=\"hdr\">$v</span><span title=\"$sText\">$sText</span></div>";
		    }

		    if($_SESSION['maxstep'] == $_SESSION['totalsteps'])
		    {
		        echo "<input class='submit' type='submit' accesskey='l' onclick=\"javascript:document.limesurvey.move.value = 'movesubmit';\" value=' "
		            . $clang->gT("Submit")." ' name='move2' />\n";
		    }

		    echo '</div></div>';
		}

        // TMSW Conditions->Relevance:  EM already does this

		if (isset($conditions) && is_array($conditions) && count($conditions) != 0)
		{
		    //if conditions exist, create hidden inputs for 'previously' answered questions
		    // Note that due to move 'back' possibility, there may be answers from next pages
		    // However we make sure that no answer from this page are inserted here
		    foreach (array_keys($_SESSION) as $SESak)
		    {
		        if (in_array($SESak, $_SESSION['insertarray']) && !in_array($SESak, $inputnames))
		        {
		            echo "<input type='hidden' name='java$SESak' id='java$SESak' value='" . htmlspecialchars($_SESSION[$SESak],ENT_QUOTES) . "' />\n";
		        }
		    }
		}


		//SOME STUFF FOR MANDATORY QUESTIONS
		if (remove_nulls_from_array($mandatorys) && $newgroup != "Y")
		{
		    $mandatory=implode("|", remove_nulls_from_array($mandatorys));
		    echo "<input type='hidden' name='mandatory' value='$mandatory' id='mandatory' />\n";
		}
		if (remove_nulls_from_array($conmandatorys))
		{
		    $conmandatory=implode("|", remove_nulls_from_array($conmandatorys));
		    echo "<input type='hidden' name='conmandatory' value='$conmandatory' id='conmandatory' />\n";
		}
		if (remove_nulls_from_array($mandatoryfns))
		{
		    $mandatoryfn=implode("|", remove_nulls_from_array($mandatoryfns));
		    echo "<input type='hidden' name='mandatoryfn' value='$mandatoryfn' id='mandatoryfn' />\n";
		}
		if (remove_nulls_from_array($conmandatoryfns))
		{
		    $conmandatoryfn=implode("|", remove_nulls_from_array($conmandatoryfns));
		    echo "<input type='hidden' name='conmandatoryfn' value='$conmandatoryfn' id='conmandatoryfn' />\n";
		}

		echo "<input type='hidden' name='thisstep' value='{$_SESSION['step']}' id='thisstep' />\n";
		echo "<input type='hidden' name='sid' value='$surveyid' id='sid' />\n";
		echo "<input type='hidden' name='start_time' value='".time()."' id='start_time' />\n";
		if(!isset($token)) $token = "";
		echo "<input type='hidden' name='token' value='$token' id='token' />\n";
		echo "<input type='hidden' name='lastgroupname' value='".htmlspecialchars(strip_tags($groupname),ENT_QUOTES,'UTF-8')."' id='lastgroupname' />\n";
		echo "</form>\n";
		//foreach(file("$thistpl/endpage.pstpl") as $op)
		//{
		//	echo templatereplace($op);
		//}
        $redata = compact(array_keys(get_defined_vars()));
		echo templatereplace(file_get_contents("$thistpl/endpage.pstpl"),array(),$redata,'Question_format[760]');
		doFooter();

	}

	function _checkIfNewGroup($ia)
	{
	    foreach ($_SESSION['grouplist'] as $gl)
	    {
	        if ($gl[0] == $ia[5])
	        {
	            $gid=$gl[0];
	            $groupname=$gl[1];
	            $groupdescription=$gl[2];
	            if (isset($_POST['lastgroupname']) && auto_unescape($_POST['lastgroupname']) != strip_tags($groupname) && $groupdescription)
	            {
	                $newgroup = "Y";
	            }
	            else
	            {
	                $newgroup = "N";
	            }
	            if (!isset($_POST['lastgroupname']) && isset($move)) {$newgroup="Y";}
	        }
	    }
	    return array($newgroup, $gid, $groupname, $groupdescription, $gl);
	}
}
