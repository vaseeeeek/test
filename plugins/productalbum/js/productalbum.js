$(document).ready(function() {
    $('#productalbum-select').change(function() {
        var albumId = $(this).val();
        var productId = $('#shop-productprofile').data('product-id');
        
        $.ajax({
            url: '/webasyst/shop/?plugin=productalbum&action=savealbum',
            type: 'post',
            data: {
                product_id: productId,
                album_id: albumId,
            },
            success: function(response) {
                console.log(response)
                // Обработка успешного сохранения
            },
            error: function() {
                // Обработка ошибки
            }
        });
    });
    // Код для админки
    $('.productalbum--backend .productalbum__item').on('click', function() {
        alert('Вы кликнули на альбом в админке');
    });

    // Код для фронтенда
    $('.productalbum--frontend .productalbum__item').on('click', function() {
        alert('Вы кликнули на альбом на фронтенде');
    });
});
