<?php
/**
 * Plugin Name: ACF Icons Shortcode
 * Plugin URI: https://github.com/dmitryturin-art/acf-icons-shortcode
 * Description: Шорткод для вывода иконок комплектации из полей ACF с поддержкой размера и ориентации
 * Version: 1.7.0
 * Author: Дмитрий Тюрин
 * Author URI: https://studio-spline.ru
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-icons-shortcode
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

if (!defined('ABSPATH')) {
    exit; // Выход, если доступ напрямую
}

// Определяем константы плагина
define('ACF_ICONS_SHORTCODE_VERSION', '1.7.0');
define('ACF_ICONS_SHORTCODE_URL', plugin_dir_url(__FILE__));
define('ACF_ICONS_SHORTCODE_PATH', plugin_dir_path(__FILE__));

/**
 * Шорткод для вывода иконок комплектации из ACF
 *
 * @param array $atts Атрибуты шорткода
 * @return string HTML-код иконок
 */
function acf_icons_shortcode($atts) {
    // Настройки по умолчанию
    $atts = shortcode_atts(array(
        'field' => 'komplekt',
        'layout' => 'horizontal',
        'size' => '28',
        'display' => 'tooltip',
        'label_position' => 'bottom',
    ), $atts);

    $field_name = sanitize_text_field($atts['field']);
    $layout = in_array($atts['layout'], array('horizontal', 'vertical')) ? $atts['layout'] : 'horizontal';
    $size = absint($atts['size']);
    $display = in_array($atts['display'], array('tooltip', 'label')) ? $atts['display'] : 'tooltip';
    $label_position = in_array($atts['label_position'], array('top', 'bottom')) ? $atts['label_position'] : 'bottom';

    // Валидация размера
    if ($size < 10) $size = 10;
    if ($size > 200) $size = 200;

    // Получаем значения поля ACF
    $values = get_field($field_name);

    // Отладка (раскомментировать для проверки)
    // return '<pre>' . print_r($values, true) . '</pre>';

    // Проверяем, есть ли значения
    if (empty($values) || !is_array($values)) {
        return '<!-- Поле ' . esc_html($field_name) . ' пустое -->';
    }

    // Уникальный класс для стилей
    $unique_class = 'acf-icons-' . sanitize_html_class($field_name) . '-' . $layout . '-' . $display;

    // Начинаем формирование HTML
    $output = '<div class="acf-icons-wrapper ' . esc_attr($unique_class) . ' layout-' . esc_attr($layout) . ' display-' . esc_attr($display) . '" style="--icon-size: ' . $size . 'px;">';

    // Обрабатываем каждый элемент массива
    foreach ($values as $item) {
        // Проверяем, что это массив с ключами 'value' и 'label'
        if (is_array($item) && isset($item['value']) && isset($item['label'])) {
            $icon_path = $item['value'];
            $label = $item['label'];

            if ($display === 'label') {
                // Режим с подписью
                $output .= '
                    <div class="acf-icon-item with-label label-' . esc_attr($label_position) . '">
                        ' . ($label_position === 'top' ? '<span class="acf-icon-label">' . esc_html($label) . '</span>' : '') . '
                        <img src="' . esc_url($icon_path) . '" alt="' . esc_attr($label) . '" class="acf-icon">
                        ' . ($label_position === 'bottom' ? '<span class="acf-icon-label">' . esc_html($label) . '</span>' : '') . '
                    </div>';
            } else {
                // Режим с всплывающей подсказкой
                $output .= '
                    <div class="acf-icon-item" data-tooltip="' . esc_attr($label) . '">
                        <img src="' . esc_url($icon_path) . '" alt="' . esc_attr($label) . '" class="acf-icon">
                    </div>';
            }
        }
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('acf_icons', 'acf_icons_shortcode');

/**
 * Подключение стилей плагина
 */
function acf_icons_shortcode_enqueue_styles() {
    wp_enqueue_style(
        'acf-icons-shortcode',
        ACF_ICONS_SHORTCODE_URL . 'css/acf-icons-shortcode.css',
        array(),
        ACF_ICONS_SHORTCODE_VERSION
    );
}
add_action('wp_enqueue_scripts', 'acf_icons_shortcode_enqueue_styles');

/**
 * Интеграция с WPBakery (Visual Composer)
 */
function register_acf_icons_vc_element() {
    if (!function_exists('vc_map')) {
        return;
    }

    vc_map(array(
        'name' => __('Иконки комплектации ACF', 'acf-icons-shortcode'),
        'base' => 'acf_icons',
        'category' => __('Content', 'acf-icons-shortcode'),
        'icon' => 'fas fa-icons',
        'description' => __('Вывод иконок из поля ACF', 'acf-icons-shortcode'),
        'params' => array(
            array(
                'type' => 'textfield',
                'heading' => __('Имя поля ACF', 'acf-icons-shortcode'),
                'param_name' => 'field',
                'value' => 'komplekt',
                'description' => __('Системное имя поля ACF (например: komplekt)', 'acf-icons-shortcode'),
                'admin_label' => true,
            ),
            array(
                'type' => 'dropdown',
                'heading' => __('Ориентация', 'acf-icons-shortcode'),
                'param_name' => 'layout',
                'value' => array(
                    __('Горизонтальная', 'acf-icons-shortcode') => 'horizontal',
                    __('Вертикальная', 'acf-icons-shortcode') => 'vertical',
                ),
                'std' => 'horizontal',
                'description' => __('Выберите ориентацию вывода иконок', 'acf-icons-shortcode'),
                'admin_label' => true,
            ),
            array(
                'type' => 'dropdown',
                'heading' => __('Режим отображения', 'acf-icons-shortcode'),
                'param_name' => 'display',
                'value' => array(
                    __('Всплывающие подсказки', 'acf-icons-shortcode') => 'tooltip',
                    __('Подпись к иконке', 'acf-icons-shortcode') => 'label',
                ),
                'std' => 'tooltip',
                'description' => __('Выберите режим отображения: всплывающие подсказки или подписи к иконкам', 'acf-icons-shortcode'),
                'admin_label' => true,
            ),
            array(
                'type' => 'dropdown',
                'heading' => __('Позиция подписи', 'acf-icons-shortcode'),
                'param_name' => 'label_position',
                'value' => array(
                    __('Снизу', 'acf-icons-shortcode') => 'bottom',
                    __('Сверху', 'acf-icons-shortcode') => 'top',
                ),
                'std' => 'bottom',
                'description' => __('Выберите позицию подписи (работает только в режиме "Подпись к иконке")', 'acf-icons-shortcode'),
                'dependency' => array(
                    'element' => 'display',
                    'value' => 'label',
                ),
            ),
            array(
                'type' => 'textfield',
                'heading' => __('Размер иконок (px)', 'acf-icons-shortcode'),
                'param_name' => 'size',
                'value' => '28',
                'description' => __('Размер иконок в пикселях (например: 28, 32, 40)', 'acf-icons-shortcode'),
                'param_holder_class' => 'vc_colored-bg',
            ),
        ),
    ));
}

// Регистрируем элемент на нескольких хуках
add_action('vc_before_init', 'register_acf_icons_vc_element', 10);
add_action('init', 'register_acf_icons_vc_element', 20);
