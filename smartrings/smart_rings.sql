-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Дек 25 2024 г., 22:38
-- Версия сервера: 10.4.28-MariaDB
-- Версия PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `smart_rings`
--

-- --------------------------------------------------------

--
-- Структура таблицы `authorization`
--

CREATE TABLE `authorization` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `authorization`
--

INSERT INTO `authorization` (`id`, `patient_id`, `username`, `password_hash`) VALUES
(23, 24, '1234', '$2y$10$00wo6jIaMwc/G.muhcIFW.RaBaXc2IV4su4q/qQwQa2iyR4yU050q'),
(24, NULL, '1111', '$2y$10$H/A//ZxMydYKD4HUXRwlPu2InSLUM8Vx5jDhw33Zt8K6eabie9an2'),
(25, 25, 'admin', '$2y$10$erpDT.d.Dz3.4qe5EmAHHeYJJmgbcZ.qWygZPT2akPGbp6EN.otmW');

-- --------------------------------------------------------

--
-- Структура таблицы `data`
--

CREATE TABLE `data` (
  `data_id` int(11) NOT NULL,
  `ring_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `heart_rate` int(11) DEFAULT NULL,
  `blood_oxygen_level` decimal(5,2) DEFAULT NULL,
  `sleep_quality` int(11) DEFAULT NULL,
  `stress_level` int(11) DEFAULT NULL,
  `respiratory_rate` int(11) DEFAULT NULL,
  `steps_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `data`
--

INSERT INTO `data` (`data_id`, `ring_id`, `timestamp`, `heart_rate`, `blood_oxygen_level`, `sleep_quality`, `stress_level`, `respiratory_rate`, `steps_count`) VALUES
(2, 52, '2024-12-24 18:00:03', 150, 90.00, 10, 60, 15, 5001),
(3, 53, '2024-12-20 13:51:04', 75, 98.00, 1, 100, 1, 200000),
(4, 54, '2024-12-24 17:59:45', 75, 98.00, 80, 30, 15, 10000);

-- --------------------------------------------------------

--
-- Структура таблицы `limit_values`
--

CREATE TABLE `limit_values` (
  `message_type_id` int(11) NOT NULL,
  `message_type` varchar(100) NOT NULL,
  `metric` varchar(50) NOT NULL,
  `min_value` int(11) DEFAULT NULL,
  `max_value` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `limit_values`
--

INSERT INTO `limit_values` (`message_type_id`, `message_type`, `metric`, `min_value`, `max_value`) VALUES
(1, 'Высокий пульс', 'heart_rate', NULL, 100.00),
(2, 'Низкий пульс', 'heart_rate', 50, NULL),
(3, 'Низкий уровень кислорода', 'blood_oxygen_level', 94, NULL),
(4, 'Плохое качество сна', 'sleep_quality', 50, NULL),
(5, 'Высокий уровень стресса', 'stress_level', NULL, 80.00),
(6, 'Высокая дыхательная частота', 'respiratory_rate', NULL, 20.00),
(7, 'Низкая дыхательная частота', 'respiratory_rate', 10, NULL),
(8, 'Недостаточное количество шагов', 'steps_count', 5000, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `alert_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `data_id` int(11) DEFAULT NULL,
  `message_type_id` int(11) DEFAULT NULL,
  `alert_message` varchar(200) DEFAULT NULL,
  `message_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `messages`
--

INSERT INTO `messages` (`alert_id`, `patient_id`, `data_id`, `message_type_id`, `alert_message`, `message_timestamp`, `resolved`) VALUES
(111, 24, 2, 1, 'Высокий пульс - Показатель heart_rate выше нормы: 150.00', '2024-12-25 10:32:13', 0),
(112, 24, 2, 4, 'Плохое качество сна - Показатель sleep_quality ниже нормы: 10.00', '2024-12-25 10:32:13', 0),
(113, 24, 2, 3, 'Низкий уровень кислорода - Показатель blood_oxygen_level ниже нормы: 90.00', '2024-12-25 10:32:13', 0),
(114, 25, 3, 7, 'Низкая дыхательная частота - Показатель respiratory_rate ниже нормы: 1.00', '2024-12-25 10:32:13', 0),
(115, 25, 3, 5, 'Высокий уровень стресса - Показатель stress_level выше нормы: 100.00', '2024-12-25 10:32:13', 0),
(116, 25, 3, 4, 'Плохое качество сна - Показатель sleep_quality ниже нормы: 1.00', '2024-12-25 10:32:13', 0);

-- --------------------------------------------------------

--
-- Структура таблицы `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `patients`
--

INSERT INTO `patients` (`patient_id`, `name`, `surname`, `gender`, `date_of_birth`, `phone`, `email`, `address`) VALUES
(24, 'petya', 'kholkin', 'Мужской', '2004-11-27', '+79672449731', 'p@p.com', 's'),
(25, 'po', 'e', 'Мужской', '2024-12-04', '+79672449000', 'L@l.com', 'd'),
(26, 'уу', 'уу', 'Мужской', '2024-12-10', '+79672449731', 'F@f.com', 'w');

-- --------------------------------------------------------

--
-- Структура таблицы `rings`
--

CREATE TABLE `rings` (
  `ring_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `activated_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `rings`
--

INSERT INTO `rings` (`ring_id`, `patient_id`, `serial_number`, `model`, `activated_date`) VALUES
(52, 24, 'SN3849', 'Model X', '2024-12-13'),
(53, 25, 'SN3254', 'Model X', '2024-12-17'),
(54, 26, 'SN3783', 'Model X', '2024-12-24');

-- --------------------------------------------------------

--
-- Структура таблицы `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_timestamp` datetime NOT NULL,
  `logout_timestamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `sessions`
--

INSERT INTO `sessions` (`session_id`, `user_id`, `login_timestamp`, `logout_timestamp`) VALUES
(1, 25, '2024-12-25 11:43:06', '2024-12-25 11:43:16'),
(2, 25, '2024-12-25 11:43:24', NULL),
(3, 25, '2024-12-25 15:39:48', NULL),
(4, 25, '2024-12-25 19:30:42', '2024-12-25 20:04:45'),
(5, 25, '2024-12-25 20:06:14', NULL),
(6, 25, '2024-12-25 21:36:22', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `authorization`
--
ALTER TABLE `authorization`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `patient_id_unque` (`patient_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Индексы таблицы `data`
--
ALTER TABLE `data`
  ADD PRIMARY KEY (`data_id`),
  ADD KEY `bracelet_id` (`ring_id`);

--
-- Индексы таблицы `limit_values`
--
ALTER TABLE `limit_values`
  ADD PRIMARY KEY (`message_type_id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `measurement_id` (`data_id`),
  ADD KEY `alert_type_id` (`message_type_id`);

--
-- Индексы таблицы `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`);

--
-- Индексы таблицы `rings`
--
ALTER TABLE `rings`
  ADD PRIMARY KEY (`ring_id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Индексы таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `authorization`
--
ALTER TABLE `authorization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT для таблицы `data`
--
ALTER TABLE `data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `limit_values`
--
ALTER TABLE `limit_values`
  MODIFY `message_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT для таблицы `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT для таблицы `rings`
--
ALTER TABLE `rings`
  MODIFY `ring_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT для таблицы `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `authorization`
--
ALTER TABLE `authorization`
  ADD CONSTRAINT `fk_auth_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `data`
--
ALTER TABLE `data`
  ADD CONSTRAINT `data_ibfk_1` FOREIGN KEY (`ring_id`) REFERENCES `rings` (`ring_id`);

--
-- Ограничения внешнего ключа таблицы `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`data_id`) REFERENCES `data` (`data_id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`message_type_id`) REFERENCES `limit_values` (`message_type_id`);

--
-- Ограничения внешнего ключа таблицы `rings`
--
ALTER TABLE `rings`
  ADD CONSTRAINT `rings_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Ограничения внешнего ключа таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `authorization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
