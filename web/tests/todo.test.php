<?php
use Silex\WebTestCase;

class TodoTest extends WebTestCase
{
    
    public function testGET(){
        $client = $this->createClient();
        $crawler = $client->request('GET','/');
        $this->assertTrue($client->getRestponse()->isOk());
    }
}

?>
