/**
 * @file Javascript core function for public survey
 * @description loaded before template javascript : allow template to use own function (if function is called after template.js)
 * @copyright LimeSurvey <http://www.limesurvey.org/>
 * @license magnet:?xt=urn:btih:1f739d935676111cfff4b4693e3816e664797050&dn=gpl-3.0.txt GPL-v3-or-Later
 */

/**
 * Manage the index
 */

function manageIndex(){
    $("#index").on('click','li,.row',function(e){
        if(!$(e.target).is('button')){
            $(this).children("[name='move']").click();
        }
    });
}

/**
 * Update survey just when select a new language
 */
function activateLanguageChanger(){
    $('.ls-language-changer-item').on('change','select',function() {
        if(!$(this).closest('form').length){
            /* we are not in a forum, can not submit directly */
            if($('form#limesurvey').length==1){
                /* The limesurvey form exist in document, move select and button inside and click */
                $("form#limesurvey [name='lang']").remove();// Remove existing lang selector
                $("<input type='hidden']>").attr('name','lang').val($(this).find('option:selected').val()).appendTo($('form#limesurvey'));
                $(this).closest('.ls-language-changer-item').find(".ls-change-lang").clone().addClass("ls-js-hidden").appendTo($('form#limesurvey')).click();
            }else{
                // If there are no form : we can't use it */
                if($(this).data('targeturl')){
                    /* If we have a target url : just move location to this url with lang set */
                    var target=$(this).data('targeturl');
                    /* adding lang in get param manually */
                    if(target.indexOf("?") >=0){
                        target+="&lang="+$(this).val();
                    }else{
                        target+="?lang="+$(this).val();
                    }
                    /* directly move to location */
                    location.href = target;
                    return false;
                }else{
                    /* No form, not targeturl : just see what happen */
                    $("<form>", {
                        "class":'ls-js-hidden',
                        "html": '<input type="hidden" name="lang" value="' + $(this).find('option:selected').val() + '" />',
                        "action": target,
                        "method": 'get'
                    }).appendTo(document.body).submit();
                }

            }
        }else{
            /* we are inside a form : just submit : but remove other lang input if exist : be sure it's this one send */
            $(this).closest('form').find("[name='lang']").not($(this)).remove();
            $(this).closest('.ls-language-changer-item').find(":submit").click();
        }
    });
}
/**
 * Action link with submit object (json) : add params to form and submit
 */
function activateActionLink(){
    /* If no limesurvey form : don't need it */
    if(!$('form#limesurvey').length){
        $('[data-limesurvey-submit]').remove();
    }
    /* Submit limesurvey form on click */
    else{
        $('[data-limesurvey-submit]').on('click',function() {
            var submit=$(this).data('limesurvey-submit');
            var confirmedby=$(this).data('confirmedby');
            if(!confirmedby || confirm($(this).data('confirmlabel')))
            {
                $.each(submit, function(name, value) {
                    $("<input/>",{
                        'type':"hidden",
                        'name':name,
                        'value':value,
                    }).appendTo('form#limesurvey');
                });
                $.each(confirmedby, function(name, value) {
                    $("<input/>",{
                        'type':"hidden",
                        'name':name,
                        'value':value,
                    }).appendTo('form#limesurvey');
                });
                $('form#limesurvey').submit();
            }
        });
    }
}

/* Ask confirmation on click on .needconfirm*/
function activateConfirmButton(){
    $(document).on('click',"button[data-confirmedby]", function(event){
        // @todo : allow multiple here : remove extra
        if(!$("[name='"+$(this).data('confirmedby')+"']").is(":checked"))
        {
            text=$("[name='"+$(this).data('confirmedby')+"']").parent("label").text();
            if (confirm(text)) {
                $("[name='"+$(this).data('confirmedby')+"']").prop('checked',true);
                return true;
            }
            return false;
        }
    });
}
/* Trigger tip class when classChangeGood/classChangeError happen */
function triggerEmClassChange(){
    /* The tips */
    $(document).on('classChangeError','.ls-em-tip', function(event){
        $(this).removeClass("ls-em-success").addClass("ls-em-error text-danger");
    });
    $(document).on('classChangeGood','.ls-em-tip', function(event){
        $(this).removeClass("ls-em-error text-danger").addClass("ls-em-success");
    });
    /* The dynamic sum */
    $(document).on('classChangeError','.dynamic-total', function(event){
        $(this).removeClass("ls-em-success text-success").addClass("ls-em-error text-danger");
    });
    $(document).on('classChangeGood','.dynamic-total', function(event){
        $(this).removeClass("ls-em-error text-danger").addClass("ls-em-success text-success");
    });
    /* The input */
    $(document).on('classChangeError','input,select,textarea', function(event){
        $(this).closest(".form-control").addClass("has-warning"); // Use warning, not error : in multiple : if one input have error : it's apply to all input
    });
    $(document).on('classChangeGood','input,select,textarea', function(event){
        $(this).closest(".form-control").removeClass("has-warning");
    });
}
