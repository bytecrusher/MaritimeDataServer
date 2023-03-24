-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Erstellungszeit: 24. Dez 2022 um 13:12
-- Server-Version: 8.0.31
-- PHP-Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `mds`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `boardconfig`
--

CREATE TABLE `boardconfig` (
  `id` int NOT NULL,
  `owner_userid` int DEFAULT NULL,
  `macaddress` varchar(30) NOT NULL,
  `name` varchar(30) DEFAULT NULL,
  `location` varchar(20) DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  `performupdate` tinyint DEFAULT '0',
  `firmwareversion` varchar(10) DEFAULT NULL,
  `alarmOnUnavailable` tinyint DEFAULT '0',
  `updateDataTimer` int DEFAULT NULL,
  `boardtypeid` int DEFAULT NULL,
  `ttn_app_id` text NOT NULL,
  `ttn_dev_id` text CHARACTER SET utf16 COLLATE utf16_general_ci NOT NULL,
  `onDashboard` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `boardtype`
--

CREATE TABLE `boardtype` (
  `id` int NOT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(30) NOT NULL,
  `image` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `securitytokens`
--

CREATE TABLE `securitytokens` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `securitytoken` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensorconfig`
--

CREATE TABLE `sensorconfig` (
  `id` int NOT NULL,
  `boardid` int DEFAULT NULL,
  `sensorAddress` varchar(20) DEFAULT NULL,
  `name` varchar(20) DEFAULT NULL,
  `description` varchar(30) DEFAULT NULL,
  `typid` int DEFAULT NULL,
  `locationOfMeasurement` varchar(20) DEFAULT NULL,
  `nameValue1` varchar(10) DEFAULT NULL,
  `Value1GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value1GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value1GaugeRedAreaLowValue` int NOT NULL,
  `Value1GaugeRedAreaLowColor` text NOT NULL,
  `Value1GaugeRedAreaHighValue` int NOT NULL,
  `Value1GaugeRedAreaHighColor` text NOT NULL,
  `Value1GaugeNormalAreaColor` text NOT NULL,
  `Value1DashboardOrdnerNr` int DEFAULT '1',
  `Value1onDashboard` tinyint(1) NOT NULL,
  `nameValue2` varchar(10) DEFAULT NULL,
  `Value2GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value2GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value2GaugeRedAreaLowValue` int NOT NULL,
  `Value2GaugeRedAreaLowColor` text NOT NULL,
  `Value2GaugeRedAreaHighValue` int NOT NULL,
  `Value2GaugeRedAreaHighColor` text NOT NULL,
  `Value2GaugeNormalAreaColor` text NOT NULL,
  `Value2DashboardOrdnerNr` int DEFAULT '1',
  `Value2onDashboard` tinyint(1) NOT NULL,
  `nameValue3` varchar(10) DEFAULT NULL,
  `Value3GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value3GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value3GaugeRedAreaLowValue` int NOT NULL,
  `Value3GaugeRedAreaLowColor` text NOT NULL,
  `Value3GaugeRedAreaHighValue` int NOT NULL,
  `Value3GaugeRedAreaHighColor` text NOT NULL,
  `Value3GaugeNormalAreaColor` text NOT NULL,
  `Value3DashboardOrdnerNr` int DEFAULT '1',
  `Value3onDashboard` tinyint(1) NOT NULL,
  `nameValue4` varchar(10) DEFAULT NULL,
  `Value4GaugeMinValue` int NOT NULL DEFAULT '0',
  `Value4GaugeMaxValue` int NOT NULL DEFAULT '20',
  `Value4GaugeRedAreaLowValue` int NOT NULL,
  `Value4GaugeRedAreaLowColor` text NOT NULL,
  `Value4GaugeRedAreaHighValue` int NOT NULL,
  `Value4GaugeRedAreaHighColor` text NOT NULL,
  `Value4GaugeNormalAreaColor` text NOT NULL,
  `Value4DashboardOrdnerNr` int DEFAULT '1',
  `Value4onDashboard` tinyint(1) NOT NULL,
  `onDashboard` tinyint DEFAULT '0',
  `ttn_payload_id` int DEFAULT NULL COMMENT 'Position in der TTN Payload.',
  `NrOfUsedSensors` int NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensordata`
--

CREATE TABLE `sensordata` (
  `id` int UNSIGNED NOT NULL,
  `sensorid` int NOT NULL,
  `value1` varchar(20) NOT NULL,
  `value2` varchar(20) DEFAULT NULL,
  `value3` varchar(10) DEFAULT NULL,
  `value4` varchar(10) DEFAULT NULL,
  `val_date` varchar(10) NOT NULL COMMENT 'When was record added.',
  `val_time` varchar(10) NOT NULL COMMENT 'When was record added.',
  `reading_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `transmissionpath` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sensortypes`
--

CREATE TABLE `sensortypes` (
  `id` int NOT NULL,
  `name` varchar(10) NOT NULL,
  `siUnitVal1` varchar(10) NOT NULL,
  `siUnitVal2` varchar(10) NOT NULL,
  `siUnitVal3` varchar(10) NOT NULL,
  `siUnitVal4` varchar(10) NOT NULL,
  `oneWireFamilyCode` varchar(2) NOT NULL,
  `description` varchar(20) NOT NULL,
  `MaxNrOfValues` int NOT NULL COMMENT 'How many values are expected.',
  `hasAddress` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Tabellenstruktur für Tabelle `ttnDataLoraBoatMonitor`
--

CREATE TABLE `ttnDataLoraBoatMonitor` (
  `id` int NOT NULL,
  `datetime` datetime NOT NULL,
  `app_id` text NOT NULL,
  `dev_id` text NOT NULL,
  `ttn_timestamp` text NOT NULL,
  `gtw_id` text NOT NULL,
  `gtw_rssi` float NOT NULL,
  `gtw_snr` float NOT NULL,
  `dev_raw_payload` text NOT NULL,
  `dev_value_1` float NOT NULL,
  `dev_value_2` float NOT NULL,
  `dev_value_3` float NOT NULL,
  `dev_value_4` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `firstname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `lastname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint NOT NULL DEFAULT '0',
  `usergroup_admin` tinyint NOT NULL DEFAULT '0',
  `passwordcode` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `passwordcode_time` date DEFAULT NULL,
  `dashboardUpdateInterval` int NOT NULL DEFAULT '15'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indizes für die Tabelle `ttnDataLoraBoatMonitor`
--
ALTER TABLE `ttnDataLoraBoatMonitor`
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `boardtype`
--
ALTER TABLE `boardtype`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `securitytokens`
--
ALTER TABLE `securitytokens`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensorconfig`
--
ALTER TABLE `sensorconfig`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensordata`
--
ALTER TABLE `sensordata`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `sensortypes`
--
ALTER TABLE `sensortypes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT für Tabelle `ttnDataLoraBoatMonitor`
--
ALTER TABLE `ttnDataLoraBoatMonitor`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
