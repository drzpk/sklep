<?php
include 'skeleton.php';

class ListView extends Skeleton {
	
	/*
		parametr sort:
		1 - według nazwy
		2 - według numeru
		3 - według kategorii
	*/
	private $is_result = false;
	private $error_msg = false;
	private $result = array();
	
	private $sort = -1;
	private $filter = -1;
	
	public function init() {
		$this->init_db();
		
		if (isset($_REQUEST['sort'])) {
			$this->sort = (int) $_REQUEST['sort'];
		}

		if (isset($_REQUEST['filter'])) {
			$this->filter = (int) $_REQUEST['filter'];
		}
			
		if ($this->sort >= -1 && $this->sort < 3) {
			$this->create_result();
		}
		else {
			$error_msg = 'Parametry są filtrowane, powodzenia!';
		}
	}
	
	public function getTitle() {
		return 'Widok listy';
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
	<?php
	$rs = $this->db->query('SELECT * FROM kategorie');
	?>
	<div class="input-field">
		<select name="filter">
			<option value="-1">Brak filtrowania</option>
			<?php 
			while ($row = $rs->fetch_row()) {
				$str = "<option value=\"{$row[0]}\"";
				if ($row[0] == $this->filter)
					$str .= ' selected="selected"';
				$str .= ">{$row[1]}</option>";
				echo $str;
			}
			$rs->free();
			?>
		</select>
		<label>Filtrowanie</label>
	</div>
	<br><input class="btn" type="submit" value="wyślij">
</form>	
<?php
	}

	public function drawSection() {
?>
<?php if ($this->is_result) { ?>
<table class="highlight">
	<thead>
		<tr>
			<th>ID</th>
			<th>Nazwa</th>
			<th>Cena</th>
			<th>Kategoria</th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ($this->result as $row) : ?>
		<tr>
			<td><?php echo $row['1']; ?></td>
			<td><?php echo $row['2']; ?></td>
			<td><?php echo $row['3']; ?></td>
			<td><?php echo $row['4']; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php
}
elseif ($this->error_msg)
	$this->error($this->error_msg);
else
	$this->info("Wybierz jakąś opcję z menu nawigacji");
	}
	
	private function create_result() {
		$q_str = "SELECT t.id_towar AS '1', t.nazwa AS '2', t.cena AS '3', k.nazwa AS '4' FROM towary AS t JOIN kategorie AS k ON t.id_kategoria=k.id_kategoria";
		
		if ($this->filter > -1) {
			$q_str .= " WHERE k.id_kategoria={$this->filter} ";
		}
		if ($this->sort > -1) {
			$q_str .= " ORDER BY ";
			switch ($this->sort) {
				case 0:
					$q_str .= 't.nazwa';
					break;
				case 1:
					$q_str .= 't.id_towar';
					break;
				case 2:
					$q_str .= 'k.nazwa';
			}
		}
		
		$q = $this->db->query($q_str);
		while ($row = $q->fetch_assoc())
			$this->result[] = $row;
		$this->is_result = true;
	}
}

new ListView();
?>
