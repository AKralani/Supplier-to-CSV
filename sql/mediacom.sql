USE csv;
CREATE TABLE mediacom (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    Lieferantenname VARCHAR(255),
    Herstellerartikelnummer VARCHAR(255),
    EAN VARCHAR(255),
    Artikelnummer VARCHAR(255),
    Titel VARCHAR(255),
    Preis VARCHAR(255),
    Verfuegbar VARCHAR(255),
    Verfuegbar_Tage VARCHAR(255),
    Lieferantenbestand VARCHAR(255),
    Streckgengeschaeft VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);