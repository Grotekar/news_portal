create table if not exists `news` (
    `news_id` int(10) unsigned not null auto_increment,
    `article` varchar(255) not null,
    `created` timestamp not null default now(),
    `author_id` int(10) unsigned not null,
    `category_id` int(10) unsigned not null,
    `content` text not null,
    `main_image` text,
    primary key (news_id),
    foreign key (author_id) references authors (user_id),
    foreign key (category_id) references categories (category_id)
)
engine = innodb
auto_increment = 1
default charset utf8mb4
collate utf8mb4_unicode_ci;
