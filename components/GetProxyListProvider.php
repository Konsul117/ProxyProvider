<?php

namespace proxyProvider\components;

use proxyProvider\exceptions\ProxyProviderDataNotReceivedException;

/**
 * @link https://getproxylist.com/
 */
class GetProxyListProvider extends AbstractProxyProvider {

	/**
	 * Получение списка прокси из api.
	 *
	 * @return ProxyData[]
	 *
	 * @throws ProxyProviderDataNotReceivedException
	 */
	protected function getProxyListFromApi() {
		$result = [];

		for($i = 0; $i < 10; $i++) {
			$result[] = $this->getSingle();
		}

		return $result;
	}

	protected function getSingle() {
		$queryUrl = 'https://api.getproxylist.com/proxy';

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

		$apiResult = @json_decode($curlResult, true);

		$result = new ProxyData();

		$result->address = $apiResult['ip'];
		$result->port = $apiResult['port'];

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