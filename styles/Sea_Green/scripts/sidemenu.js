
var SideMenuMovement = function(sidemenuSelector, sideBodySelector, dragButtonSelector, collapseButtonSelector, collapsedHomeSelector, uncollapsedHomeSelector, options){
    
    //define options, or standardized values
    options = options || {};
    options.fixedTopMargin = options.fixedTopMargin || $('#questiongroupbarid').height()+2;
    options.baseWidth = options.baseWidth || 320;

    var 
        isRTL       = ($('html').attr('dir') == 'rtl'),
    //define DOM Variables
        oSideMenu           = $(sidemenuSelector),
        oSideBody           = $(sideBodySelector),
        oDragButton         = $(dragButtonSelector),
        oCollapseButton      = $(collapseButtonSelector),
        oUnCollapsedHome    = $(uncollapsedHomeSelector),
        oCollapsedHome      = $(collapsedHomeSelector),
    
    //define calculateble values
        wWidth      = $('html').width(),
        wHeight     = $('html').height(),
        dHeight     = oSideBody.parent().height(),
    
    //define runtimevariables
        offsetX     = 0,
        offsetY     = 0,
        position    = 0,
    
    //define rtl-specific classes
        chevronClosed = (isRTL ? 'fa-chevron-left' : 'fa-chevron-right'),
        chevronOpened = (isRTL ? 'fa-chevron-right' : 'fa-chevron-left'),

//////define methods
    //setter methods to set the items
        setBody = function(newValue){
            if(isRTL) {
                oSideBody.css({'margin-right': (newValue+10)+"px"});
            } else {
                oSideBody.css({'margin-left': (newValue+10)+"px"});
            }

        },
        setMenu = function(newValue){
            oSideMenu.css({'width': newValue+"px"});
        },
        setDraggable = function(newValue){
          //  oDragButton.css({'left': (newValue)+"px"})
        },
        collapseSidebar = function(force){
            force = force || false;
            console.log("collapsing",oCollapseButton.data('collapsed'));
            var collapsedWidth = isRTL ? wWidth-50 : 50;
            setDivisionOn(collapsedWidth,false);
            if(oCollapseButton.data('collapsed') != 1 || force){ 
                oCollapseButton.closest('div').css({'width':'100%'});
                oSideMenu.find('.side-menu-container').css({'visibility': 'hidden',});
                oCollapseButton.find('i').removeClass(chevronOpened).addClass(chevronClosed);
                oCollapsedHome.css({display: 'inline-block'});
                oUnCollapsedHome.css({display: 'none'});
                oCollapseButton.data('collapsed', 1);
            }
        },
        unCollapseSidebar = function(position){
            setDivisionOn(position,true);
            console.log(oCollapseButton.data('collapsed'));
            if(oCollapseButton.data('collapsed') != 0){
                oCollapseButton.closest('div').css({'width':''});
                oSideMenu.find('.side-menu-container').css({'visibility': 'visible',});
                oCollapseButton.find('i').removeClass(chevronClosed).addClass(chevronOpened);
                oUnCollapsedHome.css({display: 'inline-block'});
                oCollapsedHome.css({display: 'none'});
                oCollapseButton.data('collapsed', 0);
            }
        },

    //definer and mutators
        defineOffset = function(oX,oY){
            offsetX = oX;
            offsetY = oY;
        },
        getSavedOffset = function(){
            
            try{
                var savedOffset = window.localStorage.getItem('ls_admin_view_sidemenu');
            } catch(e){}

            console.log('savedOffset', savedOffset || false);
            var startOffset = savedOffset ? parseInt(savedOffset) : options.baseWidth;
            console.log('startOffset', startOffset)
            startOffset = isRTL ? wWidth-startOffset : startOffset;

            return startOffset;
        },

    //utility and calculating methods
        calculateValue = function(xClient){
            if(isRTL){
                xClient = (wWidth-xClient);
                var sidebarWidth = xClient+(xClient>50 ? (50-offsetX) : 5);
                var sidebodyMargin = sidebarWidth+Math.floor(wWidth/200);
                var buttonLeftTop = Math.abs(wWidth-(xClient-offsetX));
            } else {
                var sidebarWidth = xClient+(xClient>50 ? (50-offsetX) : 5);
                var sidebodyMargin = sidebarWidth+Math.floor(wWidth/200);
                var buttonLeftTop = xClient-offsetX;
            }
            return {sidebar : sidebarWidth, body : sidebodyMargin, button: buttonLeftTop};
        },
        saveOffsetValue = function(offset){
            try{
                window.localStorage.setItem('ls_admin_view_sidemenu',offset);
            } catch(e){}
        },
        setDivisionOn = function(xClient,save){
            save = save || false;
            var oValues = calculateValue(xClient);
            setBody(oValues.body);
            setMenu(oValues.sidebar);
            setDraggable(oValues.button);
            if(save){
                saveOffsetValue(xClient);
            }
        },

    //eventHandler
        onDblClick = function(e){
            var baseWidth = isRTL ? wWidth-options.baseWidth : options.baseWidth;
            setDivisionOn(baseWidth);
            window.localStorage.setItem('ls_admin_view_sidemenu',null);
        },
        onClickCollapseButton = function(e){
            if(oCollapseButton.data('collapsed')==0 ){ 
                collapseSidebar();
            } else {
                var setWidth = getSavedOffset();
                unCollapseSidebar(setWidth);
            }
        },
        onDragStartMethod = function(e){
            // console.log('dragstart triggered', e);
            defineOffset(e.offsetX, e.offsetY);
        },
        onDragMethod = function(e){
            // console.log('drag triggered', e);
            position =  e.clientX;
            setDivisionOn(position);
        },
        onDragEndMethod = function(e){
            // console.log('dragend triggered', e);
            position =  e.clientX;
            if(position <  wWidth/8 ){
                collapseSidebar();
            } else {
                unCollapseSidebar(position);
            }
        };
    
    var startOffset = getSavedOffset();

    if(startOffset <  wWidth/8 || oCollapseButton.data('collapsed')==1 ){
        collapseSidebar(true);
    } else {
        unCollapseSidebar(startOffset);
    }

    oDragButton
        .on('dblclick', onDblClick)
        .on('dragstart', onDragStartMethod)
        .on('drag', onDragMethod)
        .on('dragend', onDragEndMethod);
    oCollapseButton
        .on('click', onClickCollapseButton);
};

