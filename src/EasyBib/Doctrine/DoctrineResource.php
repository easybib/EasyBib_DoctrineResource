<?php
/**
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * PHP Version 5.3
 *
 * @category Database
 * @package  EasyBib\Doctrine
 * @author   Michael Scholl <michael@sch0ll.de>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  GIT: <git_id>
 * @link     https://github.com/easybib/EasyBib_Form_Decorator
 */

namespace EasyBib\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use Doctrine\Common\ClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\ORM\Configuration;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tree\TreeListener;
//use DoctrineExtensions\Versionable\VersionListener;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * EasyBib\Doctrine\DoctrineResource
 *
 * Setup Doctrine EntityManager and add support for some Gedmo PlugIns
 * - provides model support
 * - provides buildBootstrapErrorDecorators method
 *   for adding css error classes to form if not valid
 *
 * @category Database
 * @package  EasyBib\Doctrine
 * @author   Michael Scholl <michael@sch0ll.de>
 * @author   Leander Damme <leander@wesrc.com>
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @version  Release: @package_version@
 * @link     https://github.com/easybib/EasyBib_Form_Decorator
 */
class DoctrineResource
{
    /**
     * Application dir: app or application
     * @var string
     */
    protected $appDir = 'app';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EventManager
     */
    protected $evm;

    /**
     * Could be an instance of `\Zend_Config` or `\ArrayObject`
     * @var mixed
     */
    protected $config;

    /**
     * @var string
     */
    protected $rootPath;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $modulePath;

    /**
     * @var array
     */
    protected $options;

    /**
     * Setup Paths & Config
     *
     * @param object $config
     * @param string $rootPath
     * @param string $module
     * @param array  $options  (array with keys for timestampable,sluggable,tree, platform)
     *
     * @return $this
     */
    public function __construct($config, $rootPath, $module, array $options)
    {
        if (!($config instanceof \Zend_Config) && !($config instanceof \ArrayObject)) {
            throw new \InvalidArgumentException("Configuration must be a \Zend_Config or \IniParser object. Config was: " . \gettype($config));
        }
        if (empty($rootPath)) {
            throw new \InvalidArgumentException('RootPath needs to be given');
        }
        if (empty($module)) {
            throw new \InvalidArgumentException('Module name needs to be given');
        }

        $this->config   = $config;
        $this->rootPath = $rootPath;
        $this->module   = $module;

        $this->setOptions($options);
    }

    /**
     * Return options (DoctrineExtenions-related)
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set options and validate input a little.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $defaultOptions = array(
            'timestampable' => false,
            'sluggable'     => false,
            'tree'          => false,
            'profile'       => false,
            'bibplatform'   => false,
        );

        if (count($options) > 0) {
            foreach ($options as $option => $value) {
                if (false === array_key_exists($option, $defaultOptions)) {
                    throw new \InvalidArgumentException("We currently do not support: {$option}.");
                }
                if (false === is_bool($value)) {
                    throw new \InvalidArgumentException("Value for '{$option}' must be 'true' or 'false'.");
                }
                $defaultOptions[$option] = $value;
            }
        }

        $this->options = $defaultOptions;

        return $this;
    }

    /**
     * Setup Doctrine Class Loaders & EntityManager
     *
     * return void
     */
    protected function init()
    {
        $this->evm = new EventManager();
        $config    = new Configuration();

        // timestampable
        if (!empty($this->options['timestampable'])) {
            $this->addTimestampable();
        }
        // sluggable
        if (!empty($this->options['sluggable'])) {
            $this->addSluggable();
        }
        // tree
        if (!empty($this->options['tree'])) {
            $this->addTree();
        }
        // profile logger
        if (!empty($this->options['profile'])) {
            $config->setSQLLogger(new EchoSQLLogger());
        }

        $cache        = new $this->config->cacheImplementation();
        $entityFolders = $this->getEntityFolders();
        $proxyFolder  = $this->getProxyFolder();
        $driverImpl   = $config->newDefaultAnnotationDriver($entityFolders);

        AnnotationReader::addGlobalIgnoredName('package_version');
        $annotationReader = new AnnotationReader;
        $cachedAnnotationReader = new \Doctrine\Common\Annotations\CachedReader(
            $annotationReader, // use reader
            $cache // and a cache driver
        );

        $annotationDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
            $cachedAnnotationReader, // our cached annotation reader
            $entityFolders // paths to look in
        );

