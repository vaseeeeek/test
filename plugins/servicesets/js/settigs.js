$('.servicesets-reset-image').on('click', function () {
    const id = $(this).data('id');
    const type = $(this).data('type');
    const form = $(this).closest('form')[0];
    const input = $(this).siblings('input');
    const parent = $(this).closest('.servicesets-image--box')
    const num = parent.data('num');

    parent.find(`input[name="image_${num}"]`).attr('value', '');
    parent.find('img').remove();
    $(this).remove();
    parent.find(`[name="reset-image-${num}"]`).val('reset');
    servicesetsFormSubmit(form);
})

$('.servicesets-tabs__item').on('click', function () {
    const id = $(this).data('tab-btn');
    $('.servicesets-tabs__item.active').removeClass('active');
    $(this).addClass('active');
    $('.servicesets-tabs__contents__item').attr('style', 'display:none');
    $(`#content-${id}`).attr('style', 'display:block');
})

$('.servicesets-service__btn-open-variant').on('click', function () {
    $(this).siblings('.servicesets-variants__list').toggleClass('hide');
})

$('.servicesets-group__service-ids').on('click', function (e) {
    if ($(e.target)[0]['nodeName'] == "INPUT") {
        $(this).find('.servicesets-service-ids__list').removeClass('hide');
    }
    if ($(e.target).hasClass('servicesets-service-ids__item')) {
        const elem = $(e.target);
        const parent = elem.closest('.servicesets-service-ids__list');
        const input = parent.siblings('input');
        let ids = [];

        if (elem.hasClass('selected')) {
            elem.removeClass('selected');
        } else {
            elem.addClass('selected');
        }
        parent.find('.selected').each(function () {
            ids.push($(this).data('id').toString());
        })
        input.val(ids.toString());
    }
})
$(document).mouseup(function (e) {
    let container = $(".servicesets-service-ids__list");
    if (container.has(e.target).length === 0) {
        container.each(function () {
            if (!$(this).hasClass('hide')) {

                $(this).addClass('hide');
                $(this).siblings('input').trigger('change');
            }
        })
    }
});

$('.servicesets-group__form').on('change', function (e) {
    e.preventDefault();
    servicesetsFormSubmit(this);
})

$('.servicesets-variants__form').on('change', function (e) {
    e.preventDefault();
    servicesetsFormSubmit(this);
})

$('.servicesets-service__form').on('change', function (e) {
    e.preventDefault();
    servicesetsFormSubmit(this);
})

function servicesetsFormSubmit(form) {
    const formBlock = $(form);
    $.ajax({
        type: formBlock.attr('method'),
        url: formBlock.attr('action'),
        data: new FormData(form),
        contentType: false,
        processData: false,
        cashe: false,
        success: function (e) {
            console.log(e)
            formBlock.find('.form-success-text').fadeIn();
            formBlock.find('.form-success-text').fadeOut();
            $('[name="reset-image-one"]').val('');
            $('[name="reset-image-two"]').val('');
        }
    })
}