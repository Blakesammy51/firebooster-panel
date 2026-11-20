<?php
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
return [
'default'=>env('LOG_CHANNEL','stderr'),
'deprecations'=>['channel'=>env('LOG_DEPRECATIONS_CHANNEL','null'),'trace'=>false],
'channels'=>[
'stack'=>['driver'=>'stack','channels'=>explode(',',env('LOG_STACK','stderr')),'ignore_exceptions'=>false],
'single'=>['driver'=>'single','path'=>storage_path('logs/laravel.log'),'level'=>env('LOG_LEVEL','debug'),'replace_placeholders'=>true],
'stderr'=>['driver'=>'monolog','level'=>env('LOG_LEVEL','info'),'handler'=>StreamHandler::class,'handler_with'=>['stream'=>'php://stderr'],'processors'=>[PsrLogMessageProcessor::class]],
'null'=>['driver'=>'monolog','handler'=>Monolog\Handler\NullHandler::class],
'emergency'=>['path'=>storage_path('logs/laravel.log')],
],
];