<div class='messagebox ui-corner-all'>
    <div class='header ui-widget-header'>
        <?php $clang->eT("Sending invitations..."); ?>
    </div>
    <?php
    if ($tokenids)
    {
        echo " (" . $clang->gT("Sending to Token IDs") . ":&nbsp;" . implode(", ", $tokenids) . ")";
    }
    ?>
    <br />
</div>
