<?php
class Example extends PluginBase {

    protected $storage = 'DbStorage';    
    static protected $description = 'Example plugin';
    static protected $name = 'Example';
    
    protected $settings = array(
        'logo' => array(
                'type' => 'logo',
                'path' => 'assets/logo.png'
            ),
        'message' => array(
            'type' => 'string',
            'label' => 'Message'
        )
    );
    
    public function __construct(PluginManager $manager, $id) {
        parent::__construct($manager, $id);
        
        
        /**
         * Here you should handle subscribing to the events your plugin will handle
         */
        $this->subscribe('afterPluginLoad', 'helloWorld');
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');
    }
    
    
    /*
     * Below are the actual methods that handle events
     */
    public function helloWorld() 
    {
        $this->pluginManager->getAPI()->setFlash($this->get('message', null, null, 'Example popup. Change this via plugin settings.'));
    }
    
    /**
     * This event is fired by the administration panel to gather extra settings
     * available for a survey.
     * The plugin should return setting meta data.
     */
    public function beforeSurveySettings()
    {
        $event = $this->getEvent();
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'message' => array(
                    'type' => 'string',
                    'label' => 'Example survey specific setting (not used):',
                    'current' => $this->get('message', 'Survey', $event->get('survey'))
                )
            )
         ));
    }
    
    public function newSurveySettings()
    {
        $event = $this->getEvent();
        foreach ($event->get('settings') as $name => $value)
        {
            
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }

}
