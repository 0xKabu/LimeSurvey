<div class='menubar'>
    <div class='menubar-title ui-widget-header'>
        <strong><?php $clang->eT("Label Set"); ?>:</strong> <?php echo $row['label_name']; ?>
    </div>
    <div class='menubar-main'>
        <div class='menubar-left'>
            <img src='<?php echo $sImageURL; ?>blank.gif' width='40' height='20' border='0' hspace='0' alt='' />
            <img src='<?php echo $sImageURL; ?>seperator.gif' border='0' hspace='0' alt='' />
            <a href='<?php echo $this->createUrl("admin/labels/editlabelset/lid/".$lid); ?>' title="<?php $clang->eTview("Edit label set"); ?>" >
 			<img name='EditLabelsetButton' src='<?php echo $sImageURL; ?>edit.png' alt='<?php $clang->eT("Edit label set"); ?>'  /></a>
 			<a href='#' title='<?php $clang->eTview("Delete label set"); ?>' onclick="if (confirm('<?php $clang->eT("Do you really want to delete this label set?","js"); ?>')) { <?php echo convertGETtoPOST($this->createUrl("admin/labels/process")."?action=deletelabelset&amp;lid=$lid"); ?>}" >
 			<img src='<?php echo $sImageURL; ?>delete.png' border='0' alt='<?php $clang->eT("Delete label set"); ?>' /></a>
 			<img src='<?php echo $sImageURL; ?>seperator.gif' border='0' hspace='0' alt='' />
 			<a href='<?php echo $this->createUrl("admin/export/dumplabel/lid/$lid");?>' title="<?php $clang->eTview("Export this label set"); ?>" >
            <img src='<?php echo $sImageURL; ?>dumplabel.png' alt='<?php $clang->eT("Export this label set"); ?>' /></a>
        </div>
        <div class='menubar-right'>
            <input type='image' src='<?php echo $sImageURL; ?>close.png' title='<?php $clang->eT("Close Window"); ?>' href="<?php echo $this->createUrl("admin/labels/view"); ?>" />
        </div>
    </div>
</div>
<p style='margin:0;font-size:1px;line-height:1px;height:1px;'>&nbsp;</p>