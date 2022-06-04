<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header ('Content-type: text/html; charset=utf-8');
class Store{
	public $titulo;
	public $tag;
	public $precio;
	public $precio_uy;
	public $moneda;
	public $url;
	public $usd;
	public $info;
	public $estado;
	private static $dolar = 40;
	//fecha

	function __construct(){}
	public function retrieve($pid, $tag, $callback, $conn){
		if(is_null($this->estado)){
			$this->estado = 1;
		}
		$this->precio = tofloat($this->precio);
		$this->titulo = trim($this->titulo);
		if($this->usd == 0){
			$this->precio_uy = $this->precio;
			$this->info = "Precio en origen $".$this->precio;
			$currentDolar = tofloat(CurrencyValue::get());
			$this->precio = $this->precio / $currentDolar;

		}else{
			if($this->tag == 'Geant'){
				$currentDolar = tofloat(CurrencyValue::get());
				$this->precio = $this->precio / $currentDolar;
			}
			$this->precio_uy = NULL;
			$this->info = '';
		}
		$this->precio = round($this->precio, 2);
		//print_r($this);echo 'Cant get: '.$url;
		if($callback != null){
			$callback($pid, $tag, $this->precio, $this->precio_uy, $conn, $this->estado);
		}
		echo json_encode($this);
	}
}
class Loi{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		if(sizeof($page->find('.nombre-producto-info')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find('.nombre-producto-info') as $key => $value) {
			$store->titulo = trim($value->innertext);
		}
		foreach ($page->find('.pv3-pv-loi') as $key => $value) {
			$_moneda = $value->children(0)->innertext;
			
			if($_moneda == 'USD'){
				$store->moneda = 'USD';
				$store->usd = 1;
			}else{
				$store->moneda = '$';
				$store->usd = 0;
			}
			$decimal = '';
			if($val = explode('<sup>', $value->innertext)[1]){
				$decimal = '.'.explode('</sup>', $val)[0];
			}
			$price = explode('<sup>', explode('</span>', $value->innertext)[1])[0];
			$_precio = $price.$decimal;
			$store->precio = trim($_precio);
		}
		
		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class TiendaInglesa{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		if(sizeof($page->find('.ProductNameFull')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find('.ProductNameFull') as $key => $value) {
			$store->titulo = mb_convert_encoding($value->innertext, 'utf-8', 'ISO-8859-1');
			
		}
		$g = 0;
		//print_r($page);die;
		foreach ($page->find('#MAINFORM') as $key => $value) {

			//$track = $value->getElementById('TXTJSTRACKERS');
			foreach ($value->find('#TXTJSTRACKERS') as $key1 => $value1) {
				foreach ($value1->find('script') as $key2 => $value2) {
					$info = $value2->innertext;
					//echo strlen($info);die;
					$_cut = substr($info, 0, strlen($info) - 2);
					$objInfo = "[".explode("fbq('track', 'ViewContent', ", $_cut)[1]."]";
					$obj = json_decode($objInfo, true)[0];
					$_precio = $obj['value'];
					$store->precio = trim($_precio);
					$_moneda = $obj['currency'];
					if($_moneda == 'USD'){
						$store->moneda = 'USD';
						$store->usd = 1;
					}else{
						$store->moneda = '$';
						$store->usd = 0;
					}
					break;
				}
				break;
			}
		}

		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class Dimm{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		if(sizeof($page->find('.nombre')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find('.nombre') as $key => $value) {
			$store->titulo = mb_convert_encoding($value->innertext, 'utf-8', 'ISO-8859-1');
		}
		$g = 0;
		foreach ($page->find('.moneda') as $key => $value) {
			$_moneda = $value->innertext;
			

			if($_moneda == 'USD'){
				$store->moneda = 'USD';
				$store->usd = 1;
			}else{
				$store->moneda = '$';
				$store->usd = 0;
			}
			if($g == 0){
				break;
			}
		}
		foreach ($page->find('#precio_ent_actual') as $key => $value) {
			$_precio = $value->innertext;
			$store->precio = trim($_precio);
		}

		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class Pcm{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		if(sizeof($page->find('.product-name')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find('.product-name') as $key => $value) {
			$store->titulo = trim($value->children(0)->innertext);
		}
		foreach ($page->find('.product-price') as $key => $value) {
			foreach ($value->find('[itemprop=price]') as $key => $value) {
				$_precio = $value->innertext;
				$currency = strpos($_precio, 'U$S');
				
				if($currency){
					$_moneda = 'USD';
					$store->usd = 1;
					$_price = explode('U$S', $_precio)[1];
					$_price = str_replace(".", "", $_price);
					$store->precio = trim($_price);
				}else{
					$_moneda = '$';
					$store->usd = 0;
					$_price = explode($_moneda, $_precio)[1];
					$_price = str_replace(".", "", $_price);
					$store->precio = trim($_price);
				}
				$store->moneda = $_moneda;
			}
			
			//echo $_precio;echo 'Cant get: '.$url;
			//return;
			
			
		}

		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class Nnet{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		if(sizeof($page->find('.nombre')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find('.nombre') as $key => $value) {
			$store->titulo = mb_convert_encoding($value->innertext, 'utf-8', 'ISO-8859-1');
		}
		foreach ($page->find('.moneda') as $key => $value) {
			$_moneda = $value->innertext;
			$store->moneda = $_moneda;
			if($_moneda == 'USD'){
				$store->usd = 1;
			}else{
				$store->usd = 0;
			}
		}
		foreach ($page->find('#precio_ent_actual') as $key => $value) {
			$_precio = str_replace(',', '.', $value->getAttribute('content'));
			$store->precio = trim($_precio);
		}

		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class Multiahorro{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		if(sizeof($page->find('#fichaProducto')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		$f0 = 0;
		foreach ($page->find('#fichaProducto') as $key => $value) {
			$break = sizeof($value->find('.tit'));
			$f1 = 0;
			foreach ($value->find('.tit') as $key1 => $value1) {
				if($f1 == 0){
					$f1 = 1;
					$store->titulo = $value1->innertext;
				}
			}
			$f2 = 0;
			foreach ($value->find('.sim') as $key2 => $value2) {
				if($f2 == 0){
					$f2 = 1;
					$_moneda = $value2->innertext;
					
					if($_moneda == 'USD'){
						$store->moneda = 'USD';
						$store->usd = 1;
					}else{
						$store->moneda = '$';
						$store->usd = 0;
					}
				}

			}
			$discount = 0;
			$discountPrice = 0;
			foreach ($value->find('.descuentosMDP') as $key5 => $value5) {
				if(sizeof($value5->children(0)) == 1){
					$discount = 1;
					foreach ($value5->find('.monto') as $key6 => $value6) {
						$discountPrice = str_replace('.', '', $value6->innertext);
						break;
					}
				}
				break;
			}

			$f3 = 0;
			foreach ($value->find('.venta') as $key3 => $value3) {
				if($f3 == 0){
					$f3 = 1;
					if($discount == 1){

						$store->precio = $discountPrice;
						
					}else{
						$_precio = str_replace('.', '', $value3->children(1)->innertext);

						$store->precio = trim($_precio);
					}
				}
			}
			

		}

		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class Carlosgutierrez{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		$_pid = explode("/", $url);
		$pid_ = $_pid[sizeof($_pid) - 1];
		$apiURL = "https://api.carlosgutierrez.com.uy/api/articulos/".$pid_;
		$handle = curl_init($apiURL);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);

		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		if($httpCode == 404){
			SendMail::send('El producto <a href="'.$url.'">'.$url.'</a> no se ha encontrado (Código 404)');
		}else{
			$page = @file_get_html(null, $apiURL);
			$decoded = json_decode($page);
			if(property_exists($decoded, "ArtNombre")){
				$store->titulo = trim($decoded->ArtNombre);
				if(sizeof($decoded->PrecioVigente) > 0){
					$precio = $decoded->PrecioVigente[2]->PViPrecio;
				}else{
					$precio = 0;
				}
				$store->usd = 0;
				$store->precio = $precio;
				$store->moneda = "USD";
				$store->url = $url;
				$store->retrieve($pid, $tag, $callback, $conn);
			}else{
				echo 'Cant get: '.$url;
				return;
			}
		}
		

	}
}
class Latentacion{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		//print_r($page);die;
		if(sizeof($page->find('.product_title')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find('.product_title') as $key => $value) {
			$store->titulo = $value->innertext;
			//echo $value->innertext;die;
		}

		foreach ($page->find('.woocommerce-Price-currencySymbol') as $key => $value) {
			$_moneda = $value->innertext;
			$store->moneda = $_moneda;
			if($_moneda == "USD"){
				$store->usd = 1;
			}else{
				$store->usd = 0;
			}
			break;
		}
		$f = 0;
		foreach ($page->find('.price') as $key1 => $value1) {
			foreach ($value1->find('.woocommerce-Price-amount') as $key => $value) {
				$f++;
				if($f == sizeof($value1->children())){
					$_s = explode("&nbsp;", $value->innertext);
					if($store->usd == 0){
						$_precio = str_replace('<span class="woocommerce-Price-currencySymbol">&#36;</span>', '', $_s[0]);
					}else{
						$_precio = str_replace('<span class="woocommerce-Price-currencySymbol">USD</span>', '', $_s[0]);
					}
					$_precio = str_replace('.', '', $_precio);

					$store->precio = trim($_precio);
					break;
				}

			}
			break;
		}


		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class Mercadolibre{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		//$classTitle = '.item-title__primary ';
		$classTitle = '.ui-pdp-title';
		$pausedClass = '.andes-message--warning';
		$store = new Store();
		$store->tag = "Mercadolibre";
		//andes-message--warning

		if(sizeof($page->find($classTitle)) == 0){
			SendMail::send('Hay problemas para extraer la información del producto '.$url.', por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find($classTitle) as $key => $value) {
			$store->titulo = $value->innertext;
		}
		if(sizeof($page->find($pausedClass)) == 0){
			foreach ($page->find('.ui-pdp-container__row--price') as $key => $value) {
				foreach ($value->find('.price-tag-symbol') as $key2 => $value2) {
					$moneda = $value2->innertext;
				
					if($moneda == "$"){
						$store->usd = 0;
						$store->moneda = "$";
					}else{
						$store->usd = 1;
						$store->moneda = "USD";
					}
					//$store->precio = $value->getAttribute('content');

					break;
				}
				foreach ($value->find('meta') as $key1 => $value1) {
					$_precio = explode('.',$value1->getAttribute('content'))[0];
					$store->precio = $_precio;
				}
			}
		}else{
			$store->usd = 1;
			$store->moneda = "USD";
			$store->precio = 0;
			$store->estado = 0;
		}
		
		
		//echo 2;die;
		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);

	}
}
class Geant{
	function __construct(){}
	public static function get($pid, $tag, $url, $page, $conn, $callback){
		$store = new Store();
		$store->tag = "Geant";
		if(sizeof($page->find('.productName')) == 0){
			SendMail::send('Hay problemas para extraer la información del producto <a href="'.$url.'">'.$url.'</a>, por favor contacte a nuestro soporte.');
			return;
		}
		foreach ($page->find('.productName') as $key => $value) {
			$store->titulo = $value->innertext;
		}
		foreach ($page->find('#___rc-p-sku-ids') as $key => $value) {
			$productInfo = json_decode(file_get_html(null, "https://www.geant.com.uy/api/catalog_system/pub/products/search/?fq=skuId:".$value->getAttribute('value')))[0];
			if(property_exists($productInfo, 'Precio Dolar')){
				if($productInfo->{"Precio Dolar"}[0] == "Si"){
					$store->moneda = "USD";
					$store->usd = 1;
				}else{
					$store->moneda = "$";
					$store->usd = 0;
				}
			}else{
				$store->usd = 0;
			}

		}
		foreach ($page->find('#___rc-p-dv-id') as $key => $value) {
			//estan siempre en pesos en el source
			$_price = $value->getAttribute('value');
			
			$store->precio = floatval($_price);
		}
		$store->url = $url;
		$store->retrieve($pid, $tag, $callback, $conn);
	}
}
class SendMail{
	public static function send($msg){
		mail("dummy@example.com","Panel Productos",$msg);
	}

}
class CurrencyValue{
	private static $c;
	function __construct(){}
	public static function get(){
		$cURL = "https://www.brou.com.uy/c/portal/render_portlet?p_l_id=20593&p_p_id=cotizacionfull_WAR_broutmfportlet_INSTANCE_otHfewh1klyS&p_p_lifecycle=0&p_t_lifecycle=0&p_p_state=normal&p_p_mode=view&p_p_col_id=column-1&p_p_col_pos=0&p_p_col_count=2&p_p_isolated=1&currentURL=%2Fcotizaciones";
		$page = file_get_html(null, $cURL);
		$global_cotizacion = '';
		foreach ($page->find('table') as $key => $value) {
		   self::$c = str_replace(',', '.', trim($value->children(1)->children(0)->children(4)->plaintext));
		}

		return self::$c;
	}
}
function tofloat($num) {
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
  
    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    }

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
    );
}

?>