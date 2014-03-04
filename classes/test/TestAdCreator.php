<?php

require_once __DIR__.'/setup.php';


class TestAdCreator extends PHPUnit_Framework_TestCase {

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->setBackupGlobals(false); // This is needed when unit testing wordpress
    }

    function testComposedJS()
    {
        $creator = new ERWP_AdCreator();
        $created_js = $creator->create(123, 'js');
        $expected_js = "<script>ERWP.composed('//ad1.emediate.dk/eas?cu=123;cre=mu;js=y;target=_blank;')</script>";

        $this->assertEquals($expected_js, $created_js);
    }

    function testComposedJSWithOtherHost()
    {
        $creator = new ERWP_AdCreator(array(), 'groda.com', 'cu_key');
        $created_js = $creator->create('test', 'js');
        $expected_js = "<script>ERWP.composed('//groda.com/eas?cu_key=test;cre=mu;js=y;target=_blank;')</script>";

        $this->assertEquals($expected_js, $created_js);
    }

    function testComposedJSWithSeveralCU()
    {
        $creator = new ERWP_AdCreator();
        $created_js = $creator->create('99,12,34', 'js');
        $expected_js = "<script>ERWP.composed('//ad1.emediate.dk/eas?cu=99;cre=mu;js=y;target=_blank;')</script>";

        $this->assertEquals($expected_js, $created_js);
    }

    function testFifWithOutBreakPoints()
    {
        $creator = new ERWP_AdCreator();

        $ad_index = ERWP_AdCreator::currentAdIndex();
        $created_js = $creator->create(123, 'fif');
        $expected_js = '<div id="emediate-fif-'.$ad_index.'" class="emediate-ad fif" data-ad-index="'.$ad_index.'" data-height="0" data-cu="123"></div>'.
                        PHP_EOL.'<script>ERWP.fif('.$ad_index.')</script>';

        $this->assertEquals($expected_js, $created_js);

        $ad_index = ERWP_AdCreator::currentAdIndex();
        $created_js = $creator->create('666,12,33', 'fif');
        $expected_js = '<div id="emediate-fif-'.$ad_index.'" class="emediate-ad fif" data-ad-index="'.$ad_index.'" data-height="0" data-cu="666"></div>'.
                        PHP_EOL.'<script>ERWP.fif('.$ad_index.')</script>';
        $this->assertEquals($expected_js, $created_js);
    }

    function testFifWithBreakPoints()
    {
        $bp = array(
            array('min_width'=>0, 'max_width'=>400),
            array('min_width'=>400, 'max_width'=>1200),
            array('min_width'=>1200, 'max_width'=>5000),
        );
        $creator = new ERWP_AdCreator($bp);

        // Missing one cu at the end
        $ad_index = ERWP_AdCreator::currentAdIndex();
        $created_js = $creator->create('11,12', 'fif');
        $expected_js = '<div id="emediate-fif-'.$ad_index.'" class="emediate-ad fif" data-ad-index="'.$ad_index.
                            '" data-height="0" data-bp-0-400="11" data-bp-400-1200="12" data-bp-1200-5000=""></div>'.
                        PHP_EOL.'<script>ERWP.fif('.$ad_index.')</script>';

        $this->assertEquals($expected_js, $created_js);

        // Missing one cu in the middle
        $ad_index = ERWP_AdCreator::currentAdIndex();
        $created_js = $creator->create('11,,12', 'fif');
        $expected_js = '<div id="emediate-fif-'.$ad_index.'" class="emediate-ad fif" data-ad-index="'.$ad_index.
                            '" data-height="0" data-bp-0-400="11" data-bp-400-1200="" data-bp-1200-5000="12"></div>'.
                        PHP_EOL.'<script>ERWP.fif('.$ad_index.')</script>';

        $this->assertEquals($expected_js, $created_js);

        // Only one cu
        $ad_index = ERWP_AdCreator::currentAdIndex();
        $created_js = $creator->create('11', 'fif', 200);
        $expected_js = '<div id="emediate-fif-'.$ad_index.'" class="emediate-ad fif" data-ad-index="'.$ad_index.
                            '" data-height="200" data-bp-0-400="11" data-bp-400-1200="" data-bp-1200-5000=""></div>'.
                            PHP_EOL.'<script>ERWP.fif('.$ad_index.')</script>';

        $this->assertEquals($expected_js, $created_js);
    }
}