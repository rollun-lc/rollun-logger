
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
  `message` text NOT NULL,
  `context` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8
ADD PRIMARY KEY (`id`);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);