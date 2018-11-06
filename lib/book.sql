/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50722
Source Host           : 127.0.0.1:3306
Source Database       : tt

Target Server Type    : MYSQL
Target Server Version : 50722
File Encoding         : 65001

Date: 2018-09-04 22:04:44
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for book_main
-- ----------------------------
DROP TABLE IF EXISTS `book_main`;
CREATE TABLE `book_main` (
  `book_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '图书ID',
  `website_id` int(11) unsigned NOT NULL COMMENT '合作方编号',
  `book_name` varchar(200) NOT NULL COMMENT '图书名',
  `author_name` varchar(50) NOT NULL DEFAULT '' COMMENT '作者名称',
  `book_status` tinyint(1) unsigned NOT NULL COMMENT '书籍完结状态：0断更 1连载 2完结',
  `ftype_id` int(11) unsigned NOT NULL COMMENT '大分类IDD',
  `stype_id` int(11) unsigned NOT NULL COMMENT '小分类ID',
  `attribution` tinyint(1) unsigned NOT NULL DEFAULT '3' COMMENT '男女生： 1男生，2女生，3畅销合作',
  `image_url` varchar(255) NOT NULL COMMENT '封面地址',
  `is_del` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除：0否，1是',
  `is_hot` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否为热门书：0否，1是',
  `uptime` int(10) unsigned NOT NULL COMMENT '首次上传时间',
  `finish_time` int(11) NOT NULL DEFAULT '0' COMMENT '图书完本时间',
  `menu_count` int(11) DEFAULT '0' COMMENT '总章节数',
  `max_menu_name` varchar(255) DEFAULT NULL COMMENT '最新章节',
  `max_menu_id` int(11) DEFAULT NULL COMMENT '最新章节ID',
  `max_menu_uptime` int(10) DEFAULT '0' COMMENT '最新章节更新时间',
  `tag` varchar(255) DEFAULT NULL COMMENT '标签',
  `instruct` text COMMENT '简介',
  PRIMARY KEY (`book_id`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8 COMMENT='图书基本信息';

-- ----------------------------
-- Table structure for book_main_menu
-- ----------------------------
DROP TABLE IF EXISTS `book_main_menu`;
CREATE TABLE `book_main_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `book_id` int(11) NOT NULL DEFAULT '0' COMMENT '图书id',
  `menu_id` int(11) NOT NULL DEFAULT '0' COMMENT '章节id',
  `menu_name` varchar(200) NOT NULL DEFAULT '' COMMENT '章节标题',
  `menu_content` mediumtext NOT NULL COMMENT '章节内容',
  `word_count` int(11) NOT NULL DEFAULT '0' COMMENT '字数',
  `check_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '审核状态,0新增,1通过,2修改,3未通过,4删除',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `upd_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`,`book_id`),
  UNIQUE KEY `book_menu_id` (`book_id`,`menu_id`),
  KEY `book_id` (`book_id`),
  KEY `menu_id` (`menu_id`),
  KEY `check_status` (`check_status`)
) ENGINE=InnoDB AUTO_INCREMENT=198071 DEFAULT CHARSET=utf8 COMMENT='章节内容'
/*!50100 PARTITION BY RANGE (book_id)
(PARTITION p1 VALUES LESS THAN (10000) ENGINE = InnoDB,
 PARTITION p2 VALUES LESS THAN (20000) ENGINE = InnoDB,
 PARTITION p3 VALUES LESS THAN (30000) ENGINE = InnoDB,
 PARTITION p4 VALUES LESS THAN (40000) ENGINE = InnoDB,
 PARTITION p5 VALUES LESS THAN (50000) ENGINE = InnoDB,
 PARTITION p6 VALUES LESS THAN (60000) ENGINE = InnoDB,
 PARTITION p7 VALUES LESS THAN (70000) ENGINE = InnoDB,
 PARTITION p8 VALUES LESS THAN (80000) ENGINE = InnoDB,
 PARTITION p9 VALUES LESS THAN (90000) ENGINE = InnoDB,
 PARTITION p10 VALUES LESS THAN (100000) ENGINE = InnoDB,
 PARTITION p11 VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;

-- ----------------------------
-- Table structure for book_main_type
-- ----------------------------
DROP TABLE IF EXISTS `book_main_type`;
CREATE TABLE `book_main_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `typename` varchar(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `channelid` int(11) NOT NULL DEFAULT '1' COMMENT '频道:1男生,2女生,3畅销合作',
  `description` varchar(50) NOT NULL DEFAULT '' COMMENT '描述',
  `parent_id` int(11) NOT NULL DEFAULT '0' COMMENT '父分类的id',
  `parent_name` varchar(50) NOT NULL COMMENT '父分类名称',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for spider_cpinfo
-- ----------------------------
DROP TABLE IF EXISTS `spider_cpinfo`;
CREATE TABLE `spider_cpinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `website_name` varchar(50) NOT NULL COMMENT '来源网站',
  `website` varchar(200) NOT NULL COMMENT '来源网址',
  `uptime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='网站表';

-- ----------------------------
-- Records of spider_cpinfo
-- ----------------------------
INSERT INTO `spider_cpinfo` VALUES ('1', 'http://m.qu.la', '笔趣阁', '2018-08-26 10:32:37');
INSERT INTO `spider_cpinfo` VALUES ('2', 'http://m.txtduo.com', '多多书院', '2018-08-26 10:32:37');
INSERT INTO `spider_cpinfo` VALUES ('3', 'http://www.yzsqc.com/', '乡村小说网', '2018-08-26 10:33:17');
INSERT INTO `spider_cpinfo` VALUES ('4', 'https://m.23us.la/', '顶点小说网', '2018-08-26 10:33:31');
INSERT INTO `spider_cpinfo` VALUES ('5', 'https://www.23us.cc/', '顶点小说网', '2018-08-26 10:33:41');
INSERT INTO `spider_cpinfo` VALUES ('6', 'https://www.booktxt.net/ ', '顶点小说', '2018-08-26 10:33:54');
INSERT INTO `spider_cpinfo` VALUES ('7', 'https://www.yqxsy.com/', '言情小说园', '2018-08-26 10:34:05');
INSERT INTO `spider_cpinfo` VALUES ('8', 'https://www.xxbiquge.com', '新笔趣阁', '2018-08-26 10:34:16');
INSERT INTO `spider_cpinfo` VALUES ('9', 'https://www.biqugex.com', '笔趣阁', '2018-08-26 10:34:27');
INSERT INTO `spider_cpinfo` VALUES ('10', 'http://www.daomubiji.com', '盗墓笔记', '2018-08-26 10:34:37');

-- ----------------------------
-- Table structure for spider_info
-- ----------------------------
DROP TABLE IF EXISTS `spider_info`;
CREATE TABLE `spider_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `cpid` int(11) DEFAULT NULL COMMENT '合作方ID',
  `cpbookid` varchar(20) NOT NULL DEFAULT '0' COMMENT '合作方bookID',
  `bookid` int(11) NOT NULL DEFAULT '0' COMMENT 'BookID',
  `bookname` varchar(200) NOT NULL COMMENT '书籍名',
  `author` varchar(200) NOT NULL COMMENT '作者名',
  `updatetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8;
