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
     * @var array
     */
    private $objects = null;

    /**
     * @example
     *   $this->translate('%post.post_name%');
     *   $this->translate('%site.name%');
     *   $this->translate('%category.slug%');
     *
     * @param string $param
     * @return string
     */
    protected function translate($param)
    {
        $chunks = explode('.', $param);
        $translation = '';

        if( count($chunks) == 2 ) {

            if( $this->hasObjectProperty($chunks[0], $chunks[1]) ) {
                // We're referring to an object
                $translation = $this->objects[$chunks[0]]->$chunks[1];
            } elseif( $chunks[0] == 'site' ) {
                // We want blog info
                $translation = get_bloginfo($chunks[1]);
            } elseif( WP_DEBUG ) {
                // Tell the dev that he's referring to an object that is undefined
                trigger_error('PHP Warning: Ad query parameter "'.$param.'" does not exist', E_USER_WARNING);
            }

        } else {
            $translation = isset($this->objects[$param]) ? $this->objects[$param] : '';
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
    private function hasObjectProperty($object_name, $prop_name)
    {
        return isset($this->objects[$object_name]) &&
                is_object($this->objects[$object_name]) &&
                property_exists($this->objects[$object_name], $prop_name);
    }

    /**
     * @param string $query_str
     * @return string
     */
    public function parse($query_str)
    {
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
     * request to the application. It
     */
    protected function loadQueryObjects()
    {
        if( $this->objects === null ) {
            $this->objects = array('post' => get_queried_object());
            $this->objects['category'] = wp_get_post_categories($this->objects['post']->ID);
            // todo: add parent/child category
            $this->objects = apply_filters('erwp_ad_query_objects', $this->objects);
        }
    }
}