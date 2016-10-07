<?php
/**
 * Gender question, button item Html
 *
 * @var $name
 * @var $checkconditionFunction
 * @var $fChecked
 * @var $mChecked
 * @var $naChecked
 * @var $value
 */
?>

<!--Gender question, buttons display -->
<!-- answer -->
<ul class="list-unstyled form-inline btn-group btn-group-justified answers-list button-list gender-button" data-toggle="buttons">
    <!-- Female -->
    <li id="javatbd<?php echo $name;?>F" class="button-item btn btn-primary <?php if($fChecked!=''){echo 'active';}?>">
        <input
            class="radio"
            type="radio"
            name="<?php echo $name;?>"
            id="answer<?php echo $name;?>F"
            value="F"
            <?php echo $fChecked; ?>
        />
        <label for="answer<?php echo $name;?>F">
            <span class="fa fa-venus" aria-hidden="true"></span> <?php eT('Female');?>
        </label>
    </li>

    <!-- Male -->
    <li id="javatbd<?php echo $name;?>M" class="button-item btn btn-primary  <?php if($mChecked!=''){echo 'active';}?> ">
        <input
            class="radio"
            type="radio"
            name="<?php echo $name;?>"
            id="answer<?php echo $name;?>M"
            value="M"
            <?php echo $mChecked;?>
        />
        <label for="answer<?php echo $name;?>M">
            <span class="fa fa-mars" aria-hidden="true"></span> <?php eT('Male');?>
        </label>
    </li>

<!-- No answer -->
    <?php if($noAnswer):?>
    <li id="javatbd<?php echo $name;?>" class="button-item btn btn-primary  <?php if($naChecked!=''){echo 'active';}?>">
        <input
            class="radio"
            type="radio"
            name="<?php echo $name;?>"
            id="answer<?php echo $name;?>"
            value=""
            <?php echo $naChecked;?>
        />
        <label for="answer<?php echo $name;?>">
            <span class="fa fa-genderless" aria-hidden="true"></span> <?php eT('No answer'); ?>
        </label>
    </li>
    <?php endif;?>
</ul>
<!-- Value for expression manager-->
<input
    type="hidden"
    name="java<?php echo $name;?>"
    id="java<?php echo $name; ?>"
    value="<?php echo $value;?>"
/>

<!-- end of answer -->
