(function($) {
$(function() {

    var active_field_remove_action = function(e) {
        var classes = $(this).parents('li').attr('class');
        classes = classes.split(" ");
        var fieldset = null;
        var field = null;
        for (var i in classes) {
            if (classes[i].length > 9 && classes[i].substring(0, 9) == "fieldset_") {
                fieldset = classes[i];
            }
            if (classes[i].length > 6 && classes[i].substring(0, 6) == "field_") {
                field = classes[i];
            }
        }
        if (fieldset != null && field != null) {
            $('div.available_fields li.'+fieldset+'.'+field).removeClass('active');
            $(this).parents('li').remove();
        }
    }

    var name_blur_action = function(e, textbox) {
        var this_li = textbox.parents('li');
        var rename_link = this_li.find('a.rename');
        if (this_li.hasClass('renaming')) {
            this_li.removeClass('renaming');
            var fieldname = textbox.val();
            if (fieldname != '') {
                this_li.find('input.fieldname').val(fieldname);
                rename_link.html(fieldname);
            } else {
                this_li.find('input.fieldname').val('');
                rename_link.html(rename_link.data('default'));
            }
            this_li.find('input.fieldname_textbox').remove();
            rename_link.show();
        }
    }

    $('.fieldsets li').click(function(e) {
        var fieldset = $(this).data('fieldset');
        $('.fieldsets li.active').removeClass('active');
        $(this).addClass('active');
        $('.available_fields li').hide();
        $('.available_fields').find('li.fieldset_'+fieldset).show();
    });

    $('.active_fields').find('li .remove').click(active_field_remove_action);
    $('.available_fields li:not(.fieldset_user)').hide();

    $('.available_fields').find('li').draggable({
        connectToSortable: ".active_fields ul",
        revert: true,
        revertDuration: 0,
        scroll: true,
        zIndex: 100,
        helper: 'clone',
        start: function(event, ui) {
            $(this).addClass('placeholder');
            ui.helper.css('width', $(this).outerWidth()+'px');
        },
        stop: function(event, ui) {
            $(this).removeClass('placeholder');
        }
    });

    $('.active_fields').on('click', 'li a.rename', function(e) {
        var parent_li = $(this).parents('li');
        var fieldname = parent_li.find('input.fieldname').val();
        var fieldname_textbox = $('<input class="fieldname_textbox" value=""/>').val(fieldname);
        parent_li.addClass('renaming');
        $(this).after(fieldname_textbox);
        $(this).hide();
        fieldname_textbox.focus();
    });

    $('.active_fields').on('keydown', 'input.fieldname_textbox', function(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            e.stopPropagation();
            name_blur_action(e, $(this));
        }
    });
    $('.active_fields').on('blur', 'input.fieldname_textbox', function(e) {
        name_blur_action(e, $(this));
    });

    $('.active_fields ul').sortable({
        axis: "y",
        containment: ".active_fields",
        forceHelperSize: true,
        forcePlaceholderSize: true,
        placeholder: 'placeholder',
        receive: function(event, ui) {
            ui.item.addClass('active');
        },
        update: function(event, ui) {
            if (ui.item.hasClass('placeholder')) {
                var fieldset = ui.item.data('fieldset');
                var fieldsetlabel = ui.item.data('fieldsetlabel');
                var field = ui.item.data('field');
                var rename_default = ui.item.data('renamedefault');

                // Remove placeholder and prefix field.
                ui.item
                    .removeClass('placeholder')
                    .html(fieldsetlabel+': '+ui.item.html())
                    .append('<input type="hidden" name="fields[]" value="'+fieldset+'/'+field+'"/>')
                    .append('<input type="hidden" class="fieldname" name="fieldnames[]" value=""/>')
                    .append('<a class="rename" data-default="'+rename_default+'" href="javascript:;">'+rename_default+'</a>');

                // Render remove button.
                var remove = $('<span class="remove">X</span>');
                remove.click(active_field_remove_action);
                ui.item.prepend(remove);

            }
        }
    });
});
})(jQuery);
jQuery.noConflict();