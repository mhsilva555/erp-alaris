<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Push (VAPID)
    |--------------------------------------------------------------------------
    |
    | Chaves usadas para enviar notificações push ao Service Worker (public/sw.js)
    | mesmo com o PWA fechado ou o celular bloqueado. Gere com:
    |   php artisan webpush:vapid
    |
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:contato@alarisservice.com.br'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    // Tempo (segundos) que o serviço de push do navegador guarda a mensagem se o aparelho estiver offline
    'ttl' => (int) env('WEBPUSH_TTL', 86400),
];
