<?php

namespace DarthSoup\Whmcs;

use DarthSoup\Whmcs\Adapter\ConnectorInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Config\Repository;

/**
 * Class WhmcsManager
 */
class WhmcsManager
{
    /**
     * @var
     */
    protected $config;

    /**
     * @var ConnectorInterface
     */
    protected $client;

    /**
     * Whmcs constructor.
     * @param $config
     * @param ConnectorInterface $client
     */
    public function __construct(Repository $config, ConnectorInterface $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * @return ConnectorInterface
     */
    public function connection()
    {
        return $this->client->connect($this->config->get('whmcs'));
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    protected function execute(string $method, $parameters = [])
    {
        $parameters['action'] = $method;

        try {
            $response = $this->connection()->post('api.php', [
                'form_params' => array_merge($parameters, $this->connection()->getConfig()['form_params']),
            ]);

            if ($this->config['responsetype'] === 'xml') {
                return simplexml_load_string($response->getBody());
            }

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $ex) {
            $response = json_decode($ex->getResponse()->getBody()->getContents(), true);
            return $response;
        }
    }

    /**
     * Dynamically pass methods to the default connection.
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, $parameters)
    {
        return $this->execute($method, ...$parameters);
    }
}
