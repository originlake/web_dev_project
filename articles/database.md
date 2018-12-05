# Database Design
1. create new database and new user

        create database module3;
        create user 'module3'@'localhost' identified by 'cse330';
        grant select,insert,update,delete on module3.* to module3@'localhost';

2. create commands  
need to add unsigned

        CREATE TABLE `users` (
            `uid` mediumint unsigned NOT NULL,
            `username` varchar(20) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `reg_date` DATETIME NOT NULL,
            `email` varchar(50) NOT NULL UNIQUE,
            PRIMARY KEY (`uid`)
        )engine = InnoDB default character set = utf8 collate = utf8_general_ci;

        CREATE TABLE `stories` (
            `story_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
            `uid` mediumint unsigned NOT NULL,
            `title` tinytext NOT NULL,
            `content` TEXT NOT NULL,
            `up_date` DATETIME NOT NULL,
            `link` varchar(50) UNIQUE,
            PRIMARY KEY (`story_id`)
        )engine = InnoDB default character set = utf8 collate = utf8_general_ci;

        CREATE TABLE `comments` (
            `comment_id` int unsigned NOT NULL AUTO_INCREMENT,
            `story_id` mediumint unsigned NOT NULL,
            `uid` mediumint unsigned NOT NULL,
            `reply_id` int unsigned NOT NULL,
            `content` text NOT NULL,
            `com_date` DATETIME NOT NULL,
            PRIMARY KEY (`comment_id`)
        )engine = InnoDB default character set = utf8 collate = utf8_general_ci;
        
        create table validation(
            val_id mediumint unsigned NOT NULL AUTO_INCREMENT,
            uid mediumint unsigned NOT NULL unique,
            token varchar(32),
            PRIMARY KEY (val_id)
        )engine = InnoDB default character set = utf8 collate = utf8_general_ci;

        ALTER TABLE `stories` ADD CONSTRAINT `stories_fk0` FOREIGN KEY (`uid`) REFERENCES `users`(`uid`);

        ALTER TABLE `comments` ADD CONSTRAINT `comments_fk0` FOREIGN KEY (`story_id`) REFERENCES `stories`(`story_id`);

        ALTER TABLE `comments` ADD CONSTRAINT `comments_fk1` FOREIGN KEY (`uid`) REFERENCES `users`(`uid`);

        ALTER TABLE `comments` ADD CONSTRAINT `comments_fk2` FOREIGN KEY (`reply_id`) REFERENCES `users`(`uid`);
        
        ALTER TABLE validation ADD CONSTRAINT `validation_fk0` FOREIGN KEY (`uid`) REFERENCES `users`(`uid`);

