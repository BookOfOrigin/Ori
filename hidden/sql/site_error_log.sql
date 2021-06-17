CREATE TABLE `site_error_log` (
  `ID` int(11) NOT NULL,
  `LineNumber` text DEFAULT NULL,
  `ErrorString` text DEFAULT NULL,
  `File` text DEFAULT NULL,
  `Line` longtext DEFAULT NULL,
  `Error` longtext DEFAULT NULL,
  `Server` varchar(256) DEFAULT NULL,
  `HashValue` varchar(254) NOT NULL,
  `Count` int(1) NOT NULL DEFAULT 0,
  `Deleted` int(1) NOT NULL DEFAULT 0,
  `Genesis` timestamp NOT NULL DEFAULT current_timestamp(),
  `Mutation` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `site_error_log`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `error_hash` (`HashValue`);

ALTER TABLE `site_error_log`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;