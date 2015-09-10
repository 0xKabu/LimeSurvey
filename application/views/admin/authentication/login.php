<div class="container-fluid welcome">
    <div class="row text-center">
		<div class="col-lg-3 col-md-offset-4">
			<div class="panel panel-primary login-pannel" id="pannel-1">

			<div class="panel-body">
				<div class="row">
            		<img id="profile-img" class="profile-img-card" src="<?php echo Yii::app()->getBaseUrl(true);?>/images/Limesurvey_logo.png" style="height: 100px; "/>
        			<p>Dashboard</p>					
				</div>

      			</div>
      			<div class="row login-title login-content">
					<h3><?php eT("Log In");?></h3>
				</div>
				<div class="row login-content login-content-form">
					

					<?php echo CHtml::form(array('admin/authentication/sa/login'), 'post', array('id'=>'loginform', 'name'=>'loginform'));?>
					        
					            <?php 
					            	$pluginNames = array_keys($pluginContent);
					            	if (!isset($defaultAuth)) 
					            	{
					                	// Make sure we have a default auth, if not set, use the first one we find
					                	$defaultAuth = reset($pluginNames);
					            	}
					            
					            	if (count($pluginContent)>1) 
					            	{
					                	$selectedAuth = App()->getRequest()->getParam('authMethod', $defaultAuth);
					                	if (!in_array($selectedAuth, $pluginNames)) 
					                	{
					                    	$selectedAuth = $defaultAuth;
					                	}
					          ?>
									<label for='authMethod'><?php eT("Authentication method"); ?></label>
							<?php
					                	$possibleAuthMethods = array();
					                	foreach($pluginNames as $plugin) 
					                	{
					                    	$info = App()->getPluginManager()->getPluginInfo($plugin);
					                    	$possibleAuthMethods[$plugin] = $info['pluginName'];
					                	}
					                	$this->widget('yiiwheels.widgets.select2.WhSelect2', array(
					                
					                    'name' => 'authMethod',
					                    'pluginOptions' => array(
					                    'value' => $selectedAuth,
					                    'data' => $possibleAuthMethods,
					                    'options' => array(
					                        'onChange'=>'this.form.submit();'
					                    )
					                	)));				
									
									
					            	} 
					            	else 
					            	{
					                	echo CHtml::hiddenField('authMethod', $defaultAuth);
					                	$selectedAuth = $defaultAuth;
					            	}
					          		?>
					          		
					          	<?php
					            if (isset($pluginContent[$selectedAuth])) {
					                $blockData = $pluginContent[$selectedAuth];
					                /* @var $blockData PluginEventContent */
					                echo $blockData->getContent();
					            }
					
					            $languageData = array(
					                'default' => gT('Default')
					            );
					            foreach (getLanguageDataRestricted(true) as $sLangKey => $aLanguage)
					            {
					                $languageData[$sLangKey] =  html_entity_decode($aLanguage['nativedescription'], ENT_NOQUOTES, 'UTF-8') . " - " . $aLanguage['description'];
					            }
					            echo CHtml::label(gT('Language'), 'loginlang');
								
								//$this->widget('bootstrap.widgets.TbSelect2', array(
								
								$this->widget('yiiwheels.widgets.select2.WhSelect2', array(
					            
					                'name' => 'loginlang',
					                'data' => $languageData,
					                'pluginOptions' => array(
					                'options' => array(
					                    'width' => '230px'
					                ),
					                'htmlOptions' => array(
					                    'id' => 'loginlang'
					                ),
					                'value' => 'default'
					            )));
					            ?>

					        <?php   if (Yii::app()->getConfig("demoMode") === true && Yii::app()->getConfig("demoModePrefill") === true)
					        { ?>
					        <p><?php eT("Demo mode: Login credentials are prefilled - just click the Login button."); ?></p>
					        <?php } ?>
					
					
				</div>			
				<div class="row login-submit login-content">

					        <p><input type='hidden' name='action' value='login' />
					            <button type="submit" class="btn btn-default" name='login_submit' value='<?php eT("Login"); ?>' >Submit</button><br />
					            <br/>
					            <?php
					            if (Yii::app()->getConfig("display_user_password_in_email") === true)
					            {
					                ?>
					                <a href='<?php echo $this->createUrl("admin/authentication/sa/forgotpassword"); ?>'><?php eT("Forgot your password?"); ?></a><br />
					                <?php
					            }
					            ?>
					        </p>


				</div>
				<?php echo CHtml::endForm(); ?>							
			</div>
			

		</div>
	</div>

</div>	
    




<script type='text/javascript'>
    document.getElementById('user').focus();
</script>
