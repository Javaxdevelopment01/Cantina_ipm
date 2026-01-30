CREATE DATABASE  IF NOT EXISTS `ipm_cantina` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `ipm_cantina`;
-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: ipm_cantina
-- ------------------------------------------------------
-- Server version	8.3.0

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

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Gabriel Pedro','gabriel@gmail.com','$2y$10$H4OsDFtTPjOrjxvxe1pZqeYYTM/m.qobr97RzD/fy3S6BCgkfBFk2',NULL,'ativo','2025-11-26 14:01:20',NULL),(2,'Leonel António','oprogramador@gmail.com','$2y$10$Eouq6imCG8b.W4w.3u5piuxUmDuOVu4ImjOXJPb9Tg.V6ck/i6JVq','/uploads/admin/adm_1764166027_5461.jpg','ativo','2025-11-26 14:07:07','2025-12-07 00:12:48'),(3,'Ernesto Quitas Buka','ernesto@gmail.com','$2y$10$gFagi5EjSErOdRnIHhViEuZ99zH9dmziP7HtlTSO0k5aN6o9fytgy','/uploads/admin/adm_1764257960_8305.jpg','ativo','2025-11-27 15:39:21',NULL),(4,'Ramos Panzo','ramos@gmail.com','$2y$10$S9T8jdaolGDK0Yt9otE3HugkMmhg7b8omx6GcFMDo.mdJc69bFRXy','/uploads/admin/adm_1765067742_7388.jpg','ativo','2025-12-06 23:59:48','2025-12-07 00:35:42'),(5,'Laurinda Constantino','laurinda@gmail.com','$2y$10$B5SNUet8czdy1M6HDOizou6TZxgWASbCVHaNEwXX/anUP87fj2..C','/uploads/admin/admin_1765065806_6934c44e667d2.jpg','ativo','2025-12-07 00:03:26','2025-12-07 00:12:48'),(6,'Francisco Manuel','francisco@gmail.com','$2y$10$7HvANEucksdNtttWxzP/FuSRTbdb8eeAQoIAXbhvamLoaszpa/c5G','/uploads/admin/adm_1765066671_4951.png','ativo','2025-12-07 00:15:13','2025-12-07 00:17:51');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categoria`
--

DROP TABLE IF EXISTS `categoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_actualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categoria`
--

