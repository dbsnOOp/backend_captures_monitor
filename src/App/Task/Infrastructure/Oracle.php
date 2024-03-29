<?php

namespace Monitor\App\Task\Infrastructure;

use Monitor\App\Task\Domain\Task;
use Monitor\App\TaskResult\Domain\TaskResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Exception;
use Monitor\App\Task\Domain\Collector;
use Monitor\App\Task\Domain\Exceptions\CantConnect;

class Oracle extends Collector
{

    const CONNECTOR_NAME = "oci";

    private string $host;
    private string $user;
    private string $password;
    private string $port;
    private string $sid = "";

    private Connection $connection;

    public function setConfig(array $config): void
    {
        list("db_host_ip" => $host, "db_host_port" => $port, "db_user" => $user, "db_password" => $pass) = $config;

        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $pass;
        if (array_key_exists("db_sid", $config) && !empty($config['db_sid']))
            $this->sid = $config['db_sid'];


        $this->setHash(sha1(json_encode($config) . self::CONNECTOR_NAME));
    }

    public function connect(): void
    {

        if ($this->hasConnection()) {
            $this->connection = $this->getConnection();
            $this->isConnected();
            return;
        }


        try {
            $connectionParams = [
                'user' => $this->user,
                'password' => $this->password,
                'host' => $this->host,
                'port' => $this->port,
                'driver' => 'oci8',
                'driverOptions' => array(
                    \PDO::ATTR_TIMEOUT => 5
                )
            ];
            if (!empty($this->sid))
                $connectionParams['servicename'] = $this->sid;
            $this->connection = DriverManager::getConnection($connectionParams);
            $this->setConnection($this->connection);
        } catch (Exception $e) {
            $this->invalidate();
            throw new CantConnect(self::CONNECTOR_NAME, 1000);
        }
        return;
    }

    public function isConnected(): void
    {
        if (!$this->connection->isConnected()) {
            $this->invalidate();
            $this->connect();
        }
    }

    public function invalidate(): void
    {
        $this->deleteConnection();
    }

    public function run(string $task_id, string $command): TaskResult
    {
        $task_result = new TaskResult($task_id, self::CONNECTOR_NAME);
        try {

            $task_result->startTimer("connection_time_" . self::CONNECTOR_NAME);

            $this->connect();
            $task_result->finishTimer("connection_time_" . self::CONNECTOR_NAME);

            $stm = $this->connection->prepare($command);

            $task_result->startTimer("task_$task_id");
            $result = $stm->executeQuery();
            $task_result->finishTimer("task_$task_id");

            $data = [];
            if ($this->limit > 0) {
                for ($i = 0; $i < $this->limit; $i++) {
                    if (!$row = $result->fetchAssociative())
                        break;
                    $data[] = $row;
                }
            } else {
                $data = $result->fetchAllAssociative();
            }
            $task_result->setResult($data);
            $task_result->setStatus("successfully");
        } catch (\Exception $e) {
            $task_result->setStatus("failed");
            $task_result->log($task_id, "Error", $e->getCode(), $e->getMessage());
        }
        $task_result->finish();
        return $task_result;
    }
}
