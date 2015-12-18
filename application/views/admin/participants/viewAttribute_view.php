<script type="text/javascript">
    var url = "<?php echo Yii::app()->getController()->createUrl("admin/participants/sa/getAttributeBox"); ?>";
    var attname = "<?php eT("Attribute name:"); ?>";
    removeitem = new Array(); // Array to hold values that are to be removed from langauges option
</script>

<?php
    $aOptions = array();
    $aOptions[''] = gT('Select...');
    foreach (getLanguageData(false, Yii::app()->session['adminlang']) as $langkey2 => $langname)
    {
        $aOptions[$langkey2] = $langname['description'];
    }
?>

<div class="col-lg-12 list-surveys">
    <h3><?php eT("Attribute settings"); ?></h3>

    <div class="row">
        <div class="col-lg-12 content-right">

    <?php echo CHtml::beginForm(Yii::app()->getController()->createUrl('admin/participants/sa/saveAttribute/aid/' . Yii::app()->request->getQuery('aid')) . '/', "post",array('class'=>'form-inline col-md-6  col-md-offset-3')); ?>

    <div class="form-group"><label for="atttype"><?php eT('Default attribute name:'); ?></label>
        <?php echo CHtml::textField('defaultname', $attributes['defaultname'],array('required'=>'required')); ?>
    </div>
    <div class="form-group "><label for="atttype"><?php eT('Attribute type:'); ?></label>
        <?php
            echo CHtml::dropDownList('attribute_type', $attributes['attribute_type'], array(
                'TB' => 'Text box',
                'DD' => 'Drop-down list',
                'DP' => 'Date'),
                array('class'=>'form-control')

                );
        ?>
    </div>
    <div class="form-group"><label for='attvisible' id='attvisible'><?php eT('Attribute visible:') ?></label>
        <?php  echo CHtml::checkbox('visible', ($attributes['visible'] == "TRUE"),array('value'=>'TRUE','uncheckValue'=>'FALSE')); ?>
    </div>


<div id='ddtable' style='display: none'>
    <br/><br/>
    <table class='hovertable table table-striped'>
        <thead>
            <tr>
                <th colspan='2'><?php eT('Values:'); ?></th>
            </tr>
        </thead>
        <?php
            foreach ($attributevalues as $row => $value)
            {
            ?>
            <tr>
                <td class='data' data-text='<?php echo $value['value']; ?>' data-id='<?php echo $value['value_id']; ?>'>
                    <div class=editable id="<?php echo $value['value_id']; ?>">
                        <?php
                            echo $value['value'];
                        ?>
                    </div>
                </td>
                <td class='actions'>
                    <span class="glyphicon glyphicon-remove-circle text-warning cancel" title="<?php eT('Cancel editing'); ?>"></span>
                    <span class="glyphicon glyphicon-pencil text-success edit" name="<?php echo $value['value_id']; ?>" title="<?php eT('Edit value'); ?>"></span>
                    <a href="<?php echo $this->createUrl('admin/participants/sa/delAttributeValues/aid/' . $attributes['attribute_id'] . '/vid/' . $value['value_id']); ?>" title="<?php eT('Delete value'); ?>" >
                        <span class="glyphicon glyphicon-trash text-warning delete" title="<?php eT('Delete value'); ?>"></span>
                    </a>
                </td>
            </tr>
            <?php
            }
        ?>
    </table>
    <table>
        <tr>
            <td></td>
            <td class='actions'>
                <a href='#' class='add'>
                    <span class="icon-add text-success" title='<?php eT("Add value") ?>' id='addsign' name='addsign'></span>
                </a>
            </td>
        </tr>
    </table>
</div>

<div id="addlang">
    <table width='400' >
        <tr>
            <th colspan='2'>
                <?php eT('Add a language:'); ?>
            </th>
        </tr>
        <tr>
            <td class='data'>
                <?php
                    echo CHtml::dropDownList('langdata', '', $aOptions, array('class'=>'form-control'));
                ?>
            </td>
            <td class='actions'>
                <span class="icon-add text-success" id="add" title="<?php eT('Add language'); ?>" ></span>
            </td>
        </tr>
    </table>
</div>


<ul class="nav nav-tabs" id="">
        <?php foreach ($attributenames as $key => $value): ?>
            <li role="presentation" <?php if($key==0){ echo 'class="active"'; }?>>
                <a data-toggle="tab" href='#<?php echo $value['lang']; ?>'>
                    <?php echo $aOptions[$value['lang']] ?>
                </a>
                <script type='text/javascript'>
                    removeitem.push('<?php echo $value['lang'] ?>');
                </script>
            </li>
        <?php endforeach;?>
    </ul>
    <?php
        foreach ($attributenames as $key => $value)
        {
        ?>
        <div class='commonsettings'>
            <div id="<?php echo $value['lang'] ?>">
                <table width='400' class='nudgeleft'>
                    <tr>
                        <th>
                            <label for='attname' id='attname'>
                                <?php eT('Attribute name:'); ?>
                            </label>
                        </th>
                    </tr>
                    <tr>
                        <td class='data'>
                            <?php echo CHtml::textField('lang[' . $value['lang'] . ']', $value['attribute_name'], array('class'=>'languagesetting', 'style'=>'border: 1px solid #ccc')); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
        }
        echo CHtml::hiddenField('attname', $value['attribute_name']);
    ?>
</div>



<p>
    <?php
        echo CHtml::submitButton('submit', array('value' => gT('Save')));
        echo CHtml::endForm();
    ?>
</p>



        </div>
    </div>
</div>
