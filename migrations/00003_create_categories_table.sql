create table if not exists `categories` (
    `category_id` int(10) unsigned not null auto_increment,
    `name` varchar(255) not null,
    `parent_category` int(10),
    primary key (category_id)
)
engine = innodb
auto_increment = 1
character set utf8mb4
collate utf8mb4_unicode_ci;
