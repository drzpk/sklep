<?php
require_once 'skeleton.php';

class Index extends Skeleton {

	public function init() {
		$this->init_db();
		$this->addLink('Lista przedmiotów', 'list_view.php');
	}

	public function getTitle() {
		return 'Strona główna';
	}

	public function drawNav() {
		//brak
	}

	public function shouldDrawNav() {
		return false;
	}

	public function drawSection() {
?>
		<p>To jest strona główna sklepu komputerowego. Jest to projekt stworzony na potrzeby przedmiotu
		"administracja bazami danych". Z racji ograniczonego budżetu nie posiada ona stopki, 
		więc podstawowe informacje zostaną wypisane poniżej: </p>
		<ul>
			<li>Język: PHP <?php echo phpversion(); ?></li>
			<li>Bilioteka CSS: Materialize v0.98.0</li>
			<li>Biblioteka JS: jQuery 3.1.1</li>
			<li>Liczba linijek kodu (15.05.2017): 1822</li>
			<li>Autor: <strong>Dominik Rzepka</strong></li>
		</ul>
		<br>
		<h5>Wybrane dla ciebie:</h5>
		<div class="row">
<?php
		$q =  'SELECT t.id_towar, t.nazwa, t.opis, t.cena, k.nazwa, t.image FROM towary AS t ';
		$q .= 'INNER JOIN kategorie AS k ON k.id_kategoria=t.id_kategoria ORDER BY RAND() LIMIT 1';
		$result = $this->db->query($q);
		$row = $result->fetch_row();

		echo '<div class="col s10 offset-s1 item-view">';
		if ($row[5]) {
			$data = 'data:image/png;base64,' . base64_encode($row[5]);
			echo '<img src="' . $data . '" alt="zdjęcie przedmiotu">';
		}
		else
			echo '<img src="img/blank-image.png" alt="zdjęcie przedmiotu">';
?>
		<div>
			<p><?php echo $row[1]; ?></p>
			<p><?php echo $row[2]; ?></p>
			<p><?php echo $row[4]; ?></p>
			<div>
				<p><?php echo $row[3] . 'zł'; ?></p>
				<a class="btn shop-cart-add" eid="<?php echo $row[0]; ?>"><i class="material-icons">shopping_card</i>Dodaj do koszyka</a>
			</div>
		</div>
		</div>
		</div>
<?php
		$result->close();
	}

}
new Index();
