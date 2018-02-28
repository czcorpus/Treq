-- phpMyAdmin SQL Dump
-- version 4.3.10
-- http://www.phpmyadmin.net
--
-- Počítač: trnka.korpus.cz
-- Vytvořeno: Pát 11. zář 2015, 15:53
-- Verze serveru: 10.0.21-MariaDB-1~precise-log
-- Verze PHP: 5.3.10-1ubuntu3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databáze: `ic_slovnik`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `data_packs`
--
-- Vytvořeno: Pát 24. dub 2015, 17:08
-- Poslední změna: Pát 11. zář 2015, 12:25
--

DROP TABLE IF EXISTS `data_packs`;
CREATE TABLE IF NOT EXISTS `data_packs` (
  `id` varchar(20) COLLATE utf8_bin NOT NULL,
  `name` varchar(20) COLLATE utf8_bin NOT NULL,
  `trans` varchar(2) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Vyprázdnit tabulku před vkládáním `data_packs`
--

TRUNCATE TABLE `data_packs`;
--
-- Vypisuji data pro tabulku `data_packs`
--

INSERT INTO `data_packs` (`id`, `name`, `trans`) VALUES
('CORE', 'Jádro', 'cs'),
('ACQUIS', 'Acquis', 'cs'),
('PRESSEUROP', 'Presseurop', 'cs'),
('SYNDICATE', 'Syndicate', 'cs'),
('CORE', 'Core', 'en'),
('ACQUIS', 'Acquis', 'en'),
('PRESSEUROP', 'Presseurop', 'en'),
('SYNDICATE', 'Syndicate', 'en'),
('SUBTITLES', 'Titulky', 'cs'),
('SUBTITLES', 'Subtitles', 'en'),
('EUROPARL', 'Europarl', 'cs'),
('EUROPARL', 'Europarl', 'en');

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `data_packs`
--
ALTER TABLE `data_packs`
  ADD PRIMARY KEY (`id`,`trans`), ADD KEY `name` (`name`,`trans`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `data_packs`
--
ALTER TABLE `data_packs`
AUTO_INCREMENT=9;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
