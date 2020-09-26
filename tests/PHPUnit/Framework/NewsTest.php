<?php

namespace PHPUnit\Framework;

use PDO;
use Models\Database as Database;
use Api\News as News;
use PHPUnit\Framework\TestCase as TestCase;

class NewsTest extends TestCase
{
    protected News $news;
    protected PDO $pdo;

    public function setUp(): void
    {
        $database = new Database();
        $this->pdo = $database->getConnect();

        $this->news = new News($this->pdo);
    }

    public function testIsGetAllCompleted(): void
    {
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
    }

    public function testIsGetAllCompletedWithValidPagination(): void
    {
        $_GET['pagination'] = '[1,4]';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetAllCompletedWithNoValidPagination(): void
    {
        $_GET['pagination'] = '[1,4a]';
        $this->assertFalse($this->news->isGetAllCompleted()['status']);
        unset($_GET['pagination']);
    }

    public function testIsGetAllCompletedWithNoValidFilterCreated(): void
    {
        $_GET['created'] = '20300-09-02';
        $this->assertFalse($this->news->isGetAllCompleted()['status']);
        unset($_GET['created']);
    }

    public function testIsGetAllCompletedWithNoValidFilterCreatedBefore(): void
    {
        $_GET['created__before'] = '2030-09-020';
        $this->assertFalse($this->news->isGetAllCompleted()['status']);
        unset($_GET['created__before']);
    }

    public function testIsGetAllCompletedWithNoValidFilterCreatedAfter(): void
    {
        $_GET['created__after'] = '2030-090-02';
        $this->assertFalse($this->news->isGetAllCompleted()['status']);
        unset($_GET['created__after']);
    }

    public function testIsGetAllCompletedWithValidFilterFirstname(): void
    {
        $_GET['firstname'] = 'adfsf';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        unset($_GET['firstname']);
    }

    public function testIsGetAllCompletedWithNoValidFilterCategoryId(): void
    {
        $_GET['category_id'] = '1000';
        $this->assertEquals(0, $this->news->isGetAllCompleted()['rowCount']);
        unset($_GET['category_id']);
    }

    public function testIsGetAllCompletedWithValidFilterTagId(): void
    {
        $_GET['tag_id'] = '1';
        $this->assertNotEquals(0, $this->news->isGetAllCompleted()['rowCount']);
        unset($_GET['tag_id']);
    }

    public function testIsGetAllCompletedWithNoValidFilterTagId(): void
    {
        $_GET['tag_id'] = '1000';
        $this->assertEquals(0, $this->news->isGetAllCompleted()['rowCount']);
        unset($_GET['tag_id']);
    }

    public function testIsGetAllCompletedWithValidFilterTagIn(): void
    {
        $_GET['tag__in'] = '[1,2]';
        $this->assertNotEquals(0, $this->news->isGetAllCompleted()['rowCount']);
        unset($_GET['tag__in']);
    }

    public function testIsGetAllCompletedWithNoValidFilterTagIn(): void
    {
        $_GET['tag__in'] = '[600,1000]';
        $this->assertEquals(0, $this->news->isGetAllCompleted()['rowCount']);
        unset($_GET['tag__in']);
    }

    public function testIsGetAllCompletedWithValidFilterTagAll(): void
    {
        $_GET['tag__all'] = '[1,2]';
        $this->assertNotEquals(0, $this->news->isGetAllCompleted()['rowCount']);
        unset($_GET['tag__all']);
    }

    public function testIsGetAllCompletedWithNoValidFilterTagAll(): void
    {
        $_GET['tag__all'] = '[600,1000]';
        $this->assertEquals(0, $this->news->isGetAllCompleted()['rowCount']);
        unset($_GET['tag__all']);
    }


    public function testIsGetAllCompletedWithValidSort(): void
    {
        $_GET['sort'] = 'created_asc';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        $_GET['sort'] = 'created_desc';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        $_GET['sort'] = 'firstname';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        $_GET['sort'] = 'category';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        $_GET['sort'] = 'images_asc';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        $_GET['sort'] = 'images_desc';
        $this->assertTrue($this->news->isGetAllCompleted()['status']);
        unset($_GET['sort']);
    }

    public function testIsGetAllCompletedWithNoValidSort(): void
    {
        $_GET['sort'] = 'asdasd';
        $this->assertFalse($this->news->isGetAllCompleted()['status']);
        unset($_GET['sort']);
    }

    public function testIsGetElementCompleted(): void
    {
        $this->assertTrue($this->news->isGetElementCompleted(1)['status']);
    }

    public function testIsCreateElementCompleted()
    {
        $postParams['article'] = 'Новость';
        $postParams['category_id'] = '1';
        $postParams['content'] = 'asdfsdfgdfgdsgffdsgdfg';
        $postParams['main_image'] = 'sgdgldfkgl;dflg;k';

        $this->assertFalse($this->news->isCreateElementCompleted($postParams)['status']);
    }

    public function testIsDeleteElementCompleted()
    {
        $id = 1;

        $this->assertFalse($this->news->isDeleteElementCompleted($id)['status']);
    }
}
