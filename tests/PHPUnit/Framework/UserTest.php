<?php

namespace PHPUnit\Framework;
namespace Models;
namespace Api;

use PDO;
use Models\Database as Database;
use PHPUnit\Framework\TestCase as TestCase;

class UserTest extends TestCase
{
    protected $users;

    public function setUp(): void
    {
        $database = new Database();
        $pdo = $database->getConnect();

        $this->users = new User($pdo);
    }

    public function testIsGetAllComplited(): void
    {
        $this->assertTrue($this->users->isGetAllComplited());
    }

    public function testIsGetElementComplited(): void
    {
        $this->assertTrue($this->users->isGetElementComplited(1));
    }
    
    public function testIsCreateElementCompleted()
    {
        $postParams['firstname'] = 'testUser';
        $postParams['lastname'] = 'testUser';
        $postParams['avatar'] = '12313';
        $postParams['is_admin'] = 0;
        
        $this->assertTrue($this->users->isCreateElementCompleted($postParams));
    }

    public function testIsUpdateElementCompleted()
    {
        $putParams['firstname'] = 'testUser';
        $putParams['lastname'] = 'testUser';
        $putParams['avatar'] = '12313';
        $putParams['is_admin'] = 0;
        $id = 11;
        
        $this->assertTrue($this->users->isUpdateElementCompleted($id, $putParams));
    }

    public function testIsDeleteteElementCompleted()
    {
        $this->assertTrue($this->users->isDeleteteElementCompleted(11));
    }
}