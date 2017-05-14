<?php
require_once 'skeleton.php';
require_once 'cart.php';

class CartView extends Skeleton {

    public function init() {
        $this->init_db();

        $this->addLink('Wyszyść koszyk', 'cart_view.php?clear=');
        if (array_key_exists('clear', $_REQUEST)) {
            $this->cart->clear();
            $this->info('Koszyk został wyczyszczony.');
        }
    }

	public function getTitle() {
        return 'Koszyk';
    }

    public function shouldDrawNav() {
		return false;
	}

	protected function getBackUrl() {
		return 'list_view.php';
	}

    public function drawNav() {
        //brak panelu nawigacji
    }

	public function drawSection() {
        $cart = new Cart($this->db);
        if ($cart->size()) {
            $total = 0;
            $num = 1;
            $list = $cart->fetch();
            foreach ($list as $id => $v) {
                $total += $this->drawcartElement($num++, $id, $v);
            }

            $this->drawSummary($total);
        }
        else
            echo '<p>Koszyk jest pusty. Możesz dodać nowe towary do koszyka korzystając z <a href="list_view.php">TEJ</a> strony.';
    }

    private function drawcartElement($num, $id, $element) {
        $total = $element['amount'] * $element['price'];
         if ($num > 1)
            echo '<div class="hr"></div>';
?>
    <div class="shop-cart-item" eid=<?php echo $id; ?>>
        <p><?php echo $num; ?>.</p>
        <p><a href="#"><?php echo $element['name']; ?></a></p>
        <div class="fr">
            <div class="shop-cart-button shop-cart-button-minus">-</div>
            <p class="shop-cart-amount"><?php echo $element['amount']; ?></p>
            <div class="shop-cart-button shop-cart-button-plus">+</div>
            <p class="shop-cart-total"><?php echo $total . ' zł'; ?></p>
            <div class="delete-button">X</div>
        </div>
    </div>
<?php
        return $total;
    }

    private function drawSummary($total) {
?>
    <div class="shop-cart-summary">
        <p>Razem: <span><?php echo $total . ' zł'; ?></span></p>
        <button id="button-buy-now" class="btn">Kup teraz!</button>
    </div>
<?php
    }
}

new cartView();