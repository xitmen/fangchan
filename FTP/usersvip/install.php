<?php
$sql=<<<EOD
CREATE TABLE `ims_ace_usersvip_card` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `u_id` int(11) unsigned NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `color` varchar(255) NOT NULL DEFAULT '',
  `background` varchar(255) NOT NULL DEFAULT '',
  `logo` varchar(255) NOT NULL DEFAULT '',
  `format` varchar(50) NOT NULL DEFAULT '',
  `fields` varchar(1000) NOT NULL DEFAULT '',
  `snpos` int(11) NOT NULL DEFAULT '0',
  `grade` varchar(255) NOT NULL DEFAULT '',
  `class` int(10) unsigned NOT NULL,
  `explain` varchar(255) NULL,
  `info` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `ims_ace_usersvip_card_privilege` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `u_id` int(11) unsigned NOT NULL,
  `card_id` int(11) NOT NULL,
  `type` int(3) NOT NULL DEFAULT '0',
  `value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

create table `ims_ace_uservip_fillrule` (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`u_id` int(11) not null comment '商户id',
		`enable` tinyint(2) not null default 0 comment '是否启用规则',
		`rules` varchar(800) default '' comment '规则json字符串',
		`type` tinyint(2) not null default 0 comment '规则类型 默认0 为充值规则  --扩展预留',
		primary key (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `ims_ace_recharge_list` (
		`rid` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`rmid` varchar(50) NOT NULL comment '订单 ',
		`content` varchar(100) comment '基本信息',
		`fee` decimal(10,2) comment '充值金额',
		`exfee` decimal(10,2) comment '充值送的金额',
		`createtime` int(11) comment '下单时间',
		`weid` int(11) comment 'weid',
		`mid` int(11) comment '用户member编号',
		`openid` varchar(100) comment '微信openid',
		`status` tinyint(2) default 0 comment '状态 0 下单 1完成 其他保留',
		PRIMARY KEY (`rid`) 
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
EOD;
//pdo_run($sql);
?>