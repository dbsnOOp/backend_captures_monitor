<?php

namespace App;

use React\EventLoop\Loop;
use Sohris\Core\ComponentControl;
use Sohris\Core\Server as CoreServer;
use Sohris\Event\Event;

class Server extends ComponentControl
{
    private $server;
    private $runner;
    
    /**
     * @var Event
     */
    private $event;


    public function __construct()
    {
        $this->server = CoreServer::getServer();
        $this->event = $this->server->getComponent("Sohris\\Event\\Event");
        $this->runner = $this->event->getEvent("App\\Runner");
    }

    public function install()
    {
        Loop::addPeriodicTimer(360, fn () => $this->restartCollector());
    }

    public function start()
    {
    }

    private function restartCollector()
    {
        $this->runner->restart();
    }

    private function sendStats()
    {
        
        $base = json_decode(file_get_contents(CoreServer::getRootDir() . "/stats"),true);
        $base['uptime'] = $this->server->getUptime();
        $base['thread_status'] = $this->runner->getStats();
        API::sendStats($base);
    }
}