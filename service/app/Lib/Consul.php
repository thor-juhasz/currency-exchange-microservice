<?php

namespace App\Lib;

use SensioLabs\Consul\ServiceFactory;
use SensioLabs\Consul\ConsulResponse;


/**
 * Class Consul
 *
 * @provides  getServiceFactory()  Get a consul ServiceFactory instance
 * @provides  getKeyValue()        Get a KeyValue from the consul KeyValue store
 * @provides  clearKeyValue()      Clear the statically cached KeyValue store
 * @provides  getSharedSecret()    Get OpenExchangeRates API secret
 * @provides  @getAgentStatus()    Get status of consul agent
 *
 * @package App\Lib
 *
 * @author  Þói Juhasz
 */
class Consul
{

    /**
     * This is here for being able to mock the GuzzleClient:
     * @var null|\GuzzleHttp\Client
     */
    public static $guzzleClient = null;

    /** @var bool $enabled */
    public static $enabled = true;  // Set to `(bool) env('CONSUL_ENABLED')` after class definition

    /** @var null|\SensioLabs\Consul\ServiceFactory $serviceFactory */
    private static $serviceFactory = null;

    /** @var null|\SensioLabs\Consul\ConsulResponse $agentStatus */
    private static $agentStatus = null;

    /** @var array $keyValues */
    private static $keyValues = [];

    /**
     * Get or create an instance of ServiceFactory
     *
     * @param   bool $force Default false. If true, will
     *                      create a new ServiceFactory
     *
     * @return  \SensioLabs\Consul\ServiceFactory
     *
     * @author  Þói Juhasz
     */
    public static function getServiceFactory($force = false): ServiceFactory
    {
        if (self::$serviceFactory === null || $force) {
            self::$serviceFactory = new ServiceFactory(
                ['base_uri' => CONSUL_ENDPOINT],
                null,
                static::$guzzleClient
            );
        }

        return self::$serviceFactory;
    }

    /**
     * Get a value by key from the Consul KeyValue store
     *
     * @param   string  $key
     *
     * @return  null|string
     *
     * @author  Þói Juhasz
     */
    public static function getKeyValue(string $key): ?string
    {
        if (!array_key_exists($key, self::$keyValues)) {
            $body = self::getServiceFactory()
                ->get('kv')
                ->get($key)
                ->json();
            self::$keyValues[$key] = base64_decode($body[0]['Value']);
        }

        return self::$keyValues[$key];
    }

    /**
     * Empty the KV store cache
     *
     * @return  void
     *
     * @author  Þói Juhasz
     */
    public static function clearKeyValue(): void
    {
        self::$keyValues = [];
    }

    /**
     * Get Agent Status from Consul
     *
     * @return  null|\SensioLabs\Consul\ConsulResponse
     *
     * @author  Þói Juhasz
     */
    public static function getAgentStatus(): ?ConsulResponse
    {
        if (static::$enabled) {
            if (self::$agentStatus === null) {
                self::$agentStatus = static::getServiceFactory()->get('agent')->self();
            }
            return self::$agentStatus;
        }
        return null;
    }
}

Consul::$enabled = (bool) env('CONSUL_ENABLED');
