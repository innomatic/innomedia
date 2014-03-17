<?php
namespace Innomedia\Tests;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testGetHomeIsCorrect()
    {
        $this->assertEquals(
            \Innomedia\Context::instance('\Innomedia\Context', 'innomatic')->getHome(),
            \Innomatic\Core\RootContainer::instance('\Innomatic\Core\RootContainer')->getHome().'innomatic/'
        );
    }
}
