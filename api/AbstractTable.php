<?php

namespace Api;

/**
 * Класс для осуществления GET, POST, PUT и DELETE-запросов.
 */
abstract class AbstractTable implements TableInterface
{
    protected $response;
    protected $statment;

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
     * @return string
     */
    //abstract public function processingGetRequest(): string;

    /**
     * Обработка GET-запроса.
     *
     * @return bool
     */
    public function isGetRequestSuccess(): bool
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: access");
        header("Access-Control-Allow-Methods: GET");
        header("Access-Control-Allow-Credentials: true");

        $status = false;

        // Если нужно получить по id, либо всё
        if ($this->getId() > 0) {
            $status = $this->isGetElement($this->getId());
        } else {
            $status = $this->isGetAll();
        }   
        
        return $status;
    }

    /**
     * @return bool
     */
    public function isGetAll(): bool
    {
        // Результат запроса к таблице
        $status = $this->isGetAllComplited();

        if ($status === false) {
            $this->logger->error(
                "Bad request. Message from PDO: {errorInfo}.",
                ['errorInfo' => $this->statment->errorInfo()[2]]
            );
            
            http_response_code(400);
            $this->response = json_encode([
                "status" => "false",
                "message" => $this->statment->errorInfo()[2]
            ]);
        } else {
            $this->logger->info('The request was completed successfully.');
            // Фиксация полученных данных
            http_response_code(200);
            $this->response = json_encode($this->statment->fetchAll(\PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        }

        return $status;
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return bool
     */
    abstract protected function isGetAllComplited(): bool;

    /**
     * @param int $id
     *
     * @return bool
     */
    public function isGetElement(int $id): bool
    {
        // Результат запроса к таблице        
        $status = $this->isGetElementComplited($id);
        
        if ($status === false) {
            $this->logger->info(
                "Not found. Message from PDO: {errorInfo}.",
                ['errorInfo' => $this->statment->errorInfo()[2]]
            );
            
            http_response_code(404);
            $this->response = json_encode([
                "status" => false,
                "message" => $this->statment->errorInfo()[2]
            ]);
        } elseif ($this->statment->rowCount() === 0) {
            http_response_code(404);
            $this->response = json_encode([
                "status" => false,
                "message" => $this->statment->errorInfo()[2]
            ]);
        } else {
            $this->logger->info(' The request was completed successfully.');
            // Фиксация полученных данных
            http_response_code(200);
            $this->response = json_encode($this->statment->fetch(\PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        }

        return $status;
    }

     /**
     * Запрос для получения элемента
     *
     * @return bool
     */
    abstract public function isGetElementComplited(int $id): bool;

    /**
     * Обработка POST-запроса.
     *
     * @return bool
     */
    public function createElement(): bool
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: *");

        $status = false;
        // Проверка переданных переменных
        if ($this->isExistsParamsInArray($_POST)) {
            // Результат запроса к таблице
            $status = $this->isCreateElementCompleted($_POST);
            
            if ($status === false) {
                $this->logger->error(
                    'Bad request. Message from PDO: {errorInfo}.',
                    ['errorInfo' => $this->statment->errorInfo()[2]]
                );
                
                http_response_code(400);
                $this->response = json_encode([
                    "status" => false,
                    "message" => $this->statment->errorInfo()[2]
                ]);
            } else {
                http_response_code(201);
                $this->response = json_encode([
                    'status' => true,
                    'id' => $this->pdo->lastInsertId()
                ]);
            } 
        } else {
            http_response_code(400);
            $this->response = json_encode([
                'status' => false,
                "message" => "Bad request or not enough data."
            ]);
        }

        return $status;
    }

    /**
     * @param array $params - параметры запроса
     *
     * @return bool
     */
    abstract protected function isExistsParamsInArray(array $params): bool;

    /**
     * Запрос на создание элемента
     *
     * @param array $postParams
     *
     * @return bool
     */
    abstract public function isCreateElementCompleted(array $postParams): bool;

    /**
     * Обработка PUT-запроса
     * @param array $putParams
     *
     * @return void
     */
    public function updateElement($putParams): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: *");

        // Получить идентификатор
        $id = $this->getId();

        if ($this->isExistsParamsInArray($putParams)) {
            // Запрос к таблице
            $status = $this->isUpdateElementCompleted($id, $putParams);
                        
            if ($status === false) {
                $this->logger->error(
                    'Bad request. Message from PDO: {errorInfo}.',
                    ['errorInfo' => $this->statment->errorInfo()[2]]
                );
                
                http_response_code(304);
                $this->response = json_encode([
                    "status" => false,
                    "message" => $this->statment->errorInfo()[2]
                ]);
            } else {
                http_response_code(200);
                $this->response = json_encode([
                    "status" => true,
                    'id' => "$id"
                ]);
            }
        } else {
            http_response_code(400);
            $this->response = json_encode([
                'status' => false,
                "message" => "Bad request or not enough data."
            ]);
        }
    }

    /**
     * Запрос для обновления элемента
     *
     * @param int $id
     * @param array $putParams - параметры запроса
     *
     * @return bool
     */
    abstract public function isUpdateElementCompleted(int $id, array $putParams): bool;

    /**
     * Обработка DELETE-запроса
     *
     * @return void
     */
    public function deleteElement(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: DELETE");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: *");

        // Получить идентификатор
        $id = $this->getId();

        if ($id > 0) {
            // Запрос к таблице
            $status = $this->isDeleteteElementCompleted($id);

            if ($status === false) {
            
                $this->logger->error(
                    'Bad request. Message from PDO: {errorInfo}.',
                    ['errorInfo' => $this->statment->errorInfo()[2]]
                );
                
                http_response_code(400);
                $this->response = json_encode([
                    'status' => false,
                    "message" => $this->statment->errorInfo()[2]
                ]);
            } elseif ($this->statment->rowCount() === 0) {
                http_response_code(404);
                $this->response = json_encode([
                    "status" => false,
                    "message" => 'Not Found'
                ]);
            } else {
                http_response_code(200);
                $this->response = json_encode([
                    'status' => true,
                    "id" => "$id"
                ]);
            }
        } else {
            http_response_code(400);
            $this->response = json_encode([
                'status' => false,
                "message" => "Bad request or not enough data."
            ]);
        }
    }

    /**
     * Запрос на удаление элемента
     *
     * @param int $id
     *
     * @return bool
     */
    abstract public function isDeleteteElementCompleted(int $id): bool;

    /**
     * @return int
     */
    abstract protected function getId(): int;

    /**
     * Идентификация администратора
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        $users = new User($this->pdo);

        if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== '' && $users->isGetElementComplited($_SERVER['PHP_AUTH_USER']) === true) {
            $this->statment = $users->getStatment();
            
            if ($this->statment !== null) {
                if ($this->statment->rowCount() !== 0) {
                    $user = $this->statment->fetch(\PDO::FETCH_ASSOC);

                    if ($user['is_admin'] === '1') {
                        $this->logger->debug('Access is allowed.');
                        return true;
                    }
                }
            }
        }
        $this->logger->debug('Access denied');

        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
        return false;        
    }

    /**
     * Идентификация пользователя
     *
     * @return bool
     */
    public function isAccessAllowed(): bool
    {
        $users = new User($this->pdo);

        if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== '' && $users->isGetElementComplited($_SERVER['PHP_AUTH_USER']) === true) {
            $this->statment = $users->getStatment();

            if ($this->statment !== null) {
                $users = $this->statment->fetch(\PDO::FETCH_ASSOC);

                if ($users['user_id'] === (string) $this->getId()) {
                    $this->logger->debug('Access is allowed.');
                    return true;
                }
            }
        }
        $this->logger->debug('Access denied');

        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
        return false;    
    }

    /**
     * Идентификация автора
     *
     * @return bool
     */
    public function isAuthor(): bool
    {
        $authors = new Author($this->pdo);

        if (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] !== '' && $authors->isGetElementComplited($_SERVER['PHP_AUTH_USER']) === true) {
            $this->statment = $authors->getStatment();
            
            if ($this->statment !== null) {
                $authors = $this->statment->fetch(\PDO::FETCH_ASSOC);

                if ($authors['user_id'] === (string) $_SERVER['PHP_AUTH_USER']) {
                    $this->logger->debug('Access is allowed.');
                    return true;
                }
            }
        }
        $this->logger->debug('Access denied');

        http_response_code(404);
        $this->response = json_encode([
            'status' => false,
            'message' => 'Not Found.'
        ]);
        return false;    
    }

    /**
     * Получить ответ
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }
    
    /**
     * @return object
     */
    public function getStatment(): object
    {
        return $this->statment;
    }
}
