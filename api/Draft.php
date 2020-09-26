<?php

namespace Models;
namespace Api;

use PDO;
use Utils\Logger;
use Psr\Log\LoggerInterface;

/**
 * Класс подготавливает SQL-запросы.
 */
class Draft extends AbstractTable
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
        if ($this->isAuthor() === true) {
            $this->isGetRequestSuccess();
        }
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return array
     */
    public function isGetAllCompleted(): array
    {
        $pagination = ' LIMIT ';
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

            $query = "SELECT *
                    FROM drafts
                    WHERE author_id = :author_id" .
                    $pagination . $paginationArg;
            $statement = $this->pdo->prepare($query);

            $statement->bindParam(":author_id", $_SERVER['PHP_AUTH_USER']);

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
        }
    }

    /**
     * Запрос для получения элемента
     *
     * @param int $id
     *
     * @return array
     */
    public function isGetElementCompleted(int $id): array
    {
        $query = "SELECT * FROM drafts WHERE draft_id = (:draft_id) AND author_id = (:author_id)";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(":draft_id", $id);
        $statement->bindParam(":author_id", $_SERVER['PHP_AUTH_USER']);

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
        if (
            array_key_exists('article', $params) &&
            array_key_exists('category_id', $params) &&
            array_key_exists('content', $params) &&
            array_key_exists('main_image', $params)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Обработка POST-запроса
     *
     * @return void
     */
    public function processingPostRequest(): void
    {
        // Если
        // запрос к комментарию выполняется успешно и
        // существует дополнение к пути и
        // это дополнение равно publish
        if (
            $this->isGetRequestSuccess() === true &&
            isset($this->getParamsRequest()[3]) === true &&
            $this->getParamsRequest()[3] === 'publish'
        ) {
            $response = json_decode($this->response);

            // Если поле статус не существует (значит, черновик есть),
            // то изменить новость в таблице news
            // и изменить статус черновика is_published на 1
            if (isset($response->status) === false) {
                // Заполнить массив $putParams новыми значениями
                $putParams['article'] = $response->article;
                $putParams['category_id'] = $response->category_id;
                $putParams['content'] = $response->content;
                $putParams['main_image'] = $response->main_image;
                // Изменить новость
                $news = new News($this->pdo);

                $news->updateElement($putParams, $response->news_id);
                $this->response = $news->getResponse();
                // Изменить статус черновика
                $query = "UPDATE drafts SET
                            is_published = 1
                            WHERE draft_id = :draft_id";
                $statement = $this->pdo->prepare($query);

                $statement->bindParam(":draft_id", $this->getParamsRequest()[2]);

                $statement->execute();
            }
        } else {
            http_response_code(404);
            $this->response = json_encode([
                'status' => false,
                'message' => 'Not Found.'
            ]);
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
        $query = "INSERT INTO drafts (news_id, article, author_id, category_id, content, main_image)
                VALUES (:news_id, :article, :author_id, :category_id, :content, :main_image)";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':news_id', $postParams['news_id']);
        $statement->bindParam(':article', $postParams['article']);
        $statement->bindParam(':author_id', $_SERVER['PHP_AUTH_USER']);
        $statement->bindParam(':category_id', $postParams['category_id']);
        $statement->bindParam(':content', $postParams['content']);
        $statement->bindParam(':main_image', $postParams['main_image']);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'lastInsertId' => $this->pdo->lastInsertId()
        ];

        return $result;
    }

    /**
     * Обработка PUT-запроса
     *
     * @return void
     */
    public function processingPutRequest(): void
    {
        if ($this->isAccessAllowed() === true) {
            $id = $this->getParamsRequest()[2];
            parse_str(file_get_contents('php://input'), $putParams);
            $this->updateElement($putParams, $id);
        }
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
        $query = "UPDATE drafts SET
            article = :article, category_id = :category_id, content = :content, main_image = :main_image
            WHERE draft_id = :draft_id";
        $statement = $this->pdo->prepare($query);
        
        $statement->bindParam(':article', $putParams['article']);
        $statement->bindParam(':category_id', $putParams['category_id']);
        $statement->bindParam(':content', $putParams['content']);
        $statement->bindParam(':main_image', $putParams['main_image']);
        $statement->bindParam(':draft_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2]
        ];

        return $result;
    }

    /**
     * Обработка DELETE-запроса
     *
     * @return void
     */
    public function processingDeleteRequest(): void
    {
        if ($this->isAccessAllowed() === true) {
            if ($this->isAuthor() === true) {
                $this->deleteElement();
            }
        }
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
        $query = "DELETE FROM drafts WHERE draft_id = :draft_id";
        $statement = $this->pdo->prepare($query);

        $statement->bindParam(':draft_id', $id);

        $result = [
            'status' => $statement->execute(),
            'errorInfo' => $statement->errorInfo()[2],
            'rowCount' => $statement->rowCount()
        ];

        return $result;
    }

    /**
     * Доступ автора к своим черновикам
     *
     * @return bool
     */
    public function isAccessAllowed(): bool
    {
        if (
            isset($_SERVER['PHP_AUTH_USER']) === true &&
            $_SERVER['PHP_AUTH_USER'] !== '' &&
            isset($this->getParamsRequest()[2]) === true &&
            $this->getParamsRequest()[2] !== '' &&
            $this->isGetElementCompleted($this->getParamsRequest()[2])['status'] === true
        ) {
            $draft = $this->isGetElementCompleted($this->getParamsRequest()[2]);

            if ($draft['fetch'] !== null) {
                if ($draft['fetch']['author_id'] === $_SERVER['PHP_AUTH_USER']) {
                    $this->logger->debug('Access is allowed.');
                    return true;
                } else {
                    $this->logger->debug('Access denied');
                }
            }
        } else {
            $this->logger->debug('User not found.');
        }

        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
        return false;
    }
}
