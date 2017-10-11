<?php

class SurveymenuController extends Survey_Common_Action
{

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function update($id=0)
	{
		if($id != 0)
			$model = $this->loadModel($id);
		else 
			$model = new Surveymenu();
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		
		$success = false;
		if(Yii::app()->request->isPostRequest)
		{
			$aSurveymenu = Yii::app()->request->getPost('Surveymenu', []);
			if($aSurveymenu['id'] == ''){
				unset($aSurveymenu['id']);
				$aSurveymenu['created_at'] = date('Y-m-d H:i:s');
				$aSurveymenu['parent_id'] = (int) $aSurveymenu['parent_id'];
				if($aSurveymenu['parent_id'] > 0)
					$aSurveymenu['level'] = ((Surveymenu::model()->findByPk($aSurveymenu['parent_id'])->level)+1);
			}

			$model->setAttributes($aSurveymenu);
			if($model->save()){
				$model->id = $model->getPrimaryKey();
				$success = true;
			}
		}

		return Yii::app()->getController()->renderPartial(
            '/admin/super/_renderJson',
            array(
                'data' => [
                    'success'=> $success,
					'redirect' => $this->getController()->createUrl('admin/menus/sa/view'),
					'debug' => [$model,$aSurveymenu, $_POST],
					'debugErrors' => $model->getErrors(),
                    'settings' => array(
                        'extrasettings' => false,
                        'parseHTML' => false,
                    )
                ]
            ),
            false,
            false
        );
		
	}
    public function batchEdit()
    {
        $aSurveyMenuEntryIds = json_decode(Yii::app()->request->getPost('sItems'));
        $aResults     = array();
        $oBaseModel = SurveymenuEntries::model();
        if ( Permission::model()->hasGlobalPermission('settings','update') )
        {

            // First we create the array of fields to update
            $aData = array();
            $aResults['global']['result']  = true;

            // Core Fields
            $aCoreTokenFields = array('position', 'parent',  'survey', 'user', 'language');

            foreach($aCoreTokenFields as $sCoreTokenField)
            {
                if (trim(Yii::app()->request->getPost($sCoreTokenField, 'lskeep')) != 'lskeep')
                {
                    $aData[$sCoreTokenField] = flattenText(Yii::app()->request->getPost($sCoreTokenField));
                }
            }
            
            if (count($aData) > 0)
            {
                foreach ($aSurveyMenuEntryIds as $iSurveyMenuEntryId){
                    $iSurveyMenuEntryId = (int) $iSurveyMenuEntryId;
                    $oSurveyMenuEntry = SurveymenuEntries::model()->findByPk($iSurveyMenuEntryId);

                    foreach ($aData as $k => $v){
                        $oSurveyMenuEntry->$k = $v;
                    }

                    $bUpdateSuccess = $oSurveyMenuEntry->update();
                    if ( $bUpdateSuccess ){
                        $aResults[$iSurveyMenuEntryId]['status']    = true;
                        $aResults[$iSurveyMenuEntryId]['message']   = gT('Updated');
                    }else{
                        $aResults[$iSurveyMenuEntryId]['status']    = false;
                        $aResults[$iSurveyMenuEntryId]['message']   = $oSurveyMenuEntry->error;
                    }
                }
            }
            else
            {
                $aResults['global']['result']  = false;
                $aResults['global']['message'] = gT('Nothing to update');
            }

        } 
        else 
        {
            $aResults['global']['result'] = false;
            $aResults['global']['message'] = gT("We are sorry but you don't have permissions to do this.");
        }

        $oBaseModel->reorder();

        Yii::app()->getController()->renderPartial('/admin/surveymenu/massive_action/_update_results', array('aResults'=>$aResults));

    }
	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function delete()
	{
		if( Yii::app()->request->isPostRequest )
		{
			$menuid = Yii::app()->request->getPost('menuid', 0);
			$success = false;
			$model = $this->loadModel($menuid);
			$success = $model->delete();

			return Yii::app()->getController()->renderPartial(
				'/admin/super/_renderJson',
				array(
					'data' => [
						'success'=> $success,
						'redirect' => $this->getController()->createUrl('admin/menus/sa/view'),
						'debug' => [$model, $_POST],
						'debugErrors' => $model->getErrors(),
						'settings' => array(
							'extrasettings' => false,
							'parseHTML' => false,
						)
					]
				),
				false,
				false
			);
		}
	}

	/**
	 * Restores the default surveymenus
	 */
	public function restore(){
        if( Yii::app()->request->isPostRequest )
		{
            //Check for permission!
            if(!Permission::model()->hasGlobalPermission('superadmin','read'))
            {
                return Yii::app()->getController()->renderPartial(
                    '/admin/super/_renderJson',
                    array(
                        'data' => [
                            'success'=> false,
                            'redirect' => false,
                            'debug' => [$model, $_POST],
                            'debugErrors' => $model->getErrors(),
                            'message' => gT("You don't have the right to restore the settings to default")
                        ]
                    ),
                    false,
                    false
                );
            }
            //get model to do the work
            $model=Surveymenu::model();
            $success = $model->restoreDefaults();
            return Yii::app()->getController()->renderPartial(
                '/admin/super/_renderJson',
                array(
                    'data' => [
                        'success'=> $success,
                        'redirect' => false,
                        'debug' => [$model, $_POST],
                        'debugErrors' => $model->getErrors(),
                        'message' =>  ($success ? gT("Default survey menus restored.") : gT("Something went wrong!"))
                    ]
                ),
                false,
                false
            );
        }
    }

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Surveymenu the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Surveymenu::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Surveymenu $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='surveymenu-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function index()
    {
        $this->getController()->redirect(array('admin/menus/sa/view'));
    }

	public function view()
	{
		//$this->checkPermission();

        $data = array();
        $data['model'] = Surveymenu::model();
        App()->getClientScript()->registerPackage('surveymenufunctions');
        $this->_renderWrappedTemplate(null, array('surveymenu/index'), $data);
	}

	public function getsurveymenuform($menuid=null){
		$menuid = Yii::app()->request->getParam('menuid', null);
		if($menuid != null)
		{
        	$model = Surveymenu::model()->findByPk(((int) $menuid));
		} else 
		{
        	$model = new Surveymenu();
		}
		$user = Yii::app()->session['loginID'];
		return Yii::app()->getController()->renderPartial('/admin/surveymenu/_form', array('model'=>$model, 'user'=>$user));
	}
}
