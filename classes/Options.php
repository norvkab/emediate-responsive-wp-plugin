<?php

/**
 * Class that can create/update options
 *
 * @since 0.1
 * @package ERWP
 */
class ERWP_Options {

    /**
     * @return array
     */
    static function load()
    {
        $default_opts = array(
            'ads' => array(),
            'breakpoints' => array(),
            'default_js_host' => 'ad1.emediate.dk',
            'cu_param_name' => 'cu',
            'empty_ad_tags' => ''
        );
        $default_opts = apply_filters('erwp_options', $default_opts);
        $options = array_merge($default_opts, get_option('erwp_options', array()));
        if( MULTISITE ) {
            $options =  array_merge($options, get_site_option('erwp_options', array()));
        }
        return $options;
    }

    /**
     * @param array $options
     */
    static function save($options)
    {
        update_site_option('erwp_options', $options);
    }

}
