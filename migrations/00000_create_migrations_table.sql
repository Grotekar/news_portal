create table if not exists `migrations` (
    `id` int(10) unsigned not null auto_increment,
    `name` varchar(255) not null,
    `created` timestamp not null default now(),
    primary key (id)
)
engine = innodb
auto_increment = 1
character set utf8mb4
collate utf8mb4_unicode_ci;
