<?php
require 'skeleton.php';
require_once 'utils.php';

class ListView extends Skeleton {
	
	//liczba elementów na stronę
	const ELEMENTS_PER_PAGE = 6;
	
	/** gotowe zapytanie */
	private $query;
	/** lista kategorii */
	private $cateogies;

	/** obecna strona, liczone od 1 */
	private $current_page;
	private $total_pages;
	private $has_next_page;
	
	/*
		parametr sort:
		1 - według nazwy
		2 - według numeru
		3 - według kategorii
	*/
	private $sort = -1;
	private $filter = -1;
	
	public function init() {
		$this->init_db();
		
		if (isset($_REQUEST['sort'])) {
			//parametr sortowania
			$this->sort = (int) $_REQUEST['sort'];
		}

		if (isset($_REQUEST['filter'])) {
			//parametr filtrowania
			$this->filter = (int) $_REQUEST['filter'];
		}
			
		//przygotowanie zapytania
		if ($this->sort >= -1 && $this->sort < 3) {
			$q_str = "SELECT t.id_towar, t.nazwa, t.opis, t.cena, k.nazwa, t.image"
						. " FROM towary AS t JOIN kategorie AS k ON t.id_kategoria=k.id_kategoria";
		
			if ($this->filter > -1) {
				$q_str .= " WHERE k.id_kategoria={$this->filter} ";
			}
		
			$q_str .= " ORDER BY ";
			switch ($this->sort) {
				case -1:
					$q_str .= 't.id_towar';
					break;
				case 0:
					$q_str .= 't.nazwa';
					break;
				case 1:
					$q_str .= 't.id_towar';
					break;
				case 2:
					$q_str .= 'k.nazwa';
				
			}
			
			//zapisanie zapytania
			$this->query = $q_str;
		}
		else {
			$his->error('Podano nieprawidłowy parametr sortowania.');
			return;
		}

		//określenie obecnej strony
		if (isset($_REQUEST['page'])) {
			$page = (int) $_REQUEST['page'];
		}
		else
			$page = 1;

		$result = $this->db->query($this->query);
		$pages = ceil($result->num_rows / self::ELEMENTS_PER_PAGE);
		$result->close();

		if ($page < 1 || $page > $pages) {
			$this->error('Nieprawidłowy numer strony!');
			return;
		}

		$this->current_page = $page;
		$this->total_pages = $pages;
		$this->has_next_page = $pages - $page > 0;

		$offset = ($page - 1) * self::ELEMENTS_PER_PAGE;
		$this->query .= ' LIMIT ' . ((string) $offset);
		$this->query .= ', ' . ((string) self::ELEMENTS_PER_PAGE);
		
		//pobranie listy kategorii
		$result = $this->db->query('SELECT * FROM kategorie');
		while ($row = $result->fetch_array()) {
			$this->categories[$row[0]] = $row[1];
		}
		$result->close();
	}
	
	public function getTitle() {
		return 'Lista towarów';
	}
	
	public function shouldDrawHome() {
		return true;
	}
	
	public function drawNav() {
?>
<form action="list_view.php" method="GET">
	<fieldset>
	<legend>Sortowanie</legend>
	<?php
	$names = array('brak', 'według nazwy', 'według numeru', 'według kategorii');
	for ($i = -1; $i < 3; $i++) {
		$str = "<input id=\"radio{$i}\" type=\"radio\" name=\"sort\" value=\"{$i}\"";
		if ($i == $this->sort)
			$str .= ' checked="checked"';
		$str .= "><label for=\"radio{$i}\">{$names[$i + 1]}</label>";
		echo '<p>' . $str . '</p>';
	}
	?>
	</fieldset>
	<br>
	<div class="input-field">
		<select name="filter">
			<option value="-1">Brak filtrowania</option>
			<?php 
			foreach ($this->categories as $k => $v) {
				$str = "<option value=\"{$k}\"";
				if ($k == $this->filter)
					$str .= ' selected="selected"';
				$str .= ">{$v}</option>";
				echo $str;
			}
			?>
		</select>
		<label>Filtrowanie</label>
	</div>
	<br><input class="btn" type="submit" value="wyślij">
</form>	
<?php
	} //drawNav

	public function drawSection() {
		$result = $this->db->query($this->query);
		$first = true;
		while ($row = $result->fetch_array()) {
			if (!$first)
				echo '<hr>';
			else
				$first = false;

			$this->displayRow($row);
		}
		$result->close();

		$file = $_SERVER['PHP_SELF'];
		echo '<br>';

		if ($this->current_page > 1) {
			$prev = $this->getCompleteUrl('page', $this->current_page - 1);
			echo "<a class=\"btn-flat\" href=\"{$file}{$prev}\">Poprzednia strona</a>";
		}
		else
			echo '<p class="disabled inactive btn-flat">Poprzednia strona</p>';

		//TODO: wyśrodkować informację o stronie
		echo '<p class="inline">Strona: ' . $this->current_page . '/' . $this->total_pages . '</p>';
		
		if ($this->has_next_page) {
			$next = $this->getCompleteUrl('page', $this->current_page + 1);
			echo "<a class=\"btn-flat right\" href=\"{$file}{$next}\">Następna strona</a>";
		}
		else
			echo '<p class="disabled inactive btn-flat right">Następna strona</p>';
	}

	private function displayRow($row) {
		//TODO: implementacja koszyka
		echo '<div class="item-view">';
		if ($row[5]) {
			$data = 'data:image/png;base64,' . $row[5];
			echo '<img src="' . $data . '" alt="zdjęcie przedmiotu">';
			$this->getSortedUrl(null, null);
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
				<a class="btn" eid="<?php echo $row[0]; ?>"><i class="material-icons">shopping_card</i>Dodaj do koszyka</a>
			</div>
		</div>
	</div>
<?php
	}

	/**
	 * Zwraca adres url, który zawiera parametry filtrowania, sortowania oraz ten podany jako argument.
	 *
	 * @param string $param nazwa parametru
	 * @param string $val wartość parametru
	 * @return void
	 */
	private function getCompleteUrl($param, $val) {
		$pc = new ParamConstructor();
		if ($this->sort != -1)
			$pc->add('sort', $this->sort);
		if ($this->filter != -1)
			$pc->add('filter', $this->filter);
		$pc->add($param, $val);
		return $pc->get();
	}
}

new ListView();
?>
