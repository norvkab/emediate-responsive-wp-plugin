<?php


/**
 * Class that has the know-how to create markup for emediate ads
 *
 * @since 0.1
 * @package ERWP
 */
class ERWP_AdCreator {

    const IMPL_COMPOSED = 'composed';
    const IMPL_FIF = 'fif';

    /**
     * @var array
     */
    private $break_points = array();

    /**
     * @var string
     */
    private $default_js_host = 'ad1.emediate.dk';

    /**
     * @var string
     */
    private $cu_param_name = 'cu';

    /**
     * @var int
     */
    private static $ad_index = 0;

    /**
     * @param array $break_points Only used when creating FiF-ads
     * @param string $default_js_host Only used when creating js-ads
     * @param string $cu_param_name
     */
    public function __construct($break_points=array(), $default_js_host='ad1.emediate.dk', $cu_param_name='cu') {
        $this->break_points = $break_points;
        $this->default_js_host = $default_js_host;
        $this->cu_param_name = $cu_param_name;
    }

    /**
     * @param string|int|array $cu_nums Either one cu number/key or comma separated list with cu numbers/keys
     * @param string $impl Either 'composed' or 'fif' (ERWP_AdCreator::IMPL_COMPOSED, ERWP_AdCreator::IMPL_FIF)
     * @param int $height Default height of the ad, only used when using the fif implementation
     * @return string
     */
    public function create($cu_nums, $impl, $height=0) {
        if( !is_array($cu_nums) )
            $cu_nums = explode(',', $cu_nums);

        if( $impl == 'js' || $impl == self::IMPL_COMPOSED ) { // js is deprecated
            if( $impl == 'js' && WP_DEBUG ) {
                trigger_error('ERWP_AdCreator::create does not accept js as argument, use composed or fif', E_USER_DEPRECATED);
            }
            return $this->createComposedJSAd( $cu_nums );
        } else {
            return $this->createFifAd($cu_nums, $height);
        }
    }

    /**
     * @param string|int $cu
     * @return string
     */
    private function createComposedJSAd($cu){
        return "<script>ERWP.composed('" . $this->default_js_host . "','". $this->cu_param_name . "','". json_encode($cu) . "')</script>\n";
    }

    /**
     * @param array $cu_nums
     * @param int $height
     * @return string
     */
    private function createFifAd($cu_nums, $height) {
        $attr = array(
            'id' => 'emediate-fif-'.self::$ad_index,
            'class' => 'emediate-ad fif',
            'data-ad-index' => self::$ad_index,
            'data-height' => $height
        );

        if( empty($this->break_points) ) {
            $attr['data-cu'] = trim(current($cu_nums));
        } else {
            foreach ($this->break_points as $i => $bp) {
                $bp_attr = 'data-bp-' . $bp['min_width'] . '-' . $bp['max_width'];
                $attr[$bp_attr] = empty($cu_nums[$i]) ? '' : trim($cu_nums[$i]);
            }
        }
        $div = '<div';
        foreach($attr as $name=>$val) {
            $div .= ' '.$name.'="'.$val.'"';
        }
        $div .= '></div>'.PHP_EOL.'<script>ERWP.fif('.self::$ad_index.')</script>';
        self::$ad_index++;
        return $div;
    }

    /**
     * @return int
     */
    public static function getAdIndex() {
        return self::$ad_index;
    }

    public static function currentAdIndex() {
        return self::getAdIndex(); // back compat
    }

    /**
     * @param int $index
     */
    public static function setAdIndex($index) {
        self::$ad_index = $index;
    }
}