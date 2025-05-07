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
use Jaeger\Span\StackSpanManager;
use Jaeger\Thrift\Agent\AgentClient;
use Jaeger\Tracer\Tracer;
use Jaeger\Transport\TUDPTransport;
use Psr\Container\ContainerInterface;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TracerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Laminas\ServiceManager\Exception\ServiceNotFoundException if unable to resolve the service.
     * @throws \Laminas\ServiceManager\Exception\ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
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
            //new SplStack(),
            new StackSpanManager(),
            new SpanFactory(
                new RandomIntGenerator(),
                new ConstSampler($isDebugEnable)
            ),
            $client
        );

        return $tracer;
    }
}
