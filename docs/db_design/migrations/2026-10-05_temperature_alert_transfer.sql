CREATE TABLE IF NOT EXISTS temperatureChannelTransfers (
    sourceChannelId INT NOT NULL PRIMARY KEY,
    targetChannelId INT NOT NULL,
    sourceSnapshot TEXT NOT NULL,
    targetSnapshot TEXT NOT NULL
) ENGINE=InnoDB;
