<?php
/**
 * This contains a list of survey-related admin views that we can loop for testing
 * // TODO not complete views list
 */

return [
    // NB the import_id is needed only for the first item OR if you need to change the survey you want to work with
    'surveySummary' =>['route'=>'survey/sa/view/surveyid/{SID}','import_id'=>'454287'],
    'surveyGeneralSettings' =>['route'=>'survey/sa/rendersidemenulink/subaction/generalsettings/surveyid/{SID}'],
    'surveyTexts' =>['route'=>'survey/sa/rendersidemenulink/subaction/surveytexts/surveyid/{SID}'],
    'surveyTemplateOptionsUpdate' =>['route'=>'templateoptions/sa/updatesurvey/surveyid/{SID}/gsid/1'],
    'surveyPresentationOptions' =>['route'=>'survey/sa/rendersidemenulink/subaction/presentation/surveyid/{SID}'],

    // FIXME these FAIL !!
    //'surveyParticipantsIndex' =>['route'=>'tokens/sa/index/surveyid/{SID}'],
    //'surveyPublicationOptions' =>['route'=>'rendersidemenulink/subaction/publication/surveyid/{SID}'],

    'surveyPermissions' =>['route'=>'surveypermission/sa/view/surveyid/{SID}'],
    'surveyParticipantTokenOptions' =>['route'=>'survey/sa/rendersidemenulink/subaction/tokens/surveyid/{SID}'],
    'surveyQuotas' =>['route'=>'quotas/sa/index/surveyid/{SID}'],
];