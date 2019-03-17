<?php
/**
 * Created by PhpStorm.
 * User: victor
 * Date: 17.03.19
 * Time: 13:32
 */

namespace rollun\tracer;

use Jaeger\Client\ThriftClient;
use Jaeger\Id\RandomIntGenerator;
use Jaeger\Sampler\ConstSampler;
use Jaeger\Span\Factory\SpanFactory;
use Jaeger\Thrift\Agent\AgentClient;
use Jaeger\Tracer\Tracer;
use Jaeger\Transport\TUDPTransport;
use SplStack;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Zend\ServiceManager\Factory\FactoryInterface;

class TracerFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param  \Interop\Container\ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Zend\ServiceManager\Exception\ServiceNotFoundException if unable to resolve the service.
     * @throws \Zend\ServiceManager\Exception\ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws \Interop\Container\Exception\ContainerException if any other error occurs
     */
    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $tracerConfig = $config[Tracer::class] ?? [];

        $tgAgentHost = $tracerConfig['host'] ?? 'localhost';
        $tgAgentPort = $tracerConfig['port'] ?? 6832;
        $serviceName = $tracerConfig['serviceName'] ?? 'application';
        $isDebugEnable = $tracerConfig['debugEnable'] ?? true;

        $transport = new TUDPTransport($tgAgentHost, $tgAgentPort);
        $bufferTransport = new TBufferedTransport($transport);
        $binaryProtocol = new TBinaryProtocol($bufferTransport);

        $client = new ThriftClient(
            $serviceName,
            new AgentClient($binaryProtocol)
        );

        $bufferTransport->open();

        $tracer = new Tracer(
            new SplStack(),
            new SpanFactory(
                new RandomIntGenerator(),
                new ConstSampler($isDebugEnable)
            ),
            $client
        );

        return $tracer;
    }
}