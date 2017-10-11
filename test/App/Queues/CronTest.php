<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 05.01.17
 * Time: 10:30
 */

namespace rollun\test\skeleton\Queues;

use Interop\Container\ContainerInterface;
use rollun\callback\Queues\Extractor;
use rollun\callback\Queues\Queue;
use rollun\callback\Callback\Interruptor\Queue as QueueInterruptor;
use rollun\dic\InsideConstruct;
use rollun\installer\Command;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use Zend\Http\Client;

class CronTest extends \PHPUnit_Framework_TestCase
{
    /** @var Queue */
    protected $minQueue;

    /** @var Queue */
    protected $secQueue;

    protected $url;

    protected $config;

    public function setUp()
    {
        /** @var ContainerInterface $container */
        $container = include 'config/container.php';
        $this->config = $container->get('config');

        $this->url = 'http://' . constant("HOST") . '/webhook/cron';

        InsideConstruct::setContainer($container);
        $this->deleteJob();
    }

    protected function deleteJob()
    {
        if (file_exists(Command::getDataDir() . DIRECTORY_SEPARATOR . 'interrupt_sec')) {
            unlink(Command::getDataDir() . DIRECTORY_SEPARATOR . 'interrupt_sec');
        }
        if (file_exists(Command::getDataDir() . DIRECTORY_SEPARATOR . 'interrupt_min')) {
            unlink(Command::getDataDir() . DIRECTORY_SEPARATOR . 'interrupt_min');
        }
    }

    public function testCron()
    {
        $httpClient = new Client($this->url);
        $headers['Content-Type'] = 'text/text';
        $headers['Accept'] = 'application/json';
        $httpClient->setHeaders($headers);
        $httpClient->setMethod('POST');
        $req = $httpClient->send();

        $this->assertTrue($req->isOk());

        sleep(60);

        $minFileData = file_get_contents(Command::getDataDir() . 'interrupt_min');
        $secFileData = file_get_contents(Command::getDataDir() . 'interrupt_sec');
        $data = explode("\n", $minFileData);
        $this->assertEquals(4, count(array_diff($data, [''])));
        $this->assertEquals(120, count(array_diff(explode("\n", $secFileData), [''])));

        //$this->deleteJob();
    }

}
