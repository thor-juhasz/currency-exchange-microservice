<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

use SensioLabs\Consul\ServiceFactory;
use SensioLabs\Consul\ConsulResponse;

use App\Lib\Consul;

/**
 * Class ConsulTest
 *
 * @covers  \App\Lib\Consul
 *
 * @author  Þói Juhasz
 */
class ConsulTest extends TestCase
{
    /** @var null|GuzzleHttp\Handler\MockHandler $mockHandler  */
    private static $mockHandler = null;

    /**
     * Create a mock KV response for consul
     *
     * @param   string  $string  Response string
     * @param   int     $status  Default 200. Response status code
     *
     * @return  \GuzzleHttp\Psr7\Response
     *
     * @author  Þói Juhasz
     */
    private static function createKvResponse(string $string, int $status = 200): Response
    {
        return new Response($status, [], json_encode(
            [[
                 'Value' => base64_encode($string)
             ]]
        ));
    }

    /**
     * Create mocked guzzle client before tests
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public static function setUpBeforeClass()
    {
        static::$mockHandler = new MockHandler();
        $handler = HandlerStack::create(static::$mockHandler);
        Consul::$guzzleClient = new Client(['handler' => $handler]);

        parent::setUpBeforeClass();
    }

    /**
     * Test \App\Lib\Consul::$guzzleClient
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public function testGuzzleClientVariable()
    {
        $this->assertInstanceOf(Client::class, Consul::$guzzleClient);
    }

    /**
     * Test \App\Lib\Consul::getServiceFactory()
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public function testGetServiceFactory()
    {
        $this->assertInstanceOf(ServiceFactory::class, Consul::getServiceFactory(true));
    }

    /**
     * Test \App\Lib\Consul::getKeyValue()
     *
     * @return void
     *
     * @author  Þói Juhasz
     */
    public function testGetKeyValue()
    {
        static::$mockHandler->append(static::createKvResponse("all your base are belong to us"));
        $key = 'skeleton-key';

        // This should send a request:
        $this->assertEquals('all your base are belong to us', Consul::getKeyValue($key));

        // This time the value has already been stored, so this won't send a new request:
        $this->assertEquals('all your base are belong to us', Consul::getKeyValue($key));
    }

    /**
     * Test \App\Lib\Consul::getAgentStatus()
     *
     * @return void
     *
     * @author  Þói Juhasz
     */
    public function testGetAgentStatus()
    {
        static::$mockHandler->append(static::createKvResponse("test"));

        Consul::$enabled = true;
        $response = Consul::getAgentStatus();
        $this->assertInstanceOf(ConsulResponse::class, $response);

        Consul::$enabled = false;
        $this->assertNull(Consul::getAgentStatus());
    }
}