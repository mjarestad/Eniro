<?php namespace Mjarestad\Eniro;

use App;
use Config;
use Buzz\Browser;

class Eniro
{
	/**
	 * API URL
	 * @var string
	 */
	private $apiUrl = 'api.eniro.com/cs/search/basic';

	/**
	 * Api request params
	 * @var array
	 */
	private $apiParams = array();

	/**
	 * Result attributes
	 * @var array
	 */
	private $attributes = array();

	/**
	 * Request URL
	 * The complete for api request
	 * @var string
	 */
	private $request;

	/**
	 * Response
	 * @var string
	 */
	private $response = null;

	/**
	 * Result content
	 * @var string
	 */
	private $content = null;

	/**
	 * Eniro response error messages
	 * @var array
	 */
	private $messages = array(
		'001' => 'Eniro api - Invalid profile name or key.', // HTTP response code 401
		'002' => 'Eniro api - Base parameters invalid or missing.', // HTTP response code 400
		'003' => 'Eniro api - Invalid version.', // HTTP response code 400
		'005' => 'Eniro api - Invalid country code.', // HTTP response code 400
		'006' => 'Eniro api - Profile does not have rights to perform this search.', // HTTP response code 403
		'007' => 'Eniro api - Profile blocked.', // HTTP response code 403
		'008' => 'Eniro api - Profile search limit depleted.', // HTTP response code 429
		'010' => 'Eniro api - Internal server error.' //  HTTP response code 500
	);

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->setDefaults();
	}

	/**
	 * Set default configs
	 * @return void
	 */
	protected function setDefaults()
	{
		$this->setApiParam('profile', Config::get('eniro::profile'));
		$this->setApiParam('key', 	  Config::get('eniro::key'));
		$this->setApiParam('version', Config::get('eniro::version'));
		$this->setApiParam('country', Config::get('eniro::country'));
	}

	/**
	 * Search Query
	 * Specifies the search criteria, the keyword or keywords you would like to search for.
	 * Example values: advokat, hoteller, vvs
	 * @param  string $query
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function search($query = null)
	{
		$this->setApiParam('search_word', trim($query));
		$this->callApi();

		return $this;
	}
	
	/**
	 * Offset
	 * Specifies the pagination start location, i.e. where in the result set you want to the results to start.
	 * The start of the result set is at location 1. The default result set size is 25.
	 * Note: to be used in conjuction with to_list to get expected results.
	 * @param  integer $offset
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function skip($offset)
	{
		$this->setApiParam('from_list', (int) $offset);

		return $this;
	}

	/**
	 * Limit
	 * Specifies the pagination end location, i.e. where in the result set you want to the results to end.
	 * The default result set size is 25. Note that you can maximum get a response with 100 results.
	 * I.e. to_list - from_list < 100. Also note to_list is to be used in conjuction with from_list to get expected results.
	 * @param  integer $limit
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function take($limit)
	{
		$limit = (int) isset($this->apiParams['from_list']) ? $this->apiParams['from_list'] + $limit : $limit;
		$this->setApiParam('to_list', $limit);

		return $this;
	}

	/**
	 * Country
	 * Specifies the country you would like to search in. You may search in only one country at a time.
	 * Acceptable values:
	 * se Sweden (eniro.se), dk Denmark (krak.dk), no Norway (gulesider.no)
	 * @param  string $country
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function country($country)
	{
		$this->setApiParam('country', $country);

		return $this;
	}

	/**
	 * Geo Area
	 * Specifies the general location where you would like to search, usually a city or area.
	 * Example values: stockholm, københavn, oslo
	 * @param  string $area
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function area($area)
	{
		$this->setApiParam('geo_area', $area);

		return $this;
	}

	/**
	 * Eniro ID
	 * Specifies the Eniro ID of a particular company you want to search for.
	 * @param  integer $id
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function id($id)
	{
		$this->setApiParam('eniro_id', (int) $id);

		return $this;
	}

	/**
	 * Seed
	 * When receiving search results with the same relevance level,
	 * their sort order will be based on the value of the seed parameter.
	 * Example value: 1337
	 * @param  integer $seed
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function seed($seed)
	{
		$this->setApiParam('seed', (int) $seed);

		return $this;
	}

	/**
	 * JSONP Callback
	 * Specifies the JSONP callback name. Example: &callback=parseResponse will result in JSONP code:
	 * parseResponse({...}); where {...} is the JSON result object. 
	 * Note! If an error occurs the response will be something like: parseResponse({"error": "010"});
	 * Example values: parseResponse, object.displayList, obj%5B%22function-name%22%5D = obj["function-name"]
	 * @param  string   $callback
	 * @return Mjarestad\Eniro\Eniro
	 */
	public function callback($callback)
	{
		$this->setApiParam('callback', $callback);

		return $this;
	}

	/**
	 * Get the json formated result
	 * @return string
	 */
	public function toJson()
	{
		return $this->getContent();
	}

	/**
	 * Convert result to an array
	 * @return array
	 */
	public function toArray()
	{
		return (array) json_decode(json_encode($this->getAttributes()), true);
	}

	/**
	 * Make the call to the api
	 * @return void
	 */
	protected function callApi()
	{
		$this->buildUrl();

		$browser 	= new Browser;
		$response 	= $browser->get($this->getRequest());
		$statusCode = $response->getStatuscode();

		$this->setResponse($response);
		$this->setContent(trim($response->getContent()));

		if( $statusCode !== 200 )
		{
			$this->throwError($statusCode);
		}

		$this->setAttributes();
	}

	/**
	 * Throw exception error
	 * @param  integer $statusCode
	 * @return Exception
	 */
	protected function throwError($statusCode)
	{
		$content = $this->getDecodedContent();
		App::abort($statusCode, $this->messages[$content->error]);
	}

	/**
	 * Build the request url
	 * @return void
	 */
	protected function buildUrl()
	{
		$protocol 		= Config::get('eniro::secure') === true ? 'https' : 'http';
		$queryString 	= $this->getApiParams();
		$url 			= "$protocol://$this->apiUrl?$queryString";

		$this->setRequest($url);
	}

	/**
	 * Set request
	 * @return void
	 */
	protected function setRequest($url)
	{
		$this->request = $url;
	}

	/**
	 * Get request
	 * @return string
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set response
	 * @param string $response
	 * @return void
	 */
	protected function setResponse($response)
	{
		$this->response = $response;
	}

	/**
	 * Get the response
	 * @return string
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Set content
	 * @param string $content
	 * @return void
	 */
	protected function setContent($content)
	{
		$this->content = trim($content);
	}

	/**
	 * Get content
	 * @return string
	 */
	protected function getContent()
	{
		return $this->content;
	}

	/**
	 * Get the content in json decoded format
	 * @return stdClass
	 */
	protected function getDecodedContent()
	{
		return json_decode($this->getContent());
	}

	/**
	 * Set api call params
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function setApiParam($key, $value)
	{
		$this->apiParams[$key] = $value;
	}

	/**
	 * Get api call params encoded to querystring
	 * @return string
	 */
	protected function getApiParams()
	{
		return http_build_query($this->apiParams);
	}

	/**
	 * Set all attributes from content
	 * @return void
	 */
	protected function setAttributes()
	{
		foreach($this->getDecodedContent() as $key => $value)
		{
			$this->setAttribute($key, $value);
		}
	}

	/**
	 * Get all attributes
	 * @return array
	 */
	protected function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set attribute
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function setAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}

	/**
	 * Get attribute
	 * @param  string $key
	 * @return mixed
	 */
	protected function getAttribute($key)
	{
		if(array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
		}

		return null;
	}

	/**
	 * Set attributes dynamically
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->attributes[$key] = $value;
	}

	/**
	 * Get attributes dynamically
	 * @param  string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if(array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
		}

		return null;
	}

	/**
	 * Checks if an attribute is set
	 * @param  string  $key
	 * @return boolean
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unset an attribute
	 * @param string $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}
}