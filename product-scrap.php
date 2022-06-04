<?php
//header ('Content-type: text/html; charset=utf-8');
ini_set('user_agent', 'My-Application/2.5');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('simple_html_dom.php');
include_once('modules.php');

class Scraper{
	private static $allowedUrls = [
		"loi.com.uy" => "Loi",
		"dimm.com.uy" => "Dimm",
		"pcm.com.uy" => "Pcm",
		"nnet.com.uy" => "Nnet",
		"multiahorro.com.uy" => "Multiahorro",
		"carlosgutierrez.com.uy" => "Carlosgutierrez",
		"latentacion.com.uy" => "Latentacion",
		"geant.com.uy" => "Geant",
		"mercadolibre.com.uy" => "Mercadolibre",
		"tiendainglesa.com.uy" => "TiendaInglesa"
	];
	private $existence = 0;
	private $instance = "";
	private $debugger = array();
	public function __construct(){

	}
	public static function scrap($url, $pid=null, $tag=null, $conn=null, $callback=null){
		global $existence, $instance;
		foreach (self::$allowedUrls as $key => $value) {
			$t = strpos($url, $key);
			if($t){
				$existence = 1;
				$instance = $value;
			}
		}
		if($existence == 0){
			//echo "Site not allowed";
			//die;
		}else{

			$handle = curl_init($url);
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);

			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			//echo $httpCode; die;
			if($httpCode == 404) {
			    /* Handle 404 here. */
			    SendMail::send('El producto <a href="'.$url.'">'.$url.'</a> no se ha encontrado (Código 404)');
			}else if($httpCode == 301){
			  	SendMail::send('La extracción ha detectado que el producto: '.$url.' se ha movido permanentemente (Código 301)');
			}else if($httpCode == 502){
				$fallbackpage =  get_web_page_502fallback($url);
				$content = $fallbackpage['content'];
				$page = file_get_html($content);
				if($page){
					$instance::get($pid, $tag, $url, $page, $conn, $callback);
				}else{
					SendMail::send('El producto '.$url.' ya no existe');
				}
			}else{
				
				$page = @file_get_html(null, $url);
				//print_r($page);die;
				if($page){
					$instance::get($pid, $tag, $url, $page, $conn, $callback);
				}else{
					SendMail::send('El producto '.$url.' ya no existe');
				}//else send mail
				//print_r($page);die;
			     // If not 404, you can use it as usually, ->find(), etc
			}
		}
	}
}


if(isset($_POST['url'])){
	$url = trim($_POST['url']);
	Scraper::scrap($url);
	//print_r(get_web_page($url));die;
}
/*TESTING*/
if(isset($_GET['url'])){
	$url = trim($_GET['url']);
	Scraper::scrap($url);
	//print_r(get_web_page($url));die;
}

 /**
     * Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
     * array containing the HTTP server response header fields and content.
     */
function get_web_page_502fallback( $url ){
    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    $options = array(

        CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
        CURLOPT_POST           =>false,        //set to GET
        CURLOPT_USERAGENT      => $user_agent, //set user agent
        CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
        CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
}
//Scraper::scrap("https://www.tiendainglesa.com.uy/Monitor-SAMSUNG-24%22-F390-Curvo.producto?335505");die;
?>