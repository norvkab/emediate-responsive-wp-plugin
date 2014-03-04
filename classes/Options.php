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
    static function load()
    {
        $default_opts = array(
            'ads' => array(),
            'breakpoints' => array(),
            'default_js_host' => 'ad1.emediate.dk',
            'cu_param_name' => 'cu',
            'empty_ad_tags' => '',
            'ad_query' => ''
        );
        $default_opts = apply_filters(self::OPT_NAME, $default_opts);
        $options = array_merge($default_opts, get_option(self::OPT_NAME, array()));
        if( MULTISITE ) {
            $options =  array_merge($options, get_site_option(self::OPT_NAME, array()));
        }
        return $options;
    }

    /**
     * @param array $options
     */
    static function save($options)
    {
        update_site_option(self::OPT_NAME, $options);
    }

    /**
     * Removes all options saved to the database
     */
    static function clear()
    {
        delete_site_option(self::OPT_NAME);
    }

}
