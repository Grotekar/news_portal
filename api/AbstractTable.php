<?php

namespace Api;

/**
 * Класс для осуществления GET, POST, PUT и DELETE-запросов.
 */
abstract class AbstractTable implements TableInterface
{
    protected string $response;

    /**
     * Обработка запросов
     *
     * @return void
     */
    public function processingRequest()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processingGetRequest();
                break;
            case 'POST':
                $this->processingPostRequest();
                break;
            case 'PUT':
                $this->processingPutRequest();
                break;
            case 'DELETE':
                $this->processingDeleteRequest();
                break;
            default:
                $this->response = json_encode([
                    'status' => false,
                    'message' => 'Invalid request'
                ]);
                break;
        }
    }

    /**
     * Подготовка данных перед выдачей
     *
     * @return void
     */
    abstract public function processingGetRequest(): void;

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
        if (
            isset($this->getParamsRequest()[2]) &&
            $this->getParamsRequest()[2] !== '' &&
            $this->getParamsRequest()[2] > 0
        ) {
            $status = $this->isGetElement($this->getParamsRequest()[2]);
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
        $resultOfRequest = $this->isGetAllCompleted();

        if ($resultOfRequest['status'] === false) {
            $this->logger->error(
                "Bad request. Message: {errorInfo}.",
                ['errorInfo' => $resultOfRequest['errorInfo']]
            );
            
            http_response_code(400);
            $this->response = json_encode([
                "status" => "false",
                "message" => $resultOfRequest['errorInfo']
            ]);
        } elseif ($resultOfRequest['rowCount'] === 0) {
            http_response_code(404);
            $this->response = json_encode([
                "status" => false,
                "message" => $resultOfRequest['errorInfo']
            ]);
            $resultOfRequest['status'] = false;
        } else {
            $this->logger->info('The request was completed successfully.');
            // Фиксация полученных данных
            http_response_code(200);
            $this->response = json_encode($resultOfRequest['fetchAll'], JSON_UNESCAPED_UNICODE);
        }

        return $resultOfRequest['status'];
    }

    /**
     * Запрос для получения всех элементов
     *
     * @return array
     */
    abstract protected function isGetAllCompleted(): array;

    /**
     * @param int $id
     *
     * @return bool
     */
    public function isGetElement(int $id): bool
    {
        // Результат запроса к таблице
        $resultOfRequest = $this->isGetElementCompleted($id);
        
        if ($resultOfRequest['status'] === false) {
            $this->logger->info(
                "Not found. Message: {errorInfo}.",
                ['errorInfo' => $resultOfRequest['errorInfo']]
            );
            
            http_response_code(404);
            $this->response = json_encode([
                "status" => false,
                "message" => $resultOfRequest['errorInfo']
            ]);
        } elseif ($resultOfRequest['rowCount'] === 0) {
            http_response_code(404);
            $this->response = json_encode([
                "status" => false,
                "message" => $resultOfRequest['errorInfo']
            ]);
        } else {
            $this->logger->info('The request was completed successfully.');
            // Фиксация полученных данных
            http_response_code(200);
            $this->response = json_encode($resultOfRequest['fetch'], JSON_UNESCAPED_UNICODE);
        }

        return $resultOfRequest['rowCount'];
    }

    /**
     * Запрос для получения элемента
     *
     * @param int $id
     *
     * @return array
     */
    abstract public function isGetElementCompleted(int $id): array;
    
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
            $resultOfRequest = $this->isCreateElementCompleted($_POST);
            
            if ($resultOfRequest['status'] === false) {
                $this->logger->error(
                    'Bad request. Message: {errorInfo}.',
                    ['errorInfo' => $resultOfRequest['errorInfo']]
                );
                
                http_response_code(400);
                $this->response = json_encode([
                    "status" => false,
                    "message" => $resultOfRequest['errorInfo']
                ]);
            } else {
                http_response_code(201);
                // При создании автора наследуется user_id
                if ($resultOfRequest['lastInsertId'] === '0') {
                    $this->response = json_encode([
                        'status' => true,
                        'id' => 'Inherits.'
                    ]);
                } else {
                    $this->response = json_encode([
                        'status' => true,
                        'id' => $resultOfRequest['lastInsertId']
                    ]);
                }
            }
            $status = $resultOfRequest['status'];
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
     * @return array
     */
    abstract public function isCreateElementCompleted(array $postParams): array;

    /**
     * Обработка PUT-запроса
     *
     * @param array $putParams
     * @param int $id
     *
     * @return void
     */
    public function updateElement(array $putParams, int $id): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: PUT");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: *");

        if ($this->isExistsParamsInArray($putParams) === true) {
            // Запрос к таблице
            $resultOfRequest = $this->isUpdateElementCompleted($putParams, $id);

            if ($resultOfRequest['status'] === false) {
                $this->logger->error(
                    'Bad request. Message from PDO: {errorInfo}.',
                    ['errorInfo' => $resultOfRequest['errorInfo']]
                );
                
                http_response_code(404);
                $this->response = json_encode([
                    "status" => false,
                    "message" => $resultOfRequest['errorInfo']
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
     * @param array $putParams - параметры запроса
     * @param int $id
     *
     * @return array
     */
    abstract public function isUpdateElementCompleted(array $putParams, int $id): array;

    /**
     * Обработка DELETE-запроса
     *
     * @param int $basePosId
     *
     * @return void
     */
    public function deleteElement(int $basePosId = 2): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: DELETE");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: *");

        // Получить идентификатор
        $id = $this->getParamsRequest()[$basePosId];

        if ($id > 0) {
            // Запрос к таблице
            $resultOfRequest = $this->isDeleteElementCompleted($id);

            if ($resultOfRequest['status'] === false) {
                $this->logger->error(
                    'Bad request. Message from PDO: {errorInfo}.',
                    ['errorInfo' => $resultOfRequest['errorInfo']]
                );
                
                http_response_code(400);
                $this->response = json_encode([
                    'status' => false,
                    "message" => $resultOfRequest['errorInfo']
                ]);
            } elseif ($resultOfRequest['rowCount'] === 0) {
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
     * @return array
     */
    abstract public function isDeleteElementCompleted(int $id): array;

    /**
     * Идентификация администратора
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        $users = new User($this->pdo);

        if (
            isset($_SERVER['PHP_AUTH_USER']) === true &&
            $_SERVER['PHP_AUTH_USER'] !== ''
        ) {
            $receivedUser = $users->isGetElementCompleted($_SERVER['PHP_AUTH_USER']);
            
            if (
                $receivedUser['status'] === true &&
                $receivedUser['fetch'] !== null &&
                $receivedUser['rowCount'] !== 0 &&
                $receivedUser['fetch']['is_admin'] === '1'
            ) {
                $this->logger->debug('Access is allowed.');
                return true;
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

        if (
            isset($_SERVER['PHP_AUTH_USER']) === true &&
            $_SERVER['PHP_AUTH_USER'] !== ''
        ) {
            $receivedAuthor = $authors->isGetElementCompleted($_SERVER['PHP_AUTH_USER']);

            if (
                $receivedAuthor['status'] === true &&
                $receivedAuthor['fetch'] !== null &&
                $receivedAuthor['rowCount'] !== 0 &&
                $receivedAuthor['fetch']['user_id'] === $_SERVER['PHP_AUTH_USER']
            ) {
                $this->logger->debug('Access is allowed.');
                return true;
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
     * Получить параметры запроса
     *
     * @return array
     */
    public function getParamsRequest(): array
    {
        return explode('/', $_SERVER['REQUEST_URI']);
    }

    /**
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * Валидация пагинации
     *
     * @param string $paginationArgs
     *
     * @return bool
     */
    public function isValidPagination(string $paginationArgs)
    {
        $pattern = "/^\[[0-9]+,?[0-9]+?\]/";
        $numberOfOccurrences = preg_match_all($pattern, $paginationArgs);

        if ($numberOfOccurrences === 1) {
            return true;
        } else {
            return false;
        }
    }
}
