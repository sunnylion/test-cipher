<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream\Base;


interface Filter
{
    public function filter(string $data): string;
}