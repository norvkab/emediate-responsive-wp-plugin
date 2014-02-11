<?php


class ERWP_Options {

    static function load()
    {
        $default_opts = apply_filters('erwp_options', array());
        $options = array_merge($default_opts, get_option('erwp_options', array()));
        if( MULTISITE ) {
            $options =  array_merge($options, get_site_option('erwp_options', array()));
        }
        return $options;
    }

    static function save($options)
    {
        if( MULTISITE && !is_network_admin() ) {
            update_site_option('erwp_options', $options);
        } else {
            update_option('erwp_options', $options);
        }
    }

}