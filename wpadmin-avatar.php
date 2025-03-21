<?php

/**
 * Основной файл плагина
 *
 * @package WPAdminAvatar
 * @author  wpadmin
 * @link    https://github.com/wpadmin
 *
 * Plugin Name: WPAdmin Avatar
 * Plugin URI: https://github.com/wpadmin
 * Description: Удобное управление аватарами пользователей WordPress
 * Version: 1.0.1
 * Author: wpadmin
 * Author URI: https://github.com/wpadmin
 */

// Защита от прямого доступа к файлу
if (!defined('ABSPATH')) {
    exit; // Выход при попытке прямого доступа к файлу
}

/**
 * Основной класс для работы с пользовательскими аватарами
 */
class Custom_User_Avatars
{

    /**
     * Конструктор класса, инициализация хуков WordPress
     */
    public function __construct()
    {
        // Добавляем поле для загрузки аватара в профиль пользователя
        add_action('show_user_profile', array($this, 'add_avatar_upload_field'));
        add_action('edit_user_profile', array($this, 'add_avatar_upload_field'));

        // Обрабатываем загрузку аватара при сохранении профиля
        add_action('personal_options_update', array($this, 'save_user_avatar'));
        add_action('edit_user_profile_update', array($this, 'save_user_avatar'));

        // Перехватываем функцию стандартных аватаров и заменяем своими
        add_filter('get_avatar', array($this, 'get_custom_avatar'), 10, 6);

        // Регистрируем JavaScript для обработки загрузки
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Добавляем Ajax-обработчик для загрузки аватара
        add_action('wp_ajax_upload_user_avatar', array($this, 'ajax_upload_user_avatar'));
    }

