create table if not exists `users` (
    `user_id` int(10) unsigned not null auto_increment,
    `firstname` varchar(255) not null,
    `lastname` varchar(255) not null,
    `avatar` varchar(255) not null,
    `created` timestamp not null default now(),
    `is_admin` boolean not null,
    primary key (user_id)
)
engine = innodb
auto_increment = 1
character set utf8mb4
collate utf8mb4_unicode_ci;
