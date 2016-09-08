<?php
/**
 * 5 point choice Html : item row
 *
 * @var $name
 * @var $value
 * @var $id
 * @var $labelText
 * @var $itemExtraClass
 * @var $checkedState
 * @var $checkconditionFunction
 */
?>

<!-- item_row -->
<li id="javatbd<?php echo $myfname; ?>" class="form-group answer-item radio-item <?php echo $itemExtraClass; ?>">
    <input
        type="radio"
        name="<?php echo $name; ?>"
        id="answer<?php echo $id; ?>"
        value="<?php echo $value;?>"
        <?php echo $checkedState; ?>
        onclick="<?php echo $checkconditionFunction; ?>(this.value, this.name, this.type)"
    />
    <label for="answer<?php echo $id; ?>" class="radio-label"><?php echo $labelText; ?></label>
</li>
<!-- end of item_row -->
