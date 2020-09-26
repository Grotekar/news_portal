<?php

namespace PHPUnit\Framework;

use PDO;
use Models\Database as Database;
use Api\Category as Category;
use PHPUnit\Framework\TestCase as TestCase;

class CategoryTest extends TestCase
{
    protected Category $categories;
    protected PDO $pdo;

    public function setUp(): void
    {
        $_SERVER['LOG_LEVEL'] = 0;
        $database = new Database();
        $this->pdo = $database->getConnect();

        $this->categories = new Category($this->pdo);
    }

    public function testIsGetAllCompleted(): void
    {
        $this->assertTrue($this->categories->isGetAllCompleted()['status']);
    }

    public function testIsGetAllCompletedWithValidPagination(): void
    {
        $_GET['pagination'] = '[1,4]';
        $this->assertTrue($this->categories->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetAllCompletedWithNoValidPagination(): void
    {
        $_GET['pagination'] = '[1,4a]';
        $this->assertFalse($this->categories->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetElementCompleted(): void
    {
        $this->assertTrue($this->categories->isGetElementCompleted(1)['status']);
    }
    
    public function testIsCreateElementCompleted()
    {
        $postParams['name'] = 'testCategory';
        
        $this->assertTrue($this->categories->isCreateElementCompleted($postParams)['status']);
    }

    public function testIsUpdateElementCompleted()
    {
        $putParams['name'] = 'testCategory121212';
        $putParams['parent_category_id'] = 1;

        $id = $this->pdo->lastInsertId();
        
        $this->assertTrue($this->categories->isUpdateElementCompleted($putParams, $id)['status']);
    }

    public function testIsUpdateElementNoCompleted()
    {
        $putParams['name'] = 'testCategory121212';
        $putParams['parent_category_id'] = 1000;

        $id = $this->pdo->lastInsertId();

        $this->assertFalse($this->categories->isUpdateElementCompleted($putParams, $id)['status']);
    }

    public function testIsDeleteElementCompleted()
    {
        $id = $this->pdo->lastInsertId();

        $this->assertTrue($this->categories->isDeleteElementCompleted($id)['status']);
    }
}
