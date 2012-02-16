$(document).ready(function() {
    if(!$('#csvattribute').length ) {
        //alert("All the attributes are automatically mapped");
    }
    var height = $(document).height();
    var width = $(document).width();
    var cpdbattarray = {};
    var newcurrentarray = {};
    $('#centralattribute').css({'height' : height-200});
    $('#csvattribute').css({'height' : height-200});
    $('#newcreated').css({'height' : height-200});
    //The original fieldnames bucket
    $(".csvatt").sortable({
        connectWith:".cpdbatt,.newcreate",
        helper: "clone",
        appendTo: "ul"
    });
    //The 'create new' bucket
    $(".newcreate").sortable({
        helper: "clone",
        connectWith:".cpdbatt,.csvatt"
    });
    //The existing attributes bucket
    $("ul.cpdbatt").sortable({
        helper: "clone",
        appendTo: "body",
        connectWith: "ul",
        beforeStop: function(event,ui) {
            $(this).sortable('cancel');
        },
        receive: function(event,ui) {
            cpdbattarray = $(this).sortable('toArray');
            var cpdbattpos = jQuery.inArray($(ui.item).attr('id'),cpdbattarray);
            var csvpos = cpdbattpos+1;
            var cpdbattid = cpdbattarray[cpdbattpos-1];
            var csvattid = $(ui.item).attr('id');
            if(cpdbattpos == 0 ) {
                alert(notPairedErrorTxt);
                $(ui.sender).sortable('cancel');
            } else if($("#"+cpdbattid).css('color') == 'white') {
                alert(onlyOnePairedErrorTxt);
                $(ui.sender).sortable('cancel');
            } else {
	            $('ul.cpdbatt > li:nth-child('+cpdbattpos+')').css("color","white");
	            $('ul.cpdbatt > li:nth-child('+csvpos+')').css("color","white");
	            $("#"+cpdbattid).css("background-color","#328639");
	            $("#"+cpdbattid).css("border-color","#FFFFFF");
	            $("#"+csvattid).css("border-color","#FFFFFF");
	            $("#"+csvattid).css("background-color","#328639");
       	    }
        }
    });

    $("ul.newcreate").sortable({
	    helper: 'clone',
	    appendTo: 'body',
	    dropOnEmpty: true,
	    receive: function(event,ui) {
	        if($(ui.item).attr('id')[0]=='t') {
	            alert(cannotAcceptErrorTxt);
	            $(ui.sender).sortable('cancel');
	        }
	        newcurrentarray = $(this).sortable('toArray');
	        var cpdbattpos = jQuery.inArray($(ui.item).attr('id'),newcurrentarray)
	        cpdbattpos = cpdbattpos+1;
	        $('ul.newcreate > li:nth-child('+cpdbattpos+')').css("color", "white");
	        $('ul.newcreate > li:nth-child('+cpdbattpos+')').css("background-color","#328639");
	    }
	});

	$('#attmapcancel').click(function(){
	    $.post(mapCSVcancelled, {fullfilepath : thefilepath},
	    function(data){
	        $(location).attr('href',displayParticipants);
	    });
	});

	$('#attmap').click(function(){
	    var mappedarray = {};
	    $.each(cpdbattarray, function(index,value) {
	        if(value[0]=='c' && value[1]=='s') {
	            mappedarray[cpdbattarray[index-1].substring(2)] = value.substring(3);
	        }
		});

		$.each(newcurrentarray, function(index,value) {
		    newcurrentarray[index] = value.substring(3);
		});

		var dialog_buttons={};

		dialog_buttons[okBtn]=function(){
		    $(location).attr('href',displayParticipants);
		};

		$("#processing").dialog({
			height: 450,
		    width: 700,
		    modal: true,
		    buttons: dialog_buttons,
		    open: function(event, ui) {
		        $('#processing').parent().find("button").each(function() {
		            if ($(this).text() == okBtn) {
		                $(this).attr('disabled', true);
		            }
		        });
		    }
		});

		$("#processing").load(copyUrl, {
		    characterset: characterset,
		    seperatorused : seperator,
		    fullfilepath : thefilepath,
		    newarray : newcurrentarray,
		    mappedarray : mappedarray
		}, function(msg){
		    $('#processing').parent().find("button").each(function() {
		        if ($(this).text() == okBtn) {
		            $(this).attr('disabled', false);
		        }
		    });
	    });
	});
});