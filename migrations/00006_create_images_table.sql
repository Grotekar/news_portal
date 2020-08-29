create table if not exists `images` (
    `image_id` varchar(255) not null,
    `news_id` int(10) unsigned not null,
    primary key (image_id),
    foreign key (news_id) references news (news_id)
)
engine = innodb
auto_increment = 1
default charset utf8mb4
collate utf8mb4_unicode_ci;
