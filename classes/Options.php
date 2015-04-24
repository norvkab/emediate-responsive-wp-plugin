<?php

/**
 * Class that can create/update options
 *
 * @since 0.1
 * @package ERWP
 */
class ERWP_Options {

    const OPT_NAME = 'erwp_options';

    /**
     * @return array
     */
    static function load() {
        $default_opts = array(
            'ads' => array(),
            'breakpoints' => array(),
            'default_js_host' => 'ad1.emediate.dk',
            'cu_param_name' => 'cu',
            'empty_ad_tags' => '',
            'ad_query' => '',
            'enable_location_app' => false,
            'enable_location_browser' => false,
            'enable_location_android' => false,
            'enable_location_ios' => false,
            'location_query_title' => '',
            'location_query_text' => '',
            'location_jquery_filter' => '',
            'show_app_options' => apply_filters('emediate_app_show_options', false),
        );
        $default_opts = apply_filters(self::OPT_NAME, $default_opts);
        $options = self::mergeInDbOptions($default_opts, false);
        if( MULTISITE ) {
            $options = self::mergeInDbOptions($options);
        }
        return $options;
    }

    /**
     * @param $opts
     * @param bool $site
     * @return mixed
     */
    private static function mergeInDbOptions($opts, $site=true)
    {
        $db_opts = $site ? get_site_option(self::OPT_NAME, array()) : get_option(self::OPT_NAME, array());
        foreach($db_opts as $name => $val) {
            if( !empty($val) ) {
                $opts[$name] = $val;
            }
        }
        return $opts;
    }

    /**
     * @param array $options
     */
    static function save($options) {
        update_site_option(self::OPT_NAME, $options);
    }

    /**
     * Removes all options saved to the database
     */
    static function clear() {
        delete_site_option(self::OPT_NAME);
        delete_option(self::OPT_NAME);
    }

}
