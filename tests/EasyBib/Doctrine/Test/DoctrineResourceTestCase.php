<?php
namespace EasyBib\Doctrine\Test;

use EasyBib\Doctrine\DoctrineResource;

class DoctrineResourceTestCase extends \PHPUnit_Framework_TestCase
{
    public static function modulePathProvider()
    {
        return array(
            array('default', 'application'),
            array('foo', 'app'),
        );
    }

    /**
     * @dataProvider modulePathProvider()
     */
    public function testModulePath($module, $appDir)
    {
        $configParser = $this->getMock('\IniParser', array('parse'));
        $configParser->expects($this->once())
            ->method('parse')
            ->will($this->returnValue(new \ArrayObject()));

        $config = $configParser->parse();

        $resource = new DoctrineResource($config, '__ROOT__PATH__', $module, array());
        $resource->setAppDir($appDir);

        $this->assertEquals(
            sprintf('__ROOT__PATH__/%s/modules/%s/', $appDir, $module),
            $resource->getModulePath()
        );
    }
}
