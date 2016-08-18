<?php

use \ls\menu\MenuItem;
use \ls\menu\Menu;

require_once(__DIR__ . "/CintLinkAPI.php");
require_once(__DIR__ . "/model/CintLinkOrder.php");
require_once(__DIR__ . "/CintXml.php");

/**
 * CintLink integration to be able to buy respondents
 * from within LimeSurvey.
 *
 * About the Cint active flag: The flag is set to true
 * when user clicks "Pay now" on a Cint order. It is
 * deactivated when all orders are completed or cancelled.
 *
 * @since 2016-07-13
 * @author Olle Härstedt
 */
class CintLink extends \ls\pluginmanager\PluginBase
{
    static protected $description = 'Buy respondents inside LimeSurvey';
    static protected $name = 'CintLink';

    protected $storage = 'DbStorage';
    //protected $settings = array();

    /**
     * Key from Cint Link to access their widget
     *
     * @var string
     */
    private $cintApiKey = "7809687755495";  // Sandbox

    /**
     * This is the key handed to you from the
     * com_api Joomla component on limesurvey.org
     * after login, to access Rest plugins
     *
     * @var string
     */
    private $limesurveyOrgKey = "";

    /**
     * What URL to call for Rest API (limesurvey.org or limeservice.com for testing)
     *
     * @var string
     */
    //public $baseURL = "https://www.limesurvey.org/index.php?option=com_api";
    public static $baseURL = "https://www.limesurvey.org/index.php?option=com_api";

    public function init()
    {
        $this->subscribe('beforeActivate');  // Create db
        $this->subscribe('beforeAdminMenuRender');
        $this->subscribe('beforeToolsMenuRender');
        $this->subscribe('afterQuickMenuLoad');
        $this->subscribe('newDirectRequest');  // Ajax calls
        $this->subscribe('beforeControllerAction');  // To load Cint icon
        $this->subscribe('beforeSurveyDeactivate');
        $this->subscribe('beforeSurveyActivate');

        // Login session key from com_api at limesurvey.org
        $limesurveyOrgKey = Yii::app()->user->getState('limesurveyOrgKey');
        if (!empty($limesurveyOrgKey))
        {
            $this->limesurveyOrgKey = $limesurveyOrgKey;
        }
    }

    /**
     * Add database tables to store information from CintLink
     *
     * @return void
     */
    public function beforeActivate()
    {
        $oDB = Yii::app()->getDb();

        $tableDoesNotExist = $oDB->schema->getTable("{{plugin_cintlink_orders}}") === null;
        if ($tableDoesNotExist)
        {
            $this->createDatabase();
        }

        $this->fetchGlobalVariables();
    }

    /**
     * Creates database table for Cint plugin
     * @return void
     */
    protected function createDatabase()
    {
        $oDB = Yii::app()->getDb();
        $oDB->schemaCachingDuration = 0;  // Deactivate schema caching
        $oTransaction = $oDB->beginTransaction();
        try
        {
            $aFields = array(
                'url' => 'string(127) primary key',
                'sid' => 'int',  // Survey id
                'raw' => 'text',  // Order xml
                'country' => 'string(63)',
                'status' => 'string(15)',
                'ordered_by' => 'int',  // User id
                'deleted' => 'int DEFAULT 0',  // Soft delete
                'created' => 'datetime',
                'modified' => 'datetime',
            );
            $oDB->createCommand()->createTable('{{plugin_cintlink_orders}}', $aFields);
            $oTransaction->commit();
        }
        catch(Exception $e)
        {
            $oTransaction->rollback();
            // Activate schema caching
            $oDB->schemaCachingDuration = 3600;
            // Load all tables of the application in the schema
            $oDB->schema->getTables();
            // Clear the cache of all loaded tables
            $oDB->schema->refresh();
            $event = $this->getEvent();
            $event->set('success', false);
            $event->set(
                'message',
                $this->gT('An non-recoverable error happened during the update. Error details:')
                . "<p>"
                . htmlspecialchars($e->getMessage())
                . "</p>"
            );
        }
    }

    /**
     * Fetch the global variables from Cint, like number of children,
     * personal income etc.
     * @return void
     */
    protected function fetchGlobalVariables()
    {
        $cintXml = new CintXml($this->cintApiKey);
        $gv = $cintXml->getGlobalVariables();

        // Store raw XML in plugin settings
        $this->set('cint-global-variables', $gv);
    }

    /**
     * Used from model
     * @return SimpleXmlElement
     */
    public function getGlobalVariables()
    {
        return new SimpleXmlElement($this->get('cint-global-variables'));
    }

