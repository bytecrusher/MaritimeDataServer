CREATE TABLE IF NOT EXISTS accountActivationTokens (
    userId INT NOT NULL PRIMARY KEY,
    tokenHash CHAR(64) NOT NULL,
    expiresAt BIGINT NOT NULL
) ENGINE=InnoDB;