LOCK TABLES `categoria` WRITE;
/*!40000 ALTER TABLE `categoria` DISABLE KEYS */;
INSERT INTO `categoria` VALUES (1,'Bebidas','Refrescante','2025-10-21 21:48:24','2025-10-21 21:48:24'),(2,'Futas','Saúdavel','2025-10-22 05:05:47','2025-10-22 05:05:47'),(3,'Aperitivos','Carnes & muito mais','2025-10-22 08:06:52','2025-10-22 08:06:52'),(4,'Doces','Não saudavel','2025-10-24 14:19:03','2025-10-24 14:19:03');
/*!40000 ALTER TABLE `categoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente`
--

DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cliente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('pré-pago','fiado') COLLATE utf8mb4_unicode_ci DEFAULT 'pré-pago',
  `dataCriacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente`
--

LOCK TABLES `cliente` WRITE;
/*!40000 ALTER TABLE `cliente` DISABLE KEYS */;
INSERT INTO `cliente` VALUES (1,'Leonel','leonel@gmail.com','944666304','','2025-10-27 14:18:26'),(2,'Leonel','',NULL,'pré-pago','2025-10-30 09:44:11'),(3,'Baptista','',NULL,'pré-pago','2025-10-30 09:48:05'),(4,'Fernando','',NULL,'pré-pago','2025-10-30 09:48:28'),(5,'Bernardo','',NULL,'pré-pago','2025-10-30 18:59:05'),(6,'Ernesto','',NULL,'pré-pago','2025-10-30 18:59:29'),(7,'Adp','',NULL,'pré-pago','2025-10-31 15:24:04'),(8,'Adp','',NULL,'pré-pago','2025-10-31 15:28:29'),(9,'Hermenegildo','',NULL,'pré-pago','2025-11-01 01:05:48'),(10,'Gouveia','',NULL,'pré-pago','2025-11-01 01:07:40'),(11,'Ernesto','',NULL,'pré-pago','2025-11-01 01:09:21'),(12,'Major','',NULL,'pré-pago','2025-11-01 16:22:01'),(13,'António Yamuebo','',NULL,'pré-pago','2025-11-01 16:22:57'),(14,'Do Major','',NULL,'pré-pago','2025-11-01 16:33:05'),(15,'Hermenegildo','',NULL,'pré-pago','2025-11-01 16:51:59'),(16,'Hermenegildo','',NULL,'pré-pago','2025-11-01 16:52:25'),(17,'Gildo','',NULL,'pré-pago','2025-11-01 16:53:43'),(18,'Hermenegildo','',NULL,'pré-pago','2025-11-01 16:55:21'),(19,'Gildo','',NULL,'pré-pago','2025-11-01 16:57:10'),(20,'Apolinário Jamba','',NULL,'pré-pago','2025-11-01 20:27:48'),(21,'Apolinário','',NULL,'pré-pago','2025-11-01 20:29:06'),(22,'Gabriel Pedro','',NULL,'pré-pago','2025-11-03 10:56:19'),(23,'Gouveia','',NULL,'pré-pago','2025-11-04 09:54:42'),(24,'Jamba António','',NULL,'pré-pago','2025-11-17 07:56:45'),(25,'Estanislau António','',NULL,'pré-pago','2025-11-17 07:59:59'),(26,'Leonel António Pandox','',NULL,'pré-pago','2025-11-17 08:08:55'),(27,'Leonel António Pandox','',NULL,'pré-pago','2025-11-23 15:48:34'),(28,'llelrkfkr','',NULL,'pré-pago','2025-11-23 15:52:17'),(29,'Gabriel Aurelio','',NULL,'pré-pago','2025-11-24 12:24:22'),(30,'Aguinaldo','',NULL,'pré-pago','2025-11-24 12:35:16'),(31,'AGT','',NULL,'pré-pago','2025-11-26 15:37:30'),(32,'Ramos Panzo','',NULL,'pré-pago','2025-11-30 14:30:30'),(33,'Carla António','',NULL,'pré-pago','2025-11-30 15:31:48'),(34,'Gomes António','',NULL,'pré-pago','2025-11-30 15:32:15'),(35,'Leonel António Pandox','',NULL,'pré-pago','2025-12-01 14:33:56');
/*!40000 ALTER TABLE `cliente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversas_ia`
--

DROP TABLE IF EXISTS `conversas_ia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversas_ia` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sessao_usuario` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utilizador_id` int DEFAULT NULL,
  `origem_mensagem` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `texto_mensagem` text COLLATE utf8mb4_unicode_ci,
  `intencao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadados` json DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilizador_id` (`utilizador_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversas_ia`
--

LOCK TABLES `conversas_ia` WRITE;
/*!40000 ALTER TABLE `conversas_ia` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversas_ia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entrada_estoque`
--

DROP TABLE IF EXISTS `entrada_estoque`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entrada_estoque` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int NOT NULL,
  `quantidade` int NOT NULL,
  `utilizador_id` int DEFAULT NULL,
  `data_entrada` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  KEY `utilizador_id` (`utilizador_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entrada_estoque`
--

LOCK TABLES `entrada_estoque` WRITE;
/*!40000 ALTER TABLE `entrada_estoque` DISABLE KEYS */;
/*!40000 ALTER TABLE `entrada_estoque` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fatura`
--

DROP TABLE IF EXISTS `fatura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fatura` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venda_id` int NOT NULL,
  `numero_fatura` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caminho_pdf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_impressao` timestamp NULL DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `venda_id` (`venda_id`),
  UNIQUE KEY `numero_fatura` (`numero_fatura`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fatura`
--

LOCK TABLES `fatura` WRITE;
/*!40000 ALTER TABLE `fatura` DISABLE KEYS */;
/*!40000 ALTER TABLE `fatura` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fornecedor`
--

DROP TABLE IF EXISTS `fornecedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fornecedor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contacto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_actualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fornecedor`
--

LOCK TABLES `fornecedor` WRITE;
/*!40000 ALTER TABLE `fornecedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `fornecedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_venda_produto`
--

DROP TABLE IF EXISTS `historico_venda_produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_venda_produto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int NOT NULL,
  `venda_id` int NOT NULL,
  `quantidade` int NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `data_venda` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  KEY `venda_id` (`venda_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_venda_produto`
--

LOCK TABLES `historico_venda_produto` WRITE;
/*!40000 ALTER TABLE `historico_venda_produto` DISABLE KEYS */;
/*!40000 ALTER TABLE `historico_venda_produto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagamento`
--

DROP TABLE IF EXISTS `pagamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venda_id` int NOT NULL,
  `fornecedor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'PENDENTE',
  `referencia_fornecedor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_actualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `venda_id` (`venda_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagamento`
--

LOCK TABLES `pagamento` WRITE;
/*!40000 ALTER TABLE `pagamento` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int DEFAULT NULL,
  `data_pedido` datetime DEFAULT CURRENT_TIMESTAMP,
  `forma_pagamento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `estado` enum('pendente','atendido','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `lido` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `id_cliente` (`id_cliente`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
INSERT INTO `pedido` VALUES (1,NULL,'2025-10-29 23:27:47','mao',200.00,'atendido',1),(2,NULL,'2025-10-29 23:28:09','mao',300.00,'atendido',1),(3,NULL,'2025-10-29 23:32:09','mao',1500.00,'atendido',1),(4,NULL,'2025-10-30 00:03:32','mao',1500.00,'atendido',1),(5,NULL,'2025-10-30 00:24:46','multicaixa',1000.00,'pendente',1),(6,2,'2025-10-30 10:44:11','mao',1500.00,'pendente',1),(7,3,'2025-10-30 10:48:05','mao',2300.00,'pendente',0),(8,4,'2025-10-30 10:48:28','mao',1120.00,'pendente',0),(9,5,'2025-10-30 19:59:05','multicaixa',3000.00,'atendido',1),(10,6,'2025-10-30 19:59:29','mao',1200.00,'cancelado',1),(11,NULL,'2025-10-30 19:59:54','cartao',1100.00,'',1),(12,7,'2025-10-31 16:24:04','mao',3000.00,'',1),(13,8,'2025-10-31 16:28:29','multicaixa',2400.00,'',1),(14,9,'2025-11-01 02:05:48','mao',5000.00,'',1),(15,10,'2025-11-01 02:07:40','mao',2000.00,'',1),(16,11,'2025-11-01 02:09:21','mao',1800.00,'',1),(17,12,'2025-11-01 17:22:01','mao',2300.00,'cancelado',1),(18,13,'2025-11-01 17:22:57','mao',2000.00,'cancelado',1),(19,14,'2025-11-01 17:33:05','multicaixa',3000.00,'atendido',1),(20,15,'2025-11-01 17:51:59','mao',2000.00,'cancelado',1),(21,16,'2025-11-01 17:52:25','mao',1000.00,'cancelado',1),(22,17,'2025-11-01 17:53:43','mao',1000.00,'cancelado',1),(23,18,'2025-11-01 17:55:21','mao',3100.00,'atendido',1),(24,19,'2025-11-01 17:57:10','mao',500.00,'atendido',1),(25,20,'2025-11-01 21:27:48','mao',1850.00,'atendido',1),(26,21,'2025-11-01 21:29:06','mao',1850.00,'atendido',1),(27,22,'2025-11-03 11:56:19','mao',2300.00,'atendido',1),(28,23,'2025-11-04 10:54:42','mao',1300.00,'atendido',1),(29,24,'2025-11-17 08:56:45','multicaixa',1300.00,'atendido',1),(30,25,'2025-11-17 08:59:59','mao',1200.00,'cancelado',1),(31,26,'2025-11-17 09:08:55','mao',600.00,'atendido',1),(32,27,'2025-11-23 16:48:34','multicaixa',400.00,'atendido',1),(33,28,'2025-11-23 16:52:17','multicaixa',200.00,'cancelado',1),(34,29,'2025-11-24 13:24:22','mao',120.00,'atendido',1),(35,30,'2025-11-24 13:35:16','mao',3700.00,'atendido',1),(36,31,'2025-11-26 16:37:30','mao',1300.00,'atendido',1),(37,32,'2025-11-30 15:30:30','mao',600.00,'atendido',1),(38,33,'2025-11-30 16:31:48','mao',1100.00,'atendido',1),(39,34,'2025-11-30 16:32:15','mao',1000.00,'atendido',1),(40,35,'2025-12-01 15:33:56','mao',2000.00,'',1);
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_itens`
--

DROP TABLE IF EXISTS `pedido_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int DEFAULT NULL,
  `id_produto` int DEFAULT NULL,
  `quantidade` int DEFAULT NULL,
  `preco` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_produto` (`id_produto`)
) ENGINE=MyISAM AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_itens`
--

LOCK TABLES `pedido_itens` WRITE;
/*!40000 ALTER TABLE `pedido_itens` DISABLE KEYS */;
INSERT INTO `pedido_itens` VALUES (1,1,3,1,200.00),(2,1,6,1,200.00),(3,1,3,1,200.00),(4,2,6,1,200.00),(5,2,5,1,100.00),(6,3,3,1,200.00),(7,3,6,1,200.00),(8,3,5,1,100.00),(9,3,8,1,1000.00),(10,4,3,1,200.00),(11,4,6,1,200.00),(12,4,5,1,100.00),(13,4,8,1,1000.00),(14,5,20,1,1000.00),(15,6,3,1,200.00),(16,6,6,1,200.00),(17,6,5,1,100.00),(18,6,8,1,1000.00),(19,7,6,6,200.00),(20,7,5,1,100.00),(21,7,8,1,1000.00),(22,8,11,1,100.00),(23,8,16,1,20.00),(24,8,17,1,1000.00),(25,9,20,1,1000.00),(26,9,19,1,1000.00),(27,9,10,1,1000.00),(28,10,16,10,20.00),(29,10,17,1,1000.00),(30,11,17,1,1000.00),(31,11,5,1,100.00),(32,12,8,1,1000.00),(33,12,10,1,1000.00),(34,12,19,1,1000.00),(35,13,6,1,200.00),(36,13,19,1,1000.00),(37,13,17,1,1000.00),(38,13,16,10,20.00),(39,14,20,5,1000.00),(40,15,19,2,1000.00),(41,16,6,6,200.00),(42,16,5,6,100.00),(43,17,6,1,200.00),(44,17,5,1,100.00),(45,17,8,1,1000.00),(46,17,10,1,1000.00),(47,18,19,1,1000.00),(48,18,17,1,1000.00),(49,19,22,5,600.00),(50,20,26,2,1000.00),(51,21,26,1,1000.00),(52,22,20,1,1000.00),(53,23,19,1,1000.00),(54,23,26,1,1000.00),(55,23,24,1,500.00),(56,23,23,1,600.00),(57,24,24,1,500.00),(58,25,24,1,500.00),(59,25,25,1,350.00),(60,25,26,1,1000.00),(61,26,24,1,500.00),(62,26,25,1,350.00),(63,26,26,1,1000.00),(64,27,10,1,1000.00),(65,27,8,1,1000.00),(66,27,5,1,100.00),(67,27,6,1,200.00),(68,28,6,1,200.00),(69,28,5,1,100.00),(70,28,8,1,1000.00),(71,29,6,1,200.00),(72,29,5,1,100.00),(73,29,8,1,1000.00),(74,30,6,6,200.00),(75,31,6,3,200.00),(76,32,6,2,200.00),(77,33,6,1,200.00),(78,34,16,1,20.00),(79,34,11,1,100.00),(80,35,5,1,100.00),(81,35,8,1,1000.00),(82,35,10,1,1000.00),(83,35,22,1,600.00),(84,35,20,1,1000.00),(85,36,6,1,200.00),(86,36,5,1,100.00),(87,36,8,1,1000.00),(88,37,6,1,200.00),(89,37,5,4,100.00),(90,38,5,1,100.00),(91,38,8,1,1000.00),(92,39,10,1,1000.00),(93,40,10,2,1000.00);
/*!40000 ALTER TABLE `pedido_itens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfil`
--

DROP TABLE IF EXISTS `perfil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfil` (
  `id_perfil` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id_perfil`),
  UNIQUE KEY `id_perfil` (`id_perfil`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfil`
--

LOCK TABLES `perfil` WRITE;
/*!40000 ALTER TABLE `perfil` DISABLE KEYS */;
/*!40000 ALTER TABLE `perfil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `perfis`
--

DROP TABLE IF EXISTS `perfis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `perfis` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_actualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `perfis`
--

LOCK TABLES `perfis` WRITE;
/*!40000 ALTER TABLE `perfis` DISABLE KEYS */;
/*!40000 ALTER TABLE `perfis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produto`
--

DROP TABLE IF EXISTS `produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `produto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int DEFAULT NULL,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `preco` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantidade` int NOT NULL DEFAULT '0',
  `imagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alerta_stock` int DEFAULT '5',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_actualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produto`
--

LOCK TABLES `produto` WRITE;
/*!40000 ALTER TABLE `produto` DISABLE KEYS */;
INSERT INTO `produto` VALUES (6,1,'Batido de frutas','',200.00,15,'uploads/produtos/1761083454_34cc15b5.jpg',5,'2025-10-21 21:50:54','2025-11-30 14:30:30'),(5,1,'Agua Clara','Para refrescar e matar a sede',100.00,80,'uploads/produtos/1761083395_2e0c6345.jpg',5,'2025-10-21 21:49:55','2025-11-30 15:31:48'),(8,3,'Salgadinhos','',1000.00,191,'uploads/produtos/1761957527_5b15c32bc1.jpg',5,'2025-10-21 21:54:36','2025-11-30 15:31:48'),(10,2,'Banana','',1000.00,100,'uploads/produtos/1761109623_d32f0adc.jpg',5,'2025-10-22 05:07:03','2025-12-04 14:31:18'),(21,4,'Bolo de Chocolate','Bolo saboroso para dar mais energia na vida',7500.00,120,'uploads/produtos/1762014597_0dee32d6f6.jpg',5,'2025-11-01 16:29:57','2025-11-01 16:29:57'),(11,2,'Morango',NULL,100.00,19,'uploads/produtos/1761315553_f4907205fb.jpg',5,'2025-10-24 14:17:10','2025-11-24 12:24:22'),(16,4,'Rebuçados',NULL,20.00,99,'uploads/produtos/1761315584_304d80d035.jpg',5,'2025-10-24 14:19:44','2025-11-24 12:24:22'),(17,3,'Frango',NULL,1000.00,29,'uploads/produtos/1761349294_02e7c3f60e.jpg',5,'2025-10-24 23:41:34','2025-11-01 16:22:57'),(19,3,'Arroz com Feijão Frango e Ovo',NULL,1000.00,45,'uploads/produtos/1761409707_810b482bb7.jpg',5,'2025-10-25 16:26:29','2025-11-01 16:55:21'),(20,3,'Arroz com salada',NULL,1000.00,42,'uploads/produtos/1761409671_0a8ecd6014.jpg',5,'2025-10-25 16:26:48','2025-11-24 12:35:16'),(22,1,'Fanta','Fresquinha',600.00,12,'uploads/produtos/1762014683_e5b96b0a42.jpg',5,'2025-11-01 16:31:23','2025-11-24 12:35:16'),(23,1,'Coca-cola','',600.00,49,'uploads/produtos/1762015051_ba82968efc.jpg',5,'2025-11-01 16:36:42','2025-11-01 16:55:21'),(24,4,'Gelado','Saboroso',500.00,31,'uploads/produtos/1762015294_4d2ea58aca.jpg',5,'2025-11-01 16:41:34','2025-11-01 20:29:06'),(25,3,'Pipoca','',350.00,23,'uploads/produtos/1762015363_cedb5da4c8.jpg',5,'2025-11-01 16:42:43','2025-11-01 20:29:06'),(26,3,'Hamburguer','',1000.00,34,'uploads/produtos/1762015475_5e01e3df21.jpg',5,'2025-11-01 16:44:35','2025-11-01 20:29:06'),(27,3,'Cachorro Quente','',1000.00,45,'uploads/produtos/1762015608_e9136aece6.jpg',5,'2025-11-01 16:46:48','2025-11-01 16:46:48');
/*!40000 ALTER TABLE `produto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registos_auditoria`
--

DROP TABLE IF EXISTS `registos_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registos_auditoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilizador_id` int DEFAULT NULL,
  `acao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detalhes` text COLLATE utf8mb4_unicode_ci,
  `endereco_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilizador_id` (`utilizador_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registos_auditoria`
--

LOCK TABLES `registos_auditoria` WRITE;
/*!40000 ALTER TABLE `registos_auditoria` DISABLE KEYS */;
/*!40000 ALTER TABLE `registos_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilizador`
--

DROP TABLE IF EXISTS `utilizador`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `utilizador` (
  `id` int NOT NULL AUTO_INCREMENT,
  `perfil_id` int NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` tinyint DEFAULT '1',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `data_actualizacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `perfil_id` (`perfil_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilizador`
--

LOCK TABLES `utilizador` WRITE;
/*!40000 ALTER TABLE `utilizador` DISABLE KEYS */;
INSERT INTO `utilizador` VALUES (1,2,'Leonel','leonel@fmail.com','1234','944898233',1,'2025-10-21 19:46:33','2025-10-21 19:46:33');
/*!40000 ALTER TABLE `utilizador` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venda`
--

DROP TABLE IF EXISTS `venda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int DEFAULT NULL,
  `id_vendedor` int DEFAULT NULL,
  `data_venda` datetime DEFAULT CURRENT_TIMESTAMP,
  `total` decimal(10,2) DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `troco` decimal(10,2) DEFAULT NULL,
  `estado` enum('finalizada','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'finalizada',
  PRIMARY KEY (`id`),
  KEY `id_pedido` (`id_pedido`),
  KEY `id_vendedor` (`id_vendedor`)
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venda`
--

LOCK TABLES `venda` WRITE;
/*!40000 ALTER TABLE `venda` DISABLE KEYS */;
INSERT INTO `venda` VALUES (1,4,3,'2025-10-30 00:04:19',1500.00,1500.00,0.00,'finalizada'),(2,5,3,'2025-10-30 00:25:16',1000.00,2000.00,1000.00,'finalizada'),(3,6,3,'2025-10-30 10:44:56',1500.00,2000.00,500.00,'finalizada'),(4,9,3,'2025-10-30 20:00:35',3000.00,3000.00,0.00,'finalizada'),(5,11,3,'2025-10-30 20:00:39',1100.00,2000.00,900.00,'finalizada'),(6,12,5,'2025-10-31 16:25:01',3000.00,5000.00,2000.00,'finalizada'),(7,13,8,'2025-10-31 16:29:18',2400.00,3000.00,600.00,'finalizada'),(8,14,3,'2025-11-01 02:06:10',5000.00,5000.00,0.00,'finalizada'),(9,15,3,'2025-11-01 02:07:57',2000.00,3000.00,1000.00,'finalizada'),(10,16,3,'2025-11-01 02:09:31',1800.00,2000.00,200.00,'finalizada'),(11,18,8,'2025-11-01 17:23:22',2000.00,5000.00,3000.00,'finalizada'),(12,17,8,'2025-11-01 17:25:05',2300.00,2300.00,0.00,'finalizada'),(13,19,8,'2025-11-01 17:33:17',3000.00,3000.00,0.00,'finalizada'),(14,24,8,'2025-11-01 17:57:29',500.00,500.00,0.00,'finalizada'),(15,26,8,'2025-11-01 21:29:17',1850.00,2000.00,150.00,'finalizada'),(16,27,8,'2025-11-03 11:57:56',2300.00,3000.00,700.00,'finalizada'),(17,28,7,'2025-11-04 10:55:33',1300.00,2000.00,700.00,'finalizada'),(18,29,5,'2025-11-17 08:57:48',1300.00,2000.00,700.00,'finalizada'),(19,31,5,'2025-11-17 09:09:21',600.00,1000.00,400.00,'finalizada'),(20,34,8,'2025-11-24 13:24:50',120.00,10000.00,9880.00,'finalizada'),(21,36,8,'2025-11-27 06:35:09',1300.00,2000.00,700.00,'finalizada'),(22,35,8,'2025-11-27 06:35:12',3700.00,5000.00,1300.00,'finalizada'),(23,37,3,'2025-11-30 15:31:08',600.00,1000.00,400.00,'finalizada'),(24,39,3,'2025-11-30 16:32:46',1000.00,1000.00,0.00,'finalizada'),(25,38,9,'2025-11-30 17:45:40',1100.00,1100.00,0.00,'finalizada'),(26,37,9,'2025-11-30 17:45:42',600.00,600.00,0.00,'finalizada'),(27,36,9,'2025-11-30 17:45:45',1300.00,1300.00,0.00,'finalizada'),(28,35,9,'2025-11-30 17:45:48',3700.00,3700.00,0.00,'finalizada'),(29,34,9,'2025-11-30 17:45:50',120.00,120.00,0.00,'finalizada'),(30,32,9,'2025-11-30 17:45:52',400.00,400.00,0.00,'finalizada'),(31,31,9,'2025-11-30 17:45:55',600.00,600.00,0.00,'finalizada'),(32,29,9,'2025-11-30 17:45:57',1300.00,1300.00,0.00,'finalizada'),(33,28,9,'2025-11-30 17:46:00',1300.00,1300.00,0.00,'finalizada'),(34,27,9,'2025-11-30 17:46:02',2300.00,2300.00,0.00,'finalizada'),(35,26,9,'2025-11-30 17:46:04',1850.00,1850.00,0.00,'finalizada'),(36,25,9,'2025-11-30 17:46:07',1850.00,1850.00,0.00,'finalizada'),(37,23,9,'2025-11-30 17:46:09',3100.00,3100.00,0.00,'finalizada'),(38,40,9,'2025-12-01 15:34:16',2000.00,5000.00,3000.00,'finalizada'),(39,16,8,'2025-12-05 17:41:53',1800.00,2000.00,200.00,'finalizada'),(40,15,8,'2025-12-05 17:43:12',2000.00,3000.00,1000.00,'finalizada'),(41,14,8,'2025-12-05 17:43:15',5000.00,5000.00,0.00,'finalizada'),(42,13,8,'2025-12-05 17:43:18',2400.00,3000.00,600.00,'finalizada'),(43,12,8,'2025-12-05 17:43:20',3000.00,5000.00,2000.00,'finalizada'),(44,11,5,'2025-12-07 01:35:02',1100.00,2000.00,900.00,'finalizada');
/*!40000 ALTER TABLE `venda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venda_item`
--

DROP TABLE IF EXISTS `venda_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venda_item` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venda_id` int NOT NULL,
  `produto_id` int NOT NULL,
  `quantidade` int NOT NULL DEFAULT '1',
  `preco_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `venda_id` (`venda_id`),
  KEY `produto_id` (`produto_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venda_item`
--

LOCK TABLES `venda_item` WRITE;
/*!40000 ALTER TABLE `venda_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `venda_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendedor`
--

DROP TABLE IF EXISTS `vendedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendedor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dataCriacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendedor`
--

LOCK TABLES `vendedor` WRITE;
/*!40000 ALTER TABLE `vendedor` DISABLE KEYS */;
INSERT INTO `vendedor` VALUES (3,'Ernesto Buka','buka@gmail.com','$2y$10$KWk9sY6t/5VBxEMyKQ.XU.LDBzBcvD9Rb1GQ5fvDMPj6uE.i/DWK2','uploads/vendedores/1761530077_10ab91eb58.jpg','2025-10-27 01:54:37','ativo','2025-11-26 16:32:42'),(5,'Fernando Nicolau','fernando@gmail.com','$2y$10$0Zz2qSN95QTXNJTpnF0ywORJxuxikzj9BlRjeG0re/PhQ/qZkO2A6','uploads/vendedores/1761851057_7b6ec1be61.jpg','2025-10-30 19:04:17','ativo','2025-11-26 16:32:42'),(6,'Baptista Muquixi','baptista@gmail.com','$2y$10$9S5akdO1.xkAIML3yTfaT.PCIwUOjTYhwzp5Fq6hpSw3EmNT1tzai','uploads/vendedores/1764221986_56e1ca5782.jpg','2025-10-30 19:06:50','ativo','2025-11-26 16:32:42'),(7,'Gouveia Gaspar','gouveia@gmail.com','$2y$10$xmsuwciAK4ic8/9KeicrX.2iurIJkeP.gCd/eU6xMRJ7KNrVtJTei','uploads/vendedores/1761851297_35c8a71ee2.jpg','2025-10-30 19:08:17','ativo','2025-11-26 16:32:42'),(8,'Leonel António','leonelantoniopandox693@gmail.com','$2y$10$4DwQB25tE.lH66.lPgClJe021ua7QLeTU1VWMDQVc.epYlsGwvPYu','uploads/vendedores/1761851353_3d9f560537.jpg','2025-10-30 19:09:13','ativo','2025-11-26 16:32:42'),(9,'Usuario Padrão','123@gmail.com','$2y$10$twXcLDCs9J.yeinlYd7YG.8xIRZHGZ.pFm0yWB23QqjX0RHs9G.sm','uploads/vendedores/1764222030_7ebb56fc71.jpg','2025-10-31 12:25:07','ativo','2025-11-26 16:32:42'),(10,'Ramos Panzo Vendedor','ramos@gmail.com','$2y$10$ByXZCtQ73Yv33yEG37CYcuUvwS52g/jqqobI.LG6CZqRYFGAehTmy','uploads/vendedores/1765670047_419109890a.jpg','2025-12-13 23:54:07','ativo','2025-12-14 00:54:07');
/*!40000 ALTER TABLE `vendedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'ipm_cantina'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-15 19:45:36
