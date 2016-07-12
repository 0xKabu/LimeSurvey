<?php
/**
 * Set question group and position modal body (parsed to massive action widget)
 * @var $model      The question model
 * @var $oSurvey    The survey object
 */
?>
<form class="custom-modal-datas">
    <div  class="form-group" id="OtherSelection">
        <label class="col-sm-4 control-label"><?php eT("Option 'Other':"); ?></label>
        <div class="col-sm-8">
            <?php $this->widget('yiiwheels.widgets.switch.WhSwitch', array('name' => 'other', 'value'=> '', 'htmlOptions'=>array('class'=>'custom-data'), 'onLabel'=>gT('On'),'offLabel'=>gT('Off')));?>
        </div>
        <input type="hidden" name="sid" value="<?php echo $_GET['surveyid']; ?>" class="custom-data"/>
    </div>
</form>
<script>
$(document).ready(function() {
    $('#other').on('switchChange.bootstrapSwitch', function(event, state) {
        $('#other').attr('value', state);
        console.log(state);
    });
}
</script>
