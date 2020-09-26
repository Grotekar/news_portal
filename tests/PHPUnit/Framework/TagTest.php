<?php

namespace PHPUnit\Framework;

use PDO;
use Models\Database as Database;
use Api\Tag as Tag;
use PHPUnit\Framework\TestCase as TestCase;

class TagTest extends TestCase
{
    protected Tag $tags;
    protected PDO $pdo;

    public function setUp(): void
    {
        $_SERVER['LOG_LEVEL'] = 0;
        $database = new Database();
        $this->pdo = $database->getConnect();

        $this->tags = new Tag($this->pdo);
    }

    public function testIsGetAllCompleted(): void
    {
        $this->assertTrue($this->tags->isGetAllCompleted()['status']);
    }

    public function testIsGetAllCompletedWithValidPagination(): void
    {
        $_GET['pagination'] = '[1,4]';
        $this->assertTrue($this->tags->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetAllCompletedWithNoValidPagination(): void
    {
        $_GET['pagination'] = '[1,4a]';
        $this->assertFalse($this->tags->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetElementCompleted(): void
    {
        $this->assertTrue($this->tags->isGetElementCompleted(1)['status']);
    }
    
    public function testIsCreateElementCompleted()
    {
        $postParams['name'] = 'testTag';
        
        $this->assertTrue($this->tags->isCreateElementCompleted($postParams)['status']);
    }

    public function testIsUpdateElementCompleted()
    {
        $putParams['name'] = 'testTag121212';

        $id = $this->pdo->lastInsertId();
        
        $this->assertTrue($this->tags->isUpdateElementCompleted($putParams, $id)['status']);
    }

    public function testIsDeleteElementCompleted()
    {
        $id = $this->pdo->lastInsertId();

        $this->assertTrue($this->tags->isDeleteElementCompleted($id)['status']);
    }
}
