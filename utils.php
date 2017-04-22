<?php

/**
 * Klasa odpowiada za konstrukcję stringa zawierającego zapytanie (od znaku ?).
 * (Tablice jako parametry nie są obsługiwane.)
 */
class ParamConstructor {
    private $array = [];

    /**
     * Dodaje nowy parametr do zapytania.
     *
     * @param string $param nazwa parametru
     * @param string $value wartość parametru
     * @return object ten obiekt
     */
    public function add($param, $value) {
        $this->array[$param] = $value;
        return $this;
    }

    /**
     * Zwraca gotowe zapytanie.
     *
     * @return string zapytanie
     */
    public function get() {
        if (count($this->array) == 0)
            return '';

        $ret = '';
        $first = true;
        foreach ($this->array as $k => $v) {
            if ($first) {
                $ret .= '?';
                $first = false;
            }
            else
                $ret .= '&';
            $ret .= ($k . '=' . $v);
        }

        return $ret;
    }

    /**
     * Krótsza w zapisie wersja realizująca to samo zadanie, co klasa.
     *
     * @param array $arr tablica asocjacyjna zawierająca parametry i wartości do zapytania
     * @return string gotowe zapytanie
     */
    public static function getUrl($arr) {
        $inst = new static();
        foreach ($arr as $k => $v)
            $inst->add($k, $v);
        
        return $inst->get();
    }
}