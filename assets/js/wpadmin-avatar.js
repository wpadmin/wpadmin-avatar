/**
 * Скрипт для работы с медиа библиотекой WordPress
 * Обеспечивает выбор и установку аватара пользователя
 *
 * @package WPAdminAvatar
 * @author  wpadmin
 */

jQuery(document).ready(function($) {
    var fileFrame;
    
    // Обработчик клика по кнопке обновления аватара
    $('#my-avatar-link').on('click', function(event) {
        event.preventDefault();
        
        // Если фрейм уже существует, просто открываем его
        if (fileFrame) {
            fileFrame.open();
            return;
        }
        
        // Создаём новый медиа фрейм
        fileFrame = wp.media({
            title: 'Выберите или загрузите изображение профиля',
            button: {
                text: 'Использовать это изображение'
            },
            multiple: false // Запрещаем множественный выбор
        });
        
        // Обработчик выбора изображения
        fileFrame.on('select', function() {
            var attachment = fileFrame.state().get('selection').first().toJSON();
            $('#wpadmin_avatar_url').val(attachment.url);
            $('#my-avatar-display-image').html(
                '<img src="' + attachment.url + '" width="150" height="150" alt="Новый аватар" />'
            );
        });
        
        // Открываем медиа фрейм
        fileFrame.open();
    });
});