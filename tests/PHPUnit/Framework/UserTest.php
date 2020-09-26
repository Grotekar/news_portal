<?php

namespace PHPUnit\Framework;
namespace Models;
namespace Api;

use PDO;
use Models\Database as Database;
use PHPUnit\Framework\TestCase as TestCase;

class UserTest extends TestCase
{
    protected User $users;
    protected PDO $pdo;

    public function setUp(): void
    {
        $database = new Database();
        $this->pdo = $database->getConnect();

        $this->users = new User($this->pdo);
    }

    public function testIsGetAllCompleted(): void
    {
        $this->assertTrue($this->users->isGetAllCompleted()['status']);
    }

    public function testIsGetAllCompletedWithValidPagination(): void
    {
        $_GET['pagination'] = '[1,4]';
        $this->assertTrue($this->users->isGetAllCompleted()['status']);
    }

    public function testIsGetAllCompletedWithNoValidPagination(): void
    {
        $_GET['pagination'] = '[1,4a]';
        $this->assertFalse($this->users->isGetAllCompleted()['status']);
    }

    public function testIsGetElementCompleted(): void
    {
        $this->assertTrue($this->users->isGetElementCompleted(1)['status']);
    }
    
    public function testIsCreateElementCompleted()
    {
        $postParams['firstname'] = 'testUser';
        $postParams['lastname'] = 'testUser';
        $postParams['avatar'] = '12313';
        
        $this->assertTrue($this->users->isCreateElementCompleted($postParams)['status']);
    }

    public function testIsUpdateElementCompleted()
    {
        $putParams['firstname'] = 'testUser121212';
        $putParams['lastname'] = 'testUse121212r';
        $putParams['avatar'] = '123132222222';

        $id = $this->pdo->lastInsertId();
        
        $this->assertTrue($this->users->isUpdateElementCompleted($putParams, $id)['status']);
    }

    public function testIsDeleteElementCompleted()
    {
        $id = $this->pdo->lastInsertId();

        $this->assertTrue($this->users->isDeleteElementCompleted($id)['status']);
    }
}