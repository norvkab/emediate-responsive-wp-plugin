<?php


/**
 * Class that has the know-how to create markup for emediate ads
 *
 * @since 0.1
 * @package ERWP
 */
class ERWP_AdCreator {

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
    public function __construct($break_points=array(), $default_js_host='ad1.emediate.dk', $cu_param_name='cu')
    {
        $this->break_points = $break_points;
        $this->default_js_host = $default_js_host;
        $this->cu_param_name = $cu_param_name;
    }

    /**
     * @param string|int $cu Either one cu number/key or comma separated list with cu numbers/keys
     * @param string $impl Either 'js' or 'fif'
     * @return string
     */
    public function create($cu, $impl)
    {
        $cu_nums = explode(',', $cu);
        if( $impl == 'js' ) {
            return $this->createComposedJSAd( current($cu_nums) );
        } else {
            return $this->createFifAd($cu_nums);
        }
    }

    /**
     * @param string|int $cu
     * @return string
     */
    private function createComposedJSAd($cu)
    {
        $src = sprintf('//%s/eas?%s=%s;cre=mu;js=y;target=_blank;', $this->default_js_host, $this->cu_param_name, trim($cu));
        return "<script>ERWP.composed('" . $src . "')</script>";
    }

    /**
     * @param array $cu_nums
     * @return string
     */
    private function createFifAd($cu_nums)
    {
        $attr = array(
            'id' => 'fif-ad-'.self::$ad_index,
            'class' => 'emediate-ad fif',
            'data-ad-index' => self::$ad_index
        );

        if( empty($this->break_points) ) {
            $attr['data-cu'] = trim(current($cu_nums));
        } else {
            foreach ($this->break_points as $i => $bp) {
                $bp_attr = 'data-breakpoint-' . $bp['min'] . '-' . $bp['max'];
                $attr[$bp_attr] = empty($cu_nums[$i]) ? '' : trim($cu_nums[$i]);
            }
        }
        $div = '<div';
        foreach($attr as $name=>$val) {
            $div .= ' '.$name.'="'.$val.'"';
        }

        self::$ad_index++;

        return $div . '></div>';
    }

    /**
     * @return int
     */
    public static function currentAdIndex()
    {
        return self::$ad_index;
    }
}