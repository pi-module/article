CREATE TABLE `{article}` (
  `id`              int(10) UNSIGNED                NOT NULL AUTO_INCREMENT,
  `subject`         varchar(255)                    NOT NULL DEFAULT '',
  `subtitle`        varchar(255)                    NOT NULL DEFAULT '',
  `summary`         varchar(255)                    NOT NULL DEFAULT '',
  `content`         longtext                        NOT NULL DEFAULT '',
  `markup`          ENUM('html','text','markdown')  NOT NULL DEFAULT 'html',
  `image`           varchar(255)                    NOT NULL DEFAULT '',
  `user`            int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `author`          int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `source`          varchar(255)                    NOT NULL DEFAULT '',
  `seo_title`       varchar(255)                    NOT NULL DEFAULT '',
  `seo_keywords`    varchar(255)                    NOT NULL DEFAULT '',
  `seo_description` varchar(255)                    NOT NULL DEFAULT '',
  `slug`            varchar(255)                    DEFAULT NULL,
  `related_type`    tinyint(3) UNSIGNED             NOT NULL DEFAULT 0,
  `pages`           tinyint(3) UNSIGNED             NOT NULL DEFAULT 0,
  `category`        int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `topic`           int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `status`          tinyint(3) UNSIGNED             NOT NULL DEFAULT 0,
  `active`          tinyint(1) UNSIGNED             NOT NULL DEFAULT 0,
  `recommended`     tinyint(1) UNSIGNED             NOT NULL DEFAULT 0,
  `time_create`     int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `time_publish`    int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `time_update`     int(10) UNSIGNED                NOT NULL DEFAULT 0,

  PRIMARY KEY                     (`id`),
  KEY `user`                      (`user`),
  KEY `author`                    (`author`),
  KEY `publish_category`          (`time_publish`, `category`),
  KEY `create_category`           (`time_create`, `category`),
  KEY `subject`                   (`subject`),
  UNIQUE KEY `slug`               (`slug`)
);

CREATE TABLE `{draft}` (
  `id`              int(10) UNSIGNED                NOT NULL AUTO_INCREMENT,
  `article`         int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `subject`         varchar(255)                    NOT NULL DEFAULT '',
  `subtitle`        varchar(255)                    NOT NULL DEFAULT '',
  `summary`         varchar(255)                    NOT NULL DEFAULT '',
  `content`         longtext                        NOT NULL DEFAULT '',
  `markup`          ENUM('html','text','markdown')  NOT NULL DEFAULT 'html',
  `image`           varchar(255)                    NOT NULL DEFAULT '',
  `user`            int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `author`          int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `source`          varchar(255)                    NOT NULL DEFAULT '',
  `pages`           tinyint(3) UNSIGNED             NOT NULL DEFAULT 0,
  `category`        int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `tag`             varchar(255)                    NOT NULL DEFAULT '',
  `related_type`    tinyint(3) UNSIGNED             NOT NULL DEFAULT 0,
  `seo_title`       varchar(255)                    NOT NULL DEFAULT '',
  `seo_keywords`    varchar(255)                    NOT NULL DEFAULT '',
  `seo_description` varchar(255)                    NOT NULL DEFAULT '',
  `slug`            varchar(255)                    DEFAULT NULL,
  `time_publish`    int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `time_update`     int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `status`          tinyint(3) UNSIGNED             NOT NULL DEFAULT 0,
  `recommended`     tinyint(1) UNSIGNED             NOT NULL DEFAULT 0,
  `topic`           int(10) UNSIGNED                NOT NULL DEFAULT 0,
  `time_save`       int(10) UNSIGNED                NOT NULL DEFAULT 0,

  PRIMARY KEY           (`id`),
  KEY `article`         (`article`),
  KEY `usrer`           (`user`),
  KEY `time_save`       (`time_save`)
);

CREATE TABLE `{related}` (
  `id`              int(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `article`         int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `related`         int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `order`           tinyint(3) UNSIGNED   NOT NULL DEFAULT 0,

  PRIMARY KEY          (`id`),
  KEY `article`        (`article`)
);

CREATE TABLE `{visit}` (
  `id`              int(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `article`         int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `date`            int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `count`           int(10) UNSIGNED      NOT NULL DEFAULT 0,

  PRIMARY KEY                 (`id`),
  UNIQUE KEY `article_date`   (`article`,`date`),
  KEY        `date`           (`date`)
);

CREATE TABLE `{category}` (
  `id`              int(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `left`            int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `right`           int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `depth`           int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `name`            varchar(64)           NOT NULL DEFAULT '',
  `slug`            varchar(64)           DEFAULT NULL,
  `title`           varchar(64)           NOT NULL DEFAULT '',
  `description`     varchar(255)          NOT NULL DEFAULT '',
  `image`           varchar(255)          NOT NULL DEFAULT '',

  PRIMARY KEY           (`id`),
  UNIQUE KEY `name`     (`name`),
  UNIQUE KEY `slug`     (`slug`)
);

CREATE TABLE `{author}` (
  `id`              int(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `name`            varchar(64)           NOT NULL DEFAULT '',
  `photo`           varchar(255)          NOT NULL DEFAULT '',
  `description`     text                  NOT NULL DEFAULT '',

  PRIMARY KEY           (`id`),
  KEY `name`            (`name`)
);

CREATE TABLE `{statistics}` (
  `id`              int(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `article`         int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `visits`          int(10) UNSIGNED      NOT NULL DEFAULT 0,
  `vote`            varchar(255)          NOT NULL DEFAULT '',

  PRIMARY KEY           (`id`),
  UNIQUE KEY `article`  (`article`),
  KEY `article_visits`  (`article`, `visits`),
  KEY `article_vote`    (`article`, `vote`)
);
