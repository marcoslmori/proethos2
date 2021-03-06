<?php

namespace Proethos2\CoreBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class MonitoringControllerTest extends WebTestCase
{
    var $protocol_repository;
    var $client;
    var $_em;

    public function setUp()
    {
        $kernel = static::createKernel();
        $kernel->boot();
        $this->_em = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $this->protocol_repository = $this->_em->getRepository('Proethos2ModelBundle:Protocol');
        
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW'   => 'admin'
        ));
    }

    public function testNewMonitoringGET()
    {
        // getting last id
        $last_protocol = end($this->protocol_repository->findAll());
        $protocol_id = $last_protocol->getId();

        $client = $this->client;
        $route = $client->getContainer()->get('router')->generate('protocol_new_monitoring', array("protocol_id" => $protocol_id), false);
        
        $client->request('GET', $route);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    
    public function testMonitoringCreateActionPOST()
    {
        // getting last id
        $last_protocol = end($this->protocol_repository->findAll());
        $protocol_id = $last_protocol->getId();

        $client = $this->client;
        $route = $client->getContainer()->get('router')->generate('protocol_new_monitoring', array("protocol_id" => $protocol_id), false);
        
        $client->request('POST', $route, array(
            'monitoring-action' => '1',
        ));

        $this->assertEquals(301, $client->getResponse()->getStatusCode());
    }

    public function testMonitoringCreateThatNotAmendmentGET()
    {
        // getting last id
        $last_protocol = end($this->protocol_repository->findAll());
        $protocol_id = $last_protocol->getId();

        $client = $this->client;
        $route = $client->getContainer()->get('router')->generate(
            'protocol_new_monitoring_that_not_amendment', 
            array("protocol_id" => $protocol_id, 'monitoring_action_id' => 2), 
            false
        );
        
        $client->request('GET', $route);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    
    public function testMonitoringCreateThatNotAmendmentPOST()
    {
        // getting last id
        $last_protocol = end($this->protocol_repository->findAll());
        $protocol_id = $last_protocol->getId();

        $client = $this->client;
        $route = $client->getContainer()->get('router')->generate(
            'protocol_new_monitoring_that_not_amendment', 
            array("protocol_id" => $protocol_id, 'monitoring_action_id' => 2), 
            false
        );

        $file = tempnam(sys_get_temp_dir(), 'upl'); // create file
        imagepng(imagecreatetruecolor(10, 10), $file); // create and write image/png to it
        $image = new UploadedFile(
            $file,
            'new_image.png'
        );
        

        $client->request('POST', $route, array(
            'monitoring-action' => '2',
            'justification' => 'teste de justification',
            "new-atachment-file" => $image,
        ));

        $this->assertEquals(301, $client->getResponse()->getStatusCode());
    }
}
