<?php

require_once __DIR__.'/setup.php';


class TestAdQueryParser extends PHPUnit_Framework_TestCase {

    /**
     * @var ERWP_AdQueryParser
     */
    private $parser;

    function setUp() {
        $this->parser = new ERWP_AdQueryParser();
    }

    function testNotCrashing()
    {
        // Make sure it not crashes when referring to undefined stuff
        $this->assertEquals('apa=;test=1', $this->parser->parse('apa=%not.found%;test=1'));
        $this->assertEquals('apa=;test=2', $this->parser->parse('apa=%not_found%;test=2'));
    }

    function testPost()
    {
        // this assumes you have at least one post in your wordpress database
        query_posts('');
        while(have_posts()):
            the_post();
            global $post;
            $this->assertEquals('apa='.$post->post_title.';test=1', $this->parser->parse('apa=%post.post_title%;test=1'));
            break;
        endwhile;
    }

    function testBlogInfo()
    {
        $name = get_bloginfo('name');
        $this->assertEquals('apa='.$name.';test=1', $this->parser->parse('apa=%site.name%;test=1'));
    }

    function testCategory()
    {
        // todo: test translating category
    }

    function testLargeQuery()
    {
        // this assumes you have at least one post in your wordpress database
        query_posts('');
        while(have_posts()):
            the_post();
            global $post;
            $name = get_bloginfo('name');
            $expected = 'name='.$name.';post_title='.$post->post_title.';undef_obj=;';
            $this->assertEquals($expected, $this->parser->parse('name=%site.name%;post_title=%post.post_title%;undef_obj=%undef.object%'));
            break;
        endwhile;
    }
}