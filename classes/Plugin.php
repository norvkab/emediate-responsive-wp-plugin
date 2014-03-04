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
    public static function themeInit(){
        // Load our options
        self::$opts = ERWP_Options::load();
        self::$ad_markup_creator = new ERWP_AdCreator(
                                    self::$opts['breakpoints'],
                                    self::$opts['default_js_host'],
                                    self::$opts['cu_param_name']
                                );
        // Add emediate and erwp javascript
        $query_parser = new ERWP_AdQueryParser();
        wp_enqueue_script('emediate-eas', '//'.self::$opts['default_js_host'].'/EAS_tag.1.0.js', array(), '1.0');
        wp_enqueue_script('erwp-theme-js', ERWP_PLUGIN_URL.'/js/ERWP.js', array('jquery'), ERWP_PLUGIN_VERSION);
        wp_localize_script(
            'erwp-theme-js',
            'erwpSettings',
            array(
                'breakPoints' => self::$opts['breakpoints'],
                'adQuery' => $query_parser->parse(self::$opts['ad_query']),
                'defaultJSHost' => self::$opts['default_js_host'],
                'cuParamName' => self::$opts['cu_param_name'],
                'emptyAdTags' => self::$opts['empty_ad_tags'],
                'fifHtmlFile' => ERWP_PLUGIN_URL.'js/EAS_fif.html#eas-host='.self::$opts['default_js_host']
            )
        );
        // Hook into all ad-actions
        foreach(self::$opts['ads'] as $ad) {
            if( $ad['action'] == 'Yes' ) {
                add_action($ad['slug'], 'ERWP_Plugin::addActionHook');
            }
        }
    }

    /**
     * @param $action
     */
    public static function addActionHook()
    {
        self::generateAdMarkup(current_filter());
    }

    /**
     * @param string|array $ad Either slug of an ad or the ad data
     * @param bool $echo
     * @return string
     */
    public static function generateAdMarkup($ad, $echo = true){
        if( !is_array($ad) ) {
            foreach(self::$opts['ads'] as $ad_data) {
                if( $ad_data['slug'] == $ad ) {
                    $ad = $ad_data;
                    break;
                }
            }
            if( !is_array($ad) ) {
                trigger_error('PHP Warning: Refering to an ad "'.$ad.'" that does not exist', E_USER_WARNING);
                return '';
            }
        }
        $ad_html = '';
        $i = 0;
        $cus = '';

        while(true){
            $array_key = 'cu'.$i;
            if($i>0 && array_key_exists($array_key,$ad)){
                $cus.= ',';
            }
            if(array_key_exists($array_key,$ad)){
                $cus .= $ad[$array_key];
            } else {
                break;
            }
            $i++;
        }
        if( $ad['status'] == 'Active' ) {
            if( $ad['implementation'] == 'JS' ) {
                $ad_html = self::$ad_markup_creator->create($cus, 'js');
            } else {
                $ad_html = self::$ad_markup_creator->create($cus, 'fif', $ad['height']);
            }
        }
        if( $echo )
            echo $ad_html;

        return $ad_html;
    }
}
