<?php

/**
 * Class RouteTest
 *
 * Tests that application responds to health checks
 *
 * @author  Þói Juhasz
 */
class RouteTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testAppResponse()
    {
        $this->get('/');

        $this->assertEquals(
            json_encode([
                'status'     => 'OK',
                'time'       => date('Y-m-d H:i:s'),
                'agent'      => (bool)\App\Lib\Consul::getAgentStatus(),
                'agent_on'   => (bool)env('CONSUL_ENABLED'),
                'agent_addr' => CONSUL_ENDPOINT
            ]),
            $this->response->getContent()
        );
    }
}
