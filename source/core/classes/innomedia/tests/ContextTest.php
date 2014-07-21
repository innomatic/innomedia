<?php
namespace Innomedia\Tests;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    protected $context;

    protected function setUp()
    {
        $this->context = \Innomedia\Context::instance('\Innomedia\Context', 'innomatic');
    }

    public function testGetHomeIsCorrect()
    {
        $this->assertEquals(
            $this->context->getHome(),
            \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().'innomatic/'
        );
    }
}
