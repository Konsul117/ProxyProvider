<?php

namespace proxyProvider\components;

use proxyProvider\exceptions\ProxyProviderDataNotReceivedException;
use yii\base\InvalidConfigException;

/**
 * Провайдер прокси "FreeProxyList".
 *
 * @link http://www.freeproxy-list.ru
 */
class FreeProxyListProvider extends AbstractProxyProvider {

	/** Основной URL получения данных */
	const BASE_URL = 'http://www.freeproxy-list.ru/api/proxy';

	/** Период кэширования */
	const CACHE_DURATION = 15 * 60;

	/** @var string Токен доступа к сервису */
	public $token;

	/** @var ProxyData[] Акутальный список прокси */
	protected $actualProxyList = [];

	/** @var string[] Список идентификаторов прокси, которые забанены */
	protected $proxyBanList = [];

	/** @var bool Принудительная загрузка из api, минуя кэш */
	protected $forceLoad = false;

	/**
	 * @inheritdoc
	 */
	public function init() {
		parent::init();

		if ($this->token === null) {
			throw new InvalidConfigException('Отсутствует токен сервиса');
		}
	}

	/**
	 * Получение списка прокси из api.
	 *
	 * @return ProxyData[]
	 * @throws ProxyProviderDataNotReceivedException
	 */
	protected function getProxyListFromApi() {
		$queryUrl = static::BASE_URL . '?' . http_build_query([
				'anonymity' => true,
				'count'     => 30,
				'token'     => $this->token,
			]);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $queryUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl, CURLOPT_TIMEOUT, 5);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$curlResult = curl_exec($curl);

		$errNo = curl_errno($curl);

		$responseHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		if ($errNo !== 0) {
			throw new ProxyProviderDataNotReceivedException('Ошибка curl: ' . $errNo);
		}

		if ($responseHttpCode !== 200) {
			throw new ProxyProviderDataNotReceivedException('Http code ' . $responseHttpCode);
		}

		$rows = explode("\n", $curlResult);

		$result = [];

		foreach ($rows as $row) {
			$proxy = new ProxyData();

			$dataItems = explode(':', $row);

			$proxy->address = $dataItems[0];
			$proxy->port    = $dataItems[1];

			$result[$proxy->id] = $proxy;
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	protected function getProxyListCacheKey() {
		return __METHOD__ . '.v-1';
	}

	/**
	 * @inheritdoc
	 */
	protected function getBanListCacheKey() {
		return __METHOD__ . '.v-1';
	}
}