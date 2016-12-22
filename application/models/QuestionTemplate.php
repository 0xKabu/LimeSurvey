<?php

if (!defined('BASEPATH'))
    die('No direct script access allowed');
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
  */


class QuestionTemplate extends CFormModel
{
    // Main variables
    public  $oQuestion;                                                         // The current question
    public  $bHasTemplate;                                                      // Does this question has a template?
    public  $sTemplateFolderName;                                               // The folder of the template applied to this question (if no template applied, it's false)
    public  $aViews;                                                            // Array of views the template can handle ($aViews['path_to_my_view']==true)

    private $sTemplatePath;                                                     // The path to the template
    private $bHasConfigFile;
    private $xmlFile;                                                           // The path to the xml file
    private $bLoadCoreJs;                                                       // Should it render the core javascript of this question (script are registered in qanda)
    private $bLoadCoreCss;                                                      // Should it render the core CSS of this question (script are registered in qanda)
    private $bLoadCorePackage;                                                  // Should it render the core packages of this question (script are registered in qanda)

    /** @var Template - The instance of question template object */
    private static $instance;



    /**
     * Get a new instance of the template object
     * Each question on the page could have a different template.
     * So each question must have a new instance
     */
    public static function getNewInstance($oQuestion)
    {
        self::$instance = new QuestionTemplate();
        self::$instance->oQuestion = $oQuestion;
        self::$instance->aViews    = array();
        self::$instance->getQuestionTemplateFolderName();                       // Will initiate $sTemplateFolderName and $bHasTemplate.
        return self::$instance;
    }

    /**
     * Get the current instance of template question object.
     *
     * @param string $sTemplateName
     * @param int $iSurveyId
     * @return TemplateConfiguration
     */
    public static function getInstance($oQuestion=null)
    {
        if (empty(self::$instance) && $oQuestion!=null){
            self::getNewInstance($oQuestion);
        }
        return self::$instance;
    }

    /**
     * Check if the question template offer a specific remplacement for that view file.
     */
    public function checkIfTemplateHasView($sView)
    {
        if( !isset( $this->aViews[$sView])){
            $sTemplateFolderName    = $this->getQuestionTemplateFolderName();
            $sUserQTemplateRootDir  = Yii::app()->getConfig("userquestiontemplaterootdir");
            $sTemplatePath          = $this->getTemplatePath();

            if (is_file("$sTemplatePath/$sView.twig") ){
                $this->aViews[$sView] = true;
            }else{
                $this->aViews[$sView] = false;
            }

        }
        return $this->aViews[$sView];
    }

    /**
     * Retreive the template base path
     */
    public function getTemplatePath()
    {
        if (!isset($this->sTemplatePath)){
            $sTemplateFolderName    = $this->getQuestionTemplateFolderName();
            $sUserQTemplateRootDir  = Yii::app()->getConfig("userquestiontemplaterootdir");
            $this->sTemplatePath = "$sUserQTemplateRootDir/$sTemplateFolderName/";
        }
        return $this->sTemplatePath;
    }

    /**
     * Get the template folder name
     */
    public function getQuestionTemplateFolderName()
    {
        if ($this->sTemplateFolderName===null){
            $aQuestionAttributes       = QuestionAttribute::model()->getQuestionAttributes($this->oQuestion->qid);
            $this->sTemplateFolderName = ($aQuestionAttributes['question_template'] != 'core')?$aQuestionAttributes['question_template']:false;
        }
        $this->bHasTemplate       = ($this->sTemplateFolderName!=false);
        return $this->sTemplateFolderName;
    }

    /**
     * Register a core script file
     */
    public function registerScriptFile($sFile, $pos = CClientScript::POS_HEAD)
    {
        if ($this->templateLoadsCoreJs){
            Yii::app()->getClientScript()->registerScriptFile($sFile, $pos);
        }
    }

    /**
     * Register a core script
     */
    public function registerScript($sScript, $pos = CClientScript::POS_HEAD)
    {
        if ($this->templateLoadsCoreJs){
            Yii::app()->getClientScript()->registerScript($sScript, $pos);
        }
    }

    /**
     * Register a core css file
     */
    public function registerCssFile( $sCssFile, $pos = CClientScript::POS_HEAD)
    {
        if ($this->templateLoadsCoreCss){
            Yii::app()->getClientScript()->registerCssFile($sCssFile, $pos);
        }
    }

    /**
     * Register a core package file
     */
    public function registerPackage($sPackage)
    {
        if ($this->templateLoadsCorePackage){
            Yii::app()->getClientScript()->registerPackage($sPackage);
        }
    }

