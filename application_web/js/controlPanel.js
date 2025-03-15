$(document).ready(function() {
    $('.editButton').on('click', function() {
        var button = $(this);
        var uid = button.data('uid');
        var form = $('.editForm[data-uid="' + uid + '"]');

        $('.editForm').not(form).each(function() {
            $(this).css({
                'opacity': 0,
                'max-height': '0',
                'transition': 'opacity 0.3s ease, max-height 0.3s ease'
            }).hide();


            var otherButton = $('.editButton[data-uid="' + $(this).data('uid') + '"]');
            otherButton.text('Modifier');
        });


        if (form.is(':hidden')) {
            form.show();


            form.css({
                'opacity': 1,
                'max-height': '500px',
                'transition': 'opacity 1s ease, max-height 1s ease'
            });


            button.text('Annuler');
        } else {
            form.css({
                'opacity': 0,
                'max-height': '0',
                'transition': 'opacity 2s ease, max-height 2s ease'
            }).hide();

            button.text('Modifier');
        }
    });
});
