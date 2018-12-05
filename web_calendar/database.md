1. create new database and new user

        create database module5;
        create user 'module5'@'localhost' identified by 'cse330';
        grant select,insert,update,delete on module5.* to module5@'localhost';

2. create commands  
need to add unsigned

        CREATE TABLE `users` (
            `uid` mediumint unsigned NOT NULL,
            `username` varchar(20) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            PRIMARY KEY (`uid`)
        )engine = InnoDB default character set = utf8 collate = utf8_general_ci;
        
        CREATE TABLE `events` (
            `eventid` int unsigned NOT NULL,
            `uid` mediumint unsigned NOT NULL,
            `title` tinytext NOT NULL,
            `date` date NOT NULL,
            `time` time DEFAULT "00:00:00",
            `tag` enum('normal','work','study') NOT NULL DEFAULT 'normal',
            PRIMARY KEY (`eventid`)
        )engine = InnoDB default character set = utf8 collate = utf8_general_ci;
        
        ALTER TABLE `events` ADD CONSTRAINT `events_fk0` FOREIGN KEY (`uid`) REFERENCES `users`(`uid`);

