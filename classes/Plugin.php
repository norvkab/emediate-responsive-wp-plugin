<?php

/**
 * Class that contains all logic for the implementation of
 * emediate-ads in the theme
 *
 * @since 0.1
 * @package ERWP
 */
class ERWP_Plugin {

    /**
     * @var array
     */
    private static $opts;

    /**
     * @var ERWP_AdCreator
     */
    private static $ad_markup_creator;

    /**
     * Should be called once before the page gets rendered
     */
    public static function themeInit() {
        if( defined('ERWP_ENABLED') && ERWP_ENABLED === false )
            return;

        // Load our options
        self::$opts = ERWP_Options::load();
        self::$ad_markup_creator = new ERWP_AdCreator(
                                    self::$opts['breakpoints'],
                                    self::$opts['default_js_host'],
                                    self::$opts['cu_param_name']
                                );

        add_action('wp_print_scripts', array('ERWP_Plugin', 'scriptParams'));

        // Add emediate and erwp javascript
        wp_enqueue_script('emediate-eas', '//'.self::$opts['default_js_host'].'/EAS_tag.1.0.js', array(), '1.0');
        wp_enqueue_script('erwp-theme-js', plugins_url('emediate-responsive-wp-plugin/js/ERWP.js'), array('jquery'), ERWP_PLUGIN_VERSION);


        // Hook into all ad-actions
        foreach(self::$opts['ads'] as $ad) {
            if( !empty($ad['action']) ) {
                add_action($ad['slug'], 'ERWP_Plugin::addActionHook');
            }
        }
    }

    public static function scriptParams() {

        $disable_app = !apply_filters('emediate_app_location_method', false);


        $query_parser = new ERWP_AdQueryParser();

        $script = '<script type="text/javascript">/* <![CDATA[ */ '.PHP_EOL.'var erwpSettings = ';

        $script .= json_encode(array(
                'breakPoints' => self::$opts['breakpoints'],
                'adQuery' => $query_parser->parse(self::$opts['ad_query']),
                'defaultJSHost' => self::$opts['default_js_host'],
                'cuParamName' => self::$opts['cu_param_name'],
                'emptyAdTags' => self::$opts['empty_ad_tags'],
                'enableLocationApp' => (bool)self::$opts['enable_location_app'],
                'enableLocationBrowser' => (bool)apply_filters('emediatate_enable_browser_location', self::$opts['enable_location_browser']),
                'enableLocationAndroid' => (bool)self::$opts['enable_location_android'],
                'enableLocationiOS' => (bool)self::$opts['enable_location_ios'],
                'locationQueryTitle' => $disable_app ? '' : self::$opts['location_query_title'],
                'locationQueryText' => $disable_app ? '' : self::$opts['location_query_text'],
                'locationjQueryFilter' => self::$opts['location_jquery_filter'],
                'debug' => ERWP_DEBUG,
                'appLocationMethod' => apply_filters('emediate_app_location_method', ''),
                'fifHtmlFile' => apply_filters('erwp_fif_url', ERWP_PLUGIN_URL.'js/EAS_fif.html#eas-host='.self::$opts['default_js_host']),
                'adsToNotResize' => array(),
                'resizeAdWidth' => (bool)apply_filters('emediate_resize_ad_width', true),
                'useLazyLoad' => self::$opts['use_lazy_load'],
                'lazyLoadOffset' => self::$opts['lazy_load_offset'],
                'lazyLoadStart' => self::$opts['lazy_load_start']
            ));

        $script .= '; '.PHP_EOL.'/* ]]> */</script>';
        echo $script;
    }

    /**
     * @param $action
     */
    public static function addActionHook() {
        self::generateAdMarkup(current_filter());
    }

    /**
     * @param string|array $ad Either slug of an ad or the ad data
     * @param bool $echo
     * @return string
     */
    public static function generateAdMarkup($ad, $echo = true) {
        if( !is_array($ad) ) {
            foreach(self::$opts['ads'] as $ad_data) {
                if( $ad_data['slug'] == $ad ) {
                    $ad = $ad_data;
                    break;
                }
            }
            if( !is_array($ad) ) {
                trigger_error('PHP Warning: Referring to an ad "'.$ad.'" that does not exist', E_USER_WARNING);
                return '';
            }
        }
        $ad_html = '';
        if( $ad['status'] == 'active' ) {
            $i = 0;
            $cus = array();
            // Collect cu's for each break point
            while( array_key_exists('cu'.$i, $ad) ) {
                $cus[] = $ad['cu'.$i];
                $i++;
            }
            if( !empty($ad['cu']) ) {
                $cus[] = $ad['cu'];
            }
            $ad_html = self::$ad_markup_creator->create($cus, $ad['implementation'], isset($ad['height']) ? $ad['height']:0);
        }
        if( $echo ){
            echo $ad_html;
        }
        return $ad_html;
    }
}
