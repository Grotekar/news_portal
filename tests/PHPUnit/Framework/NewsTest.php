<?php

namespace PHPUnit\Framework;
namespace Models;
namespace Api;

use PDO;
use Models\Database as Database;
use PHPUnit\Framework\TestCase as TestCase;

class NewsTest extends TestCase
{
    protected $news;

    public function setUp(): void
    {
        $database = new Database();
        $pdo = $database->getConnect();

        $this->news = new News($pdo);
    }

    public function testIsGetAllComplited(): void
    {
        $this->assertTrue($this->news->isGetAllComplited());
    }

    public function testIsGetElementComplited(): void
    {
        $this->assertTrue($this->news->isGetElementComplited(1));
    }
    
    public function testIsCreateElementCompleted()
    {
        $postParams['article'] = 'News!!!';
        $postParams['author_id'] = 1;
        $postParams['category_id'] = 1;
        $postParams['content'] = 'Content';
        $postParams['main_image'] = '1233142';
        
        $this->assertTrue($this->news->isCreateElementCompleted($postParams));
    }

    public function testIsUpdateElementCompleted()
    {
        $putParams['article'] = 'Change1';
        $putParams['author_id'] = 3;
        $putParams['category_id'] = 2;
        $putParams['content'] = 'Content1';
        $putParams['main_image'] = 12345;
        $id = 8;
        
        $this->assertTrue($this->news->isUpdateElementCompleted($id, $putParams));
    }

    public function testIsDeleteteElementCompleted()
    {
        $this->assertTrue($this->news->isDeleteteElementCompleted(8));
    }
}