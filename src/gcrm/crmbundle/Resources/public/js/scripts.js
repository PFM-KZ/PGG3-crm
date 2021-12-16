(function() {
    'use strict';

    $(function() {
        $('select').select2();

        /**
         * Toggle append input text field to checkbox parent
         */
        $('.checkboxes.add-input-on-change input[type="checkbox"]').change(function() {
            var isChecked = $(this).prop('checked');
            var parent = $(this).closest('div');

            if (isChecked) {
                parent.append(
                    $('<div>').addClass('input-text').append(
                        $('<input>').attr({
                        'type': 'text',
                        'placeholder': 'Dane dot. urządzenia (IMEI / nr tymczasowy / inna nazwa)',
                        'name': 'tempImei-' + $(this).val()
                        }).addClass('temp-input')
                    )
                );
            } else {
                parent.find('.temp-input').parent().remove();
            }
        });

        $('.menu-with-target > li > a').click(function(e) {
            e.preventDefault();

            var parentMenu = $(this).closest('ul');
            var parentMenuItems = parentMenu.find('li');
            var parentLi = $(this).closest('li');

            var targetMenu = $('#' + parentMenu.data('target'));
            if (!targetMenu.length) {
                console.log('There is no target menu with this ID.');
                return;
            }

            var targetMenuItems = targetMenu.find('li');
            var target = $($(this).attr('href'));

            if (!target.length) {
                console.log('Target not found.');
                return;
            }

            /**
             * Defines what to do on second click on the same button
             *
             * Values:
             * undefined (attribute not defined) - do nothing
             * 1 - show / hide
             */
            var actionToggleShow = targetMenu.data('action-toggle-show');
            if (actionToggleShow && actionToggleShow == 1) {
                if (target.hasClass('active')) {
                    target.removeClass('active');
                    parentLi.removeClass('active');
                } else {
                    targetMenuItems.removeClass('active');
                    parentMenuItems.removeClass('active');
                    target.addClass('active');
                    parentLi.addClass('active');
                }
            } else {
                targetMenuItems.removeClass('active');
                target.addClass('active');
                parentMenuItems.removeClass('active');
                parentLi.addClass('active');
            }
        })


        var errorFieldBoxes = $('.target-menu .error');
        if (errorFieldBoxes.length) {
            setTimeout(function() {
                $('body, html').animate({scrollTop: $(errorFieldBoxes).eq(0).offset().top - 20}, 600);
            }, 1000);
        }
    });

})();