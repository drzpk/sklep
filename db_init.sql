DROP DATABASE IF EXISTS sklep;
CREATE DATABASE IF NOT EXISTS sklep DEFAULT CHARACTER SET UTF8;
USE sklep;

DROP TABLE IF EXISTS towary;
DROP TABLE IF EXISTS kategorie;

CREATE TABLE IF NOT EXISTS kategorie (
	id_kategoria INT(11) NOT NULL AUTO_INCREMENT,
	nazwa VARCHAR(64) NOT NULL,
	PRIMARY KEY(id_kategoria)
);

CREATE TABLE IF NOT EXISTS towary (
	id_towar INT(11) NOT NULL AUTO_INCREMENT,
	nazwa VARCHAR(64) NOT NULL,
	cena INT(11) NOT NULL,
	id_kategoria INT(11) NOT NULL,
	PRIMARY KEY(id_towar),
	FOREIGN KEY(id_kategoria) REFERENCES kategorie(id_kategoria)
);

INSERT IGNORE INTO kategorie VALUES 
	(1, 'procesory'),
	(2, 'płyty główne'),
	(3, 'pamięci RAM'),
	(4, 'dyski twarde');

INSERT IGNORE INTO towary VALUES 
	(1, 'Intel Core i5-6300', 990, 1),
	(2, 'Gigabyte HN37473-USB3', 350, 2),
	(3, 'Intel Pentium G630', 199, 1),
	(4, 'Seagate Barracuda 3GGJ382', 245, 4),
	(5, 'WD Black 3872KM.2', 450, 4),
	(6, 'Corsair 4GBJW8374', 200, 3);
	
	
-- SELECT t.id_towar AS '1', t.nazwa AS '2', t.cena AS '3', k.nazwa AS '4' FROM towary AS t JOIN kategorie AS k ON t.id_kategoria=k.id_kategoria ORDER BY k.nazwa;