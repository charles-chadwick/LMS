CREATE TABLE `courses`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` int(11) NOT NULL DEFAULT 1,
  `updated_by_id` int(11) NULL DEFAULT 1,
  `deleted_by_id` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

CREATE TABLE `courses_users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_instructor` bool,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` int(11) NOT NULL DEFAULT 1,
  `updated_by_id` int(11) NULL DEFAULT 1,
  `deleted_by_id` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

CREATE TABLE `discussion_posts`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `discussion_id` int(11) NOT NULL,
  `status` enum('Published','Draft') NOT NULL DEFAULT 'Draft',
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` int(11) NOT NULL DEFAULT 1,
  `updated_by_id` int(11) NULL DEFAULT 1,
  `deleted_by_id` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`)
);

CREATE TABLE `discussions`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `on` int(11) NOT NULL,
  `on_type` varchar(255) NOT NULL,
  `type` enum('Private','Group') NOT NULL DEFAULT 'Private',
  `title` varchar(255) NULL,
  `status` enum('Open','Closed') NOT NULL DEFAULT 'Open',
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` int(11) NOT NULL DEFAULT 1,
  `updated_by_id` int(11) NULL DEFAULT 1,
  `deleted_by_id` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`)
);

CREATE TABLE `pages`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `order` int(11) NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` int(11) NOT NULL DEFAULT 1,
  `updated_by_id` int(11) NULL DEFAULT 1,
  `deleted_by_id` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

CREATE TABLE `user_progress`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` int(11) NOT NULL DEFAULT 1,
  `updated_by_id` int(11) NULL DEFAULT 1,
  `deleted_by_id` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` int(11) NOT NULL DEFAULT 1,
  `updated_by_id` int(11) NULL DEFAULT 1,
  `deleted_by_id` int(11) NULL DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

ALTER TABLE `discussion_posts` ADD CONSTRAINT `fk_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;
ALTER TABLE `pages` ADD CONSTRAINT `fk_course_id` FOREIGN KEY (`course_id`) REFERENCES `customers` () ON DELETE CASCADE ON UPDATE NO ACTION;

