create table if not exists `news_has_image` (
    `news_id` int(10) unsigned not null,
    `image_id` int(10) unsigned not null,
    primary key (news_id, image_id),
    foreign key (news_id) references news (news_id),
    foreign key (image_id) references images (image_id)
)
engine = innodb
auto_increment = 1
default charset utf8mb4
collate utf8mb4_unicode_ci;
