---
apply: always
---

# Project Overview

This will be a Learning Management System. Instructors will create Courses with Pages. Students will be able to take courses and their progress will be tracked. 

# Tech Stack
First, install Laravel 12. Then add the following:

InertiaJS with support for VueJS. 
Ziggy to handle InertiaJS routes
Tailwind 4
Spatie's Activity Log to track every click and database operation. 
Spatie's Permissions to manage user permissions. 
Spatie's MediaLibrary to manage documents
PrimeVue vue components and icons. 

# Code Format and Rules
All code (PHP, JavaScript, CSS, etc) should strictly follow the PSR 12 rules https://www.php-fig.org/psr/psr-12/
Optimize all JavaScript imports, except PrimeVue's Editor component.
All confirmation dialog's should use PrimeVue's ConfirmDialog component.
All flash messages should use PrimeVue's Message component.

# Backend
Use the supplied schema. 
Never validate in controllers. Always create a Request. Optimize them as best as possible.
All models will extend from a base class called Base, that extends from Model. 
The base model will autoload the created_by, updated_by, and deleted_by relationships EXCEPT when that model is User
The base model will have the created_at, updated_at, and deleted_at fields automatically added to $fillable

# Front End
Use PrimeVue components whenever possible, EXCEPT for DataTable, or Paginator. Never use that. 
Change PrimeVue's primary color to Tailwind's Purple, the accent (or secondary) color to Tailwind's lime, and gray to Tailwind's Stone. 
Create css variables for all of Tailwind's Purple, called --color-primary-
Create css variables for all of Tailwind's Lime, called --color-accent-
Create css variables for all of Tailwind's Stone, called --color-darker-
Always create classes using Tailwind
Always use PrimeVue Select instead of Dropdown

# Schema

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


