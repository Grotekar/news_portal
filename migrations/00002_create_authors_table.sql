create table if not exists `authors` (
    `user_id` int(10) unsigned not null,
    `description` text,
    primary key (user_id),
    foreign key (user_id) references users (user_id)
)
engine = innodb
auto_increment = 1
default charset utf8mb4
collate utf8mb4_unicode_ci;
