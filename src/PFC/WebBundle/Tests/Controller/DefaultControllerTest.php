<?php

namespace PFC\WebBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hello/Antonio');

        $this->assertTrue($crawler->filter('html:contains("Hello Antonio")')->count() > 0);
    }
}
