<?php
namespace Opencart\Catalog\Controller\Feed;
/**
 * Class Sitemap
 *
 * @package Opencart\Catalog\Controller\Feed
 */
class Sitemap extends \Opencart\System\Engine\Controller {
	/**
	 * @throws \Exception
	 *
	 * @return void
	 */
	public function index(): void {
		$this->cache->get($this->getCacheHash());

		$sitemap_hash_key = $this->getCacheHash((int)$this->config->get('config_store_id'), 'feed/sitemap', 'sitemap');
		$last_update_hash_key = $this->getCacheHash((int)$this->config->get('config_store_id'), 'feed/sitemap', 'last-update');
		$preparing_hash_key = $this->getCacheHash((int)$this->config->get('config_store_id'), 'feed/sitemap', 'preparing');

		// Fetch XML from the cache.
		$sitemap_xml = $this->cache->get($sitemap_hash_key);
		$current_time = (int)(microtime(true));
		// If the cache is expired, then generate the XML again.
		if (empty($sitemap_xml)) {
			// Check if the sitemap is preparing.
			$preparation_start_time = $this->cache->get($preparing_hash_key);
			if (empty($preparation_start_time) || $current_time - (int)$preparation_start_time > 120) {
				// Mark the cache as the sitemap is preparing.
				$this->cache->set($preparing_hash_key, $current_time);
				$sitemap_xml = $this->generateXML();
				// Save the generated content to the cache.
				$this->cache->set($sitemap_hash_key, $sitemap_xml);
				$this->cache->set($last_update_hash_key, $current_time, 10 * 365 * 86400);
				// Remove the mark.
				$this->cache->delete($preparing_hash_key);
			} else {
				// Refresh the page after five seconds if the sitemap is preparing on other instance of the script.
				sleep(5);
				header('Location: ./index.php?route=feed/sitemap&_=' . $current_time);

				return;
			}
		}

		$this->response->addHeader('Content-Type: application/xml');
		$this->response->setOutput($sitemap_xml);
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
	 * @throws \Exception
	 */
	private function generateXML(): string {
		$links = $this->collectLinks();
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ($links as $link) {
			$xml .= '<url>' . "\n";
			$xml .= '<loc>' . $link['loc'] . '</loc>' . "\n";
			if (!empty($link['lastmod'])) {
				$xml .= '<lastmod>' . $link['lastmod'] . '</lastmod>' . "\n";
			}
			$xml .= '</url>' . "\n";
		}
		$xml .= '</urlset>' . "\n";

		return $xml;
	}

	/**
	 * @throws \Exception
	 *
	 * @return array
	 */
	private function collectLinks(): array {
		$this->load->model("localisation/language");

		$language =  $this->model_localisation_language->getLanguageByCode($this->config->get('config_language'));

		$links = [];

		$this->load->model('catalog/category');
		$categories_1 = $this->model_catalog_category->getCategories(0);
		foreach ($categories_1 as $category_1) {
			$categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);
			foreach ($categories_2 as $category_2) {
				$categories_3 = $this->model_catalog_category->getCategories($category_2['category_id']);
				foreach ($categories_3 as $category_3) {
					$links[] = [
						'loc' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_1['category_id'] . '_' . $category_2['category_id'] . '_' . $category_3['category_id'])
					];
				}
				$links[] = [
					'loc' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_1['category_id'] . '_' . $category_2['category_id'])
				];
			}
			$links[] = [
				'loc' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_1['category_id'])
			];
		}

		$links[] = ['loc' => $this->url->link('product/special', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('account/account', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('account/edit', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('account/password', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('account/address', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('account/order', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('account/download', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('product/search', 'language=' . $this->config->get('config_language'))];
		$links[] = ['loc' => $this->url->link('information/contact', 'language=' . $this->config->get('config_language'))];

		// Blog Articles
		$this->load->model('blog/article');
		$blog_articles = $this->model_blog_article->getArticlesForSitemap([
			'store_id' => (int)$this->config->get('config_store_id'),
		]);
		if ((int)($this->config->get('config_blog_enabled')) === 1) {
			foreach ($blog_articles as $blog_article) {
				$links[] = [
					'loc'     => $this->url->link('blog/article', 'language=' . $this->config->get('config_language') . '&blog_article_id=' . $blog_article['blog_article_id']),
					'lastmod' => (new \DateTime($blog_article['date_modified']))->format(DATE_W3C)
				];
			}
		}

		// Blog Tags
		$this->load->model('blog/store');
		$tags = $this->model_blog_store->getTags((int)$this->config->get('config_store_id'), (int)$language['language_id']);
		foreach ($tags as $tag) {
			$links[] = [
				'loc' => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&tag=' . urlencode(html_entity_decode($tag['tag'], ENT_QUOTES, 'UTF-8')))
			];
		}

		// Blog Authors
		$authors = $this->model_blog_store->getAuthors((int)$this->config->get('config_store_id'));
		foreach ($authors as $author) {
			$links[] = [
				'loc' => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&author=' . $author['blog_author_id'])
			];
		}

		return $links;
	}
}