        $this->registerAutoloadNamespaces();

        $config->setMetadataDriverImpl($annotationDriver);
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setProxyDir($proxyFolder);
        $config->setProxyNamespace($this->config->proxy->namespace);
        $config->setAutoGenerateProxyClasses($this->config->autoGenerateProxyClasses);

        if ($this->config instanceof \Zend_Config) {
            $connectionConfig = $this->config->connection->toArray();
        } else {
            $connectionConfig = (array) $this->config->connection;
        }

        if (true === $this->options['bibplatform']) {
            $connectionConfig['platform'] = new MysqlBibPlatform();
        }

        $this->em = EntityManager::create(
            $connectionConfig,
            $config,
            $this->evm
        );

        PersistentObject::setObjectManager($this->em);
        return;
    }

    /**
     * Add Timestampable listener
     *
     * @return void
     */
    protected function addTimestampable()
    {
        if (!empty($this->evm)) {
            $this->evm->addEventSubscriber(new TimestampableListener());
        }
    }

    /**
     * Add Sluggable listener
     *
     * @return void
     */
    protected function addSluggable()
    {
        if (!empty($this->evm)) {
            $this->evm->addEventSubscriber(new SluggableListener());
        }
    }

    /**
     * Add Tree listener
     *
     * @return void
     */
    protected function addTree()
    {
        if (!empty($this->evm)) {
            $this->evm->addEventSubscriber(new TreeListener());
        }
    }

    /**
     * Get Entity folders
     *
     * @return array
     */
    protected function getEntityFolders()
    {
        $folders = array();

        //only set default entity dir if it really exists
        if (is_dir($this->rootPath . '/library/Doctrine/Model')) {
            $folders[] = $this->rootPath . '/library/Doctrine/Model';
        }

        //set configured entity-dir if run in module-standalone context
        if (is_dir($this->rootPath . '/' . $this->config->modelFolder)) {
            $folders[] = $this->rootPath . '/' . $this->config->modelFolder;
        }

        //set configured entity-dir if run in module context
        if (is_dir($this->getModulePath() . $this->config->modelFolder)) {
            $folders[] = $this->modulePath . '/' . $this->config->modelFolder;
        }

        return $folders;
    }

    /**
     * Get Proxy folders
     *
     * @return string
     */
    protected function getProxyFolder()
    {
        /*$folders = array(
            $this->rootPath . '/library/Doctrine/Proxy'
        );
        if (is_dir($this->modulePath . '/' . $this->config->proxy->folder)) {
            $folders[] = $this->modulePath . '/' . $this->config->proxy->folder;
        }*/
        return $this->rootPath . '/library/Doctrine/Proxy';
    }

    /**
     * Register Autoload Namespaces
     *
     * @return void
     */
    protected function registerAutoloadNamespaces()
    {
        AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\Mapping\Annotation',
            $this->rootPath . '/vendor/gedmo/doctrine-extensions/lib'
        );
    }

    /**
     * Get the Doctrine EntityManager
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        $this->init();
        return $this->em;
    }

    public function getModulePath()
    {
        if (null === $this->modulePath) {
            $this->modulePath = sprintf(
                '%s/%s/modules/%s/',
                $this->rootPath, $this->appDir, $this->module
            );
        }
        return $this->modulePath;
    }

    /**
     * Set application dir.
     *
     * @param string $dir
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setAppDir($dir)
    {
        static $allowed = array('app', 'application');
        if (empty($dir)) {
            throw new \InvalidArgumentException("Directory cannot be empty.");
        }
        if (!in_array($dir, $allowed)) {
            throw new \InvalidArgumentException("Directory must be one of these two values: " . implode(', ', $allowed));
        }
        $this->appDir = $dir;
        return $this;
    }
}
