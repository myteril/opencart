<?php
namespace Opencart\Admin\Controller\Marketing;
/**
 * Class Coupon
 *
 * @package Opencart\Admin\Controller\Marketing
 */
class feed extends \Opencart\System\Engine\Controller {
	/**
	 * @return void
	 */
	public function index(): void {
		$this->load->language('marketing/feed');

		$this->document->setTitle($this->language->get('heading_title'));

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketing/feed', 'user_token=' . $this->session->data['user_token'] . $url)
		];
		$data['breadcrumbs'] = $this->load->controller('common/breadcrumbs', $data['breadcrumbs']);

		$data['add'] = $this->url->link('marketing/coupon.form', 'user_token=' . $this->session->data['user_token'] . $url);
		$data['delete'] = $this->url->link('marketing/coupon.delete', 'user_token=' . $this->session->data['user_token']);

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('marketing/feed', $data));
	}

	/**
	 * @param ...$args
	 *
	 * @return string
	 */
	private function getCacheHash(...$args): string {
		$hash_components = [];
		foreach ($args as $arg) {
			$hash_components[] = (string)$arg;
		}

		return hash('sha256', implode('|', $hash_components));
	}

	/**
	 * @return void
	 */
	public function list(): void {
		$this->load->language('marketing/feed');

		$this->response->setOutput($this->getList());
	}

	/**
	 * @return string
	 */
	protected function getList(): string {

		$feed_list = [];

		// Languages
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();

		$feeds = $this->getFeedList();
		$stores = $this->getStoreList();
		foreach ($feeds as $feed) {
			foreach ($stores as $store) {
				if (!empty($feed['multilanguage'])) {
					foreach ($languages as $language_code => $language) {
						$cache_hash = $this->getCacheHash($store['store_id'], $feed['action'], $language['language_id'], 'last-update');
						$feed_last_update_cache = $this->cache->get($cache_hash);
						$feed_last_update_cache = (int)$feed_last_update_cache;
						$feed_list[] = [
							'store_name'    => $store['name'],
							'feed_name'     => $feed['name'],
							'language_name' => $language['name'],
							'feed_url'      => $store['url'] . 'index.php?route=' . $feed['action'] . '&language=' . $language['code'],
							'last_update'   => !empty($feed_last_update_cache) ? date($this->language->get('datetime_format'), $feed_last_update_cache) : '-'
						];
					}
				} else {
					$cache_hash = $this->getCacheHash($store['store_id'], $feed['action'], 'last-update');
					$feed_last_update_cache = $this->cache->get($cache_hash);
					$feed_last_update_cache = (int)$feed_last_update_cache;
					$feed_list[] = [
						'store_name'    => $store['name'],
						'feed_name'     => $feed['name'],
						'language_name' => false,
						'feed_url'      => $store['url'] . 'index.php?route=' . $feed['action'],
						'last_update'   => !empty($feed_last_update_cache) ? date($this->language->get('datetime_format'), $feed_last_update_cache) : '-'
					];
				}
			}
		}

		$data = ['feeds' => $feed_list];

		return $this->load->view('marketing/feed_list', $data);
	}

	/**
	 * @return array
	 */
	private function getStoreList(): array {
		$stores = [];

		$stores[] = [
			'store_id' => 0,
			'name'     => $this->config->get('config_name'),
			'url'      => HTTP_CATALOG,
			'edit'     => $this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'])
		];

		$this->load->model('setting/store');
		$results = $this->model_setting_store->getStores();

		foreach ($results as $result) {
			$stores[] = [
				'store_id' => $result['store_id'],
				'name'     => $result['name'],
				'url'      => $result['url']
			];
		}

		return $stores;
	}

	/**
	 * @return array[]
	 */
	private function getFeedList(): array {
		return [
			[
				"name"          => "Google Merchant Feed XML",
				"action"        => "feed/google/merchant",
				"multilanguage" => true
			],
			[
				"name"          => "Sitemap XML",
				"action"        => "feed/sitemap",
				"multilanguage" => false
			]
		];
	}
}
