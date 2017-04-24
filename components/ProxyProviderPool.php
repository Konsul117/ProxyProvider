<?php

namespace proxyProvider\components;

use proxyProvider\exceptions\ProxyProviderDataNotReceivedException;
use DateTime;
use Yii;
use yii\base\Component;
use yiiCustom\helpers\DateHelper;

/**
 * Пул провайдеров прокси.
 */
class ProxyProviderPool extends Component {

	/** @var array Конфиги компонентов провайдеров */
	public $providersConfigs;
	const ATTR_PROVIDERS_CONFIGS = 'providersConfigs';

	/** @var AbstractProxyProvider[] Экземпляры провайдеров */
	protected $providers;

	/** @var ProxyData[] Список прокси-серверов */
	protected $proxies;

	/** @var ProxyStat[] Статистика по прокси серверам. Индекс по идентификатору прокси */
	protected $proxyStatistics;

	/**
	 * @inheritdoc
	 */
	public function init() {
		foreach ($this->providersConfigs as $config) {
			$this->providers[] = Yii::createObject($config);
		}
	}

	/**
	 * Получение прокси для запроса.
	 * При каждом запросе необходимо получать новый прокси через данный метод.
	 *
	 * @return ProxyData|null Прокси или null, если получить прокси не удалось
	 */
	public function getProxy() {
		if ($this->proxies === null) {
			$this->loadProxiesFromProviders();
		}

		//проходимся по статистике и подбираем подходящий
		$proxyStat = $this->proxyStatistics;

		uasort($proxyStat, function($a, $b) {/** @var $a ProxyStat *//** @var $b ProxyStat */
			$result = ($a->successRequests < $b->successRequests) ? 10 : -10;
			if ($a->errorRequests > $b->errorRequests) {
				$result -= 1;
			}
			else {
				$result += 1;
			}

			return $result;
		});

		//далее выбираем по списку сервер, к которому было обращение более 2 минут назад, но не далее 10го по списку
		$resultProxy = null;
		$currentStamp = new DateTime('now');
		foreach ($proxyStat as $stat) {
			if ($stat->lastRequestStamp === null) {
				$resultProxy = $stat->proxy;

				break;
			}

			if (($currentStamp->getOffset() - $stat->lastRequestStamp->getOffset()) > 60 * 2) {
				$resultProxy = $stat->proxy;

				break;
			}
		}

		//если прокси так и не найден, то берём последний по списку
		if ($resultProxy === null) {
			$lastStat = $proxyStat[array_rand($proxyStat)];/** @var ProxyStat $lastStat */

			$resultProxy = $lastStat->proxy;
		}

		return $resultProxy;
	}

	/**
	 * Добавление статистики по прокси.
	 *
	 * @param string $proxyId Идентификатор прокси
	 * @param bool   $result  Результат запроса по прокси
	 */
	public function addProxyStat($proxyId, $result) {
		$statItem = $this->proxyStatistics[$proxyId];

		if ($result === true) {
			$statItem->successRequests++;
		}
		else {
			$statItem->errorRequests++;
		}

		$statItem->lastRequestStamp = new DateTime('now');
	}

	protected function loadProxiesFromProviders() {
		$this->proxies = [];
		foreach($this->providers as $provider) {
			try {
				$list = $provider->getProxyList();
			}
			catch (ProxyProviderDataNotReceivedException $e) {
				continue;
			}
			$this->proxies = array_merge($this->proxies, $list);
		}

		//инициализируем статистику по прокси
		foreach($this->proxies as $proxy) {
			$statItem = new ProxyStat();

			$statItem->proxy = $proxy;

			$this->proxyStatistics[$proxy->id] = $statItem;
		}
	}
}