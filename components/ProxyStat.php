<?php

namespace proxyProvider\components\proxyProvider;

use DateTime;

/**
 * Статистика прокси.
 */
class ProxyStat {

	/** @var ProxyData Прокси */
	public $proxy;

	/** @var int Количество успешных запросов */
	public $successRequests = 0;

	/** @var int Количество ошибочных запросов */
	public $errorRequests = 0;

	/** @var DateTime Дата-время последнего запроса */
	public $lastRequestStamp;

}