<?php

namespace Majora\Bundle\GeneratorBundle\FileInfo;

/**
 * Class containing information of a PHP class.
 */
class PhpClassInfo
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     *
     * @return self
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     *
     * @return self
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Get Full Qualified Class Name.
     *
     * @return string
     */
    public function getFQCN()
    {
        return sprintf('%s\%s', $this->getNamespace(), $this->getClassName());
    }
}
