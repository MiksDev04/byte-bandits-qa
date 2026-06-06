CREATE DATABASE  IF NOT EXISTS "qa_system" /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `qa_system`;
-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: mysql-1819fe83-mikogapasan04-3fc8.g.aivencloud.com    Database: qa_system
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '0ac0f844-3c84-11f1-8dff-466e122fd547:1-312,
e8a80145-3ba1-11f1-b76c-b69356c7cc61:1-39';

--
-- Table structure for table `qa_accreditation_tasks`
--

DROP TABLE IF EXISTS `qa_accreditation_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_accreditation_tasks` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `audit_id` int NOT NULL,
  `standard_id` int DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`task_id`),
  KEY `fk_task_audit` (`audit_id`),
  KEY `fk_task_standard` (`standard_id`),
  CONSTRAINT `fk_task_audit` FOREIGN KEY (`audit_id`) REFERENCES `qa_audits` (`audit_id`),
  CONSTRAINT `fk_task_standard` FOREIGN KEY (`standard_id`) REFERENCES `qa_standards` (`standard_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_accreditation_tasks`
--

LOCK TABLES `qa_accreditation_tasks` WRITE;
/*!40000 ALTER TABLE `qa_accreditation_tasks` DISABLE KEYS */;
INSERT INTO `qa_accreditation_tasks` VALUES (31,1,4,'Review CHED compliance documents','2026-06-06','Pending',NULL),(32,3,4,'Prepare CHED accreditation evidence','2026-05-28','Pending',NULL),(33,10,NULL,'Evaluate curriculum against CHED standards',NULL,'Completed',NULL),(34,2,5,'Conduct ISO process review','2026-07-04','In Progress','Review operational workflows for ISO compliance.'),(35,5,5,'Verify ISO quality procedures','2026-05-27','Pending',NULL),(36,8,NULL,'Prepare ISO audit documentation',NULL,'Completed',NULL),(37,1,6,'Assess faculty performance standards','2026-05-28','Pending',NULL),(38,7,6,'Review institutional academic policies','2026-09-20','In Progress','Policies are under review for academic consistency.'),(39,10,NULL,'Analyze student outcome reports',NULL,'Completed',NULL),(40,3,7,'Inspect research ethics compliance','2026-05-22','Pending',NULL),(41,6,7,'Review research documentation archive','2026-04-14','Completed','Archived research records verified successfully.'),(42,9,7,'Evaluate data protection procedures','2026-03-09','Completed','Research and IT systems comply with data protection standards.');
/*!40000 ALTER TABLE `qa_accreditation_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_action_plans`
--

DROP TABLE IF EXISTS `qa_action_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_action_plans` (
  `plan_id` int NOT NULL AUTO_INCREMENT,
  `audit_id` int DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `root_cause` text COLLATE utf8mb4_unicode_ci,
  `target_date` date DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') COLLATE utf8mb4_unicode_ci DEFAULT 'Open',
  `resolution` text COLLATE utf8mb4_unicode_ci,
  `created_date` date DEFAULT (curdate()),
  PRIMARY KEY (`plan_id`),
  KEY `fk_plan_audit` (`audit_id`),
  CONSTRAINT `fk_plan_audit` FOREIGN KEY (`audit_id`) REFERENCES `qa_audits` (`audit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_action_plans`
--

LOCK TABLES `qa_action_plans` WRITE;
/*!40000 ALTER TABLE `qa_action_plans` DISABLE KEYS */;
INSERT INTO `qa_action_plans` VALUES (1,1,'Improve QA Documentation Compliance','Address gaps identified during internal QA audit.','Inconsistent documentation practices across departments.','2026-07-01','Open',NULL,'2026-05-09'),(2,2,'Resolve ISO Documentation Gaps','Fix missing and outdated ISO compliance documents.','Incomplete document control process.','2026-07-25','In Progress',NULL,'2026-05-09'),(3,3,'Post-Accreditation Improvement Actions','Implement improvements recommended after accreditation.','Minor inconsistencies in academic reporting.','2026-09-01','Closed','All accreditation findings successfully resolved.','2026-05-09'),(4,4,'Re-schedule Department Audit Improvements','Prepare corrective actions despite audit cancellation.','Scheduling conflicts and resource limitations.','2026-10-01','Closed','Audit rescheduled and planning improved.','2026-05-09'),(5,5,'Enhance Safety Compliance Measures','Implement corrective actions for safety inspection readiness.','Insufficient safety signage and training gaps.','2026-06-15','Open',NULL,'2026-05-09'),(6,6,'Improve Documentation Accuracy System','Maintain accuracy of internal records and reports.','Human error in record keeping.','2026-05-10','Closed','Documentation audit passed with corrections applied.','2026-05-09'),(7,3,'Prepare Institutional Accreditation Requirements','Strengthen compliance before accreditation review.','Incomplete submission of institutional data.','2026-09-20','In Progress',NULL,'2026-05-09'),(8,8,'Financial Audit Preparation Plan','Prepare financial documents for external audit.','Delayed financial reporting updates.','2026-11-01','Open',NULL,'2026-05-09'),(9,2,'Improve IT Security Controls','Enhance IT systems based on audit findings.','Weak access control policies.','2026-04-01','Closed','Security patches and access rules updated.','2026-05-09'),(10,1,'Strengthen Academic Standards Monitoring','Ensure academic quality consistency across programs.','Inconsistent evaluation metrics.','2026-12-20','Open',NULL,'2026-05-09');
/*!40000 ALTER TABLE `qa_action_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_audits`
--

DROP TABLE IF EXISTS `qa_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_audits` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `audit_type` enum('Internal','External','Accreditation') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`audit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_audits`
--

LOCK TABLES `qa_audits` WRITE;
/*!40000 ALTER TABLE `qa_audits` DISABLE KEYS */;
INSERT INTO `qa_audits` VALUES (1,'Internal','Annual Quality Assurance Audit','2026-06-15',NULL,'In Progress','Initial internal QA audit for evaluating compliance with institutional quality standards.'),(2,'External','ISO Compliance Review','2026-07-10',NULL,'In Progress','External auditors are reviewing documentation and operational procedures.'),(3,'Accreditation','Program Accreditation Evaluation','2026-08-05',NULL,'In Progress','Accreditation committee completed the evaluation with minor recommendations.'),(4,'Internal','Department Process Audit','2026-09-12',NULL,'Cancelled','Audit cancelled due to scheduling conflicts and resource limitations.'),(5,'External','Safety and Compliance Inspection','2026-05-20',NULL,'In Progress','Routine inspection focused on workplace safety and compliance policies.'),(6,'Internal','Documentation Accuracy Audit','2026-04-18','2026-04-20','Completed','Reviewed internal records and documentation for accuracy and completeness.'),(7,'Accreditation','Institutional Accreditation Review','2026-10-01',NULL,'In Progress','Preparation and evaluation for institutional accreditation renewal.'),(8,'External','Financial Operations Audit','2026-11-11','2026-05-16','Completed','Independent auditors assigned to assess financial transparency and accountability.'),(9,'Internal','IT Systems Quality Audit','2026-03-14','2026-03-16','Completed','Assessment of IT systems reliability, security, and operational efficiency.'),(10,'Accreditation','Academic Standards Assessment','2026-12-05','2026-05-16','Completed','Review of academic standards and curriculum effectiveness for accreditation purposes.');
/*!40000 ALTER TABLE `qa_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_indicators`
--

DROP TABLE IF EXISTS `qa_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_indicators` (
  `indicator_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `benchmark_source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`indicator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_indicators`
--

LOCK TABLES `qa_indicators` WRITE;
/*!40000 ALTER TABLE `qa_indicators` DISABLE KEYS */;
INSERT INTO `qa_indicators` VALUES (18,'Average Student Grade','Mean grade of students across all classes for the given period, sourced from LMS gradebook data.','Academic Performance','%',85.00,'ArtisansLMS API'),(19,'Task Submission Rate','Percentage of submitted tasks out of total expected submissions across all enrolled students.','Student Engagement','%',90.00,'ArtisansLMS API'),(20,'Quiz Pass Rate','Percentage of students who passed quizzes out of total quiz attempts recorded in the LMS.','Academic Performance','%',80.00,'ArtisansLMS API'),(21,'Faculty Evaluation Average Rating','Mean rating given by students to faculty members through the faculty evaluation system.','Faculty Performance','rating (1-5)',4.00,'Faculty Evaluation System'),(22,'Faculty Evaluation Response Rate','Percentage of students who completed the faculty evaluation form out of total enrolled students.','Faculty Performance','%',80.00,'Faculty Evaluation System');
/*!40000 ALTER TABLE `qa_indicators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_kpi_records`
--

DROP TABLE IF EXISTS `qa_kpi_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_kpi_records` (
  `record_id` int NOT NULL AUTO_INCREMENT,
  `indicator_id` int NOT NULL,
  `school_year` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_term` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actual_value` decimal(10,2) DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`record_id`),
  KEY `fk_kpi_indicator` (`indicator_id`),
  CONSTRAINT `fk_kpi_indicator` FOREIGN KEY (`indicator_id`) REFERENCES `qa_indicators` (`indicator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_kpi_records`
--

LOCK TABLES `qa_kpi_records` WRITE;
/*!40000 ALTER TABLE `qa_kpi_records` DISABLE KEYS */;
INSERT INTO `qa_kpi_records` VALUES (25,18,'2025 - 2026','1st Semester',87.50,'Fetched from ArtisansLMS API (avg_grade). Slightly above target of 85%.'),(26,19,'2025 - 2026','1st Semester',6.10,'Fetched from ArtisansLMS API (submission_rate). 11 of 179 expected submissions received — significantly below 90% target. Needs intervention.'),(27,20,'2025 - 2026','1st Semester',100.00,'Fetched from ArtisansLMS API (quiz_pass_rate). 1 out of 1 quiz attempt passed across 2 total quizzes. Meets target.'),(28,21,'2025 - 2026','1st Semester',4.20,'Sourced from Faculty Evaluation System (avg_rating). Based on 342 total responses. Exceeds target of 4.0.'),(29,22,'2025 - 2026','1st Semester',85.50,'Sourced from Faculty Evaluation System (response_rate). 342 responses recorded. Meets target of 80%.'),(33,20,'2025 - 2026','2nd Semester',100.00,'Imported from lms - Field: Quiz Pass Rate (%)');
/*!40000 ALTER TABLE `qa_kpi_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_policies`
--

DROP TABLE IF EXISTS `qa_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_policies` (
  `policy_id` int NOT NULL AUTO_INCREMENT,
  `standard_id` int DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `document_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_date` date DEFAULT NULL,
  `status` enum('Active','Archived') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  PRIMARY KEY (`policy_id`),
  KEY `fk_policy_standard` (`standard_id`),
  CONSTRAINT `fk_policy_standard` FOREIGN KEY (`standard_id`) REFERENCES `qa_standards` (`standard_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_policies`
--

LOCK TABLES `qa_policies` WRITE;
/*!40000 ALTER TABLE `qa_policies` DISABLE KEYS */;
INSERT INTO `qa_policies` VALUES (8,4,'CHED Curriculum Compliance Policy','All academic programs must comply with CHED prescribed curriculum standards and outcomes-based education requirements.','https://ik.imagekit.io/miksdev/qa_policies/CHED_Curriculum_Compliance_Official_ZkNVcQtxE.pdf','2024-02-01','Active'),(9,4,'CHED Faculty Qualification Policy','Faculty members must meet minimum qualification requirements set by CHED for teaching assignments.','https://ik.imagekit.io/miksdev/qa_policies/CHED_Faculty_Qualification_Official_K9EVzunys.pdf','2024-02-10','Active'),(10,5,'ISO Documentation Control Policy','All documents must be properly controlled, versioned, and approved before distribution.','https://ik.imagekit.io/miksdev/qa_policies/ISO_Documentation_Control_v3_NBMl4FCg1.pdf','2016-01-05','Active'),(11,5,'ISO Internal Audit Policy','Internal audits must be conducted at planned intervals to ensure compliance with ISO standards.','https://ik.imagekit.io/miksdev/qa_policies/ISO_Internal_Audit_v3_cjNfdcF7Z.pdf','2016-03-20','Active'),(12,6,'Institutional Performance Review Policy','Academic and administrative performance shall be reviewed every semester.','https://ik.imagekit.io/miksdev/qa_policies/Institutional_Performance_Review_v3_771Y6Zq7C.pdf','2023-06-15','Active'),(13,7,'Institutional Student Feedback Policy','Student feedback must be collected and analyzed for continuous improvement.','https://ik.imagekit.io/miksdev/qa_policies/Institutional_Student_Feedback_v3_NktbF-0rgV.pdf','2023-07-01','Active');
/*!40000 ALTER TABLE `qa_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_question_options`
--

DROP TABLE IF EXISTS `qa_question_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_question_options` (
  `option_id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `option_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`option_id`),
  KEY `fk_option_question` (`question_id`),
  CONSTRAINT `fk_option_question` FOREIGN KEY (`question_id`) REFERENCES `qa_survey_questions` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_question_options`
--

LOCK TABLES `qa_question_options` WRITE;
/*!40000 ALTER TABLE `qa_question_options` DISABLE KEYS */;
INSERT INTO `qa_question_options` VALUES (2968,58,'Library',0),(2969,58,'Computer Laboratory',1),(2970,58,'Science Laboratory',2),(2971,58,'Gymnasium / Sports Area',3),(2972,58,'Canteen / Cafeteria',4),(2973,58,'Guidance Office',5),(2979,62,'Employed Full-Time',0),(2980,62,'Employed Part-Time',1),(2981,62,'Self-Employed / Freelancer',2),(2982,62,'Currently Job Seeking',3),(2983,62,'Pursuing Further Education',4),(2984,62,'Not Currently in the Workforce',5),(2985,63,'Less than 1 month',0),(2986,63,'1 - 3 months',1),(2987,63,'4 - 6 months',2),(2988,63,'7 - 12 months',3),(2989,63,'More than 1 year',4),(2990,63,'Not yet employed',5),(2991,66,'Technical / Discipline-Specific Skills',0),(2992,66,'Communication Skills',1),(2993,66,'Problem-Solving & Critical Thinking',2),(2994,66,'Teamwork & Collaboration',3),(2995,66,'Leadership & Management',4),(2996,66,'Digital / IT Skills',5),(2997,71,'Technical Knowledge',0),(2998,71,'Written Communication',1),(2999,71,'Verbal Communication',2),(3000,71,'Work Ethic & Professionalism',3),(3001,71,'Problem-Solving Ability',4),(3002,71,'Adaptability & Initiative',5),(3018,81,'Hands-On / Laboratory Work',0),(3019,81,'Industry-Relevant Case Studies',1),(3020,81,'Internship / OJT Component',2),(3021,81,'Research & Thesis Work',3),(3022,81,'Core Theory & Foundations',4),(3023,81,'Elective / Specialization Tracks',5),(3024,83,'Every Year',0),(3025,83,'Every 2-3 Years',1),(3026,83,'Every 4-5 Years',2),(3027,83,'Rarely Updated',3),(3028,83,'Not Sure',4),(3034,74,'Strongly Disagree',0),(3035,74,'Disagree',1),(3036,74,'Neutral',2),(3037,74,'Agree',3),(3038,74,'Strongly Agree',4),(3039,75,'Strongly Disagree',0),(3040,75,'Disagree',1),(3041,75,'Neutral',2),(3042,75,'Agree',3),(3043,75,'Strongly Agree',4),(3044,76,'Strongly Disagree',0),(3045,76,'Disagree',1),(3046,76,'Neutral',2),(3047,76,'Agree',3),(3048,76,'Strongly Agree',4);
/*!40000 ALTER TABLE `qa_question_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_reports`
--

DROP TABLE IF EXISTS `qa_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` enum('KPI Summary','Audit Report','Survey Report','Accreditation','Improvement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_year` year DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `generated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `file_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`report_id`),
  KEY `fk_report_user` (`generated_by`),
  CONSTRAINT `fk_report_user` FOREIGN KEY (`generated_by`) REFERENCES `qa_users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_reports`
--

LOCK TABLES `qa_reports` WRITE;
/*!40000 ALTER TABLE `qa_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `qa_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_standards`
--

DROP TABLE IF EXISTS `qa_standards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_standards` (
  `standard_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` enum('CHED','ISO','Institutional','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` enum('Active','Archived') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  PRIMARY KEY (`standard_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_standards`
--

LOCK TABLES `qa_standards` WRITE;
/*!40000 ALTER TABLE `qa_standards` DISABLE KEYS */;
INSERT INTO `qa_standards` VALUES (4,'CHED Quality Assurance Guidelines','CHED','Standards set by the Commission on Higher Education for academic quality assurance in higher education institutions.','2024-1.0','2024-01-15','Active'),(5,'ISO 9001:2015 Quality Management','ISO','International standard for quality management systems focusing on customer satisfaction and continuous improvement.','2015','2015-09-23','Active'),(6,'Institutional Academic Standards','Institutional','Internal quality assurance standards used by the institution to monitor academic performance and services.','2023-2.1','2023-06-01','Active'),(7,'Research Compliance Standards','Institutional','Specialized standards for ensuring ethical compliance and research quality.','2022-1.0','2022-03-10','Active');
/*!40000 ALTER TABLE `qa_standards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_survey_answers`
--

DROP TABLE IF EXISTS `qa_survey_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_survey_answers` (
  `answer_id` int NOT NULL AUTO_INCREMENT,
  `respondent_id` int NOT NULL,
  `question_id` int NOT NULL,
  `option_id` int DEFAULT NULL,
  `rating_value` tinyint DEFAULT NULL,
  `text_answer` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`answer_id`),
  KEY `fk_ans_respondent` (`respondent_id`),
  KEY `fk_ans_question` (`question_id`),
  KEY `fk_ans_option` (`option_id`),
  CONSTRAINT `fk_ans_option` FOREIGN KEY (`option_id`) REFERENCES `qa_question_options` (`option_id`),
  CONSTRAINT `fk_ans_question` FOREIGN KEY (`question_id`) REFERENCES `qa_survey_questions` (`question_id`),
  CONSTRAINT `fk_ans_respondent` FOREIGN KEY (`respondent_id`) REFERENCES `qa_survey_respondents` (`respondent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_survey_answers`
--

LOCK TABLES `qa_survey_answers` WRITE;
/*!40000 ALTER TABLE `qa_survey_answers` DISABLE KEYS */;
INSERT INTO `qa_survey_answers` VALUES (81,27,74,3034,NULL,NULL),(82,27,75,3039,NULL,NULL),(83,27,76,3044,NULL,NULL),(84,27,77,NULL,10,NULL),(85,27,78,NULL,NULL,'yes'),(86,27,79,NULL,NULL,'N/A'),(87,28,56,NULL,5,NULL),(88,28,57,NULL,5,NULL),(89,28,58,2968,NULL,NULL),(90,28,58,2969,NULL,NULL),(91,28,58,2971,NULL,NULL),(92,28,58,2972,NULL,NULL),(93,28,59,NULL,NULL,'yes'),(94,28,60,NULL,5,NULL),(95,28,61,NULL,NULL,'N/A'),(96,29,62,2979,NULL,NULL),(97,29,63,2988,NULL,NULL),(98,29,64,NULL,NULL,'yes'),(99,29,65,NULL,6,NULL),(100,29,66,2991,NULL,NULL),(101,29,67,NULL,NULL,'gdgfjgjg');
/*!40000 ALTER TABLE `qa_survey_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_survey_questions`
--

DROP TABLE IF EXISTS `qa_survey_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_survey_questions` (
  `question_id` int NOT NULL AUTO_INCREMENT,
  `survey_id` int NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('rating_5','rating_10','yes_no','multiple_choice','checkbox','open_ended','likert','text') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_required` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`question_id`),
  KEY `fk_question_survey` (`survey_id`),
  CONSTRAINT `fk_question_survey` FOREIGN KEY (`survey_id`) REFERENCES `qa_surveys` (`survey_id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_survey_questions`
--

LOCK TABLES `qa_survey_questions` WRITE;
/*!40000 ALTER TABLE `qa_survey_questions` DISABLE KEYS */;
INSERT INTO `qa_survey_questions` VALUES (56,31,'How satisfied are you with the overall quality of education provided?','rating_5',1,0),(57,31,'How would you rate the availability and helpfulness of academic support services?','rating_5',1,1),(58,31,'Which of the following facilities do you use most frequently?','checkbox',1,2),(59,31,'Do you feel that the institution addresses your concerns promptly?','yes_no',1,3),(60,31,'How satisfied are you with the library and learning resources?','rating_5',1,4),(61,31,'What improvements would you suggest for student services?','open_ended',0,5),(62,32,'What is your current employment status?','multiple_choice',1,0),(63,32,'How long did it take you to land your first job after graduation?','multiple_choice',1,1),(64,32,'Is your current job aligned with your degree program?','yes_no',1,2),(65,32,'Rate how well your academic training prepared you for your current role.','rating_10',1,3),(66,32,'Which skills from your program do you use most in your current job?','checkbox',1,4),(67,32,'What competencies do you wish the program had developed more?','open_ended',0,5),(68,33,'How would you rate the overall work readiness of graduates from this institution?','rating_5',1,0),(69,33,'How satisfied are you with graduates technical and hard skills?','rating_5',1,1),(70,33,'How satisfied are you with graduates communication and interpersonal skills?','rating_5',1,2),(71,33,'Which areas need the most improvement in new graduates?','checkbox',1,3),(72,33,'Would you recommend this institution graduates to other employers?','yes_no',1,4),(73,33,'What specific competencies or training would better prepare graduates for industry?','open_ended',0,5),(74,34,'The instructor delivers lessons in a clear and understandable manner.','multiple_choice',1,0),(75,34,'The instructor is well-prepared and knowledgeable about the subject.','multiple_choice',1,1),(76,34,'The instructor is approachable and responsive to student questions.','multiple_choice',1,2),(77,34,'Rate the overall effectiveness of the instructor on a scale of 1-10.','rating_10',1,3),(78,34,'Does the instructor provide timely and constructive feedback?','yes_no',1,4),(79,34,'What suggestions do you have to help your instructor improve?','open_ended',0,5),(80,35,'How relevant is the curriculum to current industry standards and practices?','rating_5',1,0),(81,35,'Which aspects of the curriculum do you find most valuable?','checkbox',1,1),(82,35,'Is the balance between theory and practical application appropriate?','yes_no',1,2),(83,35,'How often is the curriculum updated to reflect industry changes?','multiple_choice',1,3),(84,35,'Rate your overall satisfaction with the curriculum design.','rating_5',1,4),(85,35,'What subjects or topics should be added or removed from the curriculum?','open_ended',0,5);
/*!40000 ALTER TABLE `qa_survey_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_survey_respondents`
--

DROP TABLE IF EXISTS `qa_survey_respondents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_survey_respondents` (
  `respondent_id` int NOT NULL AUTO_INCREMENT,
  `survey_id` int NOT NULL,
  `respondent_role` enum('Student','Alumni','Employer','Faculty','Staff') COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int DEFAULT NULL,
  `employee_id` int DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`respondent_id`),
  KEY `fk_resp_survey` (`survey_id`),
  CONSTRAINT `fk_resp_survey` FOREIGN KEY (`survey_id`) REFERENCES `qa_surveys` (`survey_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_survey_respondents`
--

LOCK TABLES `qa_survey_respondents` WRITE;
/*!40000 ALTER TABLE `qa_survey_respondents` DISABLE KEYS */;
INSERT INTO `qa_survey_respondents` VALUES (27,34,'Student',NULL,NULL,'2026-05-15 12:25:23'),(28,31,'Student',NULL,NULL,'2026-05-15 12:29:22'),(29,32,'Alumni',NULL,NULL,'2026-05-16 03:12:21');
/*!40000 ALTER TABLE `qa_survey_respondents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_surveys`
--

DROP TABLE IF EXISTS `qa_surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_surveys` (
  `survey_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `target_group` enum('Student','Alumni','Employer','Faculty','Staff','All') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Draft','Active','Closed') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `qr_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`survey_id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `fk_survey_creator` (`created_by`),
  CONSTRAINT `fk_survey_creator` FOREIGN KEY (`created_by`) REFERENCES `qa_users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_surveys`
--

LOCK TABLES `qa_surveys` WRITE;
/*!40000 ALTER TABLE `qa_surveys` DISABLE KEYS */;
INSERT INTO `qa_surveys` VALUES (31,'Student Satisfaction Survey 2025','Measures overall student satisfaction with academic programs, facilities, and support services for the current academic year.','Student','2026-05-01','2026-06-01','Closed',3,'QR-STU-2025-SAT01','2026-05-15 11:41:13','2026-06-02 00:04:39'),(32,'Alumni Tracer Study 2025','Tracks the employment status, career progression, and professional relevance of the institution\'s graduates.','Alumni','2026-05-16','2026-05-21','Closed',3,'QR-ALM-2025-TRC02','2026-05-15 11:41:13','2026-05-22 00:04:39'),(33,'Employer Satisfaction & Linkage Survey','Gathers feedback from industry employers on graduate competencies, work readiness, and institutional collaboration.','Employer','2026-05-15','2026-09-30','Draft',3,'QR-EMP-2025-SAT03','2026-05-15 11:41:13','2026-05-15 12:14:33'),(34,'Faculty Teaching Effectiveness Evaluation','Evaluates the teaching quality, responsiveness, and professional conduct of faculty members.','Student','2026-05-14','2026-06-30','Active',3,'QR-FAC-2025-EFF04','2026-05-15 11:41:13','2026-05-15 12:20:38'),(35,'Curriculum Quality & Relevance Assessment','Assesses whether the current curriculum aligns with industry demands and prepares students for real-world challenges.','All','2026-05-05','2026-07-21','Active',3,'QR-CUR-2025-QAL05','2026-05-15 11:41:13','2026-05-15 12:24:24');
/*!40000 ALTER TABLE `qa_surveys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qa_users`
--

DROP TABLE IF EXISTS `qa_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','qa_officer','viewer') COLLATE utf8mb4_unicode_ci DEFAULT 'viewer',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_users`
--

LOCK TABLES `qa_users` WRITE;
/*!40000 ALTER TABLE `qa_users` DISABLE KEYS */;
INSERT INTO `qa_users` VALUES (3,'admin.qa.app','$2y$10$AZMJZsCFo93F6zDResZdc.gzbKxJbOiYm0q7g7IpGOyLdpqsLG8b2','System Administrator','yoshmolato42@gmail.com','admin',1,'2026-05-03 20:55:14');
/*!40000 ALTER TABLE `qa_users` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-06 21:38:40
