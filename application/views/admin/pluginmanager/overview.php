<?php
/**
 * @var array $plugin Plugin model attributes (database values)
 * @var PluginBase $pluginObject
 * @var xml $config Config XML
 * @var xml $metadata Metadata config
 */
?>

<div class="col-sm-6">

    <!-- Name -->
    <div class="form-group col-sm-12">
        <label class="col-sm-4 control-label"><?php eT("Name:"); ?></label>
        <div class="col-sm-4"><?php echo $metadata->name; ?></div>
    </div>

    <!-- Version -->
    <div class="form-group col-sm-12">
        <label class="col-sm-4 control-label"><?php eT("Version:"); ?></label>
        <div class="col-sm-4"><?php echo $plugin['version']; ?></div>
    </div>

    <!-- Last updated -->
    <div class="form-group col-sm-12">
        <label class="col-sm-4 control-label"><?php eT("Last updated:"); ?></label>
        <div class="col-sm-4"><?php echo $metadata->last_update; ?></div>
    </div>

    <!-- Last updated -->
    <div class="form-group col-sm-12">
        <label class="col-sm-4 control-label"><?php eT("Compatible"); ?></label>
        <?php if ($plugin->isCompatible()): ?>
            <div class="col-sm-4"><span class="fa fa-check text-success"></span></div>
        <?php else: ?>
            <div class="col-sm-4"><span class="fa fa-times text-warning"></span></div>
        <?php endif; ?>
    </div>


    <!-- Active -->
    <div class="form-group col-sm-12">
        <label class="col-sm-4 control-label"><?php eT("Active:"); ?></label>
        <?php if ($plugin['active']): ?>
            <div class="col-sm-4"><span class="fa fa-check text-success"></span></div>
            <div class="col-sm-4">
                <a data-toggle="tooltip" title="<?php eT('Deactivate'); ?>" href='#activate' data-action='activate' data-id='<?php echo $plugin['id']; ?>' class='ls_action_changestate btn btn-warning btn-xs btntooltip'>
                   <span class='fa fa-power-off'></span>
                </a>
            </div>
        <?php else: ?>
            <div class="col-sm-4"><span class="fa fa-times text-warning"></span></div>
            <div class="col-sm-4">
                <a data-toggle="tooltip" title="<?php eT('Activate'); ?>" href='#activate' data-action='activate' data-id='<?php echo $plugin['id']; ?>' class='ls_action_changestate btn btn-default btn-xs btntooltip'>
                   <span class='fa fa-power-off'></span>
                </a>
            </div>
        <?php endif; ?>
    </div>


</div>
