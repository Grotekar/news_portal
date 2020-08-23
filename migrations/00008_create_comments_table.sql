create table if not exists `comments` (
    `comment_id` int(10) unsigned not null auto_increment,
    `news_id` int(10) unsigned not null,
    `user_id` int(10) unsigned not null,
    `created` timestamp not null default now(),
    `text` text not null,
    primary key (comment_id),
    foreign key (news_id) references news (news_id),   
    foreign key (user_id) references users (user_id)
)
engine = innodb
auto_increment = 1
character set utf8mb4
collate utf8mb4_unicode_ci;
