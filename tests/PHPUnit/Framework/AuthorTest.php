<?php

namespace PHPUnit\Framework;

use PDO;
use Models\Database as Database;
use Api\Author as Author;
use PHPUnit\Framework\TestCase as TestCase;

class AuthorTest extends TestCase
{
    protected Author $authors;
    protected PDO $pdo;

    public function setUp(): void
    {
        $_SERVER['LOG_LEVEL'] = 0;
        $database = new Database();
        $this->pdo = $database->getConnect();

        $this->authors = new Author($this->pdo);
    }

    public function testIsGetAllCompleted(): void
    {
        $this->assertTrue($this->authors->isGetAllCompleted()['status']);
    }

    public function testIsGetAllCompletedWithValidPagination(): void
    {
        $_GET['pagination'] = '[1,4]';
        $this->assertTrue($this->authors->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetAllCompletedWithNoValidPagination(): void
    {
        $_GET['pagination'] = '[1,4a]';
        $this->assertFalse($this->authors->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetElementCompleted(): void
    {
        $this->assertTrue($this->authors->isGetElementCompleted(1)['status']);
    }
    
    public function testIsCreateElementCompleted()
    {
        $postParams['user_id'] = 5;
        $postParams['description'] = 'safsdfsasfaf';
        
        $this->assertTrue($this->authors->isCreateElementCompleted($postParams)['status']);
    }

    public function testIsUpdateElementCompleted()
    {
        $putParams['description'] = 'testTag121212';

        $id = 5;
        
        $this->assertTrue($this->authors->isUpdateElementCompleted($putParams, $id)['status']);
    }

    public function testIsDeleteElementCompleted()
    {
        $id = 5;

        $this->assertTrue($this->authors->isDeleteElementCompleted($id)['status']);
    }
}
