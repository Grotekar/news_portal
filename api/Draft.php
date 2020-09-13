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
        $this->isGetRequestSuccess();
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    public function isGetAllComplited(): bool
    {
        $query = "SELECT *
                FROM drafts
                WHERE author_id = :author_id";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(":author_id", $_SERVER['PHP_AUTH_USER']);

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
        $query = "SELECT * FROM drafts WHERE draft_id = (:draft_id) AND author_id = (:author_id)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(":draft_id", $id);
        $this->statment->bindParam(":author_id", $_SERVER['PHP_AUTH_USER']);
        
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
            isset($this->getParamsRequest()[4]) === true &&
            $this->getParamsRequest()[4] === 'publish'
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
                $statment = $this->pdo->prepare($query);

                $statment->bindParam(":draft_id", $this->getParamsRequest()[3]);
                $statment->execute();
            }
        } else {
            $this->getNotFound();
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
        $query = "INSERT INTO drafts (news_id, article, author_id, category_id, content, main_image)
                VALUES (:news_id, :article, :author_id, :category_id, :content, :main_image)";
        $this->statment = $this->pdo->prepare($query);

        $this->statment->bindParam(':news_id', $postParams['news_id']);
        $this->statment->bindParam(':article', $postParams['article']);
        $this->statment->bindParam(':author_id', $_SERVER['PHP_AUTH_USER']);
        $this->statment->bindParam(':category_id', $postParams['category_id']);
        $this->statment->bindParam(':content', $postParams['content']);
        $this->statment->bindParam(':main_image', $postParams['main_image']);

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
        $query = "UPDATE drafts SET
            article = :article, category_id = :category_id, content = :content, main_image = :main_image
            WHERE draft_id = :draft_id";
        $this->statment = $this->pdo->prepare($query);
        
        $this->statment->bindParam(':article', $putParams['article']);
        $this->statment->bindParam(':category_id', $putParams['category_id']);
        $this->statment->bindParam(':content', $putParams['content']);
        $this->statment->bindParam(':main_image', $putParams['main_image']);
        $this->statment->bindParam(':draft_id', $id);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Обработка DELETE-запроса
     *
     * @return void
     */
    public function processingDeleteRequest(): void
    {
        // Если
        // запрос к новости выполняется успешно и
        // существует дополнение к пути и
        // это дополнение равно comments
        if (
            $this->isGetRequestSuccess() === true &&
            isset($this->getParamsRequest()[4]) === true &&
            $this->getParamsRequest()[4] === 'comments'
        ) {
            // Если пользователь удаляет свой комментарий
            if ($this->isAccessAllowedToComment() === true) {
                $response = json_decode($this->getResponse());

                // Если поле статус не существует (значит, комментарий есть),
                // то удалить комментарий к новости
                if (!isset($response->status)) {
                    $comments = new Comment($this->pdo);
                    
                    $comments->deleteElement(5);

                    $this->response = $comments->getResponse();
                }
            }
        } elseif ($this->isAuthor() === true) {
            $this->deleteElement();
        }
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
        $query = "DELETE FROM drafts WHERE draft_id = :draft_id";
        $this->statment = $this->pdo->prepare($query);
        $this->statment->bindParam(':draft_id', $id);

        $status = $this->statment->execute();

        return $status;
    }

    /**
     * Доступ автора к своим черновикам
     *
     * @return bool
     */
    public function isAccessAllowed(): bool
    {
        if (
            isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== '' &&
            isset($this->getParamsRequest()[3]) && $this->getParamsRequest()[3] !== '' &&
            $this->isGetElementComplited($this->getParamsRequest()[3]) === true
        ) {
            if ($this->statment !== null) {
                $draft = $this->statment->fetch(\PDO::FETCH_ASSOC);

                if ($draft['author_id'] === $_SERVER['PHP_AUTH_USER']) {
                    $this->logger->debug('Access is allowed.');
                    return true;
                } else {
                    $this->logger->debug('Access denied');
                }
            }
        }

        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
        return false;
    }
}
