<?php
/**
 * @package    RsfpCopernica
 *
 * @author     Perfect Web Team <hallo@perfectwebteam.nl>
 * @copyright  Copyright (C) 2018 Perfect Web Team. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://perfectwebteam.nl
 */

defined('_JEXEC') or die();

/**
 *  Class to deal with the REST API
 */
class CopernicaRestAPI
{
	/**
	 *  The access token
	 *  @var string
	 */
	private $token;

	/**
	 * Set the token.
	 *
	 * @param   string  $token  The token for communicating with Copernica
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 *  Do a GET request
	 *  @param  string      Resource to fetch
	 *  @param  array       Associative array with additional parameters
	 *  @return array       Associative array with the result
	 */
	public function get($resource, array $parameters = array())
	{
		// the query string
		$query = http_build_query(array('access_token' => $this->token) + $parameters);

		// construct curl resource
		$curl = curl_init("https://api.copernica.com/v1/$resource?$query");

		// additional options
		curl_setopt_array($curl, array(CURLOPT_RETURNTRANSFER => true));

		// do the call
		$answer = curl_exec($curl);

		// clean up curl resource
		curl_close($curl);

		// done
		return json_decode($answer, true);
	}

	/**
	 *  Do a POST request
	 *  @param  string      Resource name
	 *  @param  array       Associative array with data to post
	 *  @return mixed       False on failure or the id of the created item
	 */
	public function post($resource, array $data = array())
	{
		// the query string
		$query = http_build_query(array('access_token' => $this->token));

		// construct curl resource
		$curl = curl_init("https://api.copernica.com/v1/$resource?$query");

		// data will be json encoded
		$data = json_encode($data);

		// additional options
		curl_setopt_array($curl, array(
			CURLOPT_POST            =>  true,
			CURLOPT_HEADER          =>  true,
			CURLOPT_RETURNTRANSFER  =>  true,
			CURLOPT_HTTPHEADER      =>  array('content-type: application/json'),
			CURLOPT_POSTFIELDS      =>  $data
		));

		// do the call
		$answer = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		// clean up curl resource
		curl_close($curl);

		// bad request
		if (!$httpCode || $httpCode == 400) return false;

		// try and get the X-Created id from the header
		if (!preg_match('/X-Created:\s?(\d+)/i', $answer, $matches)) return true;

		// return the id of the created item
		return $matches[1];
	}

	/**
	 *  Do a PUT request
	 *  @param  string      Resource name
	 *  @param  array       Associative array with data to post
	 *  @param  array       Associative array with additional parameters
	 *  @return bool
	 */
	public function put($resource, array $data = array(), array $parameters = array())
	{
		// the query string
		$query = http_build_query(array('access_token' => $this->token) + $parameters);

		// construct curl resource
		$curl = curl_init("https://api.copernica.com/v1/$resource?$query");

		// data will be json encoded
		$data = json_encode($data);

		// additional options
		curl_setopt_array($curl, array(
			CURLOPT_CUSTOMREQUEST   =>  'PUT',
			CURLOPT_HTTPHEADER      =>  array('content-type: application/json', 'content-length: '.strlen($data)),
			CURLOPT_POSTFIELDS      =>  $data
		));

		// do the call
		$answer = curl_exec($curl);

		// clean up curl resource
		curl_close($curl);

		// done
		return $answer;
	}

	/**
	 *  Do a DELETE request
	 *  @param  string      Resource name
	 *  @return bool
	 */
	public function delete($resource)
	{
		// the query string
		$query = http_build_query(array('access_token' => $this->token));

		// construct curl resource
		$curl = curl_init("https://api.copernica.com/v1/$resource?$query");

		// additional options
		curl_setopt_array($curl, array(
			CURLOPT_CUSTOMREQUEST   =>  'DELETE'
		));

		// do the call
		$answer = curl_exec($curl);

		// clean up curl resource
		curl_close($curl);

		// done
		return $answer;
	}
}