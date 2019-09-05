<?php

    use LimeSurvey\Helpers\questionHelper;

    /**
     * Class QuestionThemes
     *
     * Stores all the Metadata and
     *
     * @property int     $id
     * @property string  $name   Template name
     * @property string  $folder
     * @property string  $image_path
     * @property string  $title
     * @property string  $creation_date
     * @property string  $author
     * @property string  $author_email
     * @property string  $author_url
     * @property string  $copyright
     * @property string  $license
     * @property string  $version
     * @property string  $view_folder
     * @property string  $files_folder
     * @property string  $description
     * @property string  $last_update
     * @property integer $owner_id
     * @property string  $theme_type
     * @property string  $type
     * @property string  $extends
     * @property string  $group
     * @property array   $settings
     */
    class QuestionTheme extends LSActiveRecord
    {
        static $coreQuestionsPath;
        static $customQuestionThemesPath;
        static $userQuestionThemesPath;


        /**
         * Returns the static model of the specified AR class.
         * Please note that you should have this exact method in all your CActiveRecord descendants!
         *
         * @param string $className active record class name.
         *
         * @return Template the static model class
         */
        public static function model($className = __CLASS__)
        {
            return parent::model($className);
        }

        /**
         * @return string the associated database table name
         */
        public function tableName()
        {
            return '{{question_themes}}';
        }

        /**
         * @return array relational rules.
         */
        public function relations()
        {
            return array();
        }

        /**
         * Returns this table's primary key
         *
         * @access public
         * @return string
         */
        public function primaryKey()
        {
            return 'id';
        }

        public function rules()
        {
            return [
                [
                    'name',
                    'unique',
                    'caseSensitive' => false,
                    'criteria' => [
                        'condition' => '`extends`=:extends',
                        'params' => [
                            ':extends' => $this->extends
                        ]
                    ],
                ]
            ];
        }

        /**
         * Import all Questiontypes and Themes to the {{questions_themes}} table
         *
         * @param array $questionThemeDirectories
         *
         * @throws CException
         */
        public function loadAllQuestionXMLConfigurationsIntoDatabase()
        {
            $missingQuestionThemeAttributes = [];
            $questionThemeDirectories = $this->getQuestionThemeDirectories();

            // @see: http://phpsecurity.readthedocs.io/en/latest/Injection-Attacks.html#xml-external-entity-injection
            $bOldEntityLoaderState = libxml_disable_entity_loader(true);
            // process XML Question Files
            if (isset($questionThemeDirectories)) {
                try {
                    $transaction = App()->db->beginTransaction();
                    $questionsMetaData = self::getAllQuestionMetaData();
                    foreach ($questionsMetaData as $questionMetaData) {
                        // test xml for required metaData
                        $requiredMetaDataArray = ['name', 'title', 'creationDate', 'author', 'authorEmail', 'authorUrl', 'copyright', 'copyright', 'license', 'version', 'apiVersion', 'description', 'type', 'group', 'subquestions', 'answerscales', 'hasdefaultvalues', 'assessable', 'class'];
                        foreach ($requiredMetaDataArray as $requiredMetaData) {
                            if (!array_key_exists($requiredMetaData, $questionMetaData)) {
                                $missingQuestionThemeAttributes[$questionMetaData['xml_path']][] = $requiredMetaData;
                            }
                        }
                        $questionTheme = QuestionTheme::model()->find('name=:name AND extends=:extends', [':name' => $questionMetaData['name'], ':extends' => $questionMetaData['extends']]);
                        if ($questionTheme == null) {
                            $questionTheme = new QuestionTheme();
                        }
                        $metaDataArray = $this->getMetaDataArray($questionMetaData);
                        $questionTheme->setAttributes($metaDataArray, false);
                        $questionTheme->save();
                    }
                    $transaction->commit();
                } catch (Exception $e) {
                    //TODO: flashmessage for users
                    echo $e->getMessage();
                    var_dump($e->getTrace());
                    echo $missingQuestionThemeAttributes;
                    $transaction->rollback();
                }
            }

            // Put back entity loader to its original state, to avoid contagion to other applications on the server
            libxml_disable_entity_loader($bOldEntityLoaderState);
        }

        public function search()
        {
            $pageSizeTemplateView = App()->user->getState('pageSizeTemplateView', App()->params['defaultPageSize']);

            $criteria = new CDbCriteria;
            return new CActiveDataProvider($this, array(
                'criteria' => $criteria,
                'pagination' => array(
                    'pageSize' => $pageSizeTemplateView,
                ),
            ));
        }


        // TODO: Enable when Configuration Model is ready
        public function getButtons()
        {
//            // don't show any buttons if user doesn't have update permission
//            if (!Permission::model()->hasGlobalPermission('templates', 'update')) {
//                return '';
//            }
//            $gsid = Yii::app()->request->getQuery('id', null);
//            $sEditorUrl = Yii::app()->getController()->createUrl('admin/themes/sa/view', array("templatename" => $this->template_name));
//            $sExtendUrl = Yii::app()->getController()->createUrl('admin/themes/sa/templatecopy');
//            $sOptionUrl = (App()->getController()->action->id == "surveysgroups") ? Yii::app()->getController()->createUrl('admin/themeoptions/sa/updatesurveygroup', array("id" => $this->id, "gsid" => $gsid)) : Yii::app()->getController()->createUrl('admin/themeoptions/sa/update', array("id" => $this->id));
//
//            $sEditorLink = "<a
//            id='template_editor_link_" . $this->template_name . "'
//            href='" . $sEditorUrl . "'
//            class='btn btn-default btn-block'>
//                <span class='icon-templates'></span>
//                " . gT('Theme editor') . "
//            </a>";
//
//            $OptionLink = '';
//            if ($this->hasOptionPage) {
//                $OptionLink .= "<a
//                id='template_options_link_" . $this->template_name . "'
//                href='" . $sOptionUrl . "'
//                class='btn btn-default btn-block'>
//                    <span class='fa fa-tachometer'></span>
//                    " . gT('Theme options') . "
//                </a>";
//            }
//
//
//            $sExtendLink = '<a
//            id="extendthis_' . $this->template_name . '"
//            href="' . $sExtendUrl . '"
//            data-post=\''
//                . json_encode([
//                    "copydir" => $this->template_name,
//                    "action" => "templatecopy",
//                    "newname" => ["value" => "extends_" . $this->template_name, "type" => "text", "class" => "form-control col-sm-12"]
//                ])
//                . '\'
//            data-text="' . gT('Please type in the new theme name above.') . '"
//            title="' . sprintf(gT('Type in the new name to extend %s'), $this->template_name) . '"
//            class="btn btn-primary btn-block selector--ConfirmModal">
//                <i class="fa fa-copy"></i>
//                ' . gT('Extend') . '
//            </a>';
//
//
//            if (App()->getController()->action->id == "surveysgroups") {
//                $sButtons = $OptionLink;
//            } else {
//                $sButtons = $sEditorLink . $OptionLink . $sExtendLink;
//
//            }
//
//
//
//
//            return $sButtons;
        }

        /**
         * Install Button for the available questions
         */
        public function getManifestButtons()
        {
            $sLoadLink = CHtml::form(array("/admin/themeoptions/sa/importmanifest/"), 'post', array('id' => 'forminstallquestiontheme', 'name' => 'forminstallquestiontheme')) .
                "<input type='hidden' name='templatefolder' value='" . $this->folder . "'>
                <input type='hidden' name='theme' value='questiontheme'>
                <button id='template_options_link_" . $this->name . "'class='btn btn-default btn-block'>
                    <span class='fa fa-download text-warning'></span>
                    " . gT('Install') . "
                </button>
                </form>";

            return $sLoadLink;
        }

        /**
         * @param $pathToXML
         *
         * @return bool
         */
        public function importManifest($pathToXML)
        {
            if (empty($pathToXML)) {
                throw new InvalidArgumentException('$templateFolder cannot be empty');
            }

            $questionDirectories = $this->getQuestionThemeDirectories();
            $questionMetaData = $this->getQuestionMetaData($pathToXML, $questionDirectories);

            $questionThemeExists = QuestionTheme::model()->find('(name = :name AND extends = :extends) OR (extends = :extends AND type = :type)', [':name' => $questionMetaData['name'], ':type' => $questionMetaData['type'], ':extends' => $questionMetaData['extends']]);
            if ($questionThemeExists == null) {

                $metaDataArray = $this->getMetaDataArray($questionMetaData);
                $this->setAttributes($metaDataArray, false);
                if ($this->save()) {
                    return $questionMetaData['title'];
                };
            }
            return null;
        }

        /**
         * Returns the Theme-type and Extended-type for the current question
         *
         * @param $pathToXML
         * @param $questionMetaData
         *
         * @return array
         * TODO: move to getAllQuestionMetaData
         */
        public static function getThemeTypeAndExtendedType($pathToXML, $questionMetaData)
        {
            $questionAdditionalData = [];
            $coreQuestion = App()->getConfig('corequestiontypedir') . '/survey/questions/answer';
            $customCoreTheme = App()->getConfig('userquestionthemedir');
            $customUserTheme = App()->getConfig('userquestionthemerootdir');

            $questionAdditionalData['extends'] = $questionMetaData['type'];
            if (substr($pathToXML, 0, strlen($coreQuestion)) === $coreQuestion) {
                $questionAdditionalData['themeType'] = 'Core theme';
                $questionAdditionalData['extends'] = '';
            }
            if (substr($pathToXML, 0, strlen($customCoreTheme)) === $customCoreTheme) {
                $questionAdditionalData['themeType'] = 'Core theme';
            }
            if (substr($pathToXML, 0, strlen($customUserTheme)) === $customUserTheme) {
                $questionAdditionalData['themeType'] = 'User theme';
            }
            return $questionAdditionalData;
        }


        /**
         * Returns all Questions that can be installed
         */
        public function getAvailableQuestions()
        {
            $questionThemes = $installedQuestions = $availableQuestions = $questionKeys = [];
            $questionsMetaData = $this->getAllQuestionMetaData();
            $questionsInDB = $this->findAll();

            foreach ($questionsInDB as $questionInDB) {
                if (array_key_exists($questionKey = $questionInDB->name . '_' . $questionInDB->type, $questionsMetaData)) {
                    unset($questionsMetaData[$questionKey]);
                }
            }
            array_values($questionsMetaData);
            foreach ($questionsMetaData as $questionMetaData) {
                // TODO: replace by manifest
                $questionTheme = new QuestionTheme();

                $metaDataArray = $this->getMetaDataArray($questionMetaData);
                $questionTheme->setAttributes($metaDataArray, false);
                $questionThemes[] = $questionTheme;
            }

            return $questionThemes;
        }

        /**
         * @return array
         */
        public function getAllQuestionMetaData()
        {
            $questionsMetaData = [];
            $questionDirectories = $this->getQuestionThemeDirectories();
            $questionDirectoriesAndPaths = $this->getAllQuestionXMLPaths($questionDirectories);
            if (isset($questionDirectoriesAndPaths) && !empty($questionDirectoriesAndPaths)) {
                foreach ($questionDirectoriesAndPaths as $directory => $questionConfigFilePaths) {
                    foreach ($questionConfigFilePaths as $questionConfigFilePath) {
                        $questionMetaData = self::getQuestionMetaData($questionConfigFilePath, $questionDirectories);
                        $questionsMetaData[$questionMetaData['name'] . '_' . $questionMetaData['type']] = $questionMetaData;
                    }
                }
            }
            return $questionsMetaData;
        }

        /**
         * @param $pathToXML
         * @param $questionDirectories
         *
         * @return array Question Meta Data
         */
        public static function getQuestionMetaData($pathToXML, $questionDirectories)
        {
            $bOldEntityLoaderState = libxml_disable_entity_loader(true);

            $sQuestionConfigFile = file_get_contents($pathToXML . DIRECTORY_SEPARATOR . 'config.xml');  // @see: Now that entity loader is disabled, we can't use simplexml_load_file; so we must read the file with file_get_contents and convert it as a string
            $oQuestionConfig = simplexml_load_string($sQuestionConfigFile);
            $questionMetaData = json_decode(json_encode($oQuestionConfig->metadata), true);
            $questionMetaData['xml_path'] = $pathToXML;
            $questionMetaData['image_path'] = self::getQuestionThemePreviewUrl($questionMetaData['type']);

            // set settings as json
            $questionMetaData['settings'] = json_encode([
                'subquestions' => $questionMetaData['subquestions'],
                'answerscales' => $questionMetaData['answerscales'],
                'hasdefaultvalues' => $questionMetaData['hasdefaultvalues'],
                'assessable' => $questionMetaData['assessable'],
                'class' => $questionMetaData['class'],
            ]);

            // overrides depending on directory
            $questionMetaData['extends'] = $questionMetaData['type'];
            if (substr($pathToXML, 0, strlen($questionDirectories['coreQuestion'])) === $questionDirectories['coreQuestion']) {
                $questionMetaData['themeType'] = 'Core theme';
                $questionMetaData['extends'] = '';
                $questionMetaData['image_path'] = 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'screenshots' . DIRECTORY_SEPARATOR . $questionMetaData['type'];
            }
            if (substr($pathToXML, 0, strlen($questionDirectories['customCoreTheme'])) === $questionDirectories['customCoreTheme']) {
                $questionMetaData['themeType'] = 'Core theme';
            }
            if (substr($pathToXML, 0, strlen($questionDirectories['customUserTheme'])) === $questionDirectories['customUserTheme']) {
                $questionMetaData['themeType'] = 'User theme';
            }

            libxml_disable_entity_loader($bOldEntityLoaderState);
            return $questionMetaData;
        }

        /**
         * Find all XML paths for specified Question Root folders
         *
         * @param array $questionDirectories
         * @param bool  $core
         * @param bool  $custom
         * @param bool  $user
         *
         * @return array
         */
        public function getAllQuestionXMLPaths($questionDirectories, $core = true, $custom = true, $user = true)
        {
            $questionDirectoriesAndPaths = [];
            if ($core) {
                $coreQuestionsPath = $questionDirectories['coreQuestion'];
                $selectedQuestionDirectories[] = $coreQuestionsPath;
            }
            if ($custom) {
                $customQuestionThemesPath = $questionDirectories['customCoreTheme'];
                $selectedQuestionDirectories[] = $customQuestionThemesPath;
            }
            if ($user) {
                $userQuestionThemesPath = $questionDirectories['customUserTheme'];
                if (!is_dir($userQuestionThemesPath)) {
                    mkdir($userQuestionThemesPath);
                }
                $selectedQuestionDirectories[] = $userQuestionThemesPath;
            }

            if (isset($selectedQuestionDirectories)) {
                foreach ($selectedQuestionDirectories as $questionThemeDirectory) {
                    $directory = new RecursiveDirectoryIterator($questionThemeDirectory);
                    $iterator = new RecursiveIteratorIterator($directory);
                    foreach ($iterator as $info) {
                        $ext = pathinfo($info->getPathname(), PATHINFO_EXTENSION);
                        if ($ext == 'xml') {
                            $questionDirectoriesAndPaths[$questionThemeDirectory][] = dirname($info->getPathname());
                        }
                    }
                }
            }
            return $questionDirectoriesAndPaths;
        }


        public static function uninstall($templateid)
        {
            if (Permission::model()->hasGlobalPermission('templates', 'delete')) {
                $oTemplate = self::model()->findByPk($templateid);
                return $oTemplate->delete();
            }
            return false;
        }

        /**
         * Returns All QuestionTheme settings
         *
         * @param string $question_type
         * @param string $language
         *
         * @return mixed $baseQuestions Questions as Array or Object
         */
        public static function findQuestionMetaData($question_type, $language = '')
        {
            $criteria = new CDbCriteria();
            $criteria->condition = 'extends = :extends';
            $criteria->addCondition('type = :type', 'AND');
            $criteria->addCondition('visible = :visible', 'AND');
            $criteria->params = [':extends' => '', ':type' => $question_type, ':visible' => 'Y'];

            $baseQuestion = self::model()->query($criteria, false, false);
            $baseQuestion['settings'] = json_decode($baseQuestion['settings']);

            // language settings
            $baseQuestion['title'] = gT($baseQuestion['title'], "html", $language);
            $baseQuestion['group'] = gT($baseQuestion['group'], "html", $language);

            return $baseQuestion;
        }

        /**
         * Returns all Questions Base settings
         *
         * @param string $language
         * @param bool   $typeAsKey
         * @param bool   $asAR
         *
         * @return mixed $baseQuestions Questions as Array or Object
         */
        public static function findAllQuestionBaseSettings($language = '', $typeAsKey = true, $asAR = false)
        {
            $language = App()->session['adminlang'];
            $criteria = new CDbCriteria();
            $criteria->condition = 'extends = :extends';
            $criteria->addCondition('visible = :visible', 'AND');
            $criteria->params = [':extends' => '', ':visible' => 'Y'];

            $baseQuestions = self::model()->query($criteria, true, $asAR);

            $baseQuestionsModified = [];

            foreach ($baseQuestions as $key => $baseQuestion) {
                // return type as array key
                if ($typeAsKey === true) {
                    $baseQuestionsModified[$baseQuestion['type']] = $baseQuestion;
                } else {
                    $baseQuestionsModified[] = $baseQuestion;
                }
                $lastQuestionKey = array_key_last($baseQuestionsModified);

                // language settings
                $baseQuestionsModified[$lastQuestionKey]['title'] = gT($baseQuestion['title'], "html", $language);
                $baseQuestionsModified[$lastQuestionKey]['group'] = gT($baseQuestion['group'], "html", $language);

                // decode settings json
                $baseQuestionsModified[$lastQuestionKey]['settings'] = json_decode($baseQuestion['settings']);
            }
            $baseQuestions = $baseQuestionsModified;

            return $baseQuestions;
        }

        public function getQuestionThemeDirectories()
        {
            $questionThemeDirectories['coreQuestion'] = App()->getConfig('corequestiontypedir') . '/survey/questions/answer';
            $questionThemeDirectories['customCoreTheme'] = App()->getConfig('userquestionthemedir');
            $questionThemeDirectories['customUserTheme'] = App()->getConfig('userquestionthemerootdir');

            return $questionThemeDirectories;
        }

        /**
         * Returns QuestionMetaData Array for use in ->save operations
         *
         * @param array $questionMetaData
         *
         * @return array $questionMetaData
         */
        private function getMetaDataArray($questionMetaData)
        {
            $questionMetaData = [
                'name' => $questionMetaData['name'],
                'visible' => 'Y', //todo
                'xml_path' => $questionMetaData['xml_path'],
                'image_path' => $questionMetaData['image_path'],
                'title' => $questionMetaData['title'],
                'creation_date' => $questionMetaData['creationDate'],
                'author' => $questionMetaData['author'],
                'author_email' => $questionMetaData['authorEmail'],
                'author_url' => $questionMetaData['authorUrl'],
                'copyright' => $questionMetaData['copyright'],
                'license' => $questionMetaData['license'],
                'version' => $questionMetaData['version'],
                'api_version' => $questionMetaData['apiVersion'],
                'description' => $questionMetaData['description'],
                'last_update' => 'insert time', //todo
                'owner_id' => 1, //todo
                'theme_type' => $questionMetaData['themeType'],
                'type' => $questionMetaData['type'],
                'extends' => $questionMetaData['extends'],
                'group' => $questionMetaData['group'],
                'settings' => $questionMetaData['settings']
            ];
            return $questionMetaData;
        }

        /**
         * Return the question Theme preview URL
         * @param $sType: type pof question
         * @return string : question theme preview URL
         */
        public static function getQuestionThemePreviewUrl($sType = null)
        {
            if ($sType == '*'){
                $preview_filename = 'EQUATION.png';
            } elseif ($sType == ':'){
                $preview_filename = 'COLON.png';
            } elseif ($sType == '|'){
                $preview_filename = 'PIPE.png';
            } elseif (!empty($sType)) {
                $preview_filename = $sType.'.png';
            } else {
                $preview_filename = '.png';
            }

            return App()->getConfig("imageurl").'/screenshots/'.$preview_filename;
        }

    }