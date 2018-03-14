<?php declare(strict_types=1);

/**
 *
 * DB-IP.com API client class
 *
 * Copyright (C) 2018 db-ip.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

namespace DBIP;

const baseUrl = "http://api.db-ip.com/v2/";

class ClientError extends \Exception {

}

class ServerError extends \Exception {
	private $errorCode;
	public function __construct(string $message, string $errorCode) {
		parent::__construct($message);
		$this->errorCode = $errorCode;
	}
	public function getErrorCode() : string {
		return $this->errorCode;
	}
}

class ErrorCode {
	const INVALID_KEY = "INVALID_KEY",
		INVALID_ADDRESS = "INVALID_ADDRESS",
		HTTPS_NOT_ALLOWED = "HTTPS_NOT_ALLOWED",
		TEMPORARY_BLOCKED = "TEMPORARY_BLOCKED",
		TOO_MANY_ADDRESSES = "TOO_MANY_ADDRESSES",
		OVER_QUERY_LIMIT = "OVER_QUERY_LIMIT",
		EXPIRED = "EXPIRED",
		UNAVAILABLE = "UNAVAILABLE";
}

class Client {

	private $baseUrl = "http://api.db-ip.com/v2/";
	private $apiKey;
	private $lang;

	static private $instance;

	static public function getInstance() : self {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct(string $apiKey = null, string $baseUrl = null) {
		if (isset($apiKey)) {
			$this->apiKey = $apiKey;
		} else {
			$this->apiKey = APIKey::$defaultApiKey;
		}
		if (isset($baseUrl)) {
			$this->baseUrl = $baseUrl;
		}
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
			$this->setPreferredLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		}
	}

	protected function apiCall($path = "") : \stdClass {
		$url = $this->baseUrl . $this->apiKey . $path;
		if (isset($this->lang)) {
			$httpOptions = [];
			if (isset($this->lang)) {
				$httpOptions["header"] = "Accept-Language: " . $this->lang;
			}
			$jsonData = @file_get_contents($url, false, stream_context_create([ $httpOptions ]));
		} else {
			$jsonData = @file_get_contents($url);
		}
		if (!$jsonData) {
			throw new ClientError("unable to fetch URL: {$url}");
		}
		if (!$data = @json_decode($jsonData)) {
			throw new ClientError("cannot decode server response");
		}
		if (isset($data->error)) {
			throw new ServerError("server reported an error: {$data->error}", $data->errorCode);
		}
		return $data;
	}

	public function setPreferredLanguage(string $lang) : void {
		$this->lang = $lang;
	}

	public function getAddressInfo($addr) : \stdClass {
		if (is_array($addr)) {
			return $this->apiCall("/" . implode(",", $addr));
		} else {
			return $this->apiCall("/" . $addr);
		}
	}

	public function getKeyInfo() : \stdClass {
		return $this->apiCall();
	}

}

class Address {
	static public function lookup($addr) : \stdClass {
		return Client::getInstance()->getAddressInfo($addr);
	}
}

class APIKey {
	public static $defaultApiKey;
	static public function set(string $apiKey) : void {
		self::$defaultApiKey = $apiKey;
	}
	static public function info() : \stdClass {
		return Client::getInstance()->getKeyInfo();
	}
}