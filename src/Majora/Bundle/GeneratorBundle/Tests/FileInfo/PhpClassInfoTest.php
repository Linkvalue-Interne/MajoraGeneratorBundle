<?php

namespace Majora\Bundle\GeneratorBundle\Tests\FileInfo;

use Majora\Bundle\GeneratorBundle\FileInfo\PhpClassInfo;

/**
 * Unit test PhpClassInfo
 */
class PhpClassInfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests getFQCN.
     *
     * @test
     */
    public function testGetFQCN()
    {
        $expected = 'Iron\\Man\\Versus\\Spiderman';
        $this->assertEquals($expected, $this->getPhpClassInfoWithAttributes()->getFQCN());
    }

    /**
     * Tests getNamespaceAsPath.
     *
     * @test
     */
    public function testGetNamespaceAsPath()
    {
        $expected = 'Iron/Man/Versus';
        $this->assertEquals($expected, $this->getPhpClassInfoWithAttributes()->getNamespaceAsPath());
    }

    /**
     * Return an instance of PhpClassInfo with already defined attributes.
     *
     * @return PhpClassInfo
     */
    private function getPhpClassInfoWithAttributes()
    {
        return (new PhpClassInfo())
            ->setNamespace('Iron\\Man\\Versus')
            ->setClassName('Spiderman')
        ;
    }
}
