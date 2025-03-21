/**
 * JavaScript для управления загрузкой пользовательских аватаров
 *
 * @package WPAdminAvatar
 * @author  wpadmin
 */

jQuery(document).ready(function ($) {
    // Кнопка загрузки аватара
    var uploadButton = $('#upload_avatar_button');

    // Кнопка удаления аватара
    var removeButton = $('#remove_avatar_button');

    // Контейнер для текущего аватара
    var avatarContainer = $('.current-avatar');

    // Скрытое поле для хранения ID вложения
    var avatarIdInput = $('#custom_avatar_id');

    // Объект медиа-окна
    var mediaUploader;

    // Обработчик клика по кнопке загрузки
    uploadButton.on('click', function (e) {
        e.preventDefault();

        // Если медиа-загрузчик уже существует, открываем его
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Создаем новый медиа-загрузчик
        mediaUploader = wp.media({
            title: CustomAvatarUpload.title, // Заголовок окна
            button: {
                text: CustomAvatarUpload.button // Текст кнопки
            },
            multiple: false, // Запрещаем выбор нескольких файлов
            library: {
                type: 'image' // Ограничиваем тип файлов только изображениями
            }
        });

        // Обработчик выбора изображения
        mediaUploader.on('select', function () {
            // Получаем выбранное изображение
            var attachment = mediaUploader.state().get('selection').first().toJSON();

            // Отправляем AJAX-запрос для обработки загрузки
            $.ajax({
                url: CustomAvatarUpload.ajax_url,
                type: 'POST',
                data: {
                    action: 'upload_user_avatar',
                    attachment_id: attachment.id,
                    nonce: CustomAvatarUpload.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Обновляем ID в скрытом поле
                        avatarIdInput.val(response.data.attachment_id);

                        // Обновляем изображение в контейнере
                        avatarContainer.html('<img src="' + response.data.attachment_url +
                            '" alt="Текущий аватар" style="max-width: 150px; height: auto; border-radius: 50%;">');

                        // Отображаем кнопку удаления, если её нет
                        if (removeButton.length === 0) {
                            uploadButton.after('<button type="button" class="button" id="remove_avatar_button">Удалить аватар</button>');
                            removeButton = $('#remove_avatar_button');

                            // Добавляем обработчик для новой кнопки
                            removeButton.on('click', removeAvatarHandler);
                        }
                    } else {
                        // Выводим сообщение об ошибке
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert('Произошла ошибка при обработке запроса. Пожалуйста, попробуйте еще раз.');
                }
            });
        });

        // Открываем медиа-загрузчик
        mediaUploader.open();
    });

    // Функция обработки удаления аватара
    function removeAvatarHandler(e) {
        e.preventDefault();

        // Запрашиваем подтверждение
        if (confirm('Вы уверены, что хотите удалить свой аватар?')) {
            // Очищаем ID аватара
            avatarIdInput.val('');

            // Восстанавливаем стандартный аватар (получаем через AJAX)
            $.ajax({
                url: CustomAvatarUpload.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_default_avatar',
                    user_id: $('#user_id').val() || $('#user_login').closest('form').find('input[name="user_id"]').val(),
                    size: 150,
                    nonce: CustomAvatarUpload.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Обновляем аватар на стандартный
                        avatarContainer.html(response.data.avatar);
                    } else {
                        // Просто очищаем контейнер, если возникла ошибка
                        avatarContainer.empty();
                    }

                    // Удаляем кнопку удаления
                    removeButton.remove();
                }
            });
        }
    }

    // Привязываем обработчик к кнопке удаления, если она существует
    if (removeButton.length > 0) {
        removeButton.on('click', removeAvatarHandler);
    }
});