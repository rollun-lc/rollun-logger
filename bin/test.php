<?php
/**
 * Created by PhpStorm.
 * User: itprofessor02
 * Date: 12.03.19
 * Time: 17:30
 */

use Jaeger\Sampler\ConstSampler;
use Jaeger\Span\Factory\SpanFactory;
use rollun\dic\InsideConstruct;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$container = require 'config/container.php';

/** @var \Jaeger\Tracer\Tracer $tracer */
$tracer = $container->get(\Jaeger\Tracer\Tracer::class);
$tracer->start('qqq');