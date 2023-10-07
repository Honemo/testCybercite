<?php

namespace App\Tests;

use App\Entity\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * On test le slug et le status de la classe Url
     *
     * @return void
     */
    public function testSlugAndStatus()
    {
        $slug = 'example-slug';
        $status = '200';
        $url = new Url($slug, $status);
        
        $this->assertEquals($slug, $url->getSlug());
        $this->assertEquals($status, $url->getStatus());
    }

    /**
     * Implémentation de la fonction de test 
     * @todo Write the tests
     *
     * @return void
     */
    public function testPositions()
    {
        $this->assertTrue(true);
    }
}
