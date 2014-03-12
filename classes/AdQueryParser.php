<?php


/**
 * Class that can parse and translate ad request queries.
 *
 * @example
 * <?php
 *  $parser = new ERWP_AdQueryParser();
 *  $parser->parse('arg=%post.post_title%;second_arg=%something.else%');
 *
 * @since 0.1
 * @package ERWP
 */
class ERWP_AdQueryParser {

    /**
     * Objects that a query parameter can be translated into.
     * To parse %post.post_tile% the $object array must contain
     * a property named "post" referring to an object having
     * the property "post_title"
     *
     * @see ERWP_AdQueryParse::_log()
     * @var array
     */
    private $objects = null;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @param bool $debug Whether or not to be verbose in case of an error occurring
     */
    public function __construct($debug = WP_DEBUG) {
        $this->debug = $debug;
    }

    /**
     * @example
     *   $this->translate('post.post_name');
     *   $this->translate('site.name');
     *   $this->translate('category.slug');
     *
     * @param string $param
     * @return string
     */
    protected function translate($param) {
        $chunks = explode('.', $param);
        $translation = null;

        if( count($chunks) == 2 ) {
            if( $this->hasObjectProperty($chunks[0], $chunks[1]) ) {
                // We're referring to an object
                $translation = $this->objects[$chunks[0]]->$chunks[1];
            } elseif( $chunks[0] == 'site' ) {
                // We want blog info
                $translation = get_bloginfo($chunks[1]);
            }
        } elseif( isset($this->objects[$param]) ) {
            $translation =  $this->objects[$param];
        }
        if( $translation === null && $this->debug ) {
            // Tell the dev that he's referring to an object that is undefined
            trigger_error('PHP Warning: Ad query parameter "'.$param.'" does not exist', E_USER_WARNING);
        }
        return $translation;
    }

    /**
     * Tells whether or not our query object array contains
     * an object with given property
     * @param string $object_name
     * @param string $prop_name
     * @return bool
     */
    private function hasObjectProperty($object_name, $prop_name) {
        return isset($this->objects[$object_name]) &&
                is_object($this->objects[$object_name]) &&
                property_exists($this->objects[$object_name], $prop_name);
    }

    /**
     * @param string $query_str
     * @return string
     */
    public function parse($query_str) {
        $this->loadQueryObjects();
        $translations = array();
        preg_match_all('/\%([a-z0-9A-Z\.\_]+)\%/', $query_str, $query_params);

        if( empty($query_params) ) {
            return $query_str;
        } else {
            foreach($query_params[1] as $param_name) {
                $translations['%'.$param_name.'%'] = $this->translate($param_name);
            }
            return strtr($query_str, $translations);
        }
    }

    /**
     * Setup query object array. This should only be done once during the
     * request to the application.
     */
    private function loadQueryObjects() {
        if( $this->objects === null ) {
            $this->objects = array('post' => get_queried_object());
            if( !empty($this->objects['post']) ) {
                if(is_category()){
                    $post_categories = get_category_by_slug($this->objects['post']->slug);
                    $cat_parents = get_category_parents($post_categories->cat_ID, false, '/' ,true);
                    $cat_parents = explode('/',trim($cat_parents));
                    $cat_parents = array_filter($cat_parents);
                    $cat_index = count($cat_parents);
                    $this->objects['category'] = get_category_by_slug($cat_parents[$cat_index-1]);
                    $this->objects['sub_category'] = get_category_by_slug($cat_parents[$cat_index-2]);
                    $this->objects['sub_sub_category'] = get_category_by_slug($cat_parents[$cat_index-3]);
                    unset($this->objects['post']);
                }
                if(is_singular()){
                    $post_categories = wp_get_post_categories($this->objects['post']->ID);
                    if( !empty($post_categories) && is_array($post_categories) ) {
                        $cat = get_category($post_categories[0]);
                        if( !empty($cat->category_parent) ) {
                            $cat_parents = get_category_parents($cat->cat_ID, false, '/' ,true);
                            $cat_parents = explode('/',trim($cat_parents));
                            $cat_parents = array_filter($cat_parents);
                            $cat_index = count($cat_parents);
                            $this->objects['category'] = get_category_by_slug($cat_parents[$cat_index-1]);
                            $this->objects['sub_category'] = get_category_by_slug($cat_parents[$cat_index-2]);
                            $this->objects['sub_sub_category'] = get_category_by_slug($cat_parents[$cat_index-3]);

                        } else {
                            $this->objects['category'] = $cat;
                        }
                    }
                }
            } else {
                if(is_search()){
                    unset($this->objects['post']);
                    $this->objects['special_page'] = 'search';
                }
                if(is_404()){
                    unset($this->objects['post']);
                    $this->objects['special_page'] = '404';
                }
                $this->objects = apply_filters('erwp_ad_query_objects', $this->objects);
            }
        }
    }
}