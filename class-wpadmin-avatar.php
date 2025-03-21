<?php
/**
 * Основной класс плагина WPAdmin Avatar
 *
 * Этот файл содержит основную логику работы с аватарами пользователей WordPress
 *
 * @package WPAdminAvatar
 * @author  wpadmin
 * @link    https://github.com/wpadmin
 */

if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа к файлу
}

class WPAdmin_Avatar {
    /**
     * Экземпляр класса (синглтон).
     *
     * @var WPAdmin_Avatar
     */
    private static $instance = null;

    /**
     * Получение экземпляра класса.
     * Реализация паттерна Singleton для избежания множественной инициализации.
     *
     * @return WPAdmin_Avatar
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Конструктор.
     * Приватный конструктор обеспечивает singleton паттерн.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Инициализация хуков WordPress.
     * Подключаем все необходимые действия и фильтры.
     */
    private function init_hooks() {
        // Подключение скриптов и стилей в админке
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Хуки для профиля пользователя
        add_action('show_user_profile', array($this, 'render_avatar_form'));
        add_action('edit_user_profile', array($this, 'render_avatar_form'));
        add_action('personal_options_update', array($this, 'save_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_profile_fields'));
        
        // Фильтр аватара
        add_filter('get_avatar', array($this, 'filter_avatar'), 10, 5);
        
        // Перемещение поля аватара
        add_action('admin_head', array($this, 'move_avatar_field'));
        
        // Регистрация шорткода
        add_shortcode('wpadmin_avatar', array($this, 'avatar_shortcode'));
    }

    /**
     * Подключение ресурсов в админке.
     * Загружаем необходимые CSS и JavaScript файлы.
     */
    public function enqueue_admin_assets() {
        wp_enqueue_media(); // Подключаем медиа библиотеку WordPress
        wp_enqueue_style(
            'wpadmin-avatar',
            plugins_url('assets/css/wpadmin-avatar.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );
        wp_enqueue_script(
            'wpadmin-avatar',
            plugins_url('assets/js/wpadmin-avatar.js', dirname(__FILE__)),
            array('jquery', 'media-upload'),
            '1.0.0',
            true
        );
    }

    /**
     * Получение URL аватара пользователя.
     *
     * @param int $user_id ID пользователя
     * @return string|false URL аватара или false если аватар не установлен
     */
    public function get_avatar_url($user_id) {
        $url = get_user_meta($user_id, 'wpadmin_avatar_url', true);
        return (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) ? $url : false;
    }

    /**
     * Получение HTML разметки аватара.
     *
     * @param int    $user_id ID пользователя
     * @param int    $size    Размер аватара в пикселях
     * @param string $alt     Альтернативный текст
     * @return string|false HTML код аватара или false
     */
    public function get_avatar_html($user_id, $size = 150, $alt = 'Аватар пользователя') {
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

    // Продолжение следует...