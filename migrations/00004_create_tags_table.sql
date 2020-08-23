create table if not exists `tags` (
    `tag_id` int(10) unsigned not null auto_increment,
    `name` varchar(255) not null,
    primary key (tag_id)
)
engine = innodb
auto_increment = 1
character set utf8mb4
collate utf8mb4_unicode_ci;
