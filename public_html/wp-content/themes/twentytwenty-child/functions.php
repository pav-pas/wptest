<?php

add_action( 'wp_enqueue_scripts', 'true_enqueue_styles' );
function true_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style') );
}

add_action( 'init', 'register_units' );
function register_units() {

    $labels = [
        "name" => "Units",
        "singular_name" => "Unit",
    ];

    $args = [
        "label" => "Units",
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "rest_controller_class" => "WP_REST_Posts_Controller",
        "has_archive" => false,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "delete_with_user" => false,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => false,
        "rewrite" => [ "slug" => "units", "with_front" => true ],
        "query_var" => true,
        "supports" => [ "title", "editor" ],
    ];

    register_post_type( "units", $args );
}

add_action('add_meta_boxes', 'my_extra_fields', 1);
function my_extra_fields() {
    add_meta_box( 'extra_fields', 'Дополнительные поля', 'extra_fields_box_func', 'units', 'normal', 'high');
}

function extra_fields_box_func( $post ){
    ?>

    <p>
        <label>Широта</label>
        <input type="text" name="extra[lat]" value="<?php echo get_post_meta($post->ID, 'lat', 1); ?>" style="width:50%" />
    </p>
    <p>
        <label>Долгота</label>
        <input type="text" name="extra[lon]" value="<?php echo get_post_meta($post->ID, 'lon', 1); ?>" style="width:50%" />
    </p>
    
    <input type="hidden" name="extra_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
    <?php
}

// включаем обновление полей при сохранении
add_action( 'save_post', 'my_extra_fields_update', 0 );
## Сохраняем данные, при сохранении поста
function my_extra_fields_update( $post_id ){
    // базовая проверка
    if (
            empty( $_POST['extra'] )
         || ! wp_verify_nonce( $_POST['extra_fields_nonce'], __FILE__ )
         || wp_is_post_autosave( $post_id )
         || wp_is_post_revision( $post_id )
    )
        return false;

    // Все ОК! Теперь, нужно сохранить/удалить данные
    $_POST['extra'] = array_map( 'sanitize_text_field', $_POST['extra'] ); // чистим все данные от пробелов по краям
    foreach( $_POST['extra'] as $key => $value ){
        if( empty($value) ){
            delete_post_meta( $post_id, $key ); // удаляем поле если значение пустое
            continue;
        }

        update_post_meta( $post_id, $key, $value ); // add_post_meta() работает автоматически
    }

    return $post_id;
}

add_shortcode('yandex_map','shortcode_yandex_map');
function shortcode_yandex_map( $atts ) {
    global $post, $shortYandexMapId;
    
    if ( ! isset($shortYandexMapId)){
        $shortYandexMapId = 0;
    }
    
    $shortYandexMapId++;
    
    $sLat = get_post_meta($post->ID, 'lat', 1);
    $sLon = get_post_meta($post->ID, 'lon', 1);
    
    $sMapId = 'map' . $shortYandexMapId;
    
    ob_start();
    ?>
        ymaps.ready(init<?=$shortYandexMapId?>);
        function init<?=$shortYandexMapId?>(){
            new ymaps.Map("<?=$sMapId?>", {
                center: [<?php echo $sLat?>, <?php echo $sLon?>],
                zoom: 7
            });
        }    
    <?php
    
    wp_enqueue_script('yandex.map', 'https://api-maps.yandex.ru/2.1/?lang=ru_RU');
    
    $sScriptCode = ob_get_clean();
    wp_add_inline_script('yandex.map', $sScriptCode);
    
    $sMapCode = '<div id="' .$sMapId. '" style="width: 600px; height: 400px"></div>';
    
    return $sMapCode;
}