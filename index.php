<?php
include 'skeleton.php';

class Index extends Skeleton {
	public function init() {
		#$this->init_db();
	}
	public function drawNav() {
?>
<p>Opcje:</p>
<ul>
	<li><a href="list.php">Lista Towarów</a></li>
	<li><a href="add_category.php">Dodawanie kategorii</a></li>
	<li><a href="category_view.php?cat=1">Widok kategorii</a></li>
</ul>
<?php
	}
	public function drawSection() {
?>
<h2>Strona główna</h2>
<p>To jest strona główna sklepu internetowego. Z listy po lewej stronie można wybrać jedną z dostępnych kategorii.</p>

<p>Tutaj będą jakieś statystyki bazy danych</p>
<?php
	}
}

new Index();
?>

	