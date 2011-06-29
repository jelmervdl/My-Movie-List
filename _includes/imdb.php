<?php

/**
 * IMDB Class that uses the mobile apps API of IMDb.
 * Based on https://github.com/dbr/IMDb-Python-API
 */

namespace imdb;

class IMDb
{ 
	private $_api;
	
	public function __construct(IMDbAPI $api)
	{
		$this->_api = $api;
	}
	
	public function find($title)
	{
		$response = $this->_api->request('/find', array('q' => $title));
		
		$results = array();
		
		foreach ($response->results as $result_set)
		{
			foreach ($result_set->list as $result)
			{
				if ($this->_is_movie($result))
					$results[] = IMDbMovie::factory($this->_api, $result);
			}
		}
		
		return $results;
	}
	
	public function movie($id)
	{
		return new IMDbMovie($this->_api, $id);
	}
	
	public function person($id)
	{
		
	}
	
	public function top250()
	{
		return $this->_api->request('/chart/top');
	}
	
	private function _is_movie($result)
	{
		return isset($result->tconst) && substr($result->tconst, 0, 2) == 'tt';
	}
}

class IMDbMovie
{
	private $_api;
	
	private $_id;
	
	private $_data;
	
	public function __construct(IMDbAPI $api, $id)
	{
		$this->_api = $api;
		
		$this->_id = $id;
	}
	
	static public function factory(IMDbAPI $api, $data)
	{
//		var_dump($data, $data->title);
		$movie = new self($api, $data->tconst);
		$movie->_data = $data;
		return $movie;
	}
	
	public function data($path)
	{
		if (!$this->_data)
			$this->_data = $this->_api->request('/title/maindetails', array('tconst' => $this->_id));
		
		return array_keypath($this->_data, $path);
	}
	
	public function id()
	{
	    return $this->data('tconst');
	}
	
	public function title()
	{
		return $this->data('title');
	}
	
	public function year()
	{
		return $this->data('year');
	}
	
	public function type()
	{
		return $this->data('type');
	}
	
	public function image()
	{
		return $this->data('image');
	}
	
	public function cast()
	{
		return $this->_api->request('/title/fullcredits', array('tconst' => $this->_id));
	}
}

class IMDbAPI
{
	private $_api = 'v1';
	
	private $_app_id = 'iphone1_1';
	
	private $_api_key = '2wex6aeu6a8q9e49k7sfvufd6rhh0n';
	
	private $_host = 'app.imdb.com';
	
	private $_api_policy = 'app1_1';
	
	private $_request_class;
	
	public $locale = 'en_US';
	
	public function __construct($request_classname)
	{
		$request_class = new \ReflectionClass($request_classname);
		
		if (!$request_class->implementsInterface(__NAMESPACE__ . '\IMDbRequest'))
			throw new \InvalidArgumentException('Request class has to implement IMDbRequest interface');
		
		$this->_request_class = $request_class;
		
		$this->_device_id = self::_generateDeviceId();
	}
	
	public function request($url, $arguments = array())
	{
		$params = array_merge(array(
			'api' => $this->_api,
			'app_id' => $this->_app_id,
			'device' => $this->_device_id,
			'locale' => $this->locale,
			'timestamp' => time(),
			'sig' => $this->_api_policy
		), $arguments);
		
		$url = sprintf('http://%s%s?%s',
			$this->_host, $url, http_build_query($params));
		
		$hash = hash_hmac('sha1', $url, $this->_api_key, false);
		
		$signed_url = sprintf('%s-%s', $url, $hash);
		
		//$request = $this->_request_class->newInstance($signed_url);
		// Signed urls don't seem to work as intended? They are parsed as
		// if they are part of the query string of the request.
		
		$request = $this->_request_class->newInstance($url);
		$request->addRequestHeader('User-Agent', 'Mozilla/5.0 '
			. '(Macintosh; U; Intel Mac OS X 10_6_3; en-us) '
			. 'AppleWebKit/531.22.7 (KHTML, like Gecko) '
			. 'Version/4.0.5 Safari/531.22.7');
		
		$response = $request->send();
		
		if ($response === false)
			throw new IMDbException('Request failed: ' . $request->error());
		
		$data = json_decode($response);
		
		if ($data === null)
			throw new IMDbException('Could not decode JSON response data');
		
		if (isset($data->error))
			throw new IMDbException(sprintf('%d %s: %s', $data->error->status,
				$data->error->code, $data->error->message));
		
		if (!isset($data->data))
			throw new IMDbException('No data section found in the decoded JSON response');
		
		return $data->data;
	}
	
