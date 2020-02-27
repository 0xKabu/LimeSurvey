<?php

/**
 * Class LSBaseController
 *
 * this controller will have all the necessary methods from the old AdminController
 *
 *
 */
class LSBaseController extends LSYii_Controller
{
    //todo: this variable should go to the questioneditor controller when refactoring it ...no need to declare it here ...
    /** @var null  this is needed for the preview rendering inside the questioneditor */
    public $sTemplate = null;

    /** @var array  import for all new controllers/actions (REFACTORING) to pass data before rendering the content*/
    public $aData = [];

    /** @var int userId of the logged in user */
    protected $user_id = 0; //todo: do we really need this here ?? why?

    /**
     * Initialises this controller, does some basic checks and setups
     *
     * @access protected
     * @return void
     * @throws CException
     */
    protected function _init()
    {
        parent::_init();

        //REFACTORING we have to set the main layout here (it's in /view/layouts/main)
        $this->layout = 'main';

        App()->getComponent('bootstrap');
        $this->sessionControl();

        $this->user_id = Yii::app()->user->getId();

        if (!Yii::app()->getConfig("surveyid")) {Yii::app()->setConfig("surveyid", returnGlobal('sid')); }         //SurveyID
        if (!Yii::app()->getConfig("surveyID")) {Yii::app()->setConfig("surveyID", returnGlobal('sid')); }         //SurveyID
        if (!Yii::app()->getConfig("ugid")) {Yii::app()->setConfig("ugid", returnGlobal('ugid')); }                //Usergroup-ID
        if (!Yii::app()->getConfig("gid")) {Yii::app()->setConfig("gid", returnGlobal('gid')); }                   //GroupID
        if (!Yii::app()->getConfig("qid")) {Yii::app()->setConfig("qid", returnGlobal('qid')); }                   //QuestionID
        if (!Yii::app()->getConfig("lid")) {Yii::app()->setConfig("lid", returnGlobal('lid')); }                   //LabelID
        if (!Yii::app()->getConfig("code")) {Yii::app()->setConfig("code", returnGlobal('code')); }                // ??
        if (!Yii::app()->getConfig("action")) {Yii::app()->setConfig("action", returnGlobal('action')); }          //Desired action
        if (!Yii::app()->getConfig("subaction")) {Yii::app()->setConfig("subaction", returnGlobal('subaction')); } //Desired subaction
        if (!Yii::app()->getConfig("editedaction")) {Yii::app()->setConfig("editedaction", returnGlobal('editedaction')); } // for html editor integration

        // This line is needed for template editor to work
        $oAdminTheme = AdminTheme::getInstance();

        Yii::setPathOfAlias('lsadminmodules', Yii::app()->getConfig('lsadminmodulesrootdir') );
    }

    /**
     * This part comes from _renderWrappedTemplate (not the best way to refactoring, but a temporary solution)
     *
     * todo REFACTORING find all actions that set $aData['surveyid'] and change the layout directly in the action
     *
     * @param string $view
     * @return bool
     */
    protected function beforeRender($view)
    {
        //this lines come from _renderWarppedTemplate
        //todo: this should be moved to the new questioneditor controller when it is being refactored
        if (!empty($aData['surveyid'])) {
            $aData['oSurvey'] = Survey::model()->findByPk($aData['surveyid']);

            // Needed to evaluate EM expressions in question summary
            // See bug #11845
            LimeExpressionManager::SetSurveyId($aData['surveyid']);
            LimeExpressionManager::StartProcessingPage(false, true);

            $basePath = (string) Yii::getPathOfAlias('application.views.admin.super');
            $this->layout = $basePath.'/layout_insurvey.php';
        }

        return parent::beforeRender($view);
    }

    /**
     * Checks for action specific authorization and then executes an action
     *
     * @access public
     * @param string $action
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function run($action)
    {
        // Check if the DB is up to date
        if (Yii::app()->db->schema->getTable('{{surveys}}')) {
            $sDBVersion = getGlobalSetting('DBVersion');
        }
        if ((int) $sDBVersion < Yii::app()->getConfig('dbversionnumber') && $action != 'databaseupdate') {
            // Try a silent update first
            Yii::app()->loadHelper('update/updatedb');
            if (!db_upgrade_all(intval($sDBVersion), true)) {
                $this->redirect(array('/admin/databaseupdate/sa/db'));
            }
        }

        if ($action != "databaseupdate" && $action != "db") {
            if (empty($this->user_id) && $action != "authentication" && $action != "remotecontrol") {
                if (!empty($action) && $action != 'index') {
                    Yii::app()->session['redirect_after_login'] = $this->createUrl('/');
                }

                App()->user->setReturnUrl(App()->request->requestUri);

                // If this is an ajax call, don't redirect, but echo login modal instead
                $isAjax = isset($_GET['ajax']) && $_GET['ajax'];
                if ($isAjax && Yii::app()->user->getIsGuest()) {
                    Yii::import('application.helpers.admin.ajax_helper', true);
                    ls\ajax\AjaxHelper::outputNotLoggedIn();
                    return;
                }

                $this->redirect(array('/admin/authentication/sa/login'));
            } elseif (!empty($this->user_id) && $action != "remotecontrol") {
                if (Yii::app()->session['session_hash'] != hash('sha256',
                        getGlobalSetting('SessionName').Yii::app()->user->getName().Yii::app()->user->getId())) {
                    Yii::app()->session->clear();
                    Yii::app()->session->close();
                    $this->redirect(array('/admin/authentication/sa/login'));
                }
            }
        }

        parent::run($action);
    }

    /**
     * Load and set session vars
     *
     * todo REFACTORING see comments in mehtod
     *
     * @access protected
     * @return void
     */
    protected function sessionControl()
    {
        // From personal settings

        //todo this should go into specific controller action (atm /admin/user/sa/personalsettings)
        if (Yii::app()->request->getPost('action') == 'savepersonalsettings') {
            if (Yii::app()->request->getPost('lang') == 'auto') {
                $sLanguage = getBrowserLanguage();
            } else {
                $sLanguage = sanitize_languagecode(Yii::app()->request->getPost('lang'));
            }
            Yii::app()->session['adminlang'] = $sLanguage;
        }
        //todo end

        //todo this should be done only once per session and not everytime calling an action ...
        if (empty(Yii::app()->session['adminlang'])) {
            Yii::app()->session["adminlang"] = Yii::app()->getConfig("defaultlang");
        }
        Yii::app()->setLanguage(Yii::app()->session["adminlang"]);
        //todo end
    }

}
