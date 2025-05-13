<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,  // ✅ Upgrade to PHP 8.1
        SetList::CODE_QUALITY,       // 🧹 Clean up and simplify code
        SetList::DEAD_CODE,          // 💀 Remove unused code
        SetList::TYPE_DECLARATION,   // 📎 Add missing type hints
        SetList::NAMING,             // 🔤 Improve variable/method/class naming
        SetList::EARLY_RETURN        // Simplify nested if statements into early returns
    ]);
};