    /**
     * todo Place somewhere else
     */
    public function beforeToolsMenuRender()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        $href = Yii::app()->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'sidebody',
                'plugin' => 'CintLink',
                'method' => 'actionIndex',
                'surveyId' => $surveyId
            )
        );

        $menuItem = new MenuItem(array(
            'label' => $this->gT('CintLink'),
            'iconClass' => 'cintlink-icons cinticon',
            'href' => $href
        ));

        $event->append('menuItems', array($menuItem));
    }

    /**
     * @todo Maybe place somewhere else
     */
    public function beforeAdminMenuRender()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        $href = $this->api->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'fullpagewrapper',
                'plugin' => $this->getName(),
                'method' => 'actionIndexGlobal'
            )
        );

        // Return new menu
        $event = $this->getEvent();
        $event->append('extraMenus', array(
          new Menu(array(
            'label' => $this->gT('CintLink'),
            'href' => $href
          ))
        ));
    }

    /**
     * Add quick menu icon.
     * @return void
     */
    public function afterQuickMenuLoad()
    {
        // Do nothing if QuickMenu plugin is not active
        $quickMenuExistsAndIsActive = $this->api->pluginExists('QuickMenu')
            && $this->api->pluginIsActive('QuickMenu');
        if (!$quickMenuExistsAndIsActive)
        {
            return;
        }

        $event = $this->getEvent();
        $settings = $this->getPluginSettings(true);

        $data = $event->get('aData');
        $activated = $data['activated'];
        $surveyId = $data['surveyid'];

        $href = Yii::app()->createUrl(
            'admin/pluginhelper',
            array(
                'sa' => 'sidebody',
                'plugin' => 'CintLink',
                'method' => 'actionIndex',
                'surveyId' => $surveyId
            )
        );

        $button = new QuickMenuButton(array(
            'name' => 'cintLink',
            'href' => $href,
            'tooltip' => $this->gT('CintLink'),
            'iconClass' => 'cintlink-icons cinticon',
            'neededPermission' => array('surveycontent', 'update')
        ));
        $db = Yii::app()->db;
        $userId = Yii::app()->user->getId();
        $orderings = QuickMenu::getOrder($userId);
        if (isset($orderings['cintLink']))
        {
            $button->setOrder($orderings['cintLink']);
        }

        $event->append('quickMenuItems', array($button));
    }

    /**
     * Register Cint icon css
     * Also check if user tried to pay Cint order and if survey is
     * active. Show warning message if not active.
     * @return void
     */
    public function beforeControllerAction() {
        // This CSS is always needed (icon)
        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/css');
        App()->clientScript->registerCssFile("$assetsUrl/cintlink.css");

        // Check if any Cint order is active
        $surveyId = Yii::app()->request->getParam('surveyId');
        $surveyId = empty($surveyId) ? Yii::app()->request->getParam('surveyid') : $surveyId;

        $this->checkCintActive($surveyId);
        $this->checkCintCompleted($surveyId);

        // Disable all tokens if user has any Cint order
        $this->disableTokens($surveyId);
    }

    /**
     * Check if any Cint order is active and show
     * warning message if survey is not active.
     *
     * @param int $surveyId
     * @return void
     */
    protected function checkCintActive($surveyId)
    {
        $this->log('checkCintActive begin');

        // No need to nag when user tries to activate survey
        if ($this->userIsActivatingSurvey())
        {
            return;
        }

        $this->log('surveyId = ' . $surveyId);
        // Fetch Cint active flag
        $cintActive = $this->get('cint_active_' . $surveyId);
        $this->log('cintActive = ' . $cintActive);

        if ($cintActive)
        {
            // Include Javascript that will update the orders async
            $this->renderCommonJs($surveyId);  // TODO: This is rendered twice on Cint views
            $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/js');
            App()->clientScript->registerScriptFile("$assetsUrl/checkOrders.js");

            $survey = Survey::model()->findByPk($surveyId);
            $orders = CintLinkOrder::getOrders($surveyId);
            if (empty($orders))
            {
                // Possible?
                $this->log('Internal error: beforeControllerAction: Looking for Cint orders but found nothing');
                $this->set('cint_active_' . $surveyId, false);
                return;
            }

            // Check if any order is paid and/or live
            $anyOrderIsActive = CintLinkOrder::anyOrderHasStatus($orders, array('new', 'live', 'hold'));
            $surveyIsActive = $survey->active == 'Y';  // TODO: Not enough! Expired etc.
            $this->log('anyOrderIsActive = ' . $anyOrderIsActive);
            if (!$surveyIsActive && $anyOrderIsActive)
            {
                $this->showNaggingNotification(
                    sprintf($this->gT(
                        'A Cint order is paid or about to be paid, but survey %s is not activated. Please activate it <i>as soon as possible</i> to enable the review process.',
                        'js'
                    ), $survey->defaultlanguage->surveyls_title),
                    $surveyId
                );
            }
            else
            {
                // No order is on hold, new/review or live. So completed or cancelled. Unset all flags.
                $this->set('cint_active_' . $surveyId, false);
            }
        }

    }

    /**
     * Check if all Cint orders are completed. If yes,
     * show a notification to user that survey can be
     * deactivated.
     * @param int $surveyId
     * @return void
     */
    protected function checkCintCompleted($surveyId)
    {
        // Check flag
        $cintActive = $this->get('cint_active_' . $surveyId);
        if ($cintActive &&
            CintLinkOrder::allOrdersAreCompleted($surveyId))
        {
            $not = new Notification(array(
                'survey_id' => $surveyId,
                'importance' => Notification::NORMAL_IMPORTANCE,
                'title' => $this->gT('Cint'),
                'message' => $this->gT('All Cint orders are completed. It is safe to deactivate the survey.')
            ));
            $not->save();

            // Disable flag
            $this->set('cint_active_' . $surveyId, false);
        }
    }

    /**
     * Returns true if user is on survey activation page OR the controller is notification
     * @return bool
     */
    protected function userIsActivatingSurvey()
    {
        $event = $this->getEvent();
        $controller = $event->get('controller');
        $action = $event->get('action');
        $subaction = $event->get('subaction');
        $this->log('controller = ' . $controller . ', action = ' . $action . ', subaction = ' . $subaction);

        $userIsActivatingSurvey = $controller == 'admin' && $action == 'survey' && $subaction == 'activate';
        $fetchingNotifications = $controller == 'admin' && $action == 'notification';
        if ($userIsActivatingSurvey || $fetchingNotifications)
        {
            return true;
        }

        return false;
    }

    /**
     * Disable use of tokens if there is any Cint orders
     * @return void
     */
    protected function disableTokens($surveyId)
    {
        list($contr, $action, /* subaction not used */) = $this->getControllerAction();
        $isTokenAction = $contr == 'admin' && $action == 'tokens';

        // If user has any Cint order, forbid access to participants
        // Only if survey has blocking Cint orders (hold, live, new/review)
        if ($isTokenAction &&
            CintLinkOrder::hasAnyBlockingOrders($surveyId))
        {
            $not = new Notification(array(
                'user_id' => Yii::app()->user->id,
                'title' => $this->gT('Participants disabled'),
                'message' => '<span class="fa fa-exclamation-circle text-warning"></span>&nbsp;' . 
                    $this->gT('Participants are disabled since you have a Cint order.'),
                'importance' => Notification::HIGH_IMPORTANCE,
            ));
            $not->save();

            $url = Yii::app()->request->getOriginalUrlReferrer();
            Yii::app()->getController()->redirect($url);
        }
    }

    /**
     * Get controller, action and subaction.
     * Only works from event beforeControllerAction.
     * @return array
     */
    protected function getControllerAction()
    {
        $event = $this->getEvent();
        $controller = $event->get('controller');
        $action = $event->get('action');
        $subaction = $event->get('subaction');
        return array($controller, $action, $subaction);
    }

    /**
     * If user tries to deactivate, show a warning if any
     * order has status 'new' or 'live'.
     * @return void
     */
    public function beforeSurveyDeactivate()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        $orders = CintLinkOrder::getOrders($surveyId);
        if (!empty($orders))
        {
            $orders = CintLinkOrder::updateOrders($orders);

            if (CintLinkOrder::anyOrderHasStatus($orders, array('new', 'live')))
            {
                $event->set('message', $this->gT('You cannot deactivate the survey while any Cint order is live.'));
                $event->set('success', false);
            }
        }
    }


    /**
     * Before the survey activates, add a new hidden question type
     * to store participant GUID from Cint.
     * Code copied from database.php
     * @return void
     */
    public function beforeSurveyActivate()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        if (CintLinkOrder::hasAnyOrders($surveyId))
        {
            CintLinkOrder::updateOrders($surveyId);
        }

        // Abort if survey has no blocking Cint orders (no order is hold, live or new)
        if (CintLinkOrder::hasAnyBlockingOrders($surveyId))
        {
            $survey = Survey::model()->findByPk($surveyId);
            $additionalLanguages = $survey->additionalLanguages;

            $firstGroup = QuestionGroup::getFirstGroup($surveyId);

            $questionOrder = getMaxQuestionOrder($firstGroup->gid, $surveyId);
            $questionOrder++;

            // Save base language question
            $this->createParticipantGUIDQuestion(
                $surveyId,
                $firstGroup->gid,
                $questionOrder,
                $survey->language
            );

            // Save question for all other languages
            if (!empty($additionalLanguages))
            {
                foreach ($additionalLanguages as $lang)
                {
                    $this->createParticipantGUIDQuestion(
                        $surveyId,
                        $firstGroup->gid,
                        $questionOrder,
                        $lang
                    );
                }
            }
        }
    }

    /**
     * Create hidden question that will be prefilled with GUID from Cint
     * or other panel.
     * @param int $surveyId
     * @param int $groupId
     * @param int $questionOrder
     * @param string $lang
     * @return void
     */
    protected function createParticipantGUIDQuestion($surveyId, $groupId, $questionOrder, $lang)
    {
        $question = Question::model()->findByAttributes(array(
            'title' => 'participantguid',
            'sid' => $surveyId,
            'language' => $lang
        ));

        if (!empty($question))
        {
            // We already have a question, so nothing to do
            return;
        }

        // Create new question
        $question = new Question();
        $question->sid = $surveyId;
        $question->gid = $groupId;
        $question->type = 'S';  // Short text
        $question->title = 'participantguid';
        $question->question = $this->gT('This is the GUID (global unique identifier) of the participant. The question is automatically created by the Cint plugin, and should always be hidden.');
        $question->preg = '';
        $question->help = '';
        $question->other = 'N';
        $question->mandatory = 'N';  // TODO: Cannot be both hidden and mandatory?
        $question->relevance = 1;
        $question->question_order = $questionOrder;
        $question->language = $lang;
        $question->save();

        $errors = $question->getErrors();
        if(count($errors))
        {
            throw new Exception('Could not create participantguid question while activating survey with Cint order');
        }

        // Hide question
        // NB: This attribute must not have a language
        $attribute = new QuestionAttribute;
        $attribute->qid = $question->qid;
        $attribute->value = 1;
        $attribute->attribute = 'hidden';
        $attribute->save();

        // Create panel parameter
        $param = new SurveyURLParameter();
        $param->sid = $surveyId;
        $param->targetqid = $question->qid;
        $param->parameter = 'respondent';
        $param->save();
    }

    /**
     * Survey dashboard.
     *
     * @param int $surveyId
     * @return string
     */
    public function actionIndex($surveyId)
    {
        if (empty($surveyId))
        {
            throw new InvalidArgumentException('surveyId cannot be empty');
        }

        $data = array();
        $data['surveyId'] = $surveyId;
        $data['common'] = $this->renderPartial('common', $data, true);
        $this->renderCommonJs($surveyId);

        $content = $this->renderPartial('index', $data, true);

        $this->registerCssAndJs();

        return $content;
    }

    /**
     * As actionIndex but survey agnostic
     * Global dashboard.
     *
     * @return string
     */
    public function actionIndexGlobal()
    {
        $data = array();
        $data['common'] = $this->renderPartial('common', $data, true);
        $this->renderCommonJs();

        $content = $this->renderPartial('indexGlobal', $data, true);

        $this->registerCssAndJs();

        return $content;
    }

    /**
     * Register CSS and JS
     *
     * @return void
     */
    protected function registerCssAndJs() {
        $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__) . '/js');
        App()->clientScript->registerScriptFile("$assetsUrl/cintlink.js");
        App()->clientScript->registerScriptFile("https://" . $this->cintApiKey . ".cds.cintworks.net/assets/cint-link-1-0-0.js");

        // Need to include this manually so Ajax loading of gridview will work
        App()->clientScript->registerScriptFile('/framework/zii/widgets/assets/gridview/jquery.yiigridview.js');
        App()->clientScript->registerScriptFile('/framework/web/js/source/jquery.ba-bbq.min.js');
    }

    /**
     * Return json result true if user is logged in on limesurvey.org
     *
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function checkIfUserIsLoggedInOnLimesurveyorg(LSHttpRequest $request)
    {
        $limesurveyOrgKey = Yii::app()->user->getState('limesurveyOrgKey');
        if (empty($limesurveyOrgKey))
        {
            return json_encode(array('result' => false));
        }
        else
        {
            $curl = new Curl();
            $response = $curl->post(
                self::$baseURL,
                array(
                    'app' => 'cintlinklimesurveyrestapi',
                    'format' => 'raw',
                    'resource' => 'test',
                    'key' => $limesurveyOrgKey
                )
            );
            $response = json_decode($response);

            if ($response == "post ok")
            {
                return json_encode(array('result' => true));
            }
            else
            {
                return json_encode(array('result' => false));
            }
        }
    }

    /**
     * Return HTMl for login form
     * Called by Ajax
     *
     * @param LSHttpRequest $request
     * @return string
     */
    public function getLoginForm(LSHttpRequest $request)
    {
        $data = array();
        $content = $this->renderPartial('loginform', $data, true);
        return $content;
    }

    /**
     * Return HTML for survey specific dashboard
     * Called by Ajax
     * @param LSHttpRequest $request
     * @return string
     */
    public function getDashboard(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');
        $survey = Survey::model()->findByPk($surveyId);

        // If surveyId is empty, assume this is the global dashboard
        if (empty($surveyId))
        {
            return $this->getGlobalDashboard();
        }

        $orders = $this->getOrders(array(
            'sid' => $surveyId,
            'deleted' => 0
        ));

        // Only update when request is not pagination request from grid
        $ajax = Yii::app()->request->getParam('ajax');
        if ($ajax != 'url')
        {
            $orders = CintLinkOrder::updateOrders($orders);
        }

        $data = array();
        $data['surveyId'] = $surveyId;
        $data['additionalLanguages'] = empty($survey->additionalLanguages) ? null : $survey->additionalLanguages;
        $data['user'] = Yii::app()->user;
        $data['model'] = CintLinkOrder::model();  // TODO: Only show orders for this survey
        $data['dateformatdata'] = getDateFormatData(Yii::app()->session['dateformat']);
        $data['survey'] = Survey::model()->findByPk($surveyId);
        $data['hasTokenTable'] = $this->hasTokenTable($surveyId);

        $content = $this->renderPartial('dashboard', $data, true);

        return $content;
    }

    /**
     * Return HTML for global dashboard
     * @return string
     */
    public function getGlobalDashboard()
    {
        $orders = $this->getOrders(array(
            'deleted' => 0
        ));

        // Only update when request is not pagination request from grid
        $ajax = Yii::app()->request->getParam('ajax');
        if ($ajax != 'url')
        {
            $orders = CintLinkOrder::updateOrders($orders);
        }

        $data = array();
        $data['surveyId'] = null;
        $data['user'] = Yii::app()->user;
        $data['model'] = CintLinkOrder::model();
        $data['dateformatdata'] = getDateFormatData(Yii::app()->session['dateformat']);

        $content = $this->renderPartial('dashboard', $data, true, true);

        return $content;
    }

    /**
     * Hack to make gridview not include too much javascript
     * Used in dashboard view
     *
     * @return void
     */
    public function renderClientScripts()
    {
        foreach (Yii::app()->clientScript->scripts as $index=>$script)
        {
            echo CHtml::script(implode("\n",$script));
        }
        Yii::app()->clientScript->reset();
    }

    /**
     * Login to limesurvey.org using com_api
     *
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function login(LSHttpRequest $request)
    {
        $username = $request->getParam('username');
        $password = $request->getParam('password');

        $curl = new Curl();
        $response = $curl->post(
            self::$baseURL,
            array(
                'app' => 'cintlinklimesurveyrestapi',
                'format' => 'raw',
                'resource' => 'login',
                'username' => $username,
                'password' => $password
            )
        );
        $result = json_decode($response->body);

        if ($result->code == 403)
        {
            return json_encode(array('result' => false));
        }
        else if ($result->code == 200)
        {
            Yii::app()->user->setState('limesurveyOrgKey', $result->auth);
            $this->limesurveyOrgKey = $result->auth;

            return json_encode(array('result' => true, 'response' => $response));
        }
        else
        {
            return json_encode(array('error' => 'Unknown return code: ' . $result->code));
        }
    }

    /**
     * When user click "Place order" in the widget,
     * this function is called to contact limesurvey.org
     * and place an order.
     *
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function purchaseRequest(LSHttpRequest $request)
    {
        $purchaseRequest = $request->getParam('purchaseRequest');
        $surveyId = $request->getParam('surveyId');
        $limesurveyOrgKey = Yii::app()->user->getState('limesurveyOrgKey');
        $userId = Yii::app()->user->getId();

        $curl = new Curl();
        $response = $curl->post(
            self::$baseURL,
            array(
                'app' => 'cintlinklimesurveyrestapi',
                'format' => 'raw',
                'resource' => 'order',
                'purchaseRequest' => $purchaseRequest,
                'key' => $limesurveyOrgKey
            )
        );
        $body = json_decode($response->body);

        // Abort if we got nothing
        if ($body === null)
        {
            return json_encode(array(
                'result' => 'false',
                'error' => 'Got NULL from server. Please check error logs.'
            ));
        }

        $order = new CintLinkOrder();
        $order->url = $body->url;
        $order->sid = $surveyId;
        $order->ordered_by = $userId;
        $order->country = $body->raw->{'target-group'}->country->name;
        $order->raw = json_encode(get_object_vars($body->raw));
        $order->status = (string) $body->raw->state;  // 'hold' means waiting for payment
        $order->created = date('Y-m-d H:i:m', time());
        $order->save();

        return json_encode(array('result' => $response->body));
    }

    /**
     * Cancel order
     * Run when user click "Cancel" in order table
     * Contact limesurvey.org, because cancel order needs access
     * to secret.
     *
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function cancelOrder(LSHttpRequest $request)
    {
        $orderUrl = $request->getParam('orderUrl');

        if (!$this->checkPermission(null, $orderUrl))
        {
            return json_encode(array('error' => $this->gT('No permission')));
        }

        $this->log('order url = ' . $orderUrl);

        if (empty($orderUrl))
        {
            return json_encode(array('error' => 'Missing order url'));
        }

        $limesurveyOrgKey = Yii::app()->user->getState('limesurveyOrgKey');

        if (empty($limesurveyOrgKey))
        {
            return json_encode(array('error' => 'Missing limesurveyOrgKey - user not logged in?'));
        }

        // DELETE does not support CURLOPT_POSTFIELDS?
        $url = self::$baseURL;
        $url .= '&app=cintlinklimesurveyrestapi';
        $url .= '&format=raw';
        $url .= '&resource=order';
        $url .= '&key=' . $limesurveyOrgKey;
        $url .= '&order_url=' . htmlspecialchars($orderUrl);

        $curl = new Curl();
        $response = $curl->delete($url, array());

        // Always update order no matter the result
        $order = CintLinkOrder::model()->findByAttributes(array('url' => $orderUrl));
        $order->updateFromCint();

        if (empty($response->body))
        {
            return json_encode(array('result' => $this->gT('Order was cancelled')));
        }
        else
        {
            // TODO: Body can be false if ordered was already cancelled
            return json_encode(array('result' => $response->body));
        }

    }

    /**
     * Soft delete an order
     *
     * @param LSHttpRequest $request
     * @return void
     */
    public function softDeleteOrder(LSHttpRequest $request)
    {
        $this->log('softDeleteOrder begin');

        $surveyId = $request->getParam('surveyId');
        $url = $request->getParam('orderUrl');

        if (!$this->checkPermission($surveyId, $url))
        {
            return json_encode(array('error' => $this->gT('No permission')));
        }

        $this->log('url = ' . $url);
        $this->log('surveyId = ' . $surveyId);

        $order = CintLinkOrder::model()->findByAttributes(
            array(
                'url' => $url,
                'deleted' => 0
            )
        );

        if (empty($order))
        {
            return json_encode(array('error' => $this->gT('Found no order')));
        }

        $order->deleted = 1;
        $order->save();

        $this->log("softDeleteOrder end");
        return json_encode(array('result' => $this->gT('Order was deleted')));
    }

    /**
     * Get survey information
     * Used by Cint widget.
     *
     * @param LSHttpRequest $request
     * @return string JSON
     */
    public function getSurvey(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');
        $survey = Survey::model()->findByPk($surveyId);
        $data = $survey->getAttributes();

        $surveyLanguage = SurveyLanguageSetting::model()->findByPk(array(
            'surveyls_survey_id' => $surveyId,
            'surveyls_language' => $survey->language
        ));
        $data = array_merge($data, $surveyLanguage->getAttributes());

        $user = $this->api->getCurrentUser();

        $link = Yii::app()->createAbsoluteUrl(
            'survey/index',
            array(
                'sid' => $surveyId
            )
        );

        return json_encode(array(
            'result' => json_encode($data),
            'name' => $user->full_name,
            'email' => $user->email,
            'numberOfQuestions' => $this->calculateNumberOfQuestions($surveyId),
            'link' => $link,
            'language' => $survey->language
        ));
    }

    /**
     * Run when user clicks 'Pay now' to store the fact that
     * user tried to pay and warn him/her if survey is not active.
     *
     * @param LSHttpRequest $request
     * @return void
     */
    public function userTriedToPay(LSHttpRequest $request)
    {
        $surveyId = $request->getParam('surveyId');
        if (!$this->checkPermission($surveyId))
        {
            $this->log('Internal error: userTriedToPay but lack permission. survey id = ' . $surveyId);
        }

        // Set flag in plugin settings
        $this->set('cint_active_' . $surveyId, true);
    }

    /**
     * Plugin specific code to run a function.
     * Will echo function result.
     * @return void
     */
    public function newDirectRequest()
    {
        $event = $this->event;
        if ($event->get('target') == "CintLink")
        {
            $request = $event->get('request');  // request = survey id for actionIndex?
            $functionToCall = $event->get('function');
            if ($functionToCall == 'actionIndex' 
                || $functionToCall == 'actionIndexGlobal')
            {
                $content = $this->$functionToCall($request);
                $event->setContent($this, $content);
            }
            else if ($functionToCall == 'checkIfUserIsLoggedInOnLimesurveyorg'
                    || $functionToCall == 'getLoginForm'
                    || $functionToCall == 'getDashboard'
                    || $functionToCall == 'getNBillOrderForm'
                    || $functionToCall == 'submitFirstNBillPage'
                    || $functionToCall == "login"
                    || $functionToCall == "purchaseRequest"
                    || $functionToCall == "cancelOrder"
                    || $functionToCall == "softDeleteOrder"
                    || $functionToCall == "userTriedToPay"
                    || $functionToCall == "updateAllOrders"
                    || $functionToCall == "getSurvey")
            {
                echo $this->$functionToCall($request);
            }
        }
    }

    /**
     * Get all Cint orders saved on client
     *
     * @param array $conditions Like array('deleted' => false, ...)
     * @return array<CintLinkOrder>
     */
    protected function getOrders($conditions)
    {
        $this->log('getOrders');
        $orders = CintLinkOrder::model()->findAllByAttributes(
            $conditions,
            array('order' => 'url DESC')
        );
        $this->log('got ' . count($orders) . ' orders');
        return $orders;
    }

    /**
     * Update all orders for this survey
     *
     * @param LSHttpRequest $request
     */
    public function updateAllOrders($request)
    {
        $this->log('updateAllOrders begin');
        $surveyId = $request->getParam('surveyId');
        try
        {
            $orders = CintLinkOrder::model()->findAllByAttributes(
                array(
                    'sid' => $surveyId,
                    'deleted' => 0
                ),
                array('order' => 'url DESC')
            );
            CintLinkOrder::updateOrders($orders);
        }
        catch (Exception $ex)
        {
            $this->log('Could not update all orders: ' . $ex->getMessage());
        }
        $this->log('updateAllOrders end');
    }

    /**
     * User has permission to Cint if he/she is super admin OR
     * owner of the survey.
     *
     * @param int|null $surveyId
     * @return boolean True if user has permission
     */
    protected function checkPermission($surveyId = null, $orderUrl = null)
    {
        if (empty($surveyId) && empty($orderUrl))
        {
            // You don't own survey if there is no survey
            $ownSurvey = false;
        }
        else if (empty($surveyId))
        {
            // In case we have url but no survey id (global dashboard), check ownership
            $order = CintLinkOrder::model()->findByAttributes(array('url' => $orderUrl));
            $survey = Survey::model()->findByPk($order->sid);
            $ownSurvey = $survey->owner_id == Yii::app()->user->id;
        }
        else
        {
            $survey = Survey::model()->findByPk($surveyId);
            $ownSurvey = $survey->owner_id == Yii::app()->user->id;
        }

        $isSuperAdmin = Permission::model()->hasGlobalPermission('superadmin');

        return $ownSurvey || $isSuperAdmin;
    }

    /**
     * If any order is 'new' or 'live', survey must be active.
     *
     * @return void
     */
    protected function showActivateMessage($surveyId) {
        $survey = Survey::model()->findByPk($surveyId);
        $orders = $this->getOrders(array(
            'sid' => $surveyId,
            'deleted' => 0
        ));

        if ($survey->active != 'Y')
        {
            Yii::app()->user->setFlash(
                'warning',
                $this->gT('This survey is live or under review by Cint. Please activate the survey as soon as possible.')
            );
        }
    }

    /**
     * As above, but checks so that *all* orders have
     * *any* of the status in $statuses.
     *
     * @param array<CintLinkOrder>|CintLinkOrder $orders
     * @param array|string $statuses Array of status to check for
     * @return boolean
     */
    protected function allOrdersHaveStatus($order, $statuses)
    {

        if (!is_array($orders))
        {
            $orders = array($orders);
        }

        if (!is_array($statuses))
        {
            $statuses = array($statuses);
        }

        foreach ($orders as $order)
        {
            if (!in_array($order->status, $statuses))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Show a nagging notification
     *
     * @param string $message
     * @param int $surveyId
     * @return void
     */
    protected function showNaggingNotification($message, $surveyId) {
        $not = new UniqueNotification(array(
            'survey_id' => $surveyId,
            'importance' => Notification::HIGH_IMPORTANCE,
            'title' => $this->gT('Cint warning'),
            'message' => '<span class="fa fa-exclamation-circle text-warning"></span>&nbsp;' . $message
        ));
        $not->save();
    }

    /**
     * Set the nagging notification to read
     * @return void
     */
    protected function hideNaggingNotication($surveyId)
    {
        /*
        $nagId = $this->get('nag_id_' . $surveyId);
        if (!empty($nagId))
        {
            $not = Notification::model()->findByPk($nagId);
            if (!empty($not))
            {
                $not->status = 'read';
                $not->save();
            }
        }
         */
    }

    /**
     * Cint need to know how many questions there are in the
     * survey. I use base questions + (sub questions / 3).
     * Approx is 3 questions each minute.
     * @param int $surveyId
     * @return int
     */
    protected function calculateNumberOfQuestions($surveyId)
    {
        $survey = Survey::model()->findByPk($surveyId);

        $criteria = new CDbCriteria();
        $criteria->addCondition('sid = ' . $surveyId);
        $criteria->addCondition('parent_qid = 0');
        $criteria->addCondition('language = \'' . $survey->language . '\'');
        $baseQuestions = Question::model()->count($criteria);

        // Note: An array questions with one sub question is fetched as 1 base question + 1 sub question
        $criteria = new CDbCriteria();
        $criteria->addCondition('sid = ' . $surveyId);
        $criteria->addCondition('parent_qid != 0');
        $criteria->addCondition('language = \'' . $survey->language . '\'');
        $subQuestions = Question::model()->count($criteria);

        // Subquestions are worth less "time" than base questions
        $subQuestions = intval(($subQuestions - $baseQuestions) / 2);
        $subQuestions = $subQuestions < 0 ? 0 : $subQuestions;

        return $subQuestions + $baseQuestions;

    }

    /**
     * Echoes Javascript code that is common for all scripts
     * Only runs if it's NOT an Ajax call
     * @param int $surveyId Null in global view
     * @return void
     */
    protected function renderCommonJs($surveyId = null)
    {
        $isAjax = Yii::app()->request->getParam('ajax');

        if (!$isAjax)
        {
            $data = array();
            $data['surveyId'] = $surveyId;
            $pluginBaseUrl = Yii::app()->createUrl(
                'admin/pluginhelper',
                array(
                    'sa' => 'ajax',
                    'plugin' => 'CintLink',
                    'surveyId' => $surveyId,
                    'ajax' => 1
                )
            );
            $orderPlaced = $this->gT('Order placed on hold. Please pay to start the review process. Make sure the survey is activated before you pay.');
            $couldNotLogin = $this->gT('Could not login. Please make sure username and password is correct.');

            // Code below is WEIRD, but best way to include Javascript settings from PHP?
            Yii::app()->clientScript->registerScript('cint-common-js', <<<EOT
                // Namespace
                var LS = LS || {};
                LS.plugin = LS.plugin || {};
                LS.plugin.cintlink = LS.plugin.cintlink || {};

                LS.plugin.cintlink.pluginBaseUrl = '$pluginBaseUrl';

                LS.plugin.cintlink.lang = {}
                LS.plugin.cintlink.lang.orderPlacedOnHold = '$orderPlaced';
                LS.plugin.cintlink.lang.couldNotLogin = '$couldNotLogin';
EOT
            , CClientScript::POS_END);

            if (!empty($surveyId))
            {
                Yii::app()->clientScript->registerScript('cint-common-js-survey-id', <<<EOT
                    LS.plugin.cintlink.surveyId = '$surveyId';
EOT
            , CClientScript::POS_END);
            }
        }
    }

    /**
     * Return true if this survey has a token table
     * @param int $surveyId
     * @return boolean
     * @todo Never use try-catch as control logic
     */
    protected function hasTokenTable($surveyId)
    {
        try
        {
            Token::model($surveyId);
            return true;
        }
        catch (Exception $ex)
        {
            return false;
        }
    }

}
