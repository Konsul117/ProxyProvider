<?php

namespace proxyProvider\components;

use proxyProvider\ProxyProviderDataNotReceivedException;
use Yii;
use yii\base\Component;

/**
 * Суперкласс для провайдера прокси.
 */
abstract class AbstractProxyProvider extends Component {

	/** Период кэширования */
	const CACHE_DURATION = 15 * 60;

	/** @var ProxyData[] Акутальный список прокси */
	protected $actualProxyList = [];

	/** @var string[] Список идентификаторов прокси, которые забанены */
	protected $proxyBanList = [];

	/** @var bool Принудительная загрузка из api, минуя кэш */
	protected $forceLoad = false;

	/**
	 * Получение списка прокси.
	 *
	 * @return ProxyData[]
	 *
	 * @throws ProxyProviderDataNotReceivedException
	 */
	public function getProxyList() {
		if (count($this->actualProxyList) === 0) {
			$cacheKey = $this->getProxyListCacheKey();

			$data = Yii::$app->cache->get($cacheKey);
			/** @var ProxyData[] $data */
			$banListCacheKey = $this->getBanListCacheKey();

			if ($this->forceLoad === true || $data === false) {
				$data = $this->getProxyListFromApi();

				Yii::$app->cache->set($cacheKey, $data, static::CACHE_DURATION);
			}
			else {
				//если список прокси взят из кэша, то список банов для них актуален
				$banList = Yii::$app->cache->get($banListCacheKey);

				if ($banList !== false) {
					$this->proxyBanList = $banList;

					foreach ($data as $id => $proxy) {
						if (in_array($id, $banList)) {
							unset($data[$id]);
						}
					}
				}
			}

			$this->actualProxyList = $data;
		}

		$data = $this->actualProxyList;

		shuffle($data);

		return $data;
	}

	/**
	 * Получение списка прокси из api.
	 *
	 * @return ProxyData[]
	 *
	 * @throws ProxyProviderDataNotReceivedException
	 */
	abstract protected function getProxyListFromApi();

	/**
	 * Забанить прокси (когда он был недоступен).
	 *
	 * @param string $proxyId Идентификатор прокси
	 */
	public function banProxy($proxyId) {
		if (array_key_exists($proxyId, $this->actualProxyList)) {
			unset($this->actualProxyList[$proxyId]);

			$this->proxyBanList[] = $proxyId;

			$this->cacheBanList();

			if (count($this->proxyBanList) === 0) {
				$this->proxyBanList = true;
			}
		}
	}

	/**
	 * Кэширование списка бана.
	 */
	protected function cacheBanList() {
		$cacheKey = $this->getBanListCacheKey();

		if (count($this->proxyBanList) > 0) {
			Yii::$app->cache->set($cacheKey, $this->proxyBanList, static::CACHE_DURATION);
		}
		else {
			Yii::$app->cache->delete($cacheKey);
		}
	}

	/**
	 * Получение ключа кэша для списка прокси.
	 *
	 * @return string
	 */
	abstract protected function getProxyListCacheKey();

	/**
	 * Получение ключа кэша для списка бана.
	 *
	 * @return string
	 */
	abstract protected function getBanListCacheKey();

}