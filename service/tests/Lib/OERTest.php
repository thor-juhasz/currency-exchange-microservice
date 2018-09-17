<?php

use App\Lib\OER;
use App\Lib\Consul;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

use Aveiv\OpenExchangeRatesApi\Client as OERClient;

/**
 * Class OERTest
 *
 * @covers \App\Lib\OER
 *
 * @author  Þói Juhasz
 */
class OERTest extends TestCase
{
    /** @var null|GuzzleHttp\Handler\MockHandler $mockHandler */
    private static $mockHandler = null;

    /** @var array $currencies */
    private static $currencies = [];

    /** @var array $rates */
    private static $rates = [];

    /**
     * Create a mock response for the GuzzleClient
     *
     * @param   array|object  $data        Data to be JSON encoded as response body
     * @param   int           $statusCode  Default 200. HTTP status code
     * @param   array         $headers     Default empty. Optional headers for the response
     *
     * @return  \GuzzleHttp\Psr7\Response
     *
     * @author  Þói Juhasz
     */
    public static function createResponse($data, $statusCode = 200, $headers = []): Response
    {
        return new Response($statusCode, $headers, json_encode($data));
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
        // Mock a little for consul here since OER::getOpenExchangeRatesKey() uses it
        $consulMock = new MockHandler([static::createResponse([['Value' => base64_encode('test')]])]);
        Consul::$guzzleClient = new GuzzleClient(['handler' => HandlerStack::create($consulMock)]);
        Consul::$enabled = true;
        Consul::getServiceFactory(true);

        static::$mockHandler = new MockHandler();
        $handler = HandlerStack::create(static::$mockHandler);

        OER::$client = new OERClient(
            OER::getOpenExchangeRatesKey(),
            new GuzzleClient(['handler' => $handler])
        );

        //static::$currencies = OER::getCurrencies();
        static::$rates = OER::getRates('ISK', ['EUR', 'ISK']);

        parent::setUpBeforeClass();
    }

    /**
     * Test \App\Lib\OER::$client
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public function testClientVariable()
    {
        $this->assertInstanceOf(Aveiv\OpenExchangeRatesApi\Client::class, OER::$client);
    }

    /**
     * Test \App\Lib\OER::getCurrencies()
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public function testGetCurrencies()
    {
        static::$mockHandler->append(static::createResponse([
            'CAD' => 'Canadian Dollar',
            'EUR' => 'Euro',
            'ISK' => 'Icelandic Króna',
            'SEK' => 'Swedish Krona',
            'USD' => 'United States Dollar'
        ]));

        static::$currencies = OER::getCurrencies();

        $this->assertTrue(is_array(static::$currencies));
        $this->assertTrue(array_key_exists('ISK', static::$currencies));
        $this->assertEquals('Icelandic Króna', static::$currencies['ISK']);
    }

    /**
     * Test \App\Lib\OER::getRates()
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public function testGetRates()
    {
        static::$mockHandler->append(
            static::createResponse([
                'CAD' => 'Canadian Dollar',
                'EUR' => 'Euro',
                'ISK' => 'Icelandic Króna',
                'SEK' => 'Swedish Krona',
                'USD' => 'United States Dollar'
            ]),
            static::createResponse([
                'rates' => ['ISK' => 1, 'EUR' => 0.007955]
            ])
        );

        static::$rates = OER::getRates('ISK', ['EUR', 'ISK']);

        $this->assertTrue(is_array(static::$rates));
        $this->assertTrue(array_key_exists('ISK', static::$rates));
        $this->assertTrue(array_key_exists('EUR', static::$rates));
        $this->assertFalse(array_key_exists('USD', static::$rates));
        $this->assertEquals(1, static::$rates['ISK']);
        $this->assertEquals(0.007955, static::$rates['EUR']);
    }

    /**
     * Test \App\Lib\OER::convert()
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public function testConvert()
    {
        static::$mockHandler->append(
            static::createResponse([
                'CAD' => 'Canadian Dollar',
                'EUR' => 'Euro',
                'ISK' => 'Icelandic Króna',
                'SEK' => 'Swedish Krona',
                'USD' => 'United States Dollar'
            ]),
            static::createResponse([
                'rates' => ['ISK' => 1, 'EUR' => 0.007955]
            ]),
            static::createResponse([
                'CAD' => 'Canadian Dollar',
                'EUR' => 'Euro',
                'ISK' => 'Icelandic Króna',
                'SEK' => 'Swedish Krona',
                'USD' => 'United States Dollar'
            ]),
            static::createResponse([
                'rates' => ['ISK' => 1, 'EUR' => 0.007955]
            ])
        );

        $amounts1 = [3345, 323274, 7126];
        $amounts2 = [1432, 564515, 2316];

        $converted1 = OER::convert('ISK','EUR', $amounts1);
        $converted2 = OER::convert('ISK','ISK', $amounts2);

        $this->assertEquals(round($amounts1[0] * static::$rates['EUR'], 2), $converted1[0]);
        $this->assertEquals(round($amounts1[1] * static::$rates['EUR'], 2), $converted1[1]);
        $this->assertEquals(round($amounts1[2] * static::$rates['EUR'], 2), $converted1[2]);

        $this->assertEquals($amounts2, $converted2);
    }

    /**
     * Test \App\Lib\OER::getOpenExchangeRatesKey()
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public function testGetOpenExchangeRatesKey()
    {
        $res = OER::getOpenExchangeRatesKey();

        $this->assertNotNull($res);
        $this->assertInternalType('string', $res);
    }
}