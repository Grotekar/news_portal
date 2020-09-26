create table if not exists `images` (
    `image_id` int(10) unsigned not null auto_increment,
    `name` varchar(255) not null,
    primary key (image_id)
)
engine = innodb
auto_increment = 1
default charset utf8mb4
collate utf8mb4_unicode_ci;
