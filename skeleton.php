<?php 
abstract class Skeleton {
	
	protected $db = null;
	private $info = [];
	private $warnings = [];
	private $errors = [];
	
	private $has_custom_nav = false;
	private $links = [];
	
	public function __construct() {
		$this->init();
		$this->drawLayout();
		
		/*set_error_handler(function($errno , $errstr, $errfile, $errline) {
			http_response_code(500);
			echo "BŁĄD ($errno): $errstr";
			echo "Plik: $errfile, linia $errline";
			die;
		});*/
	}
	public abstract function init();
	public abstract function getTitle();
	public abstract function drawNav();
	public abstract function drawSection();

	public function shouldDrawNav() {
		return true;
	}

	/**
	 * Zwraca adres strony, do której ma prowadzić przycisk 'powrót'
	 *
	 * @return string adres poprzedniej strony lub null, jeśli brak
	 */
	protected function getBackUrl() {
		return null;
	}

	/**
	 * Dodaje link, który zostanie wyświetlony w liście pod logiem strony.
	 *
	 * @param string $name 		wyświetlana nazwa
	 * @param string $address 	nazwa pliku, relatywnie do katalogu szkieletu
	 * @return void
	 */
	protected function addLink($name, $address) {
		$this->links[$name] = $address;
	}
	
	public function drawCustomNav() {}
	
	protected function setHasCustomNav($value) {
		$this->has_custom_nav = $value;
	}
	
	protected function init_db() {
		$this->db = new mysqli('localhost', 'root', 'Hs9do4x', 'sklep');
		if ($this->db->connect_errno) {
			http_response_code(500);
			echo 'Nie można nawiązać połączenia z bazą danych!';
			die;
		}
		$this->db->set_charset('UTF8');
	}
	
	protected function getFullUrl($appendix = 'index.php') {
		$path = $_SERVER['PHP_SELF'];
		$index = strrpos($path, '/');
		$path = substr($path, 0, $index + 1);
		$path = $path . $appendix;

		return $path;
	}
	
	protected function info($content) {
		$this->info[] = $content;
	}
	
	protected function warning($content) {
		$this->warnings[] = $content;
	}

	protected function error($content) {
		$this->errors[] = $content;
	}

	private function drawLayout() {
?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Sklep komputerowy</title>
		<link rel="stylesheet" href="css/materialize.min.css">
		<link rel="stylesheet" href="css/style.css?v=49">
		<link rel="stylesheet" href="css/icons.css">
		
		<script src="js/jquery-3.1.1.min.js"></script>
		<script src="js/materialize.min.js"></script>
		<script src="js/main.js?v=7"></script>
	</head>
	<body>
		<div class="container">
			<div class="row">
				<header class="col s12">
					<h1>Sklep komputerowy</h1>
				</header>
			</div>
			<div class="row">
				<nav class="col s12">
					<div class="nav-wrapper">
					<?php if ($this->getBackUrl()) : ?>
						<a href="<?php echo $this->getFullUrl($this->getBackUrl()); ?>">
						<i class="material-icons left">fast_rewind</i>Powrót</a>
					<?php endif; ?>
						<ul class="right">
							<?php
							foreach ($this->links as $name => $address) {
								$url = $this->getFullUrl($address);
								echo "<li><a href=\"{$url}\">{$name}</a></li>";
							}
							?>
						</ul>
					</div>
				</nav>
			</div>
			<section>
				<div class="row">
					<?php if ($this->shouldDrawNav()) : ?>
					<div class="col s4">
						<?php if (!$this->has_custom_nav) : ?>
						<div class="card gray">
							<div class="card-content">
								<?php $this->drawNav(); ?>
							</div>
						</div>
						<?php else :
						$this->drawCustomNav();
						endif;
						?>
					</div>
					<?php endif;
					$col_width = $this->shouldDrawNav() ? 's8' : 's12';
					?>
					<div class="col <?php echo $col_width; ?>">
						<div class="card gray">
							<div class="card-content">
<?php
echo '<h4>' . $this->getTitle() . '</h4>';
if (count($this->errors)) {
	//wyświetl błędy
	foreach ($this->errors as $error)
	$this->drawError($error);
}
else {
	//wyświetl ostrzerzenia i informacje
	foreach ($this->warnings as $warning)
		$this->drawWarning($warning);
	foreach ($this->info as $info)
		$this->drawInfo($info);
	
	//wyświetl zawartość sekcji
	$this->drawSection();
}

?>
							</div>
						</div>
					</div>
				</div>
			</section>
		</div>
	</body>
</html>
<?php
	}
	
	private function drawInfo($content) {
?>
<div class="card green darken-2 notification">
	<div class="card-content">
		<div class="row">
			<div class="col s2">
				<i class="material-icons medium">info</i>
			</div>
			<div class="col s10">
				<p><?php echo $content; ?></p>
			</div>
		</div>
	</div>
</div>
<?php
	}
	
	private function drawWarning($content) {
?>
<div class="card yellow darken-1 notification">
	<div class="card-content">
		<div class="row">
			<div class="col s2">
				<i class="material-icons medium">warning</i>
			</div>
			<div class="col s10">
				<p><?php echo $content; ?></p>
			</div>
		</div>
	</div>
</div>
<?php
	}
	
	private function drawError($content) {
?>
<div class="card red darken-1 notification">
	<div class="card-content">
		<div class="row">
			<div class="col s2">
				<i class="material-icons medium">error</i>
			</div>
			<div class="col s10">
				<p><?php echo $content; ?></p>
			</div>
		</div>
	</div>
</div>
<?php	
	}
}
?>