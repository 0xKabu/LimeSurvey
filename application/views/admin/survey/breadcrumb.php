<?php if(isset($oQuestion)): ?>
    <div class="">
        <div class="">
            <ol class="breadcrumb">
                <li>
                    <a href="<?php echo App()->createUrl('/admin/survey/sa/view/surveyid/'. $oQuestion->sid );?>">
                        <?php echo $oQuestion->survey->defaultlanguage->surveyls_title;?> &nbsp; (ID:<?php echo $oQuestion->sid;?>)
                    </a>
                </li>

                <li>
                    <a href="<?php echo App()->createUrl('admin/survey/sa/listquestions/surveyid/'.$oQuestion->sid.'?group_name='.urlencode($oQuestion->groups->group_name).'&yt0=Search' );?>">
                        <?php echo $oQuestion->groups->group_name;?> &nbsp; (ID:<?php echo $oQuestion->gid;?>)
                    </a>
                </li>
                <?php if(!isset($active)): ?>
                    <li class="active">
                        <?php echo $oQuestion->title;?> &nbsp; (ID:<?php echo $oQuestion->qid;?>)
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo App()->createUrl('/admin/questions/sa/view/surveyid/'.$oQuestion->sid.'/gid/'.$oQuestion->gid.'/qid/'.$oQuestion->qid );?>">
                            <?php echo $oQuestion->title;?> &nbsp; (ID:<?php echo $oQuestion->qid;?>)
                        </a>
                    </li>
                    <li class="active">
                        <?php echo $active;?>
                    </li>
                <?php endif; ?>
            </ol>
        </div>
    </div>
<?php elseif(isset($oQuestionGroup)): ?>
    <div class="">
        <div class="">
            <ol class="breadcrumb">
              <li>
                  <a href="<?php echo App()->createUrl('/admin/survey/sa/view/surveyid/'. $oQuestionGroup->sid );?>">
                      <?php echo $oQuestionGroup->survey->defaultlanguage->surveyls_title;?> &nbsp; (ID:<?php echo $oQuestionGroup->sid;?>)
                  </a>
              </li>

              <?php if(!isset($active)): ?>
               <li class="active">
                      <?php echo $oQuestionGroup->group_name;?> &nbsp; (ID:<?php echo $oQuestionGroup->gid;?>)
               </li>
              <?php else: ?>
                  <li>
                      <a href="<?php echo App()->createUrl('admin/questiongroups/sa/view/surveyid/'.$oQuestionGroup->sid.'/gid/'.$oQuestionGroup->gid  );?>">
                          <?php echo $oQuestionGroup->group_name;?> &nbsp; (ID:<?php echo $oQuestionGroup->gid;?>)
                      </a>
                  </li>
                  <li class="active">
                      <?php echo $active;?>
                  </li>
              <?php endif; ?>
            </ol>
        </div>
    </div>
<?php elseif(isset($token)): ?>
    <div class="">
        <div class="">
            <ol class="breadcrumb">
              <li>
                  <a href="<?php echo App()->createUrl('/admin/survey/sa/view/surveyid/'. $oSurvey->sid );?>">
                      <?php echo $oSurvey->defaultlanguage->surveyls_title;?> &nbsp; (ID:<?php echo $oSurvey->sid;?>)
                  </a>
              </li>
              <li>
                  <a href="<?php echo App()->createUrl('admin/tokens/sa/index/surveyid/'. $oSurvey->sid );?>">
                      <?php eT('Token summary');?>
                  </a>
              </li>
            <li class="active">
                <?php echo $active;?>
            </li>
            </ol>
        </div>
    </div>
<?php elseif(isset($oSurvey)): ?>
    <div class="">
        <div class="">
            <ol class="breadcrumb">
              <?php if(!isset($active)): ?>
                  <li>
                      <?php echo $oSurvey->defaultlanguage->surveyls_title;?> &nbsp; (ID:<?php echo $oSurvey->sid;?>)
                  </li>
              <?php else: ?>
              <li>
                  <a href="<?php echo App()->createUrl('/admin/survey/sa/view/surveyid/'. $oSurvey->sid );?>">
                      <?php echo $oSurvey->defaultlanguage->surveyls_title;?> &nbsp; (ID:<?php echo $oSurvey->sid;?>)
                  </a>
              </li>
                  <li class="active">
                      <?php echo $active;?>
                  </li>
              <?php endif; ?>
            </ol>
        </div>
    </div>
<?php endif;?>
