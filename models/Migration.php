<?php

namespace Models;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

class Migration
{
    private LoggerInterface $logger;
    private PDO $pdo;

    public function __construct()
    {
        $this->logger = new Logger();
    }
    
    /**
     * Выполнить миграции, которые отсутствуют в таблице migrations
     *
     * @param PDO $pdo
     */
    public function migrate(PDO $pdo)
    {
        $this->pdo = $pdo;
        $migrationsInTable = $this->getMigrations($this->pdo);

        $migrationsInFilesystem = [];
        foreach (glob(__DIR__ . "/../migrations/*.sql") as $filename) {
            $migrationsInFilesystem[] = basename($filename);
        }

        // Разница между файловой системой и таблицей
        $different = array_diff($migrationsInFilesystem, $migrationsInTable);

        if (count($different) !== 0) {
            $this->logger->debug(
                "Cписок недостающих миграций: \n    {different}.",
                ['different' => implode("\n    ", $different)]
            );
            $this->logger->info('Выполнение недостающих миграций');
            
            $this->makeMigrate($different);
        } else {
            $this->logger->info('База данных в актуальном состоянии');
        }
    }

    /**
     * Функция возвращает миграции из таблицы migrations
     *
     * @return array
     */
    private function getMigrations(): array
    {
        $migrations = [];
        
        // Создать таблицу migrations, если не существует
        /* $query = "SHOW TABLES LIKE 'migrations'";
        $statment = $this->pdo->prepare($query);
        $isSuccess = $statment->execute();

        if ($isSuccess === false) {
            $zeroMigration = '00000_create_migrations_table.sql';
            $query = file_get_contents(__DIR__ . '/../migrations/' . $zeroMigration);
            $statment = $this->pdo->prepare($query);
            $statment->execute();
            $this->logger->info('Создана таблица migrations.');
            $this->recordInTableMigrations($zeroMigration);
        } */
        
        // Запрос к таблице
        $query = "SELECT name FROM migrations";
        $statment = $this->pdo->prepare($query);
        $statment->execute();
        
        // Составление списка по полю names из таблицы
        foreach ($statment->fetchAll(\PDO::FETCH_NUM) as $rowInTable) {
            foreach ($rowInTable as $column => $filename) {
                $migrations[] = $filename;
            }
        }
        $this->logger->info(
            'Получен список миграций из таблицы базы данных ({count}).',
            ['count' => count($migrations)]
        );
        
        $this->logger->debug(
            "Cписок миграций из таблицы: \n    {migrations}.",
            ['migrations' => implode("\n    ", $migrations)]
        );

        return $migrations;
    }

    /**
     * Выполнение недостающих миграций
     *
     * @param array $migrations
     *
     * @return void
     */
    private function makeMigrate(array $migrations): void
    {
        $i = 1;
        $countMigrations = count($migrations);
        $countSuccess = 0;
        $countBad = 0;

        foreach ($migrations as $key => $filename) {
                $this->logger->info(
                    "[{item}/{items}].",
                    ['item' => $i, 'items' => $countMigrations]
                );
            $this->logger->debug(
                "  : {filename}.",
                ['filename' => $filename]
            );
            $i++;

            $query = file_get_contents(__DIR__ . '/../migrations/' . $filename);
            $statment = $this->pdo->prepare($query);
            $isSuccess = $statment->execute();

            if ($isSuccess === false) {
                $this->logger->error(
                    '   Не удалось выполнить миграцию {filename}.',
                    ['filename' => $filename]
                );
                $countBad++;
            } else {
                // Фиксация выполненной миграции в одноименной таблице
                $this->recordInTableMigrations($filename);
                $countSuccess++;
            }
        }
        
        $this->logger->info(
            'Успешно {countGood}/{countMigrations}, с ошибками {countBad}/{countMigrations}.',
            ['countGood' => $countSuccess, 'countBad' => $countBad, 'countMigrations' => $countMigrations]
        );
    }

    /**
     * Фиксация выполненной миграции
     *
     * @param string $filename
     *
     * @return void
     */
    private function recordInTableMigrations(string $filename): void
    {
        $query = "INSERT INTO migrations (name) VALUES (:name)";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(":name", $filename);
        $statment->execute();
    }
}
