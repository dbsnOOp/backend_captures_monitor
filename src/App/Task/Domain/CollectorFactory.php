<?php

namespace Monitor\App\Task\Domain;

use Monitor\App\Task\Domain\Interfaces\ICollector;
use Monitor\App\Task\Infrastructure\Curl;
use Monitor\App\Task\Infrastructure\DB2;
use Monitor\App\Task\Infrastructure\File;
use Monitor\App\Task\Infrastructure\Local;
use Monitor\App\Task\Infrastructure\MongoDB;
use Monitor\App\Task\Infrastructure\Mssql;
use Monitor\App\Task\Infrastructure\Mysql;
use Monitor\App\Task\Infrastructure\Neo4jAura;
use Monitor\App\Task\Infrastructure\Oracle;
use Monitor\App\Task\Infrastructure\Postgresql;
use Monitor\App\Task\Infrastructure\Ssh;

class CollectorFactory
{
    public static function get(string $connector_name): ICollector
    {
        switch ($connector_name) {
            case "mysql":
                return new Mysql;
            case "ssh":
                return new Ssh;
            case "neo4j_aura":
                return new Neo4jAura;
            case "postgresql":
                return new Postgresql;
            case "mssql":
                return new Mssql;
            case "oci":
                return new Oracle;
            case "mongodb":
                return new MongoDB;
            case "file":
                return new File;
            case "local":
                return new Local;
            case "db2":
                return new DB2;
            case "curl":
                return new Curl;
        }
    }
}
