<?php
/**
 * @category Database
 * @package  EasyBib\Doctrine
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @link     https://github.com/easybib/EasyBib_DoctrineResource
 */
namespace EasyBib\Doctrine;

use Doctrine\DBAL\Platforms\MySqlPlatform;

/**
 * EasyBib\Doctrine\MysqlBiBPlatform
 *
 * InnoDB, without foreign keys.
 *
 * @category Database
 * @package  EasyBib\Doctrine
 * @author   Till Klampaeckel <till@php.net>
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @link     https://github.com/easybib/EasyBib_DoctrineResource
 */
class MysqlBibPlatform extends MySqlPlatform
{
    /**
     * Disable foreign keys.
     *
     * @return false
     */
    public function supportsForeignKeyConstraints()
    {
        return false;
    }
}
