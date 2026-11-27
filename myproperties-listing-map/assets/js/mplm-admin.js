jQuery(function ($) {
    $('.mplm-color-picker').wpColorPicker();

    $('.mplm-range').on('input change', function () {
        var targetId = $(this).data('output');
        if (targetId) {
            $('#' + targetId).text($(this).val());
        }
    });
});
