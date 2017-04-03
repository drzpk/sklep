<?php
include 'skeleton.php';

class CategoryView extends Skeleton {
	
	private $cat_id = 0;
	private $cat_name = '';
	private $categories = [];
	private $count = 0;
	
	private $query = null;
	
	public function init() {

		session_start();
		//sprawdź, czy użytkownik jest zalogowany
		if ($_SESSION['admin'] != true) {
			//nie jest zalogowany, brak dostępu do tego panelu
			header('Location: ' . $this->getFullUrl('error.php?code=401'));
			die;
		}

		$this->init_db();
		$this->setHasCustomNav(true);
		
		//pobierz listę kategorii (identyfikatory mogą występować nie po kolei)
		$r = $this->db->query('SELECT id_kategoria FROM kategorie');
		$a = $r->fetch_all();
		$r->close();
		foreach ($a as $cid)
			$this->categories[] = (int) $cid[0];
		
		if (isset($_REQUEST['cat']))
			$this->cat_id = (int) $_REQUEST['cat'];
		elseif (count($this->categories))
			$this->cat_id = $this->categories[0];
		
		if (array_key_exists('new_cat', $_REQUEST)) {
			//dodawanie nowej kategorii
			$this->addCategory();
		}
		elseif (array_key_exists('del_cat', $_REQUEST)) {
			//usuwanie kategorii
			$this->deleteCategory();
		}
		elseif (array_key_exists('new_item', $_REQUEST)) {
			//dodawanie nowego towaru
			$this->addElement();
		}
		elseif (array_key_exists('del_item', $_REQUEST)) {
			//usuwanie towaru
			$this->deleteElement();
		}
		
		$r = $this->db->query('SELECT COUNT(*) AS count FROM kategorie');
		$a = $r->fetch_assoc();
		$this->count = $a['count'];
		$r->close();
		
		//pole $this->count może zostać przekonwertowane do zmiennej lokalnej
		if ($this->count == 0) {
			$this->warning('Baza nie zawiera żadnych kategorii.');
			return;
		}
		
		$q = $this->db->prepare('SELECT nazwa FROM kategorie WHERE id_kategoria=?');
		$q->bind_param('i', $this->cat_id);
		$q->execute();
		$r = $q->get_result();
		
		if (!$r->num_rows) {
			$this->error('Nie znaleziono kategorii o podanym identyfikatorze!');
			return;
		}
		
		$this->cat_name = $r->fetch_assoc()['nazwa'];
		$r->close();
		$q->close();
		
		$q = $this->db->prepare('SELECT * FROM towary WHERE id_kategoria=?');
		$q->bind_param('i', $this->cat_id);
		$q->execute();
		
		if ($q->error) {
			$this->error($q->error);
			$q->close();
			return;
		}
		
		$this->query = $q;

		//dodanie przycisku do wylogowania
		$this->addLink('Wyloguj', 'admin.php?logout=');
	}
	
	public function getTitle() {
		return 'Kategoria: ' . $this->cat_name;
	}
	
	public function shouldDrawHome() {
		return true;
	}
	
	private function addCategory() {
		if (isset($_REQUEST['cat_name'])) {
			$cat = $_REQUEST['cat_name'];
			if (strlen($cat) > 64) {
				$this->warning('nazwa kategorii jest za długa');
				return;
			}
			elseif (strlen($cat) < 3) {
				$this->warning('nazwa kategorii jest za krótka');
				return;
			}
			else {
				$stmt = $this->db->prepare('SELECT COUNT(*) AS \'count\' FROM kategorie WHERE nazwa=?');
				$stmt->bind_param('s', $cat);
				if (!$stmt->execute()) {
					$this->error('błąd podczas wykonywania zapytania: ' . $stmt->error);
					return;
				}
				
				//liczba istniejących kategorii o takiej samej nazwie
				$r = $stmt->get_result();
				$arr = $r->fetch_assoc();
				$r->close();
				$count = $arr['count'];
				
				if ($count > 0) {
					$this->warning('kategoria o takiej nazwie już istnieje');
					return;
				}
				
				$stmt = $this->db->prepare('INSERT INTO kategorie (nazwa) VALUES (?)');
				$stmt->bind_param('s', $cat);
				$stmt->execute();
				
				$this->info("Kategoria $cat została dodana!");
				$this->cat_id = $this->db->insert_id;
				
				$this->categories[] = $this->cat_id;
			}
		}
		else
			$this->incompleteRequest();
	}
	
