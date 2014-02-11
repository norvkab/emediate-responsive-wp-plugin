<?php
/*
Plugin Name: Emediate responsive wordpress plugin
Description: Integrates the website with Emediate ad manager
Version: 0.1
Author: Victor Jonsson <http://victorjonsson.se/>, Tom Brännström
*/


define('EMEDIATE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EMEDIATE_PLUGIN_VERSION', '0.1.1');
define('EMEDIATE_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
include 'functions.php';

add_action('admin_menu', function() {
    $js_hook = add_options_page(
        'Emediate',
        'Emediate',
        'manage_options',
        'emediate-settings',
        function() {
            require_once EMEDIATE_PLUGIN_PATH.'/templates/admin/settings-page.php';
        }
    );
    wp_enqueue_script('admin-'.$js_hook, EMEDIATE_PLUGIN_URL.'templates/admin/admin-ui.js', array('jquery'), EMEDIATE_PLUGIN_VERSION);
});