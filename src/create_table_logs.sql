--
-- Database `logs_db`
-- Table `logs`
--

CREATE TABLE `logs`
(
  `id`                     VARCHAR(255) NOT NULL,
  `timestamp`              VARCHAR(32)  NOT NULL,
  `level`                  VARCHAR(32)  NOT NULL,
  `priority`               INT(11) NOT NULL,
  `lifecycle_token`        VARCHAR(32) NULL DEFAULT NULL,
  `parent_lifecycle_token` VARCHAR(32) NULL DEFAULT NULL,
  `message`                TEXT         NOT NULL,
  `context`                TEXT         NOT NULL,
  PRIMARY KEY (`id`),
  INDEX                    `parent_lifecycle_token` (`parent_lifecycle_token`),
  INDEX                    `lifecycle_token` (`lifecycle_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;
