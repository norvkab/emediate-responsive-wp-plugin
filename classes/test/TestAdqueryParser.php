<?php

require_once __DIR__.'/setup.php';


class TestAdQueryParser extends PHPUnit_Framework_TestCase {

    /**
     * @var ERWP_AdQueryParser
     */
    private $parser;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->setBackupGlobals(false); // This is needed when unit testing wordpress
    }


    function setUp()
    {
        $this->parser = new ERWP_AdQueryParser(false);
    }

    function testNotCrashing()
    {
        // Make sure it not crashes when referring to undefined stuff
        $this->assertEquals('kw=;test=1', $this->parser->parse('kw=%not.found%;test=1'));
        $this->assertEquals('kw=;test=2', $this->parser->parse('kw=%not_found%;test=2'));
    }

    function testPost()
    {
        // this assumes you have at least one post in your wordpress database
        query_posts('');
        while(have_posts()) {
            the_post();
            global $post;
            $query = 'kw=%post.post_title%;test=1';
            $expected = 'kw='.$post->post_title.';test=1';
            $this->assertEquals($expected, $this->parser->parse($query));
            break;
        }
    }

    function testBlogInfo()
    {
        $query = 'apa=%site.name%;test=1';
        $expected = 'apa='.get_bloginfo('name').';test=1';

        $this->assertEquals($expected, $this->parser->parse($query));
    }

    function testCategory()
    {
        // this assumes you have at least one post in your wordpress database that has a category
        query_posts('posts_per_page=10');
        while(have_posts()) {
            the_post();
            global $post, $wp_query;
            if( $cats = wp_get_post_categories($post->ID) ) {
                $wp_query->queried_object = $post;
                $category = get_category($cats[0]);
                if( $category->category_parent )
                    $category = get_category($category->category_parent);

                $query = 'kw=%category.term_id%;kw2=%post.post_name%;test=1';
                $expected = 'kw='.$category->term_id.';kw2='.$post->post_name.';test=1';
                $this->assertEquals($expected, $this->parser->parse($query));
                break;
            }
        }
    }

    function testLargeQuery()
    {
        // this assumes you have at least one post in your wordpress database
        query_posts('');
        while(have_posts()) {
            the_post();
            global $post;

            $query = 'name=%site.name%;post_title=%post.post_title%;undef_obj=%undef.object%;';
            $expected = 'name='.get_bloginfo('name').';post_title='.$post->post_title.';undef_obj=;';

            $this->assertEquals($expected, $this->parser->parse($query));

            break;
        };
    }
}