	private function deleteCategory() {
		if (isset($_REQUEST['cat_id'])) {
			$category = (int) $_REQUEST['cat_id'];
			
			//zapisz pozycję na liście
			$pos = array_search($category, $this->categories);
			
			//usuń wszystkie przedmioty z danej kategorii
			$stmt = $this->db->prepare('DELETE FROM towary WHERE id_kategoria=?');
			$stmt->bind_param('i', $category);
			$stmt->execute();
			$stmt->close();
			
			//teraz usuń samą kategorię
			$stmt = $this->db->prepare('DELETE FROM kategorie WHERE id_kategoria=?');
			$stmt->bind_param('i', $category);
			$stmt->execute();
			if (!$stmt->affected_rows)
				$this->error('Nie znaleziono kategorii do usunięcia.');
			else
				$this->info('Kategoria została usunięta');
			$stmt->close();
			
			//zaktualizuj mapowanie kategorii
			unset ($this->categories[$pos]);
			$this->categories = array_values($this->categories);
			
			//wyświetl inną kategorię
			if ($pos > 0)
				$pos--;
			
			if (count($this->categories))
				$this->cat_id = $this->categories[$pos];
			else
				$this->cat_id = 0;
		}
		else
			$this->incompleteRequest();
	}
	
	private function addElement() {
		if (isset($_REQUEST['item_name']) 
			&& isset($_REQUEST['item_price'])
			&& isset($_REQUEST['cat_id'])) {
			$name = $_REQUEST['item_name'];
			$price = (int) $_REQUEST['item_price'];
			$category = (int) $_REQUEST['cat_id'];
			$stop = false;
			
			if (strlen($name) < 5) {
				$this->warning('Nazwa elementu jest za krótka');
				$stop = true;
			}
			elseif (strlen($name) > 64) {
				$this->warning('Nazwa elementu nie może być dłuższa, niż 64 znaki');
				$stop = true;
			}
			
			if ($price < 0) {
				$this->warning('Widziałeś kiedyś ujemną cenę?');
				$stop = true;
			}
			
			if (!$category) {
				$this->warning('Kategoria jest niepoprawna, proszę nie majstrować przy zapytaniu!');
				$stop = true;
			}
			
			if ($stop)
				return;
			
			$stmt = $this->db->prepare('INSERT INTO towary (nazwa, cena, id_kategoria) VALUES (?, ?, ?)');
			$stmt->bind_param('sii', $name, $price, $category);
			
			if ($stmt->execute())
				$this->info('Towar został dodany!');
			else
				$this->error('Wygląda na to, że ktoś majstrował przy zapytaniu.');
			
			$stmt->close();
		}
		else
			$this->incompleteRequest();
	}
	
	private function deleteElement() {
		if (isset($_REQUEST['item_id'])) {
			$id = (int) $_REQUEST['item_id'];
			
			$stmt = $this->db->prepare('DELETE FROM towary WHERE id_towar=?');
			$stmt->bind_param('i', $id);
			$stmt->execute();
			
			if ($stmt->affected_rows)
				$this->info('Towar został usunięty');
			else
				$this->error('Nie znaleziono towaru o podanym identyfikatorze');
			
			$stmt->close();
		}
		else
			$this->incompleteRequest();
	}
	
	private function incompleteRequest() {
		$this->warning('Zapytanie jest niekompletne');
	}
	