    /**
     * Return true if the core css should be loaded.
     */
    public function templateLoadsCoreJs()
    {
        if (!isset($this->bLoadCoreJs)){
            if ($this->bHasTemplate){

                // Init config ($this->bHasConfigFile and $this->bLoadCoreJs )
                $this->setConfig();
                if ($this->bHasConfigFile){
                    return $this->bLoadCoreJs;
                }
            }
            $this->bLoadCoreJs = true;
        }
        return $this->bLoadCoreJs;
    }

    /**
     * Return true if the core css should be loaded.
     */
    public function templateLoadsCoreCss()
    {
        if (!isset($this->bLoadCoreCss)){
            if ($this->bHasTemplate){

                // Init config ($this->bHasConfigFile and $this->bLoadCoreCss )
                $this->setConfig();
                if ($this->bHasConfigFile){
                    return $this->bLoadCoreCss;
                }
            }
            $this->bLoadCoreCss = true;
        }
        return $this->bLoadCoreCss;
    }

    /**
     * Return true if the core packages should be loaded.
     */
    public function templateLoadsCorePackage()
    {
        if (!isset($this->bLoadCorePackage)){
            if ($this->bHasTemplate){

                // Init config ($this->bHasConfigFile and $this->bLoadCorePackage )
                $this->setConfig();
                if ($this->bHasConfigFile){
                    return $this->bLoadCorePackage;
                }
            }
            $this->bLoadCoreCss = true;
        }
        return $this->bLoadCoreCss;
    }


    /**
     * In the future, could retreive datas from DB
     */
    public function setConfig()
    {
        if (!isset($this->config)){
            $sTemplatePath        = $this->getTemplatePath();
            $xmlFile              = $this->sTemplatePath.'/config.xml';
            $this->bHasConfigFile = is_file($configFilePath);

            if ($this->bHasConfigFile){
                $this->xmlFile          = $configFilePath;
                $sXMLConfigFile         = file_get_contents( realpath ($this->xmlFile));  // Entity loader is disabled, so we can't use simplexml_load_file; so we must read the file with file_get_contents and convert it as a string
                $this->config           = simplexml_load_string($sXMLConfigFile);
                $this->loadCoreJs       = $this->config->engine->load_core_js;
                $this->loadCoreCss      = $this->config->engine->load_core_css;
                $this->loadCorePackage  = $this->config->engine->load_core_package;
            }
        }
    }

    /**
     * Called from admin, to generate the template list for a given question type
     */
    static public function getQuestionTemplateList($type)
    {
        $sUserQTemplateRootDir  = Yii::app()->getConfig("userquestiontemplaterootdir");
        $aQuestionTemplates     = array();

        $aQuestionTemplates['core'] = gT('Default');

        $aTypeToFolder  = self::getTypeToFolder($type);
        $sFolderName    = $aTypeToFolder[$type];

        if ($sUserQTemplateRootDir && is_dir($sUserQTemplateRootDir) ){

            $handle = opendir($sUserQTemplateRootDir);
            while (false !== ($file = readdir($handle))){
                // Maybe $file[0] != "." to hide Linux hidden directory
                if (!is_file("$sUserQTemplateRootDir/$file") && $file != "." && $file != ".." && $file!=".svn"){

                        if (is_dir("$sUserQTemplateRootDir/$file/survey/questions/answer/$sFolderName")){
                            $templateName = $file;
                            $aQuestionTemplates[$file] = $templateName;
                        }
                    }
                }
        }
        return $aQuestionTemplates;
    }

    /**
     * Correspondance between question type and the view folder name
     * Rem: should be in question model. We keep it here for easy access
     */
    static public function getTypeToFolder()
    {
        return array(
            "1" => 'arrays/dualscale',
            "5" => '5pointchoice',
            "A" => 'arrays/5point',
            "B" => 'arrays/10point',
            "C" => 'arrays/yesnouncertain',
            "D" => 'date',
            "E" => 'arrays/increasesamedecrease',
            "F" => 'arrays/array',
            "G" => 'gender',
            "H" => 'arrays/column',
            "I" => 'language',
            "K" => 'multiplenumeric',
            "L" => 'listradio',
            "M" => 'multiplechoice',
            "N" => 'numerical',
            "O" => 'list_with_comment',
            "P" => 'multiplechoice_with_comments',
            "Q" => 'multipleshorttext',
            "R" => 'ranking',
            "S" => 'shortfreetext',
            "T" => 'longfreetext',
            "U" => 'longfreetext',
            "X" => 'boilerplate',
            "Y" => 'yesno',
            "!" => 'list_dropdown',
            ":" => 'arrays/multiflexi',
            ";" => 'arrays/texts',
            "|" => 'file_upload',
            "*" => 'equation',
        );
    }

}
