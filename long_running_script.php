<?php

require 'vendor/autoload.php';

use Predis\Client;
use Predis\Connection\ConnectionException;

/**
 * Класс для управления выполнением скрипта с использованием Redis для блокировки повторного запуска.
 */
class ScriptExecutor
{
    private Client $redisClient;
    private string $lockKey;
    private int $lockTTL;

    /**
     * Конструктор класса.
     *
     * @param Client $redisClient Клиент Redis.
     * @param string $lockKey Ключ для блокировки.
     * @param int $lockTTL Время жизни блокировки в секундах.
     */
    public function __construct(Client $redisClient, string $lockKey, int $lockTTL = 10)
    {
        $this->redisClient = $redisClient;
        $this->lockKey = $lockKey;
        $this->lockTTL = $lockTTL;
    }

    /**
     * Метод для запуска скрипта.
     */
    public function execute(): void
    {
        try {
            if ($this->acquireLock()) {
                try {
                    $this->run();
                } finally {
                    $this->releaseLock();
                }
            } else {
                echo "Скрипт уже выполняется, повторный запуск невозможен\n";
            }
        } catch (ConnectionException $e) {
            echo "Ошибка подключения к Redis: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Метод для выполнения основной логики скрипта.
     */
    private function run(): void
    {
        echo "Скрипт запущен\n";

        // Симуляция выполнения работы в течение 5 секунд
        for ($i = 0; $i < 5; $i++) {
            echo "Выполнение шага " . ($i + 1) . "...\n";
            sleep(1);
        }

        echo "Скрипт завершил выполнение\n";
    }

    /**
     * Метод для установки блокировки.
     *
     * @return bool Удалось ли установить блокировку.
     * @throws ConnectionException В случае ошибки подключения к Redis.
     */
    private function acquireLock(): bool
    {
        return (bool)$this->redisClient->set($this->lockKey, 'locked', 'NX', 'EX', $this->lockTTL);
    }

    /**
     * Метод для снятия блокировки.
     * @throws ConnectionException В случае ошибки подключения к Redis.
     */
    private function releaseLock(): void
    {
        $this->redisClient->del([$this->lockKey]);
    }
}

// Конфигурация подключения к Redis
$redisClient = new Client([
    'scheme' => 'tcp',
    'host'   => '127.0.0.1',
    'port'   => 6379,
]);

// Инициализация и запуск скрипта
$scriptExecutor = new ScriptExecutor($redisClient, 'script_lock');
$scriptExecutor->execute();