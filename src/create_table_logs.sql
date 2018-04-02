
--
-- База данных: `logs_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE `logs` (
  `id` varchar(255) NOT NULL,
  `timestamp` varchar(32) NOT NULL,
  `level` varchar(32) NOT NULL,
  `priority` int(11) NOT NULL,
  `lifecycle_token` varchar(32) NOT NULL,
  `parent_lifecycle_token` varchar(32),
  `message` text NOT NULL,
  `context` text NOT NULL,
  FOREIGN KEY (`parent_lifecycle_token`) REFERENCES logs(`lifecycle_token`),
  PRIMARY KEY (`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);