var WindowBindings = function(){
    var surveybar = $('.surveybar'),
        sideBody = $('.side-body'),
        sidemenu = $('#sideMenuContainer'),
        upperContainer = $('#in_survey_common'),
    
    //calculated vars
        maxHeight =  $(window).height() - $('#in_survey_common').offset().top - 10,
    
    //methods
        //Stick the side menu and the survey bar to the top
        onWindowScroll = function(e){
            $toTop = (surveybar.offset().top - $(window).scrollTop());

            if($toTop <= 0)
            {
                surveybar.addClass('navbar-fixed-top');
                sidemenu.css({position:"fixed", top: "45px"});
            }

            if ($(window).scrollTop() <= 45)
            {
                surveybar.removeClass('navbar-fixed-top');
                sidemenu.css({position:"absolute", top: "auto", 'height': ($(window).height() - 45)+"px"});
                sidemenu.removeClass('fixed-top');
            }
        },
        //fixSizings
        onWindowResize = function(){
            maxHeight =  ($(window).height() - $('#in_survey_common').offset().top -1);
            sidemenu.find('#fancytree').css({'max-height': (maxHeight/4)+"px", 'overflow': 'auto' });
        }
    onWindowResize();
    $(window).on('scroll',onWindowScroll);
    $(window).on('resize',onWindowResize);
};


/**
 * Side Menu
 */
    
$(document).ready(function(){
   
    new SideMenuMovement('#sideMenuContainer', '.side-body', '#scaleSidebar', '#chevronClose', '#hiddenHome', '#sidemenu-home',{baseWidth: 320});
    new WindowBindings();
});
