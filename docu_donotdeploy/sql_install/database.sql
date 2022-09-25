-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Erstellungszeit: 11. Sep 2022 um 00:44
-- Server-Version: 10.4.21-MariaDB
-- PHP-Version: 8.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `k51971_mds-demo`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `boardconfig`
--

CREATE TABLE `boardconfig` (
  `id` int(11) NOT NULL,
  `owner_userid` int(11) DEFAULT NULL,
  `macaddress` varchar(30) NOT NULL,
  `name` varchar(30) DEFAULT NULL,
  `location` varchar(20) DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  `performupdate` tinyint(1) DEFAULT 0,
  `firmwareversion` varchar(10) DEFAULT NULL,
  `alarmOnUnavailable` tinyint(1) DEFAULT 0,
  `updateDataTimer` int(11) DEFAULT NULL,
  `boardtypeid` int(10) DEFAULT NULL,
  `ttn_app_id` text NOT NULL,
  `ttn_dev_id` text CHARACTER SET utf16 NOT NULL,
  `onDashboard` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `boardtype`
--

CREATE TABLE `boardtype` (
  `id` int(10) NOT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(30) NOT NULL,
  `image` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `securitytokens`
--

CREATE TABLE `securitytokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `identifier` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `securitytoken` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensorconfig`
--

CREATE TABLE `sensorconfig` (
  `id` int(11) NOT NULL,
  `boardid` int(11) DEFAULT NULL,
  `sensorAddress` varchar(20) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `typid` int(11) DEFAULT NULL,
  `locationOfMeasurement` varchar(20) DEFAULT NULL,
  `nameValue1` varchar(10) DEFAULT NULL,
  `Value1GaugeMinValue` int(11) NOT NULL DEFAULT 0,
  `Value1GaugeMaxValue` int(11) NOT NULL DEFAULT 20,
  `Value1GaugeRedAreaLowValue` int(11) NOT NULL,
  `Value1GaugeRedAreaLowColor` text NOT NULL,
  `Value1GaugeRedAreaHighValue` int(11) NOT NULL,
  `Value1GaugeRedAreaHighColor` text NOT NULL,
  `Value1GaugeNormalAreaColor` text NOT NULL,
  `Value1DashboardOrdnerNr` int(11) NOT NULL,
  `nameValue2` varchar(10) DEFAULT NULL,
  `Value2GaugeMinValue` int(11) NOT NULL DEFAULT 0,
  `Value2GaugeMaxValue` int(11) NOT NULL DEFAULT 20,
  `Value2GaugeRedAreaLowValue` int(11) NOT NULL,
  `Value2GaugeRedAreaLowColor` text NOT NULL,
  `Value2GaugeRedAreaHighValue` int(11) NOT NULL,
  `Value2GaugeRedAreaHighColor` text NOT NULL,
  `Value2GaugeNormalAreaColor` text NOT NULL,
  `Value2DashboardOrdnerNr` int(11) NOT NULL,
  `nameValue3` varchar(10) DEFAULT NULL,
  `Value3GaugeMinValue` int(11) NOT NULL DEFAULT 0,
  `Value3GaugeMaxValue` int(11) NOT NULL DEFAULT 20,
  `Value3GaugeRedAreaLowValue` int(11) NOT NULL,
  `Value3GaugeRedAreaLowColor` text NOT NULL,
  `Value3GaugeRedAreaHighValue` int(11) NOT NULL,
  `Value3GaugeRedAreaHighColor` text NOT NULL,
  `Value3GaugeNormalAreaColor` text NOT NULL,
  `Value3DashboardOrdnerNr` int(11) NOT NULL,
  `nameValue4` varchar(10) DEFAULT NULL,
  `Value4GaugeMinValue` int(11) NOT NULL DEFAULT 0,
  `Value4GaugeMaxValue` int(11) NOT NULL DEFAULT 20,
  `Value4GaugeRedAreaLowValue` int(11) NOT NULL,
  `Value4GaugeRedAreaLowColor` text NOT NULL,
  `Value4GaugeRedAreaHighValue` int(11) NOT NULL,
  `Value4GaugeRedAreaHighColor` text NOT NULL,
  `Value4GaugeNormalAreaColor` text NOT NULL,
  `Value4DashboardOrdnerNr` int(11) NOT NULL,
  `onDashboard` tinyint(1) DEFAULT 0,
  `ttn_payload_id` int(11) DEFAULT NULL COMMENT 'Position in der TTN Payload.',
  `NrOfUsedSensors` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensordata`
--

CREATE TABLE `sensordata` (
  `id` int(10) UNSIGNED NOT NULL,
  `sensorid` int(11) NOT NULL,
  `value1` varchar(20) NOT NULL,
  `value2` varchar(20) DEFAULT NULL,
  `value3` varchar(10) DEFAULT NULL,
  `value4` varchar(10) DEFAULT NULL,
  `val_date` varchar(10) NOT NULL COMMENT 'When was record added.',
  `val_time` varchar(10) NOT NULL COMMENT 'When was record added.',
  `reading_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `transmissionpath` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensortypes`
--

CREATE TABLE `sensortypes` (
  `id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL,
  `siUnitVal1` varchar(10) NOT NULL,
  `siUnitVal2` varchar(10) NOT NULL,
  `siUnitVal3` varchar(10) NOT NULL,
  `siUnitVal4` varchar(10) NOT NULL,
  `oneWireFamilyCode` varchar(2) NOT NULL,
  `description` varchar(20) NOT NULL,
  `MaxNrOfValues` int(11) NOT NULL COMMENT 'How many values are expected.',
  `hasAddress` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Daten für Tabelle `sensortypes`
--

INSERT INTO `sensortypes` (`id`, `name`, `siUnitVal1`, `siUnitVal2`, `siUnitVal3`, `siUnitVal4`, `oneWireFamilyCode`, `description`, `MaxNrOfValues`, `hasAddress`) VALUES
(1, 'DS18B20', '&deg;C', '', '', '', '28', 'Temperature', 1, 1),
(2, 'DS2438', '&deg;C', 'V', 'A', '', '26', 'Battery monitor', 3, 1),
(3, 'ADC', 'V', 'V', 'V', 'V', '', 'input from ADC', 4, 0),
(4, 'Digital', '1/0', '1/0', '1/0', '1/0', '', 'input from Digital', 4, 0),
(5, 'BME280', '&deg;C', '%', 'mbar', '&deg;C', '', 'Temp, Hum, Pres, Dew', 4, 0),
(6, 'GPS', 'Lat', 'Lon', 'Alt', 'Spd', '', 'Coorinates', 4, 0),
(7, 'Lora', 'Gtw', 'db', '', '', '', 'TTN data', 3, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `usergroup_admin` tinyint(1) NOT NULL DEFAULT 0,
  `passwordcode` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `passwordcode_time` date DEFAULT NULL,
  `dashboardUpdateInterval` int(11) NOT NULL DEFAULT 15
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `boardconfig`
--
ALTER TABLE `boardconfig`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `macaddress` (`macaddress`),
  ADD KEY `owner_userid` (`owner_userid`),
  ADD KEY `boardconfig_ibfk_2_idx` (`boardtypeid`);

--
-- Indizes für die Tabelle `boardtype`
--
ALTER TABLE `boardtype`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `securitytokens`
--
ALTER TABLE `securitytokens`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `sensorconfig`
--
ALTER TABLE `sensorconfig`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sensorid` (`sensorAddress`),
  ADD KEY `typid` (`typid`),
  ADD KEY `boardid` (`boardid`);

--
-- Indizes für die Tabelle `sensordata`
--
ALTER TABLE `sensordata`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sensorid` (`sensorid`);

--
-- Indizes für die Tabelle `sensortypes`
--
ALTER TABLE `sensortypes`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `boardconfig`
--
ALTER TABLE `boardconfig`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `boardtype`
--
ALTER TABLE `boardtype`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `securitytokens`
--
ALTER TABLE `securitytokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensorconfig`
--
ALTER TABLE `sensorconfig`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensordata`
--
ALTER TABLE `sensordata`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensortypes`
--
ALTER TABLE `sensortypes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `boardconfig`
--
ALTER TABLE `boardconfig`
  ADD CONSTRAINT `boardconfig_ibfk_1` FOREIGN KEY (`owner_userid`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `boardconfig_ibfk_2` FOREIGN KEY (`boardtypeid`) REFERENCES `boardtype` (`id`);

--
-- Constraints der Tabelle `sensorconfig`
--
ALTER TABLE `sensorconfig`
  ADD CONSTRAINT `sensorconfig_ibfk_2` FOREIGN KEY (`typid`) REFERENCES `sensortypes` (`id`),
  ADD CONSTRAINT `sensorconfig_ibfk_4` FOREIGN KEY (`boardid`) REFERENCES `boardconfig` (`id`);

--
-- Constraints der Tabelle `sensordata`
--
ALTER TABLE `sensordata`
  ADD CONSTRAINT `sensorData_ibfk_2` FOREIGN KEY (`sensorid`) REFERENCES `sensorconfig` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
