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
	image MEDIUMBLOB,
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

#DODANIE KATEGORII
INSERT IGNORE INTO kategorie VALUES 
	(1, 'procesory'),
	(2, 'płyty główne'),
	(3, 'pamięci RAM'),
	(4, 'dyski twarde'),
	(5, 'obudowy'),
	(6, 'zasilacze');

#DODANIE TOWARÓW
INSERT IGNORE INTO towary (id_towar, nazwa, cena, id_kategoria) VALUES 
	(1, 'Intel Core i5-6300', 990, 1),
	(2, 'Gigabyte HN37473-USB3', 350, 2),
	(3, 'Intel Pentium G630', 199, 1),
	(4, 'Seagate Barracuda 3GGJ382', 245, 4),
	(5, 'WD Black 3872KM.2', 450, 4),
	(6, 'Corsair 4GBJW8374', 200, 3),
	(7, 'COOLERMASTER G750M 750W', 179, 6),
	(8, 'Corsair Builder Series CX 600W', 319, 6),
	(9, 'COOLERMASTER THUNDER 500W', 109, 6),
	(10, 'Modecom Volcano 750W', 259, 6),
	(11, 'Silentium PC RG1W SPC153', 159, 5),
	(12, 'Silentium PC M10', 99, 5),
	(13, 'Zalman Z1 NEO, 3 Went, USB 3.0', 159, 5);

#DODANIE OPISÓW
UPDATE towary SET opis='Jeden z najwydajniejszych procesorów dostępnych na rynku!'
	WHERE id_towar=1;
UPDATE towary SET opis='Nowoczesny dysk twardy oferujący ponadprzeciętną wydajność, w porównaniu do konkurencji'
	WHERE id_towar=4;
UPDATE towary SET opis='Z tą obudową Twój komputer nabierze niepowtarzalnej klasy!'
	WHERE id_towar=8;
UPDATE towary SET opis='Bardzo mocny zasilacz złożony ręcznie przez małe i wprawne chińskie rączki.'
	WHERE id_towar=10;
UPDATE towary SET opis='Krążą legendy, że tej obudowy używał sam Jan III Sobieski.'
	WHERE id_towar=13;

#DODANIE DOMYŚLNEGO UŻYTKOWNIKA (hasło: root)
INSERT IGNORE INTO users VALUES 
	(1, 'root', '4813494d137e1631bba301d5acab6e7bb7aa74ce1185d456565ef51d737677b2', TRUE);
