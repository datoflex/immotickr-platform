<?php

declare(strict_types=1);

require __DIR__ . '/_trigger.php';

runCronCommand('app:run-search-agents');
