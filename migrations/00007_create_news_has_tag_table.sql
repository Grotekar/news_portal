create table if not exists `news_has_tag` (
    `news_id` int(10) unsigned not null,
    `tag_id` int(10) unsigned not null,
    foreign key (news_id) references news (news_id),
    foreign key (tag_id) references tags (tag_id)
)
engine = innodb
auto_increment = 1
default charset utf8mb4
collate utf8mb4_unicode_ci;