    /**
     * Подключаем необходимые скрипты для загрузки изображений
     * 
     * @param string $hook Текущая страница администратора
     */
    public function enqueue_scripts($hook)
    {
        // Загружаем скрипты только на страницах редактирования профиля
        if ($hook === 'profile.php' || $hook === 'user-edit.php') {
            // Подключаем встроенную библиотеку медиа загрузки WordPress
            wp_enqueue_media();

            // Регистрируем и загружаем наш скрипт
            wp_register_script(
                'custom-avatar-upload',
                plugin_dir_url(__FILE__) . 'assets/js/wpadmin-avatar.js',
                array('jquery'),
                '1.0',
                true
            );

            // Передаем параметры в JavaScript
            wp_localize_script('custom-avatar-upload', 'CustomAvatarUpload', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom_avatar_upload_nonce'),
                'title' => __('Выберите или загрузите аватар', 'custom-avatars'),
                'button' => __('Использовать как аватар', 'custom-avatars')
            ));

            wp_enqueue_script('custom-avatar-upload');
        }
    }

    /**
     * Добавляет поле для загрузки аватара в профиль пользователя
     * 
     * @param WP_User $user Объект пользователя
     */
    public function add_avatar_upload_field($user)
    {
        // Проверяем, имеет ли текущий пользователь права на редактирование пользователей
        if (!current_user_can('upload_files')) {
            return;
        }

        // Получаем текущий аватар пользователя
        $avatar_id = get_user_meta($user->ID, 'custom_avatar_id', true);
        $avatar_url = $avatar_id ? wp_get_attachment_image_url($avatar_id, 'thumbnail') : '';

        // Выводим форму загрузки аватара
?>
        <h3><?php _e('Пользовательский аватар', 'custom-avatars'); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="custom-avatar"><?php _e('Загрузить аватар', 'custom-avatars'); ?></label></th>
                <td>
                    <div class="custom-avatar-container">
                        <div class="current-avatar" style="margin-bottom: 10px;">
                            <?php if ($avatar_url) : ?>
                                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php _e('Текущий аватар', 'custom-avatars'); ?>" style="max-width: 150px; height: auto; border-radius: 50%;">
                            <?php else : ?>
                                <?php echo get_avatar($user->ID, 150); ?>
                            <?php endif; ?>
                        </div>

                        <input type="hidden" name="custom_avatar_id" id="custom_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">

                        <button type="button" class="button" id="upload_avatar_button">
                            <?php _e('Выбрать изображение', 'custom-avatars'); ?>
                        </button>

                        <?php if ($avatar_id) : ?>
                            <button type="button" class="button" id="remove_avatar_button">
                                <?php _e('Удалить аватар', 'custom-avatars'); ?>
                            </button>
                        <?php endif; ?>

                        <p class="description">
                            <?php _e('Загрузите изображение, которое будет использоваться как ваш аватар. Рекомендуемый размер: 300x300 пикселей.', 'custom-avatars'); ?>
                        </p>
                    </div>
                </td>
            </tr>
        </table>
<?php
    }

    /**
     * Обработка сохранения аватара при обновлении профиля
     * 
     * @param int $user_id ID пользователя
     */
    public function save_user_avatar($user_id)
    {
        // Проверяем права доступа
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // Проверяем наличие значения avatar_id
        if (isset($_POST['custom_avatar_id'])) {
            $avatar_id = absint($_POST['custom_avatar_id']);

            // Обновляем или удаляем метаданные пользователя
            if ($avatar_id > 0) {
                update_user_meta($user_id, 'custom_avatar_id', $avatar_id);
            } else {
                delete_user_meta($user_id, 'custom_avatar_id');
            }
        }
    }

    /**
     * Ajax-обработчик для загрузки аватара
     */
    public function ajax_upload_user_avatar()
    {
        // Проверяем nonce для безопасности
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'custom_avatar_upload_nonce')) {
            wp_send_json_error(array('message' => __('Ошибка безопасности. Попробуйте обновить страницу.', 'custom-avatars')));
        }

        // Проверяем, является ли пользователь авторизованным
        if (!is_user_logged_in() || !current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('У вас нет прав для загрузки файлов.', 'custom-avatars')));
        }

        // Получаем ID вложения из POST
        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;

        if ($attachment_id <= 0) {
            wp_send_json_error(array('message' => __('Некорректный ID вложения.', 'custom-avatars')));
        }

        // Получаем URL изображения
        $attachment_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');

        if (!$attachment_url) {
            wp_send_json_error(array('message' => __('Не удалось получить URL изображения.', 'custom-avatars')));
        }

        // Возвращаем успешный ответ с URL изображения
        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'attachment_url' => $attachment_url
        ));
    }

    /**
     * Заменяет стандартный аватар на пользовательский, если он загружен
     * 
     * @param string $avatar      HTML-код аватара по умолчанию
     * @param mixed  $id_or_email ID пользователя, email или объект комментария
     * @param int    $size        Размер аватара
     * @param string $default     URL аватара по умолчанию
     * @param string $alt         Текст альтернативного описания
     * @param array  $args        Дополнительные аргументы
     * @return string             HTML-код аватара
     */
    public function get_custom_avatar($avatar, $id_or_email, $size, $default, $alt, $args = array())
    {
        // Получаем ID пользователя
        $user_id = 0;

        if (is_numeric($id_or_email)) {
            // Если передан ID пользователя
            $user_id = (int) $id_or_email;
        } elseif (is_string($id_or_email)) {
            // Если передан email пользователя
            $user = get_user_by('email', $id_or_email);
            if ($user) {
                $user_id = $user->ID;
            }
        } elseif (is_object($id_or_email)) {
            if (isset($id_or_email->user_id)) {
                // Если передан объект комментария
                $user_id = (int) $id_or_email->user_id;
            } elseif (isset($id_or_email->ID)) {
                // Если передан объект пользователя
                $user_id = (int) $id_or_email->ID;
            }
        }

        if ($user_id === 0) {
            return $avatar;
        }

        // Получаем ID пользовательского аватара
        $avatar_id = get_user_meta($user_id, 'custom_avatar_id', true);

        if (!$avatar_id) {
            return $avatar;
        }

        // Получаем URL изображения аватара
        $avatar_url = wp_get_attachment_image_url($avatar_id, array($size, $size));

        if (!$avatar_url) {
            return $avatar;
        }

        // Формируем HTML-код для пользовательского аватара
        $avatar_alt = !empty($alt) ? esc_attr($alt) : get_the_author_meta('display_name', $user_id);

        $html = sprintf(
            '<img alt="%1$s" src="%2$s" class="%3$s" height="%4$s" width="%4$s" %5$s />',
            $avatar_alt,
            esc_url($avatar_url),
            isset($args['class']) ? esc_attr($args['class']) : 'avatar avatar-' . $size . ' photo',
            esc_attr($size),
            isset($args['extra_attr']) ? $args['extra_attr'] : ''
        );

        return $html;
    }
}

// Инициализация плагина
function custom_user_avatars_init()
{
    new Custom_User_Avatars();
}

add_action('plugins_loaded', 'custom_user_avatars_init');
