<?php
/**
 * This view render the question explorer
 *
 * @var $aGroups
 * @var $iSurveyId
 */
?>
<li class="panel panel-default" id="explorer" class="dropdownlvl2 dropdownstyle">
    <a data-toggle="collapse" id="explorer-collapse" href="#explorer-lvl1">
        <span class="glyphicon glyphicon-folder-open"></span> <?php eT('Questions explorer');?>
       <span class="caret" ></span>
    </a>

    <div id="explorer-lvl1" class="panel-collapse collapse" >
        <div class="panel-body">
            <ul class="nav navbar-nav dropdown-first-level" id="explorer-container">

                <!--  Groups and questions-->
                <?php if(count($aGroups)):?>
                    <li class="panel panel-default dropdownstyle" id="questionexplorer-group-container">

                        <?php foreach($aGroups as $aGroup):?>

                            <!-- Group -->
                            <div class="row explorer-group-title">
                                <div class="col-sm-8">
                                    <a href="#" data-question-group-id="<?php echo $aGroup->gid; ?>" class="explorer-group">
                                        <span id="caret-<?php echo $aGroup->gid; ?>" class="fa fa-caret-right caret-explorer-group"></span>&nbsp&nbsp<?php echo $aGroup->group_name;?>
                                    </a>
                                </div>

                                <?php if (!$bSurveyIsActive): ?>
                                    <div class="col-sm-3">
                                        <!-- add question to this group -->
                                        <a  data-toggle="tooltip" data-placement="top"  title="<?php eT('Add a question to this group');?>" class="" href="<?php echo $this->createUrl("/admin/questions/sa/newquestion/surveyid/$iSurveyId/gid/$aGroup->gid"); ?>">
                                            <span class="glyphicon glyphicon-plus-sign"></span>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Questions -->
                            <div class="row" id="questions-group-<?php echo $aGroup->gid; ?>" style="display: none;">
                                <div class="col-sm-12">
                                    <?php if(count($aGroup['aQuestions'])):?>
                                        <?php foreach($aGroup['aQuestions'] as $question):?>
                                            <?php if($question->parent_qid == 0):?>
                                                <a href="<?php echo $this->createUrl("/admin/questions/sa/view/surveyid/$iSurveyId/gid/".$aGroup->gid."/qid/".$question->qid); ?>">
                                                    <span class="question-collapse-title">
                                                        <span class="glyphicon glyphicon-list"></span>
                                                        <strong>
                                                            <?php echo sanitize_html_string(strip_tags($question->title));?>
                                                        </strong>
                                                        <br/>
                                                        <em>
                                                            <?php echo substr(strip_tags(sanitize_html_string($question->question)), 0, 40);?>
                                                        </em>
                                                    </span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach;?>
                                    <?php else:?>
                                        <a href="" onclick="event.preventDefault();" style="cursor: default;">
                                            <?php eT('no questions in this group');?>
                                        </a>
                                    <?php endif;?>
                                </div>
                            </div>
                        <?php endforeach;?>
                    </li>


                <?php else:?>
                <li class="toWhite">
                    <a href="" onclick="event.preventDefault();" style="cursor: default;">
                        <?php eT('No question group in this survey');?>
                    </a>
                </li>
                <?php endif;?>
            </ul>
        </div>
</li>
