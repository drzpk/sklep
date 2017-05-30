<?php
include 'skeleton.php';

class ElementView extends Skeleton {

    /** Maksymalny rozmiar wysyłanego zdjęcia, w bajtach */
    const MAX_UPLOAD_SIZE = 500 * 1024;

    private $element;
    private $categories;

    public function init() {
        $this->session_start();
		//sprawdź, czy użytkownik jest zalogowany
		if (@$_SESSION['admin'] != true) {
			//nie jest zalogowany, brak dostępu do tego panelu
			header('Location: ' . $this->getFullUrl('error.php?code=401'));
			die;
		}
		
		//połączenie się z bazą danych
		$this->init_db();

        $id = (int) @$_REQUEST['id'];
        if ($id) {
            //znaleziono przedmiot
            
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
                $result->close();
                $stmt->close();
                return;
            }

            $result->close();
            $stmt->close();

            //aktualizacja przedmiotu
		    if ($this->tryUpdateDetails())
                return;
            //aktualizacja zdjęcia
            $this->tryUpdateFile();

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
	
	private function tryUpdateDetails() {
		if (!array_key_exists('update', $_REQUEST))
			return;
		
		//nazwy pól formularza:
		//id, name, desc, price, category
		
		//weryfikacja nazwy
		$name = (string) @$_REQUEST['name'];
		if (!$name) {
			$this->warning('Nie podano nazwy towaru.');
			return;
		}
		elseif (strlen($name) < 6) {
			$this->warning('Nazwa towaru jest za krótka (minimum 6 znaków).');
			return;
		}
		elseif (strlen($name) > 63) {
			$this->warning('Nazwa towaru jest za długa (maksymalnie 63 znaki).');
			return;
		}
		
		//weryfikacja opisu
		$desc = (string) @$_REQUEST['desc'];
		if (strlen($desc) > 255) {
			$this->warning('Podany opis jest za długi.');
			return;
		}
		
		//weryfikacja ceny
		$price = (int) @$_REQUEST['price'];
		if (!$price === null) {
			$this->warning('Nie podano ceny towaru.');
			return;
		}
		elseif ($price < 1) {
			$this->warning('Cena nie może być mniejsza, niż 1');
			return;
		}
		elseif ($price > 1000000) {
			$this->warning('Nie przesadzasz trochę z tą ceną?');
			//bez przerywania działania
		}
		
		//weryfikacja kategorii
		$cat = (int) @$_REQUEST['category'];
		$stmt = $this->db->prepare('SELECT nazwa FROM kategorie WHERE id_kategoria=?');
		$stmt->bind_param('i', $cat);
		$stmt->execute();
		
		$result = $stmt->get_result();
		$num = $result->num_rows;
		$result->close();
		$stmt->close();
		
		if ($num == 0) {
			$this->warning('Nie znaleziono kategorii o podanym identyfikatorze.');
			return;
		}
		
		//aktualizacja rekordu
		$stmt = $this->db->prepare('UPDATE towary SET nazwa=?, opis=?, cena=?, id_kategoria=? WHERE id_towar=?');
		$stmt->bind_param('ssiii', $name, $desc, $price, $cat, $id);
		$stmt->execute();
        $stmt->close();

        //aktualizacja pobranego wcześniej elementu z bazy
        $this->element['nazwa'] = $name;
        $this->element['opis'] = $desc;
        $this->element['cena'] = $price;
        $this->element['id_kategoria'] = $cat;
		
		//dla uproszczenia nie sprawdzam, czy dane faktycznie zostały zaktualizowane
		//okaże się to, gdy zostaną wyświetlone
		$this->info('Dane towaru zostały zaktualizowane.');
	}

    private function tryUpdateFile() {
        if (array_key_exists('file-submit', $_REQUEST) && isset($_FILES['file'])) {
            //aktualizacja pliku
            $file = $_FILES['file'];
            if ($file['error']) {
                $this->warning('Wystąpił nieznzny błąd podczas wysyłania pliku ('
                                . $file['error'] . '). Skonsultuj się z lekarzm lub farmaceutą.');
                return;
            }
            elseif ($file['size'] > self::MAX_UPLOAD_SIZE) {
                $this->warning('Rozmiar pliku jest za duży. Maksymalny rozmiar to'
                                . (self::MAX_UPLOAD_SIZE / 1024) . 'KB.');
                return;
            }

            $id = (int) $_REQUEST['id'];
            
            //$prev = error_reporting(0);
            $img = imagecreatefromstring(file_get_contents($file['tmp_name']));
            if (!$img) {
                $this->warning('Podany plik nie jest zdjęciem!');
                return;
            }

            ob_start();
            imagepng($img);
            $png = ob_get_contents();
            ob_end_clean();
            imagedestroy($img);
            //error_reporting($prev);

            //zapisanie zdjęcia do bazy
            $stmt = $this->db->prepare('UPDATE towary SET image=? WHERE id_towar=?');
            $n = NULL;
            $stmt->bind_param('bi', $n, $id);

            $chunks = str_split($png, 4096);
            foreach ($chunks as $c)
                $stmt->send_long_data(0, $c);
            $stmt->execute();
            $stmt->close();

            $this->element['image'] = $png;
            
            $this->info('Zdjęcie zostało zaktualizowane.');
        }
        elseif (array_key_exists('reset-image', $_REQUEST)) {
            //przywrócenie domyślnego zdjęcia
            $id = (int) $_REQUEST['id'];
            $this->db->query('UPDATE towary SET image=NULL WHERE id_towar=' . $id);
            $this->element['image'] = null;

            $this->info('Zdjęcie zostało zresetowane.');
        }
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
            <form enctype="multipart/form-data" method="POST">
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
                    <textarea lines="2" id="id_textarea" name="desc"
                     class="materialize-textarea" data-length="255"><?php echo $this->element['opis']; ?></textarea>
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