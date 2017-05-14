<?php
require_once 'utils.php';

/**
 * Klasa obsługująca koszyk. Może być używana zarówno przez klienta (dodanie lub usuwanie pozycji)
 * jak i przez serwer (pobieranie listy towarów).
 * 
 * W przypadku zapytania dokonywanego przez klienta za pośrednictwem JavaScript, dostępne są
 * następujące akcje:
 *      add - dodaje element do koszyka (każde kolejne wywołanie zwiększa ilość o 1)
 *      remove - usuwa jeden element z koszyka
 *      delete - usuwa wszystkie elementy o podanym id z koszyka
 *      clear - czyści koszyk
 * 
 * Składnia zapytania jest następująca:
 *      cart.php?action=<wybrana akcja>&id=<id przedmiotu>
 * 
 * Odpowiedzi serwera:
 *      200 - zapytanie wykonane pomyślnie
 *      400 - błędne zapytanie (szczegóły w ciele odpowiedzi)
 *      404 - nie znaleziono towaru o podanym id
 */
class Cart {

    private $db = null;
    
    public function __construct(&$db = null) {
        if (!$db) {
            //brak referencji do bazy danych - utworzenie własnej
            try {
                $this->db = get_db();
            }
            catch (Exception $e) {
                http_response_code(500);
                echo $e->getMessage();
                die;
            }
        }
        else
            $this->db = $db;

        //sprawdzenie, czy sesja została już uruchomiona
        if (session_id() == '')
            session_start();
    }

    /**
     * Zwraca tablicę zawierającą tablice asocjacyjne ze wszstkimi elementami w koszyku
     * w następującym formacie:
     * [
     *      [id][
     *          'name'      =>
     *          'price'     =>
     *          'amount'    =>
     *      ]
     *      ...
     * ]
     *
     * @return array    tablica
     */
    public function fetch() {
        if (isset($_SESSION['cart']))
            return $_SESSION['cart'];
        return [];
    }

    /**
     * Zwraca ilość wszystkich towarów znajdujących się w koszyku.
     *
     * @return int rozmar koszyka
     */
    public function size() {
        if (!isset($_SESSION['cart']))
            return 0;
        
        $count = 0;
        foreach ($_SESSION['cart'] as $v)
            $count += $v['amount'];

        return $count;
    }

    /**
     * Dodaje nowy towar do koszyka. Jeśli towar już istnieje, jego ilość jest zwiększana o jeden.
     * @param int $id       identyfikator towaru
     * @param int $amount   ilość towaru do dodania
     * @return bool         czy towar został dodany (czy istnieje)
     */
    public function add($id, $amount = 1) {
        if (!is_int($id))
            throw new Exception('Argument id musi być liczbą całkowitą.');
        if (!is_int($amount) || $amount < 1)
            throw new Exception('Ilość towaru musi być liczbą całkowitą większą od 0.');
        if (!$this->exists($id))
            return false;

        if (!isset($_SESSION['cart']))
            $_SESSION['cart'] = array();

        $s = &$_SESSION['cart'];
        if (array_key_exists($id, $s)) {
            if ( $s[$id]['amount'] < 100)
                $s[$id]['amount'] += $amount;
            else
                throw new Exception('Przekroczono maksymalną ilość towaru!');
        }
        else {
            $a = array('amount' => $amount);
            $a = array_merge($a, $this->getData($id));
            $s[$id] = $a;
        }

        //print_r($s);

        return true;
    }

    /**
     * Usuwa towar z koszyka. Jeśli zostanie podana niedodatnia ilość towaru (<1), wszystkie
     * cały towar zostanie usunięty.
     *
     * @param int $id       identyfikator towaru
     * @param int $amount   ilość towaru do usunięcia
     * @return bool         czy towar został usunięty (czy był w koszyku)
     */
    public function remove($id, $amount = 1) {
        if (!is_int($id))
            throw new Exception('Argument id musi być liczbą całkowitą.');
        if (!is_int($amount))
            throw new Exception('Ilość towaru musi być liczbą całkowitą.');
        
        $s = &$_SESSION['cart'];
        if (!isset($s) || !array_key_exists($id, $s))
            return false;

        if ($amount < 1) {
            unset($s[$id]);
            return true;
        }

        $s[$id]['amount'] -= $amount;
        if ($s[$id]['amount'] < 1)
            unset($s[$id]);

        //print_r($s);

        return true;
    }

    /**
     * Czyści cały koszyk.
     */
    public function clear() {
        unset($_SESSION['cart']);
    }

    private function exists($id) {
        $result = $this->db->query('SELECT nazwa FROM towary WHERE id_towar=' . $id);
        $ret = $result->num_rows != 0;
        $result->close();
        return $ret;
    }

    private function getData($id) {
        $result = $this->db->query('SELECT nazwa, cena FROM towary WHERE id_towar=' . $id);
        $arr = $result->fetch_assoc();
        $ret = array(
            'name' => $arr['nazwa'],
            'price' => $arr['cena']
        );
        $result->close();
        return $ret;
    }
}

if (strpos($_SERVER['SCRIPT_NAME'], 'cart.php') !== false) {
    //zapytanie bezpośrednie do tego skryptu

    if (!isset($_REQUEST['action'])) {
        http_response_code(400);
        echo 'Brak parametru akcji';
        die;
        return;
    }

    switch ($_REQUEST['action']) {
        case 'add':
        case 'remove':
        case 'delete':
        case 'clear':
            break;
        default:
            http_response_code(400);
            echo 'Niepoprawny parametr akcji';
            die;
            return;
    }

    if (!isset($_REQUEST['id']) && $_REQUEST['action'] != 'clear') {
        http_response_code(400);
        echo 'Brak identyfikatora przedmiotu';
        die;
        return;
    }

    $id = (int) @$_REQUEST['id'];
    $resp = null;
    
    try {
        $card = new Cart();
        switch ($_REQUEST['action']) {
            case 'add':
                $resp = $card->add($id);
                break;
            case 'remove':
                $resp = $card->remove($id);
                break;
            case 'delete':
                $resp = $card->remove($id, 0);
                break;
            case 'clear':
                $card->clear();
                break;
        }
    }
    catch (Exception $e) {
        http_response_code(400);
        echo $e->getMessage();
        die;
    }

    if ($resp === false)
        http_response_code(404);
    else
        http_response_code(200);
}
