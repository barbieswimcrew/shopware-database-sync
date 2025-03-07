<?php declare(strict_types=1);

namespace AtticConcepts\DatabaseSync;

use Shopware\Core\Framework\Plugin;

class DatabaseSync extends Plugin
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}