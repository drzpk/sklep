<?php
include 'skeleton.php';

class ElementView extends Skeleton {

    private $element;
    private $categories;

    public function init() {
        session_start();
		//sprawdź, czy użytkownik jest zalogowany
		if ($_SESSION['admin'] != true) {
			//nie jest zalogowany, brak dostępu do tego panelu
			header('Location: ' . $this->getFullUrl('error.php?code=401'));
			die;
		}

        $id = (int) @$_REQUEST['id'];
        if ($id) {
            //znaleziono przedmiot
            $this->init_db();

            //pobranie elementu
            $stmt = $this->db->prepare('SELECT * FROM towary WHERE id_towar=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $result = $stmt->get_result();
            if ($result->num_rows) {
                //znaleziono przedmiot
                $this->element = $result->fetch_assoc();
            }
            else {
                //brak przedmiotu o podanym id w bazie
                $this->error('Nie znaleziono przedmiotu o podanym identyfikatorze');
            }

            $result->close();
            $stmt->close();

            //pobranie listy kategorii
            $result = $this->db->query('SELECT * FROM kategorie ORDER BY nazwa DESC');
            while ($row = $result->fetch_assoc()) {
                $this->categories[$row['id_kategoria']] = $row['nazwa'];
            }

            $result->close();
        }
        else {
            //brak wymaganego parametru
            $this->error('Brak identyfikatora przedmiotu!');
        }

        //dodanie przycisku do wylogowania
		$this->addLink('Wyloguj', 'admin.php?logout=');
    }

	public function getTitle() {
        return 'Szczegóły przedmiotu';
    }

    protected function getBackUrl() {
		return 'category_view.php';
	}

    public function shouldDrawNav() {
		return false;
	}

	public function drawNav() {
        //brak
    }

	public function drawSection() {
?>
<div class="row">
    <div class="col s4">
        <h5>Zdjęcie</h5>
        <div class="col s12">
            <div class="row">
                <div class="col s12">
                <?php
                echo '<img class="item-big" src="';
                if ($this->element['image']) {
                    //wyświetl zdjęcie z bazy danych
                    $encoded = base64_encode($this->element['image']);
                    echo 'data:image/png;base64,' . $encoded;
                }
                else {
                    //wyświetl domyślne zdjęcie
                    echo 'img/blank-image.png';
                }
                echo '">';
                ?>
                </div>
            </div>
            <form method="POST">
                <div class="row">
                    <div class="input-field file-field">
                        <div class="btn">
                            <span>Wybierz</span>
                            <input type="file" name="file">
                        </div>
                        <div class="file-path-wrapper">
                            <input class="file-path validate" type="text">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col s12">
                        <input class="btn" type="submit" name="file-submit" value="Wyślij zdjęcie">
                    </div>
                </div>
            </form>
            <div class="row">
                <form method="POST">
                    <div class="input-field col s12">
                        <input class="btn" type="submit" name="reset-image" value="Resetuj zdjęcie">
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col s8">
        <h5>Właściwości</h5>
        <div class="col s12">
            <form method="POST">
                <div class="row input-field">
                    <input id="id_name" type="text" name="name" data-length="63"
                    value="<?php echo $this->element['nazwa']; ?>">
                    <label for="id_name">Nazwa</label>
                </div>
                <div class="row input-field">
                    <textarea lines="2" id="id_textarea" name="desc" value="<?php echo $this->element['opis']; ?>"
                     class="materialize-textarea" data-length="255"></textarea>
                    <label for="id_textarea">Opis</label>
                </div>
                <div class="row input-field">
                    <input id="id_price" type="number" name="price" value="<?php echo $this->element['cena']; ?>">
                    <label for="id_price">Cena</label>
                </div>
                <div class="row input-field">
                    <select name="category">
                        <?php
                            foreach ($this->categories as $value => $name) {
                                $checked = "";
                                if ($value == $this->element['id_kategoria'])
                                    $checked = 'checked="checked"';
                                
                                echo "<option value=\"{$value}\" {$checked}>{$name}</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="row">
                    <input class="btn" type="submit" name="update" value="Aktualizuj">
                    <input class="btn" type="reset" value="Resetuj">
                </div>
                <input type="hidden" name="id" value="<?php echo $this->element['id_towar']; ?>">
            </form>
        </div>
    </div>
</div>
<?php
    // TODO: przycisk resetujący wartości do tych z bazy danych
    }

}

new ElementView();