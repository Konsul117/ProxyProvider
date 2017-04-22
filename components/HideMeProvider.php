<?php

namespace proxyProvider\components;

use proxyProvider\exceptions\ProxyProviderDataNotReceivedException;
use phpQuery;

class HideMeProvider extends AbstractProxyProvider {

	/**
	 * @inheritdoc
	 *
	 * @throws ProxyProviderDataNotReceivedException
	 */
	protected function getProxyListFromApi() {
		$queryUrl = 'https://hidemy.name/ru/proxy-list/';

		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $queryUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		$agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
		curl_setopt($curl, CURLOPT_USERAGENT, $agent);
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

		return $this->parseProxies($curlResult);
	}

	/**
	 * @param string $data
	 *
	 * @return ProxyData[]
	 */
	protected function parseProxies($data) {
		$result = [];

		$firstPage = phpQuery::newDocumentHTML($data);

		foreach($firstPage->find('.proxy__t tbody tr') as $tr) {
			$proxy = new ProxyData();

			$proxy->address = phpQuery::pq($tr)->find('td')->eq(0)->text();
			$proxy->port = phpQuery::pq($tr)->find('td')->eq(1)->text();

			$result[] = $proxy;
		}

		return $result;
	}

	/**
	 * Получение ключа кэша для списка прокси.
	 *
	 * @return string
	 */
	protected function getProxyListCacheKey() {
		return __METHOD__ . '1';
	}

	/**
	 * Получение ключа кэша для списка бана.
	 *
	 * @return string
	 */
	protected function getBanListCacheKey() {
		return __METHOD__ . '1';
	}
}