<tr class='<?php echo $bgcc; ?>' valign='top'>
    <td align='center'><input type='checkbox' class='cbResponseMarker' value='<?php echo $dtrow['id']; ?>' name='markedresponses[]' /></td>
    <td align='center'>
        <a href='<?php echo $this->createUrl("/admin/browse/view/surveyid/{$iSurveyId}/id/{$dtrow['id']}"); ?>'><img src='<?php echo $imageurl; ?>/token_viewanswer.png' alt='<?php $clang->eT('View response details'); ?>'/></a>
        <a href='<?php echo $this->createUrl("/admin/dataentry/surveyid/{$iSurveyId}/edit/id/{$dtrow['id']}"); ?>'><img src='<?php echo $imageurl; ?>/edit_16.png' alt='<?php $clang->eT('Edit this response'); ?>'/></a>
        <a><img id='deleteresponse_<?php echo $dtrow['id']; ?>' src='<?php echo $imageurl; ?>/token_delete.png' alt='<?php $clang->eT('Delete this response'); ?>' class='deleteresponse'/></a>
    </td>
    <td align='center'>$browsedatafield</td>
</tr>
