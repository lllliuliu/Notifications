-- ----------------------------
-- Table structure for onethink_user_notification_broadcast
-- ----------------------------
DROP TABLE IF EXISTS `onethink_user_notification_broadcast`;
CREATE TABLE `onethink_user_notification_broadcast` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `title` varchar(500) COLLATE utf8_unicode_ci NOT NULL COMMENT '分类类型的标题',
  `content` text COLLATE utf8_unicode_ci NOT NULL COMMENT '分类类型的内容',
  `created_at` int(10) unsigned NOT NULL COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='用户广播消息表';

-- ----------------------------
-- Table structure for onethink_user_notification_categories
-- ----------------------------
DROP TABLE IF EXISTS `onethink_user_notification_categories`;
CREATE TABLE `onethink_user_notification_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '标识',
  `title` varchar(500) COLLATE utf8_unicode_ci NOT NULL COMMENT '分类类型的标题',
  `content` text COLLATE utf8_unicode_ci NOT NULL COMMENT '分类类型的内容',
  `created_at` int(10) unsigned NOT NULL COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_categories_name_unique` (`name`),
  KEY `notification_categories_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='用户系统消息表';

-- ----------------------------
-- Table structure for onethink_user_notifications
-- ----------------------------
DROP TABLE IF EXISTS `onethink_user_notifications`;
CREATE TABLE `onethink_user_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `from_id` bigint(20) unsigned NOT NULL COMMENT '发送者user_id，系统和管理员发送时为0',
  `from_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '发送者类型',
  `to_id` bigint(20) unsigned NOT NULL COMMENT '接收者user_id',
  `to_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '接收者者类型',
  `category` tinyint(1) unsigned NOT NULL COMMENT '消息分类，0-系统消息，1-广播消息，2-组播消息，3-单播消息',
  `category_id` int(10) unsigned NOT NULL COMMENT '对应分类所属id，只在系统消息，广播，组播消息有效',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '消息重定向url',
  `extra_title` varchar(500) COLLATE utf8_unicode_ci NOT NULL COMMENT '附加消息类型的标题动态值',
  `extra_content` text COLLATE utf8_unicode_ci NOT NULL COMMENT '附加消息类型的内容动态值',
  `read` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否已读，0-未读，1-已读',
  `created_at` int(10) unsigned NOT NULL COMMENT '创建时间',
  `updated_at` int(10) unsigned NOT NULL COMMENT '修改时间',
  `expire_time` int(10) unsigned NOT NULL COMMENT '消息过期时间',
  PRIMARY KEY (`id`),
  KEY `notifications_from_id_index` (`from_id`),
  KEY `notifications_to_id_index` (`to_id`),
  KEY `notifications_category_id_index` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2400 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='用户消息表';
