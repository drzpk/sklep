<?php
include 'skeleton.php';

class Admin extends Skeleton {

    private $logged_in = false;

    public function init() {
        $this->session_start();
        if (array_key_exists('logout', $_REQUEST)) {
            if (@$_SESSION['admin'] == true) {
                //wyloguj użytkownika
                session_unset();
                $this->info('Zostałeś wylogowany.');
            }
            else
                $this->warning('Nikt nie był zalogowany.');
            return;
        }

        //sprawdź, czy użytkownik jest zalogowany
        if (@$_SESSION['admin'] == true) {
            $this->logged_in = true;
        }
        else {
            //sprawdź, czy dane logowania są poprawne
            if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
                $name = $_REQUEST['username'];
                $passwd = $_REQUEST['password'];
                //hasło jest przechowywane w formie SHA256
                $passwd = hash('sha256', $passwd);

                $this->init_db();
                $stmt = $this->db->prepare('SELECT * FROM users WHERE name=? AND password=?');
                $stmt->bind_param('ss', $name, $passwd);
                $stmt->execute();

                $result = $stmt->get_result();
                if ($result->num_rows != 0) {
                    //logowanie powiodło się
                    $_SESSION['admin'] = true;

                    $this->logged_in = true;
                }
                else {
                    //błędne dane logowania
                    $this->warning('Dane logowania są niepoprawne.');
                }

                $stmt->free_result();
                $stmt->close();
            }
        }

        if ($this->logged_in) {
            //dodaj elementy do listy    
            $this->addLink('Lista kategorii', 'category_view.php');
            $this->addLink('Wyloguj', 'admin.php?logout=');
        }
    }

    protected function getBackUrl() {
		return 'index.php';
	}

	public function getTitle() {
        return 'Panel administratora - logowanie';
    }

	public function shouldDrawHome() {
        return true;
    }

	public function drawNav() {
        //brak nawigacji
    }

    public function drawSection() {
        if ($this->logged_in)
            $this->drawAdminPanel();
        else
            $this->drawLoginPanel();
    }

	private function drawLoginPanel() {
?>
<div class="row">
    <form class="col s6 offset-s3" method="POST">
        <div class="row">
            <div class="input-field col s12">
                <input id="username" type="text" name="username">
                <label for="username">Nazwa użytkownika</label>
            </div>
        </div>
        <div class="row">
            <div class="input-field col s12">
                <input id="password" type="password" name="password">
                <label for="password">Hasło</label>
            </div>
        </div>
        <div class="row">
            <div class="col s12">
                <input class="btn" type="submit" name="submit" value="Wyślij">
            </div>
        </div>
    </form>
</div>
<?php
    }

    private function drawAdminPanel() {
?>
<p>Jesteś teraz zalogowany do panelu administracyjnego sklepu internetowego.
Możesz wybrać jeden z dostępnych ekranów z listy znajdującej się powyżej</p>
<?php
    }

	public function shouldDrawNav() {
		return false;
	}
}

new Admin();