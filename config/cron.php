<?php

return [

    // Shared secret required as ?token=... when triggering public/cron/*.php over HTTP.
    'secret' => env('CRON_SECRET'),

];
