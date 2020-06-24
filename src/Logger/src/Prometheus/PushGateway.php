<?php
declare(strict_types=1);

namespace rollun\logger\Prometheus;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PushGateway
 *
 * @author r.ratsun <r.ratsun.rollun@gmail.com>
 */
class PushGateway
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port = '9091';

    /**
     * PushGateway constructor.
     */
    public function __construct()
    {
        $this->host = getenv('PROMETHEUS_HOST');
        if (!empty(getenv('PROMETHEUS_PORT'))) {
            $this->port = getenv('PROMETHEUS_PORT');
        }
    }

    /**
     * Pushes all metrics in a Collector, replacing all those with the same job.
     * Uses HTTP PUT.
     *
     * @param CollectorRegistry $collectorRegistry
     * @param string            $job
     * @param array             $groupingKey
     *
     * @throws GuzzleException
     */
    public function push(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = null): void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, 'put');
    }

    /**
     * Pushes all metrics in a Collector, replacing only previously pushed metrics of the same name and job.
     * Uses HTTP POST.
     *
     * @param CollectorRegistry $collectorRegistry
     * @param                   $job
     * @param                   $groupingKey
     *
     * @throws GuzzleException
     */
    public function pushAdd(CollectorRegistry $collectorRegistry, string $job, array $groupingKey = null): void
    {
        $this->doRequest($collectorRegistry, $job, $groupingKey, 'post');
    }

    /**
     * Deletes metrics from the Push Gateway.
     * Uses HTTP POST.
     *
     * @param string $job
     * @param array  $groupingKey
     *
     * @throws GuzzleException
     */
    public function delete(string $job, array $groupingKey = null): void
    {
        $this->doRequest(null, $job, $groupingKey, 'delete');
    }

    /**
     * @param CollectorRegistry $collectorRegistry
     * @param string            $job
     * @param array             $groupingKey
     * @param                   $method
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function doRequest(CollectorRegistry $collectorRegistry, string $job, array $groupingKey, $method): ResponseInterface
    {
        $url = "http://{$this->host}:{$this->port}/metrics/job/" . $job;
        if (!empty($groupingKey)) {
            foreach ($groupingKey as $label => $value) {
                $url .= "/" . $label . "/" . $value;
            }
        }
        $client = new Client();
        $requestOptions = [
            'headers'         => [
                'Content-Type' => RenderTextFormat::MIME_TYPE,
            ],
            'connect_timeout' => 10,
            'timeout'         => 20,
        ];
        if ($method != 'delete') {
            $renderer = new RenderTextFormat();
            $requestOptions['body'] = $renderer->render($collectorRegistry->getMetricFamilySamples());
        }
        $response = $client->request($method, $url, $requestOptions);

        return $response;
    }
}
