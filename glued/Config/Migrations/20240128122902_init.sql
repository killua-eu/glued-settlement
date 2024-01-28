-- migrate:up


CREATE TABLE `t_settlement_accounts` (
  `uuid` binary(16) NOT NULL DEFAULT (uuid_to_bin(uuid(),true)) COMMENT 'Account UUID',
  `data` json NOT NULL,
  `ext_schema` varchar(255) NOT NULL,
  `ext_data` json NOT NULL,
  `ext_fid` varchar(255) NOT NULL,
  `dt_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dt_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uuid`),
  UNIQUE KEY `ext_fid` (`ext_fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `t_settlement_transactions` (
  `account` binary(16) NOT NULL COMMENT 'Account UUID',
  `uuid` binary(16) NOT NULL DEFAULT (uuid_to_bin(uuid(),true)) COMMENT 'Transaction UUID',
  `data` json NOT NULL,
  `ext_schema` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `ext_data` json NOT NULL,
  `ext_fid` varchar(255) NOT NULL,
  `dt_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dt_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `at` datetime GENERATED ALWAYS AS ((case when ((json_unquote(json_extract(`data`,_utf8mb4'$.at')) is null) or (json_unquote(json_extract(`data`,_utf8mb4'$.at')) = _utf8mb4'null')) then NULL else cast(json_unquote(json_extract(`data`,_utf8mb4'$.at')) as datetime) end)) VIRTUAL,
  `volume` decimal(14,6) GENERATED ALWAYS AS (cast(json_unquote(json_extract(`data`,_utf8mb4'$.volume')) as decimal(14,6))) VIRTUAL,
  `reference` varchar(255) GENERATED ALWAYS AS (json_unquote(json_extract(`data`,_utf8mb4'$.reference'))) VIRTUAL,
  PRIMARY KEY (`uuid`),
  UNIQUE KEY `ext_fid` (`ext_fid`),
  KEY `idx_at` (`at`),
  KEY `idx_volume` (`volume`),
  KEY `idx_reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- migrate:down

DROP TABLE IF EXISTS `t_settlement_accounts`;
DROP TABLE IF EXISTS `t_settlement_transactions`;
