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

            $queried_obj = get_queried_object();
            $current_category = null;
            $this->objects = array('page_type' => 'unknown');

            if( is_singular() ) {
                $this->objects['post'] = $queried_obj;
                $this->objects['page_type'] = $queried_obj->post_type;
                global $authordata;
                $this->objects['author'] = $authordata;
                $post_categories = wp_get_post_categories($queried_obj->ID);
                if( !empty($post_categories) && is_array($post_categories) ) {
                    $current_category = get_category($post_categories[0]);
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