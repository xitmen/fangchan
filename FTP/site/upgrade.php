<?php
if(!pdo_fieldexists('article', 'iscommend')) {
	pdo_query("ALTER TABLE ".tablename('article')." ADD `iscommend` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `title` ;");
	pdo_query("ALTER TABLE ".tablename('article')." ADD `ishot` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `iscommend` ;");
	pdo_query("ALTER TABLE ".tablename('article')." ADD `description` VARCHAR( 1000 ) NOT NULL DEFAULT '' AFTER `title`;");
}

if(!pdo_fieldexists('article', 'type')) {
	pdo_query("ALTER TABLE ".tablename('article')." ADD `type` VARCHAR( 10 ) NOT NULL DEFAULT '' ;");
}

if(!pdo_fieldexists('article_category', 'template')) {
	pdo_query("ALTER TABLE ".tablename('article_category')." ADD `template` VARCHAR(300) NOT NULL DEFAULT '' COMMENT '分类模板' AFTER `description`;");
}

if(!pdo_fieldexists('article', 'template')) {
	pdo_query("ALTER TABLE ".tablename('article')." ADD `template` VARCHAR(300) NOT NULL DEFAULT '' COMMENT '内容模板' AFTER `ccate`;");
}
