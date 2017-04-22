<?php

namespace proxyProvider\components;

use proxyProvider\exceptions\ProxyProviderDataNotReceivedException;

/**
 * spinproxies.com
 */
class SpinProxiesProvider extends AbstractProxyProvider {

	const BASE_URL = 'https://spinproxies.com/api/v1/proxylist';

	/** @var string Ключ api */
	public $key;

	/**
	 * Получение списка прокси из api.
	 *
	 * @return ProxyData[]
	 *
	 * @throws ProxyProviderDataNotReceivedException
	 */
	protected function getProxyListFromApi() {
		$queryUrl = static::BASE_URL . '?' . http_build_query([
				'country_code' => 'US',
				'key' => $this->key,
			]);

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $queryUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
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

		$apiResult = @json_decode($curlResult, true);

		if ($apiResult === null) {
			throw new ProxyProviderDataNotReceivedException('Не удалось распарсить ответ');
		}

		if ($apiResult['message'] !== 'ok') {
			throw new ProxyProviderDataNotReceivedException('Неуспешный ответ api');
		}

		$result = [];

		foreach ($apiResult['data']['proxies'] as $row) {
			$proxy = new ProxyData();

			$proxy->address = $row['ip'];
			$proxy->port    = $row['port'];

			$result[$proxy->id] = $proxy;
		}

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	protected function getProxyListCacheKey() {
		return __METHOD__ . '.v-6';
	}

	/**
	 * @inheritdoc
	 */
	protected function getBanListCacheKey() {
		return __METHOD__ . '.v-1';
	}
}