DROP DATABASE IF EXISTS sklep;
CREATE DATABASE IF NOT EXISTS sklep DEFAULT CHARACTER SET UTF8;
USE sklep;

DROP TABLE IF EXISTS towary;
DROP TABLE IF EXISTS kategorie;
DROP TABLE IF EXISTS users;

#KATEGORIE
CREATE TABLE IF NOT EXISTS kategorie (
	id_kategoria INT(11) NOT NULL AUTO_INCREMENT,
	nazwa VARCHAR(64) NOT NULL,
	PRIMARY KEY(id_kategoria)
);

#TOWARY
CREATE TABLE IF NOT EXISTS towary (
	id_towar INT(11) NOT NULL AUTO_INCREMENT,
	nazwa VARCHAR(64) NOT NULL,
	opis VARCHAR(256), 
	cena INT(11) NOT NULL,
	id_kategoria INT(11) NOT NULL,
	image BLOB,
	PRIMARY KEY(id_towar),
	FOREIGN KEY(id_kategoria) REFERENCES kategorie(id_kategoria)
);

#UŻYTKOWNICY
CREATE TABLE IF NOT EXISTS users (
	id_user INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(32) NOT NULL,
	password CHAR(64) NOT NULL,
	admin BOOLEAN DEFAULT FALSE,
	PRIMARY KEY(id_user)
);

SET NAMES UTF8;

#DODANIE PRZYKŁADOWYCH KATEGORII
INSERT IGNORE INTO kategorie VALUES 
	(1, 'procesory'),
	(2, 'płyty główne'),
	(3, 'pamięci RAM'),
	(4, 'dyski twarde');

#DODANIE PRZYKŁADOWYCH TOWARÓW
INSERT IGNORE INTO towary (id_towar, nazwa, cena, id_kategoria) VALUES 
	(1, 'Intel Core i5-6300', 990, 1),
	(2, 'Gigabyte HN37473-USB3', 350, 2),
	(3, 'Intel Pentium G630', 199, 1),
	(4, 'Seagate Barracuda 3GGJ382', 245, 4),
	(5, 'WD Black 3872KM.2', 450, 4),
	(6, 'Corsair 4GBJW8374', 200, 3);

#DODANIE DOMYŚLNEGO UŻYTKOWNIKA (hasło: root)
INSERT IGNORE INTO users VALUES 
	(1, 'root', '4813494d137e1631bba301d5acab6e7bb7aa74ce1185d456565ef51d737677b2', TRUE);
