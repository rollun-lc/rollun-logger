<?php

namespace rollun\logger\Services;

interface JsonTruncatorInterface
{
    public function truncate(string $json): string;
}
