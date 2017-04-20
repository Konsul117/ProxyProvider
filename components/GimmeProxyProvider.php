<?php

namespace proxyProvider\components\proxyProvider;

use proxyProvider\exceptions\ProxyProviderDataNotReceivedException;

class GimmeProxyProvider extends AbstractProxyProvider {

	const BASE_URL = 'https://gimmeproxy.com/api/getProxy';

	/**
	 * Получение списка прокси из api.
	 *
	 * @return ProxyData[]
	 *
	 * @throws ProxyProviderDataNotReceivedException
	 */
	protected function getProxyListFromApi() {
		$list = [];
		for($i = 0; $i < 10; $i++) {
			$proxy = $this->getSingleProxy();

			if ($proxy !== null) {
				$list[] = $proxy;
			}
		}

		return $list;
	}

	/**
	 * Получение 1 прокси.
	 *
	 * @return ProxyData|null Проски или null, если данные получить не удалось
	 */
	protected function getSingleProxy() {
		$queryUrl = static::BASE_URL . '?' . http_build_query([]);

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
			return null;
		}

		if ($responseHttpCode !== 200) {
			return null;
		}

		$params = @json_decode($curlResult, true);

		$result = new ProxyData();

		$result->address = $params['ip'];
		$result->port    = $params['port'];

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	protected function getProxyListCacheKey() {
		return __METHOD__ . '.v-3';
	}

	/**
	 * @inheritdoc
	 */
	protected function getBanListCacheKey() {
		return __METHOD__ . '.v-1';
	}
}