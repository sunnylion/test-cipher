<?php
declare(strict_types=1);


namespace Sunnylion\TestCipher\Stream\Base;


interface Callback
{
    public function call(string $data): void;
}