<script src="<?php echo $this->config->item('generalscripts')."jquery/jquery.js" ?>" type="text/javascript"></script>
<script src="<?php echo $this->config->item('generalscripts')."jquery/jquery-ui.js" ?>" type="text/javascript"></script>
<script src="<?php echo $this->config->item('adminscripts')."attributeControl.js" ?>" type="text/javascript"></script>
<script type="text/javascript">
  var saveVisibleMsg = "<?php echo $clang->gT("Attribute Visiblity Changed") ?>";    
  var saveVisible = "<?php echo site_url("admin/participants/saveVisible");?>";
</script>
<div class='header ui-widget-header'>
  <strong>
    <?php echo $clang->gT("Attribute Control"); ?>
  </strong>
</div>
<?php
  $attribute = array('class' => 'form44');
  echo form_open('/admin/participants/storeAttributes',$attribute);
?>
<br></br>
<ul>
  <li>
    <table id='atttable'class='hovertable'>
    <tr>
      <th><?php echo $clang->gT("Attribute Name"); ?></th>
      <th><?php echo $clang->gT("Attribute Type"); ?></th>
      <th><?php echo $clang->gT("Visible in participant panel"); ?></th>
      <th><?php echo $clang->gT("Actions"); ?></th>
    </tr>
    <?php 
    foreach($result as $row=>$value)
    {
    ?>
    <tr>
      <td>
      <?php
        echo $value['attribute_name'];
      ?>
      </td>
      <td>
      <?php
        if($value['attribute_type']=='DD')
        {
          echo $clang->gT("Drop Down");
        }
        elseif($value['attribute_type']=='DP')
        {
          echo $clang->gT("Date");
        }
        else
        {
          echo $clang->gT("Text Box");
        }
      ?>
      </td>
      <td>
      <?php
      if($value['visible']=="TRUE")
      {
        $data = array('name'  => 'visible_'.$value['attribute_id'],
                      'id'    => 'visible_'.$value['attribute_id'],
                      'value' => 'TRUE',
                      'checked' => TRUE);
      }
      else
      {
        $data = array('name'    => 'visible.'.$value['attribute_id'],
                      'id'      => 'visible_'.$value['attribute_id'],
                      'value'   => 'TRUE',
                      'checked' => FALSE);
      }
        echo form_checkbox($data);
      ?>
      </td>
      <td>
      <?php
        $edit = array('src'    => 'images/token_edit.png',
                      'alt'    => 'Edit',
                      'width'  => '15',
                      'height' => '15',
                      'title'  => 'Edit Atribute');
        echo anchor('admin/participants/viewAttribute/'.$value['attribute_id'],img($edit));
        $del = array('src' => 'images/error_notice.png',
                     'alt' => 'Delete',
                     'width' => '15',
                     'height' => '15',
                     'title' => 'Delete Atribute');
        echo anchor('admin/participants/delAttribute/'.$value['attribute_id'],img($del));
      ?>
      </td>
    </tr>
    <?php
      }
    ?>
    </table>
  </li>
  <li>
    <a href="#" class="add"><img src = "<?php echo base_url().'images/plus.png' ?>" alt="Add Attribute" width="25" height="25" title="Add Attribute" id="add" name="add" /></a>
  </li>
</ul>
<br/>
<p><input type="submit" name="Save" value="Save" /></p>
<?php echo form_close(); ?>