<?php

namespace GearmanUI;

class GearmanFacadeTest extends \PHPUnit_Framework_TestCase
{
    protected $logger;

    protected $managerFactory;

    public function setUp() {
        $this->logger = $this->getMock('Monolog\Logger', array(), array('mylogger'));
    }


    public function testConstructor()
    {
        $servers = array(
            array('s1' => 'test server'),
            array('s2' => 'another test server')
        );


        $managerFactory = function() {
            return ;
        };

        $gearmanFacade = new GearmanFacade($servers, $managerFactory, $this->logger);

        $this->assertEquals($servers, $gearmanFacade->getServers());

        $actual_factory =  $gearmanFacade->getManagerFactory();
        $expected_factory = $managerFactory;
        $this->assertEquals($expected_factory, $actual_factory);

        $this->assertInstanceOf('Monolog\Logger', $gearmanFacade->getLogger());
    }


    public function testGetServerInfoConnectionFailed() {

        $managerFactory = function ($server_addr) {
            throw new \Exception("Connection Error");
        };

        $gearmanFacade = new GearmanFacade(array(), $managerFactory, $this->logger);

        $server = array(
            'name' => 'testServer',
            'addr' => '1.2.3.4:7656'
        );

        $info = $gearmanFacade->getServerInfo($server);

        $this->assertFalse($info['up']);
        $this->assertRegExp('/^.*testServer.*$/', $info['error']);
    }


    public function testGetServerInfoDataFetchFailed() {

        $managerFactory = function ($server_addr) {
            $mg = $this->getMock(
                '\Net_Gearman_Manager',
                array('version', 'workers', 'status'),
                array('server addr'),
                '',
                false
            );

            $mg->expects($this->any())
                ->method('version')
                ->will($this->throwException(new \Exception("DataFetchError")));

            $mg->expects($this->any())
                ->method('workers')
                ->will($this->returnValue("workers_array"));

            $mg->expects($this->any())
                ->method('status')
                ->will($this->returnValue("status_array"));

            return $mg;
        };

        $gearmanFacade = new GearmanFacade(array(), $managerFactory, $this->logger);

        $server = array(
            'name' => 'testServer',
            'addr' => '1.2.3.4:7656'
        );

        $info = $gearmanFacade->getServerInfo($server);

        $this->assertTrue($info['up']);
        $this->assertRegExp('/^.*testServer.*DataFetchError.*$/', $info['error']);
    }


    public function testGetServerInfoDataFetchSuccess() {

        $managerFactory = function ($server_addr) {
            $mg = $this->getMock(
                '\Net_Gearman_Manager',
                array('version', 'workers', 'status'),
                array('server addr'),
                '',
                false
            );

            $mg->expects($this->any())
                ->method('version')
                ->will($this->returnValue("workers_array"));

            $mg->expects($this->any())
                ->method('workers')
                ->will($this->returnValue("workers_array"));

            $mg->expects($this->any())
                ->method('status')
                ->will($this->returnValue("status_array"));

            return $mg;
        };

        $gearmanFacade = new GearmanFacade(array(), $managerFactory, $this->logger);

        $server = array(
            'name' => 'testServer',
            'addr' => '1.2.3.4:7656'
        );

        $info = $gearmanFacade->getServerInfo($server);

        $this->assertTrue($info['up']);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('workers', $info);
        $this->assertArrayHasKey('status', $info);
        $this->assertArrayNotHasKey('error', $info);
    }
}