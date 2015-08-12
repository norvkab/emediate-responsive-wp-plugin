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
     * @see ERWP_AdQueryParse::loadQueryObjects()
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
     *   $this->translate('post.ID');
     *   $this->translate('site.name');
     *   $this->translate('category.term_id|post.ID|"string val"');
     *
     * @param string $param
     * @return string
     */
    protected function translate($param) {

        foreach( explode('|', $param) as $query_param ) {
            $translation = null;
            $query_param = trim($query_param);
            $chunks = explode('.', $query_param);

            if( count($chunks) == 2 ) {
                if( $this->hasObjectProperty($chunks[0], $chunks[1]) ) {
                    // We're referring to an object
                    $translation = $this->objects[$chunks[0]]->$chunks[1];
                } elseif( $chunks[0] == 'site' ) {
                    // We want blog info
                    $translation = get_bloginfo($chunks[1]);
                }
            } elseif( isset($this->objects[$query_param]) ) {
                $translation =  $this->objects[$query_param];
            }

            if( $translation === null && strpos($query_param, '"') === 0) {
                return str_replace('"', '', $query_param);
            }
            elseif( $translation !== null ) {
                return $translation;
            }
        }

        // Tell the dev this couldn't be parsed
        if( $this->debug )
            error_log('PHP Warning: Ad query parameter "'.$param.'" could not be parsed into anything', E_USER_WARNING);

        return null;
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
        preg_match_all('/\%([a-z0-9A-Z\.\_\-\|\"\+]+)\%/', $query_str, $query_params);

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
            $queried_obj = get_queried_object();

            $current_category = null;
            $this->objects = array('page_type' => 'unknown');

            if( is_singular() ) {

                $this->objects['post'] = $queried_obj;
                $this->objects['page_type'] = $queried_obj->post_type;
                $get_post_cats = function_exists('vkwp_get_post_categories') ? 'vkwp_get_post_categories' : 'wp_get_post_categories';
                $post_categories = $get_post_cats($queried_obj->ID);
                if( !empty($post_categories) && is_array($post_categories) ) {
                    $current_category = is_numeric($post_categories[0]) ? get_category($post_categories[0]) : $post_categories[0];
                }
                if( is_front_page() ) {
                    $this->objects['page_type'] = 'frontpage';
                }
            }
            elseif( is_category() ) {
                $current_category = $queried_obj;
                $this->objects['page_type'] = 'category';
            }
            elseif( is_tax() ) {
                $this->objects['taxonomy'] = $queried_obj;
                $this->objects['page_type'] = $queried_obj->taxonomy;
            }
            elseif( is_404() ) {
                $this->objects['page_type'] = '404';
            }
            elseif( is_search() ) {
                $this->objects['page_type'] = 'search';
            }
            elseif( is_date() ) {
                $this->objects['page_type'] = 'date';
            }
            elseif( is_author() ) {
                $this->objects['author'] = $queried_obj;
                $this->objects['page_type'] = 'author';
            }

            // Add category and its parent (if we have one)
            if( !empty($current_category) ) {
                if( !empty($current_category->category_parent) ) {
                    $cat_parents = get_category_parents($current_category->cat_ID, false, '/' ,true);
                    $cat_parents = explode('/',trim($cat_parents));
                    $cat_parents = array_filter($cat_parents);
                    $this->objects['category'] = get_category_by_slug($cat_parents[0]);
                    $this->objects['sub_category'] = get_category_by_slug($cat_parents[1]);
                    if( isset($cat_parents[2]) ) {
                        $this->objects['sub_sub_category'] = get_category_by_slug($cat_parents[2]);
                    }
                } else {
                    $this->objects['category'] = $current_category;
                }
            }

            // Let theme and other plugins add stuff...
            $this->objects = apply_filters('erwp_ad_query_objects', $this->objects);
        }
    }
}