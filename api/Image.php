<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Image extends AbstractTable
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
     * @return array
     */
    public function isGetAllCompleted(): array
    {
        /*$pagination = ' LIMIT ';
        $paginationArg = '';
        $isValid = true;
        $result = [];

        // Пагинация
        if (array_key_exists('pagination', $_GET)) {
            // Проверка
            if ($this->isValidPagination($_GET['pagination']) === true) {
                $paginationArg = substr($_GET['pagination'], 1, -1);
            } else {
                $this->logger->debug('Invalid pagination');
                $isValid = false;
            }
        }

        if ($isValid === true) {
            if ($paginationArg === '') {
                $pagination = '';
            }

            $query = "SELECT * FROM images" .
                $pagination . $paginationArg;
            $statement = $this->pdo->prepare($query);

            $result = [
                'status' => $statement->execute(),
                'errorInfo' => $statement->errorInfo()[2],
                'rowCount' => $statement->rowCount(),
                'fetchAll' => $statement->fetchAll(PDO::FETCH_ASSOC)
            ];

            return $result;
        } else {
            $result = [
                'status' => false,
                'errorInfo' => 'Bad Request',
                'rowCount' => 0
            ];

            return $result;
        }*/
    }

    /**
     * Запрос для получения элемента +
     *
     * @param int $id
     *
     * @return array
     */
    public function isGetElementCompleted(int $id): array
    {
        $query = "SELECT * FROM images WHERE image_id=(:image_id)";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(":image_id", $id);
        
        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount(),
            'fetch' => $statement->fetch(PDO::FETCH_ASSOC)
        ];

        return $result;
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
     * @param array $postParams
     *
     * @return array
     */
    public function isCreateElementCompleted(array $postParams): array
    {
        $query = "INSERT INTO images (name) 
                VALUES (:name)";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':name', $postParams['name']);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'lastInsertId' => $this->pdo->lastInsertId()
        ];

        return $result;
    }

    /**
     * Запрос для обновления элемента
     *
     * @param array $putParams - параметры запроса
     * @param int $id
     *
     * @return array
     */
    public function isUpdateElementCompleted(array $putParams, int $id): array
    {
        /*$query = "UPDATE images SET
            name = :name
            WHERE image_id = :image_id";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':name', $putParams['name']);
        $statement->bindParam(':image_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2]
        ];

        return $result;*/
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return array
     */
    public function isDeleteElementCompleted(int $id): array
    {
        /*$query = "DELETE FRimages WHERE image_id = :image_id";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':image_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount()
        ];

        return $result;*/
    }
}
