<tr class='<?php echo $bgcc; ?>' valign='top'>
    <td align='center'><input type='checkbox' class='cbResponseMarker' value='<?php echo $dtrow['id']; ?>' name='markedresponses[]' /></td>
    <td align='center'>
        <a href='<?php echo $this->createUrl("admin/browse/view/surveyid/$surveyid/id/{$dtrow['id']}"); ?>'><img src='<?php echo $imageurl; ?>/token_viewanswer.png' alt='<?php $clang->eT('View response details'); ?>'/></a>
        <?php if (hasSurveyPermission($surveyid, 'responses', 'update'))
        { ?>
            <a href='<?php echo $this->createUrl("admin/dataentry/editdata/subaction/edit/surveyid/{$surveyid}/id/{$dtrow['id']}"); ?>'><img src='<?php echo $imageurl; ?>/token_edit.png' alt='<?php $clang->eT('Edit this response'); ?>'/></a>
        <?php }
        if (hasFileUploadQuestion($surveyid))
        { ?>
            <a><img id='downloadfile_<?php echo $dtrow['id']; ?>' src='<?php echo $imageurl; ?>/down.png' alt='<?php $clang->eT('Download all files in this response as a zip file'); ?>' class='downloadfile'/></a>
            <?php }
            if (hasSurveyPermission($surveyid, 'responses', 'delete'))
            { ?>
            <a><img id='deleteresponse_<?php echo $dtrow['id']; ?>' src='<?php echo $imageurl; ?>/token_delete.png' alt='<?php $clang->eT('Delete this response'); ?>' class='deleteresponse'/></a>
    <?php } ?>
    </td>
    <?php
    $i = 0;
    if ($surveyinfo['anonymized'] == "N" && $dtrow['token'] && tableExists($tokentable))
    {
        if (isset($dtrow['tid']) && !empty($dtrow['tid']))
        {
            //If we have a token, create a link to edit it
            $browsedatafield = "<a href='" . $this->createUrl("admin/tokens/edit/surveyid/$surveyid/{$dtrow['tid']}/") . "' title='" . $clang->gT("Edit this token") . "'>";
            $browsedatafield .= "{$dtrow['token']}";
            $browsedatafield .= "</a>";
        }
        else
        {
            //No corresponding token in the token tabel, just didsplay the token
            $browsedatafield .= "{$dtrow['token']}";
        }
        ?>
        <td align='center'><?php echo $browsedatafield; ?></td>
        <?php
        $i++;   //We skip the first record (=token) as we just outputted that one
    }

    for ($i; $i < $fncount; $i++)
    {
        if (isset($fnames[$i]['type']) && $fnames[$i]['type'] == "|")
        {
            $index = $fnames[$i]['index'];
            $metadata = $fnames[$i]['metadata'];
            $phparray = json_decode($dtrow[$fnames[$i][0]], true);
            if (isset($phparray[$index]))
            {
                if ($metadata === "size")
                {
                    ?>
                    <td align='center'><?php echo rawurldecode(((int) ($phparray[$index][$metadata])) . " KB"); ?></td>
                <?php }
                else if ($metadata === "name")
                { ?>
                    <td align='center'><a href='#' onclick=" <?php echo convertGETtoPOST('?action=browse&amp;subaction=all&amp;downloadindividualfile=' . $phparray[$index][$metadata] . '&amp;fieldname=' . $fnames[$i][0] . '&amp;id=' . $dtrow['id'] . '&amp;sid=' . $surveyid); ?>" ><?php echo rawurldecode($phparray[$index][$metadata]); ?></a></td>
                <?php }
                else
                { ?>
                    <td align='center'><?php echo rawurldecode($phparray[$index][$metadata]); ?></td>
                <?php
                }
            }
            else
            {
                ?>
                <td align='center'>&nbsp;</td>
        <?php
        }
    }
    else
    {
        if (isset($fnames[$i][4]) && $fnames[$i][4] == 'D' && $fnames[$i][0] != '')
        {
            if ($dtrow[$fnames[$i][0]] == NULL)
                $browsedatafield = "N";
            else
                $browsedatafield = "Y";
        }
        else
        {
            $browsedatafield = htmlspecialchars(strip_tags(stripJavaScript(getExtendedAnswer($surveyid, "browse", $fnames[$i][0], $dtrow[$fnames[$i][0]], ''))), ENT_QUOTES);
        }
        echo "<td align='center'>$browsedatafield</td>\n";
    }
}
?>
</tr>
