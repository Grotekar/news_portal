<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Category extends AbstractTable
{
    private PDO $pdo;
    private string $tableName = 'tags';
    protected LoggerInterface $logger;
    
    /**
     * @param PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->logger = new Logger();
        $this->pdo = $pdo;
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return object
     */
    protected function getStatmentForAllElements(): object
    {
        $query = "SELECT * FROM tags";
        $statment = $this->pdo->prepare($query);
        return $statment;
    }

    /**
     * Запрос для получения элемента
     *
     * @return object
     */
    protected function getStatmentForGetElement(int $id): object
    {
        $query = "SELECT * FROM tags WHERE tag_id=(:tag_id)";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(":tag_id", $id);
        return $statment;
    }

    /**
     * @param array $params - параметры запроса
     *
     * @return bool
     */
    protected function isExistsParamsInArray(array $params): bool
    {
        if (array_key_exists('name', $params)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Запрос на создание элемента
     *
     * @return object
     */
    protected function getStatmentForCreateElement(): object
    {
        
        $query = "INSERT INTO tags (name) 
                VALUES (:name)";
        $statment = $this->pdo->prepare($query);

        $newPostParamName = iconv('CP1251', 'UTF-8', $_POST['name']);

        $statment->bindParam(':name', $newPostParamName);

        return $statment;
    }

    /**
     * Запрос для обновления элемента
     *
     * @param int $id
     * @param array $putParams - параметры запроса
     *
     * @return object
     */
    protected function getStatmentForUpdateElement(int $id, array $putParams): object
    {
        $query = "UPDATE tags SET
            name = :name
            WHERE tag_id = :tag_id";
        $statment = $this->pdo->prepare($query);

        $newPutParamAName = iconv('CP1251', 'UTF-8', $putParams['name']);
        
        $statment->bindParam(':name', $newPutParamName);
        $statment->bindParam(':tag_id', $id);

        return $statment;
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return object
     */
    protected function getStatmentForDeleteElement(int $id): object
    {
        $query = "DELETE FROM tags WHERE tag_id = :tag_id";
        $statment = $this->pdo->prepare($query);
        $statment->bindParam(':tag_id', $id);

        return $statment;
    }

    /**
     * @return int
     */
    protected function getId(): int
    {
        return (int) substr($_SERVER['REQUEST_URI'], 10);
    }
}