	public function drawNav() {}
	
	public function drawCustomNav() {
?>
<ul class="collapsible" data-collapsible="accordion">
	<li>
		<div class="collapsible-header"><i class="material-icons">library_add</i>Dodaj kategorię</div>
		<div class="collapsible-body" style="padding: 15px">
			<form method="POST">
				<div class="input-field">
					<input id="category_name" type="text" name="cat_name" data-length="64">
					<label for="category_name">Nazwa kategorii</label>
				</div>
				<input class="btn" type="submit" name="new_cat" value="Wyślij">
			</form>
		</div>
	</li>
	<li>
		<div class="collapsible-header"><i class="material-icons">delete</i>Usuń kategorię</div>
		<div class="collapsible-body" style="padding: 15px">
			<form method="POST" onsubmit="return deleteCategory()">
				<input type="hidden" name="cat_id" value="<?php echo $this->cat_id; ?>">
				<button class="btn-large" type="submit" name="del_cat"><i class="material-icons left">delete</i>Usuń kategorię</button>
			</form>
		</div>
	</li>
	<li>
		<div class="collapsible-header"><i class="material-icons">note_add</i>Dodaj element</div>
		<div class="collapsible-body" style="padding: 15px">
			<form method="POST">
				<div class="input-field">
					<input id="item_name" type="text" name="item_name" data-length="64">
					<label for="item_name">Nazwa towaru</label>
				</div>
				<div class="input-field">
					<input id="item_price" type="number" name="item_price" data-length="20">
					<label for="item_name">Cena</label>
				</div>
				<input type="hidden" name="cat_id" value="<?php echo $this->cat_id; ?>">
				<input class="btn" type="submit" name="new_item" value="Wyślij">
			</form>
		</div>
	</li>
</ul>
<?php
	}
	
	public function drawSection() {
?>
<table class="highlight">
	<thead>
		<tr>
			<th>ID</th>
			<th>Nazwa</th>
			<th>Cena</th>
			<th>Usuń</th>
		</tr>
	</thead>
	<tbody>
<?php
	$r = $this->query->get_result();
	while ($row = $r->fetch_assoc()) {
		echo "<tr>";
		echo "<td>{$row['id_towar']}</td>";
		$full = $this->getFullUrl('element_view.php?id=' . $row['id_towar']);
		echo "<td><a href=\"{$full}\">{$row['nazwa']}</a></td>";
		echo "<td>{$row['cena']}</td>";
		echo '<td><i class="delete-item material-icons"'
			. ' onclick="deleteItem(' . $row['id_towar'] . ')">delete</i></td>';
		echo "</tr>";
	}
	
	$r->close();
	$this->query->close();
?>
	</tbody>
</table>
<div class="row" style="margin-top: 30px;">
<form id="item_delete_submit" method="POST" style="display: none">
	<input id="item_delete_id" type="hidden" name="item_id" value="test">
	<input type="text" name="del_item" value="del_item">
</form>
<div class="col s12">
<?php
	# przyciski przełączania się między kategoriami
	
	$len = count($this->categories);
	$pos = array_search($this->cat_id, $this->categories);
	
	if ($pos > 0) {
		$prev_cat = $this->categories[$pos - 1];
		echo "<a class=\"btn-flat left\" href=\"category_view.php?cat={$prev_cat}\">Poprzednia kategoria</a>";
	}
	else
		echo '<p class="disabled inactive btn-flat left">Poprzednia kategoria</p>';
	
	if ($pos < $len - 1) {
		$next_cat = $this->categories[$pos + 1];
		echo "<a class=\"btn-flat right\" href=\"category_view.php?cat={$next_cat}\">Następna kategoria</a>";
	}
	else
		echo '<p class="disabled inactive btn-flat right">Następna kategoria</p>';
?>
</div>
</div>
<?php
	}
}

new CategoryView;
?>