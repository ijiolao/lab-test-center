<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Printer Connectivity
    |--------------------------------------------------------------------------
    |
    | Configure how the thermal label printer is reached on the network. These
    | settings can be overridden via environment variables so staging and
    | production can use different printers without code changes.
    |
    */

    'printer_ip' => env('PRINTING_PRINTER_IP'),
    'printer_port' => env('PRINTING_PRINTER_PORT', 9100),

    /*
    |--------------------------------------------------------------------------
    | Feature Toggle
    |--------------------------------------------------------------------------
    |
    | Some installations may want to disable printing entirely when a printer
    | is offline. Toggling this flag makes it easier to short-circuit the print
    | service before it even tries to connect.
    |
    */

    'enabled' => env('PRINTING_ENABLED', true),
];
