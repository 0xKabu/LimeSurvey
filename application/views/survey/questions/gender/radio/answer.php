<?php
/**
 * Gender question, radio item Html
 *
 * @var $name
 * @var $checkconditionFunction
 * @var $fChecked
 * @var $mChecked
 * @var $naChecked
 * @var $value
 */
?>

<!--Gender question, radio display -->

<!-- answer -->
<ul class="list-unstyled answers-list radio-list form-horizontal">

    <!-- Female -->
    <li id='javatbd<?php echo $name; ?>F' class="form-group answer-item radio-item">
        <input
            type="radio"
            name="<?php echo $name;?>"
            id="answer<?php echo $name;?>F"
            value="F"
            <?php echo $fChecked; ?>
            onclick="<?php echo $checkconditionFunction; ?>"
        />
        <label for="answer<?php echo $name;?>F" class="control-label radio-label"><?php eT('Female');?></label>
    </li>

    <!-- Male -->
    <li id='javatbd<?php echo $name; ?>M' class="form-group answer-item radio-item">
        <input
            type="radio"
            name="<?php echo $name;?>"
            id="answer<?php echo $name;?>M"
            value="M"
            <?php echo $mChecked;?>
            onclick="<?php echo $checkconditionFunction; ?>"
            aria-labelledby="label-answer<?php echo $name;?>M"
        />

        <label for="answer<?php echo $name;?>M" class="control-label radio-label"><?php eT('Male');?></label>
    </li>

    <!-- No answer -->
    <?php if($noAnswer):?>
        <li id='javatbd<?php echo $name; ?>' class="form-group answer-item radio-item noanswer-item">
            <input
                type="radio"
                name="<?php echo $name;?>"
                id="answer<?php echo $name;?>"
                value=""
                <?php echo $naChecked;?>
                onclick="<?php echo $checkconditionFunction; ?>"
                aria-labelledby="label-answer<?php echo $name;?>"
            />

            <label for="answer<?php echo $name;?>" class="control-label radio-label"><?php eT('No answer'); ?></label>
        </li>
    <?php endif;?>
</ul>
<!-- Value -->
<input
    type="hidden"
    name="java<?php echo $name;?>"
    id="java<?php echo $name; ?>"
    value="<?php echo $value;?>"
/>
<!-- end of answer -->
