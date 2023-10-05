$(function () {

    $('body').on('click', '.listfeatures-values .listfeatures-show-all', function () {
        $(this).hide().closest('.listfeatures-values').find('.remaining.hidden').removeClass('hidden');
    });

});