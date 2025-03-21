<?php
/**
 * Шаблон формы загрузки аватара
 *
 * @package WPAdminAvatar
 * @author  wpadmin
 */

defined('ABSPATH') || exit; // Защита от прямого доступа
?>

<table class="form-table">
    <tr id="wpadmin_avatar_field_row">
        <th>
            <label for="specs"><?php esc_html_e('Изображение профиля', 'wpadmin-avatar'); ?></label>
        </th>
        <td>
            <div id="my-avatar-display">
                <div id="my-avatar-display-image">
                    <?php echo $avatar_url ? $this->get_avatar_html($profile->ID) : ''; ?>
                </div>
                <button id="my-avatar-link" class="button button-secondary">
                    <?php esc_html_e('Обновить аватар', 'wpadmin-avatar'); ?>
                </button>
                <input type="hidden" id="wpadmin_avatar_url" name="wpadmin_avatar_url" 
                       value="<?php echo esc_attr($avatar_url ?: ''); ?>" />
            </div>
        </td>
    </tr>
</table>