	private static function _generateDeviceId()
	{
		$id = '';
		
		for ($i = 0; $i < 40; ++$i)
			$id .= chr(mt_rand(97, 122));
		
		return $id;
	}
}

interface IMDbRequest
{
	public function __construct($url);
	public function addRequestHeader($key, $value);
	public function send();
	public function error();
}

class IMDbException extends \Exception
{}

class CurlRequest implements IMDbRequest
{
	private $_curl_handle;
	
	private $_headers = array();
	
	public function __construct($url)
	{
		$this->_curl_handle = curl_init($url);
	}
	
	public function addRequestHeader($key, $value)
	{
		$this->_headers[$key] = $value;
	}
	
	public function send()
	{
		$headers = array();
		
		foreach ($this->_headers as $key => $value)
			$headers[] = sprintf('%s: %s', $key, $value);
		
		curl_setopt($this->_curl_handle, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($this->_curl_handle, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($this->_curl_handle);
	}
	
	public function error()
	{
		return curl_error($this->_curl_handle);
	}
}

class FopenRequest implements IMDbRequest
{
	private $_url;
	
	private $_headers = array();
	
	private $_error;
	
	public function __construct($url)
	{
		$this->_url = $url;
	}
	
	public function addRequestHeader($key, $value)
	{
		$this->_headers[$key] = $value;
	}
	
	public function send()
	{
		$headers = array();
		
		foreach ($this->_headers as $key => $value)
			$headers[] = sprintf('%s: %s', $key, $value);
		
		$opts = array('http' => array(
			'method' => 'GET',
			'header' => $headers
		));
		
		$context = stream_context_create($opts);
		
		$response = file_get_contents($this->_url, false, $context);
		
		if ($response === false)
			$this->_set_error_msg('file_get_contents failed');
		
		return $response;
	}
	
	private function _set_error_msg($message)
	{
		$php_last_error = error_get_last();
		
		$php_error_msg = $php_last_error !== null
			? $php_last_error['message']
			: '(no php error)';
		
		$this->_error = sprintf('%s: %s', $message, $php_error_msg);
	}
	
	public function error()
	{
		return $this->_error;
	}
}

class CachedRequest extends CurlRequest
{
	private $_url;
	
	public function __construct($url)
	{
		parent::__construct($url);
		$this->_url = $url;
	}
	
	public function send()
	{
		//$cache_file = sprintf('/tmp/http_cache_%s', md5($this->_url));
		$cache_file = '/tmp/http_cache_imdb';
		
		if (file_exists($cache_file))
			return file_get_contents($cache_file);
		
		$response = parent::send();
		
		file_put_contents($cache_file, $response);
		
		return $response;
	}
}

class DebugRequest extends CurlRequest
{
	public function __construct($url)
	{
		echo "Requesting $url\n";
		parent::__construct($url);
	}
}

function array_keypath($array, $path)
{
	if (!is_array($path))
		$path = preg_split('/[\s\.]*\[([^\]]+)\]\s*|\.+/',
			$path, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	
	while (($step = array_shift($path)) !== null)
	{
		if (is_object($array) && !$array instanceof ArrayAccess)
		{
			if (!isset($array->$step))
				return null;
			
			$array = $array->$step;
		}
		else
		{
			if (!isset($array[$step]))
				return null;
				
			$array = $array[$step];
		}
	}
	
	return $array;
}

function imdb()
{
	if (function_exists('curl_init'))
		$request_class = 'CurlRequest';
	else if (ini_get('allow_url_fopen'))
		$request_class = 'FopenRequest';
	else
		throw new RuntimeException('None of the request methods are supported on this PHP configuration');
	
	return new IMDb(new IMDbAPI(__NAMESPACE__ . '\\' . $request_class));
}

if ($_SERVER['PHP_SELF'] == __FILE__)
{
  function test()
  {
  	error_reporting(E_ALL ^ E_STRICT);
  	ini_set('display_errors', true);

  	$imdb = new IMDb(new IMDbAPI('imdb\CachedRequest'));
  	//var_dump($imdb->movie('tt0347149')->title());
  	var_dump($imdb->find('Sucker Punch'));
  }

  test();
}