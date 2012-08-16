<?php
namespace Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;

/**
 * @Entity
 * @version Release: @package_version@
 */
class PackageVersion
{
    /**
     * @Id
     * @Column(type="integer")
     */
    private $id;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }
}
