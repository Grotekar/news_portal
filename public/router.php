<?php

require __DIR__ . '/../vendor/autoload.php';

$uri = explode('?', $_SERVER['REQUEST_URI'], 2)[0];

switch ($uri) {
    case '/':
        include_once 'index.php';
        break;
    case '/authors':
        include_once 'api/authors/index.php';
        break;
    case '/drafts':
        include_once 'api/drafts/index.php';
        break;
    case '/news':
        include_once 'api/news/index.php';
        //echo 'News';
        break;
    case '/tags':
        include_once 'api/tags/index.php';
        break;
    case '/users':
        include_once 'api/users/index.php';
        break;
    default:
        return false;
        break;
}