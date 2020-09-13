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
    protected PDO $pdo;
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
     * Подготовка данных перед выдачей
     *
     * @return void
     */
    public function processingGetRequest(): void
    {
        $this->isGetRequestSuccess();
    }
    
    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $pagination = ' LIMIT ';
        $paginationArg = '';
        
        // Пагинация
        if (array_key_exists('pagination', $_GET)) {
            // Разобрать аргументы
            $paginationArg = substr($_GET['pagination'], 1, -1);
        }

        if ($paginationArg === '') {
            $pagination = '';
        }

        $query = "SELECT * FROM categories" .
                $pagination . $paginationArg;
                
        $this->statment = $this->pdo->prepare($query);
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос для получения элемента
     *
     * @return bool
     */
    public function isGetElementComplited(int $id): bool
    {
        $query = "SELECT * FROM categories WHERE category_id=(:category_id)";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(":category_id", $id);
        
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * @param array $params - параметры запроса
     *
     * @return bool
     */
    protected function isExistsParamsInArray(array $params): bool
    {
        if (
            array_key_exists('name', $params)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Запрос на создание элемента
     *
     * @param array $postParams
     *
     * @return bool
     */
    public function isCreateElementCompleted(array $postParams): bool
    {
        $query = "INSERT INTO categories (name, parent_category_id) 
                VALUES (:name, :parent_category_id)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':name', $postParams['name']);
        $this->statment->bindParam(':parent_category_id', $postParams['parent_category_id']);
        
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос для обновления элемента
     *
     * @param array $putParams - параметры запроса
     * @param int $id
     *
     * @return bool
     */
    public function isUpdateElementCompleted(array $putParams, int $id): bool
    {
        $query = "UPDATE categories SET
            name = :name, parent_category_id = :parent_category_id
            WHERE category_id = :category_id";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':name', $putParams['name']);
        $this->statment->bindParam(':parent_category_id', $putParams['parent_category_id']);
        $this->statment->bindParam(':category_id', $id);
        
        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return bool
     */
    public function isDeleteElementCompleted(int $id): bool
    {
        $query = "DELETE FROM categories WHERE category_id = :category_id";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(':category_id', $id);
        
        $status = $this->statment->execute();

        return $status;
    }
}
