<?php
namespace EasyBib\Doctrine\Test;

use EasyBib\Doctrine\DoctrineResource;

class DoctrineResourceTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $fixtureDir;

    public function setUp()
    {
        $this->fixtureDir = dirname(dirname(dirname(__DIR__))) . '/fixtures';
        require_once $this->fixtureDir . '/library/Entity/PackageVersion.php';
    }

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

    public function testAnnotationPackageVersion()
    {
        $resource = new DoctrineResource($this->getConfigMock(), $this->fixtureDir, 'default', array());
        $em       = $resource->getEntityManager();

        $packageVersion = $em->getRepository('Entity\PackageVersion');
        $this->assertInstanceOf('Doctrine\ORM\EntityRepository', $packageVersion);
    }

    public function testGetEntityManager()
    {
        $resource = new DoctrineResource($this->getConfigMock(), $this->fixtureDir, 'default', array());
        $em       = $resource->getEntityManager();
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $em);
    }

    public function testPlatform()
    {
        $resource = new DoctrineResource(
            $this->getConfigMock(),
            $this->fixtureDir,
            'default',
            array('bibplatform' => true)
        );
        $em = $resource->getEntityManager();

        $platform = $em->getConnection()->getDatabasePlatform();
        $this->assertInstanceOf('EasyBib\Doctrine\MysqlBibPlatform', $platform);
        $this->assertFalse($platform->supportsForeignKeyConstraints());
    }

    /**
     * It seems hard to currently test this code without providing it with what
     * it wants/needs. So instead of setting up a complicated mock object with PHPUnit
     * I did this, until we refactor the related code.
     *
     * @return \ArrayObject
     */
    protected function getConfigMock()
    {
        $configArray = array(
            'autoGenerateProxyClasses' => 1,
            'proxy'                    => new \ArrayObject(array(
                'namespace' => "Proxy",
                'folder'    => "library/Proxy",
            ), \ArrayObject::ARRAY_AS_PROPS),
            'modelFolder'              => "library/Entity",
            'cacheImplementation'      => "Doctrine\Common\Cache\ArrayCache",
            'connection'               => new \ArrayObject(array(
                'driver'   => "mysqli",
                'dbname'   => "mysql",
                'user'     => "root",
                'host'     => "127.0.0.1",
                'password' => "",
                'charset'  => "utf8",
            ), \ArrayObject::ARRAY_AS_PROPS),
        );
        return new \ArrayObject($configArray, \ArrayObject::ARRAY_AS_PROPS);
    }
}
