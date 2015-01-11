<?php
    /**
     *
     * For code completion we add the available scenario's here
     * Attributes
     * @property int      $tid
     * @property string   $firstname
     * @property string   $lastname
     * @property string   $email
     * @property string   $emailstatus
     * @property string   $token
     * @property string   $language
     * @property string   $blacklisted
     * @property string   $sent
     * @property string   $remindersent
     * @property int      $remindercount
     * @property string   $completed
     * @property int      $usesleft
     * @property DateTime $validfrom
     * @property DateTime $validuntil
     *
     * Relations
     * @property Survey $survey The survey this token belongs to.
     *
     * Scopes
     * @method Token incomplete() incomplete() Select only uncompleted tokens
     * @method Token usable() usable() Select usable tokens: valid daterange and userleft > 0
     *
     */
    abstract class Token extends Dynamic
    {

        public function attributeLabels() {
            $labels = array(
                'tid' => gT('Token ID'),
                'partcipant' => gt('Participant ID'),
                'firstname' => gT('First name'),
                'lastname' => gT('Last name'),
                'email' => gT('Email address'),
                'emailstatus' => gT('Email status'),
                'token' => gT('Token'),
                'language' => gT('Language code'),
                'blacklisted' => gT('Blacklisted'),
                'sent' => gT('Invitation sent date'),
                'remindersent' => gT('Last reminder sent date'),
                'remindercount' =>gT('Total numbers of sent reminders'),
                'completed' => gT('Completed'),
                'usesleft' => gT('Uses left'),
                'validfrom' => gT('Valid from'),
                'validuntil' => gT('Valid until'),
            );
            // Check if we have custom attributes.
			if ($this->hasAttribute('attribute_1'))
            {
				foreach (decodeTokenAttributes($this->survey->attributedescriptions) as $key => $info)
                {
                    $labels[$key] = $info['description'];
                }
            }
            return $labels;
        }

        public function beforeDelete() {
            $result = parent::beforeDelete();
            if ($result && isset($this->surveylink))
            {
                if (!$this->surveylink->delete())
                {
                    throw new CException('Could not delete survey link. Token was not deleted.');
                }
                return true;
            }
            return $result;
        }

        public static function createTable($surveyId, array $extraFields = [])
        {
            /**
             * @todo Specify "smaller" character sets and faster collations.
             */
            $fields = [
                'tid' => 'pk',
                'participant_id' => 'string(50)',
                'firstname' => 'string(40)',
                'lastname' => 'string(40)',
                'email' => 'text',
                'emailstatus' => 'text',
                'token' => 'string(35)',
                'language' => 'string(25)',
                'blacklisted' => 'string(17)',
                'sent' => "string(17) DEFAULT 'N'",
                'remindersent' => "string(17) DEFAULT 'N'",
                'remindercount' => 'integer DEFAULT 0',
                'completed' => "string(17) DEFAULT 'N'",
                'usesleft' => 'integer DEFAULT 1',
                'validfrom' => 'datetime',
                'validuntil' => 'datetime',
                'mpid' => 'integer'
            ];
            foreach ($extraFields as $extraField) {
                $fields[$extraField] = 'text';
            }


    try{
        $sTableName="{{tokens_".intval($iSurveyID)."}}";
        createTable($sTableName, $fields);
        try{
            Yii::app()->db->createCommand()->createIndex("idx_token_token_{$iSurveyID}_".rand(1,50000),"{{tokens_".intval($iSurveyID)."}}",'token');
        } catch(Exception $e) {}
        // create fields for the custom token attributes associated with this survey
        $tokenattributefieldnames = Survey::model()->findByPk($iSurveyID)->tokenAttributes;
        foreach($tokenattributefieldnames as $attrname=>$attrdetails)
        {
            if (isset($fields[$attrname])) continue; // Field was already created
            Yii::app()->db->createCommand(Yii::app()->db->getSchema()->addColumn("{{tokens_".intval($iSurveyID)."}}", $attrname, 'string(255)'))->execute();
        }
        Yii::app()->db->schema->getTable($sTableName, true); // Refresh schema cache just in case the table existed in the past
        return true;
    } catch(Exception $e) {
        return false;
    }

        }
        public function findByToken($token)
        {
            return $this->findByAttributes(array(
                'token' => $token
            ));
        }

        public function generateToken()
        {
            $length = $this->survey->tokenlength;
            $this->token = randomChars($length);
            $counter = 0;
            while (!$this->validate('token'))
            {
                $this->token = randomChars($length);
                $counter++;
                // This is extremely unlikely.
                if ($counter > 10)
                {
                    throw new CHttpException(500, 'Failed to create unique token in 10 attempts.');
                }
            }
        }

        /**
         *
         * @param mixed $className Either the classname or the survey id.
         * @return Token
         */
        public static function model($className = null) {
            return parent::model($className);
        }

        /**
         * 
         * @param int $surveyId
         * @param string $scenario
         * @return Token Description
         */
        public static function create($surveyId, $scenario = 'insert') {
            return parent::create($surveyId, $scenario);
        }

        public function relations()
        {
            $result = array(
                'responses' => array(self::HAS_MANY, 'Response_' . $this->dynamicId, array('token' => 'token')),
                'survey' =>  array(self::BELONGS_TO, 'Survey', '', 'on' => "sid = {$this->dynamicId}"),
                'surveylink' => array(self::BELONGS_TO, 'SurveyLink', array('participant_id' => 'participant_id'), 'on' => "survey_id = {$this->dynamicId}")
            );
            return $result;
        }

        public function rules()
        {
            return array(
                array('token', 'unique', 'allowEmpty' => true),
                array(implode(',', $this->tableSchema->columnNames), 'safe'),
                array('remindercount','numerical', 'integerOnly'=>true,'allowEmpty'=>true), 
                array('email','filter','filter'=>'trim'),
                array('email','LSYii_EmailIDNAValidator', 'allowEmpty'=>true, 'allowMultiple'=>true,'except'=>'allowinvalidemail'),
                array('usesleft','numerical', 'integerOnly'=>true,'allowEmpty'=>true),
                array('mpid','numerical', 'integerOnly'=>true,'allowEmpty'=>true),
                array('blacklisted', 'in','range'=>array('Y','N'), 'allowEmpty'=>true),
                array('emailstatus', 'default', 'value' => 'OK'),
            );
        }

        public function scopes()
        {
            $now = dateShift(date("Y-m-d H:i:s"), "Y-m-d H:i:s", Yii::app()->getConfig("timeadjust"));
            return array(
                'incomplete' => array(
                    'condition' => "completed = 'N'"
                ),
                'usable' => array(
                    'condition' => "COALESCE(validuntil, '$now') >= '$now' AND COALESCE(validfrom, '$now') <= '$now'"
                ),
                'editable' => array(
                    'condition' => "COALESCE(validuntil, '$now') >= '$now' AND COALESCE(validfrom, '$now') <= '$now'"
                )
            );
        }

        public function summary()
        {
            $criteria = $this->getDbCriteria();
            $criteria->select = array(
                "COUNT(*) as count",
                "COUNT(CASE WHEN (token IS NULL OR token='') THEN 1 ELSE NULL END) as invalid",
                "COUNT(CASE WHEN (sent!='N' AND sent<>'') THEN 1 ELSE NULL END) as sent",
                "COUNT(CASE WHEN (emailstatus LIKE 'OptOut%') THEN 1 ELSE NULL END) as optout",
                "COUNT(CASE WHEN (completed!='N' and completed<>'') THEN 1 ELSE NULL END) as completed",
                "COUNT(CASE WHEN (completed='Q') THEN 1 ELSE NULL END) as screenout",
            );
            $command = $this->getCommandBuilder()->createFindCommand($this->getTableSchema(),$criteria);
            return $command->queryRow();
        }

        public function tableName()
        {
            return '{{tokens_' . $this->dynamicId . '}}';
        }
    }

?>
