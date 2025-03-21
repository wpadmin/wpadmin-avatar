<?php

/**
 * Основной класс плагина WPAdmin Avatar
 *
 * @package WPAdminAvatar
 * @author  wpadmin
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAdmin_Avatar
{
    /**
     * Экземпляр класса (синглтон).
     *
     * @var WPAdmin_Avatar
     */
    private static $instance = null;

    /**
     * Получение экземпляра класса.
     *
     * @return WPAdmin_Avatar
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор.
     */
    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Инициализация хуков WordPress.
     */
    private function init_hooks()
    {
        // Подключение скриптов и стилей в админке
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Хуки для профиля пользователя
        add_action('show_user_profile', [$this, 'render_avatar_form']);
        add_action('edit_user_profile', [$this, 'render_avatar_form']);
        add_action('personal_options_update', [$this, 'save_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_profile_fields']);

        // Фильтр аватара
        add_filter('get_avatar', [$this, 'filter_avatar'], 10, 5);

        // Перемещение поля аватара
        add_action('admin_head', [$this, 'move_avatar_field']);

        // Регистрация шорткода
        add_shortcode('wpadmin_avatar', [$this, 'avatar_shortcode']);
    }

    /**
     * Подключение ресурсов в админке.
     */
    public function enqueue_admin_assets()
    {
        wp_enqueue_media();
        wp_enqueue_style(
            'wpadmin-avatar',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/wpadmin-avatar.css',
            [],
            '1.0.0'
        );
        wp_enqueue_script(
            'wpadmin-avatar',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/wpadmin-avatar.js',
            ['jquery', 'media-upload'],
            '1.0.0',
            true
        );
    }

    /**
     * Получение URL аватара пользователя.
     *
     * @param int $user_id ID пользователя
     * @return string|false
     */
    public function get_avatar_url($user_id)
    {
        $url = get_user_meta($user_id, 'wpadmin_avatar_url', true);
        return (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) ? $url : false;
    }

    /**
     * Получение HTML разметки аватара.
     *
     * @param int    $user_id ID пользователя
     * @param int    $size    Размер аватара
     * @param string $alt     Альтернативный текст
     * @return string|false
     */
    public function get_avatar_html($user_id, $size = 150, $alt = 'Аватар пользователя')
    {
        $url = $this->get_avatar_url($user_id);
        if ($url) {
            return sprintf(
                '<img src="%s" class="avatar avatar-%d photo" width="%d" height="%d" alt="%s" />',
                esc_url($url),
                (int) $size,
                (int) $size,
                (int) $size,
                esc_attr($alt)
            );
        }
        return false;
    }

    /**
     * Отображение формы аватара.
     *
     * @param WP_User $profile Объект пользователя
     */
    public function render_avatar_form($profile)
    {
        if (!$this->can_edit_avatar($profile->ID)) {
            return;
        }

        $avatar_url = $this->get_avatar_url($profile->ID);
        include dirname(__FILE__) . '/templates/avatar-form.php';
    }

    /**
     * Проверка прав на редактирование.
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    private function can_edit_avatar($user_id)
    {
        $current_user = wp_get_current_user();
        return $user_id === $current_user->ID
            || current_user_can('edit_user', $current_user->ID)
            || is_super_admin($current_user->ID);
    }

    /**
     * Сохранение полей профиля.
     *
     * @param int $user_id ID пользователя
     * @return bool
     */
    public function save_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id) || empty($_POST['wpadmin_avatar_url'])) {
            return false;
        }

        return update_user_meta(
            $user_id,
            'wpadmin_avatar_url',
            esc_url_raw($_POST['wpadmin_avatar_url'])
        );
    }

    /**
     * Фильтр аватара WordPress.
     *
     * @param string $avatar      HTML код аватара
     * @param mixed  $id_or_email ID пользователя или email
     * @param int    $size        Размер аватара
     * @param string $default     URL аватара по умолчанию
     * @param string $alt         Альтернативный текст
     * @return string
     */
    public function filter_avatar($avatar, $id_or_email, $size, $default, $alt)
    {
        $user_id = $this->get_user_id_from_identifier($id_or_email);

        if ($user_id) {
            $custom_avatar = $this->get_avatar_html($user_id, $size, $alt);
            if ($custom_avatar) {
                return $custom_avatar;
            }
        }

        return $avatar;
    }

    /**
     * Получение ID пользователя.
     *
     * @param mixed $id_or_email ID пользователя или email
     * @return int|false
     */
    private function get_user_id_from_identifier($id_or_email)
    {
        if (is_numeric($id_or_email)) {
            return (int) $id_or_email;
        }

        if (is_object($id_or_email) && !empty($id_or_email->user_id)) {
            return (int) $id_or_email->user_id;
        }

        if (is_string($id_or_email)) {
            $user = get_user_by('email', $id_or_email);
            return $user ? $user->ID : false;
        }

        return false;
    }

    /**
     * Перемещение поля аватара.
     */
    public function move_avatar_field()
    {
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var field = $('#wpadmin_avatar_field_row').remove();
                field.insertBefore('tr.user-profile-picture');
            });
        </script>
<?php
    }

    /**
     * Шорткод аватара.
     *
     * @param array $atts Атрибуты шорткода
     * @return string
     */
    public function avatar_shortcode($atts)
    {
        $atts = shortcode_atts([
            'user_id' => get_current_user_id(),
            'size' => 150,
            'alt' => 'Аватар пользователя'
        ], $atts, 'wpadmin_avatar');

        return $this->get_avatar_html(
            $atts['user_id'],
            $atts['size'],
            $atts['alt']
        ) ?: '';
    }
}
