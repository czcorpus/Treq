-- phpMyAdmin SQL Dump
-- version 4.3.10
-- http://www.phpmyadmin.net
--
-- Počítač: trnka.korpus.cz
-- Vytvořeno: Sob 25. bře 2017, 22:12
-- Verze serveru: 10.0.22-MariaDB-1~precise-log
-- Verze PHP: 5.4.45-2+deb.sury.org~precise+2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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
-- Struktura tabulky `Acquis_cs-bg`
--

CREATE TABLE IF NOT EXISTS `Acquis_cs-bg` (
  `freq` int(11) NOT NULL,
  `primary` varchar(100) COLLATE utf8_bin NOT NULL,
  `other` varchar(100) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Klíče pro exportované tabulky
--

--
-- Klíče pro tabulku `Acquis_cs-bg`
--
ALTER TABLE `Acquis_cs-bg`
  ADD PRIMARY KEY (`primary`,`other`), ADD KEY `primary_2` (`primary`), ADD KEY `other` (`other`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
