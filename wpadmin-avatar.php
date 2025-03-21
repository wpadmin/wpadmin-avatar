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
 * Version: 1.0.0
 * Author: wpadmin
 * Author URI: https://github.com/wpadmin
 * Text Domain: wpadmin-avatar
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Защита от прямого доступа к файлу
}

// Подключаем основной класс плагина
require_once plugin_dir_path(__FILE__) . 'class-wpadmin-avatar.php';

// Инициализация плагина
function wpadmin_avatar_init() {
    return WPAdmin_Avatar::get_instance();
}
add_action('plugins_loaded', 'wpadmin_avatar_init');