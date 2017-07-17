<?php
/**
 * Needs an array now
 * $breadCrumbArray = array('oSurvey'=>$oSurvey, 'oQuestionGroup' => $oQuestionGroup, 'oQuestion' => $oQuestion, 'sSubaction' =>$sSubaction,  'active'=>$active))
 */


$oSurvey = Survey::model()->findByPk((int) $surveyid);
$oQuestion = isset($qid) ? @Question::model()->find('qid=:qid',['qid'=> $qid]) : null;
$oQuestionGroup = isset($gid) ? @QuestionGroup::model()->find('gid=:gid',['gid'=> $gid]) : null;

$subaction = isset($subaction) ? $subaction : null;

$breadCrumbArray = array(
    'oSurvey' => $oSurvey,
    'oQuestion' => $oQuestion,
    'oQuestionGroup' => $oQuestionGroup,
    'sSubaction' => $subaction,
    'title' => (isset($title_bar['title']) ? $title_bar['title'] : ' ')
    //'active' => ($oQuestion != null ? $oQuestion->title : ( $oQuestionGroup != null ? $oQuestionGroup->group_name : $oSurvey->defaultlanguage->surveyls_title ) )
);

$breadCrumbArray['extraClass'] = "title-bar-breadcrumb";
?>
<div class='menubar surveymanagerbar'>
    <?php  $this->renderPartial('/admin/survey/breadcrumb', $breadCrumbArray); ?>    
</div>

