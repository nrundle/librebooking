<?php
/**
Copyright 2012 Nick Korbel

This file is part of phpScheduleIt.

phpScheduleIt is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

phpScheduleIt is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with phpScheduleIt.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(ROOT_DIR . 'lib/external/Slim/Slim.php');
require_once(ROOT_DIR . 'lib/WebService/IRestServer.php');

class SlimServer implements IRestServer
{
	/**
	 * @var Slim\Slim
	 */
	private $slim;

	public function __construct(Slim\Slim $slim)
	{
		$this->slim = $slim;
	}

	public function GetRequest()
	{
		return json_decode($this->slim->request()->getBody());
	}

	public function WriteResponse(RestResponse $restResponse, $statusCode = 200)
	{
		$this->slim->response()->header('Content-Type', 'application/json');
		$this->slim->response()->status($statusCode);
		$this->slim->response()->write(json_encode($restResponse));
	}

	public function GetServiceUrl($serviceName, $params = array())
	{
		return $this->slim->urlFor($serviceName, $params = array());
	}

	public function GetUrl()
	{
		return $this->slim->environment()->offsetGet('slim.url_scheme') . '://' . $this->slim->environment()->offsetGet('HOST');
	}

	public function GetFullServiceUrl($serviceName, $params = array())
	{
		return $this->GetUrl() . $this->GetServiceUrl($serviceName, $params);
	}
}

?>