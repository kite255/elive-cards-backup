<?php

namespace App\Providers;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Bryceandy\Beem\Events\SmsDeliveryReportReceived::class => [
            \App\Listeners\ProcessDeliveryReport::class,
        ],
    ];

   
}       