<?php

return [
    'lora' => [
        'serverBaseUrl' => env(
            'LORA_BASE_URL',
            'https://platform.ceit.aut.ac.ir:50013'
        ),
        'organizationID' => env('LORA_ORG_ID', '1'),
        'networkServerID' => env('LORA_NET_SERVER_ID', '1'),
        'serviceProfileID' => env(
            'LORA_SERVICE_PROFILE_ID',
            '2dc02c2b-2be9-4bef-ade4-f3408e62a08c'
        ),
    ],
    'core' => [
        'serverBaseUrl' => env('CORE_BASE_URL', '172.23.132.50'),
        'pmPort' => '8080',
        'dmPort' => '1372',
        'downLinkPort' => '1373',
        'gmPort' => '1374'
    ],
    'lan' => [
        'serverBaseUrl' => env('LAN_BASE_URL', '172.23.132.50:9000'),
    ],


];
