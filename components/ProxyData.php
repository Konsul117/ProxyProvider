<?php

namespace proxyProvider\components\proxyProvider;

use Faker\Provider\Uuid;

/**
 * Модель-обёртка для данных о прокси-сервере.
 */
class ProxyData {

	/** @var string Идентификатор прокси */
	public $id;

	/** @var string IP-адрес или домен прокси */
	public $address;

	/** @var string Порт прокси */
	public $port;

	/**
	 * ProxyData constructor.
	 */
	public function __construct() {
		$this->id = Uuid::uuid();
	}

	/**
	 * Получение строки адреса: адрес:порт
	 *
	 * @return string
	 */
	public function getAddressString() {
		return $this->address . ':' . $this->port;
	}

}