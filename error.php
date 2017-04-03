<?php
include 'skeleton.php';

class Error extends Skeleton {

    public function init() {

    }

	public function getTitle() {
        return 'Błąd';
    }

	public function shouldDrawHome() {
        return true;
    }

	public function drawNav() {
        //brak nawigacji, nieużywane
    }

	public function drawSection() {
        echo '<p style="color: red">';
        $code = @$_REQUEST['code'];
        $types = new ErrorTypes();
        echo $types->getDescription($code);
        echo '</p>';
    }

	public function shouldDrawNav() {
		return false;
	}
}

/**
 * Klasa zwraca opisy do znanych błędów HTTP
 */
class ErrorTypes {

    private $descriptions = [];

    public function __construct() {
        $this->descriptions[0] = 'Nieznany błąd.';

        #unauthorized
        $this->descriptions[401] = 'Nie masz uprawnień do przeglądania tego zasobu';
    }

    public function getDescription($code) {
        $int_code = (int) $code;
        if (!array_key_exists($int_code, $this->descriptions))
            return $this->descriptions[0];
        return $this->descriptions[$int_code];
    }
}

new Error();