USE ais;

CREATE TABLE IF NOT EXISTS `announcement_dismissals` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `announcement_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `dismissed_at` DATETIME NOT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcement_id` (`announcement_id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `announcement_user` (`announcement_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
