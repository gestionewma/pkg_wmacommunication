CREATE TABLE IF NOT EXISTS `#__wmacommunication_forms` (
    `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(255) NOT NULL DEFAULT '',
    `alias`           VARCHAR(400) NOT NULL DEFAULT '',
    `description`     TEXT,
    `fields`          LONGTEXT COMMENT 'JSON array dei campi del form',
    `settings`  LONGTEXT COMMENT 'JSON opzioni di invio',   
    `recipient_email` VARCHAR(255) NOT NULL DEFAULT '',
    `email_subject`   VARCHAR(255) NOT NULL DEFAULT '',
    `success_message` TEXT,
    `state`           TINYINT(1) NOT NULL DEFAULT 1,
    `created`         DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `created_by`      INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `modified`        DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by`     INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `checked_out`     INT(11) UNSIGNED DEFAULT NULL,
    `checked_out_time` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__wmacommunication_uploads` (
    `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `token`         VARCHAR(64) NOT NULL,
    `form_id`       INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `field_uid`     VARCHAR(64) NOT NULL DEFAULT '',
    `original_name` VARCHAR(255) NOT NULL DEFAULT '',
    `stored_name`   VARCHAR(255) NOT NULL DEFAULT '',
    `subdir`        VARCHAR(255) NOT NULL DEFAULT '',
    `mime`          VARCHAR(150) NOT NULL DEFAULT '',
    `size`          INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `created`       DATETIME NOT NULL,
    `created_by`    INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `downloads`     INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `last_download` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_token` (`token`),
    KEY `idx_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
