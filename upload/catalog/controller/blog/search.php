<?php
namespace Opencart\Catalog\Controller\Blog;
/**
 * Class Search
 *
 * @package Opencart\Catalog\Controller\Blog
 */
class Search extends \Opencart\System\Engine\Controller {
	/**
	 * @return void
	 */
	public function index(): void {
		if ((int)($this->config->get('config_blog_enabled')) !== 1) {
			$this->response->redirect($this->url->link('common/home', 'language=' . $this->config->get('config_language')));

			return;
		}

		$this->load->language('blog/search');

		$this->load->model('tool/image');
		$this->load->model('blog/article');

		if (isset($this->request->get['search'])) {
			$search = oc_substr($this->request->get['search'], 0, 255);
		} else {
			$search = '';
		}

		if (isset($this->request->get['tag'])) {
			$tag = oc_substr($this->request->get['tag'], 0, 255);
		} else {
			$tag = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = match ($this->request->get['sort']) {
				'id'    => 'ba.blog_article_id',
				'title' => 'bac.title',
				default => 'ba.blog_article_id'
			};
		} else {
			$sort = 'ba.blog_article_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['author'])) {
			$blog_author_id = (int)$this->request->get['author'];
			if ($blog_author_id < 1) {
				$blog_author_id = null;
			}
		} else {
			$blog_author_id = null;
		}

		if (isset($this->request->get['limit']) && (int)$this->request->get['limit']) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('config_pagination');
		}

		if (isset($this->request->get['search'])) {
			$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->request->get['search']);
		} elseif (oc_strlen($tag) > 0) {
			$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('heading_tag') . $tag);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (oc_strlen($tag) > 0) {
			$url .= '&tag=' . urlencode(html_entity_decode($tag, ENT_QUOTES, 'UTF-8'));
		}

		if (!empty($blog_author_id)) {
			$url .= '&author=' . $blog_author_id;
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . $url)
		];

		if (isset($this->request->get['search'])) {
			$data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->request->get['search'];
		} else {
			$data['heading_title'] = $this->language->get('heading_title');
		}

		$data['articles'] = [];

		$data['user_searched'] = (isset($this->request->get['search'])) || (oc_strlen($tag) > 0) || ($blog_author_id !== null);

		$filter_data = [
			'store_id'      => (int)$this->config->get('config_store_id'),
			'filter_title'  => $search,
			'filter_author' => $blog_author_id,
			'filter_tag'    => $tag,
			'sort'          => $sort,
			'order'         => $order,
			'start'         => ($page - 1) * $limit,
			'limit'         => $limit
		];

		$article_total = $this->model_blog_article->getTotalArticles($filter_data);

		$results = $this->model_blog_article->getArticles($filter_data);

		$image_width = 1024;
		$image_height = 768;
		$placeholder_image = $this->model_tool_image->resize('placeholder.png', 1024, 768);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $this->model_tool_image->resize(html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'), $image_width, $image_height);
			} else {
				$image = $placeholder_image;
			}

			$article_data = [
				'blog_article_id' => $result['blog_article_id'],
				'thumb'           => $image,
				'title'           => $result['title'],
				'description'     => oc_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('config_product_description_length')) . '..',
				'href'            => $this->url->link('blog/article', 'language=' . $this->config->get('config_language') . '&blog_article_id=' . $result['blog_article_id'] . $url)
			];

			$data['article_thumbs'][] = $this->load->controller('blog/search.thumb', $article_data);
		}

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (oc_strlen($tag) > 0) {
			$url .= '&tag=' . urlencode(html_entity_decode($tag, ENT_QUOTES, 'UTF-8'));
		}

		if (!empty($blog_author_id)) {
			$url .= '&author=' . $blog_author_id;
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['sorts'] = [];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_default'),
			'value' => 'ba.blog_article_id-DESC',
			'href'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&sort=id&order=DESC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_title_asc'),
			'value' => 'bac.title-ASC',
			'href'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&sort=title&order=ASC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_title_desc'),
			'value' => 'bac.title-DESC',
			'href'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&sort=title&order=DESC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_date_added_asc'),
			'value' => 'ba.blog_article_id-ASC',
			'href'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&sort=id&order=ASC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_date_added_desc'),
			'value' => 'ba.blog_article_id-DESC',
			'href'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&sort=id&order=DESC' . $url)
		];

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (oc_strlen($tag) > 0) {
			$url .= '&tag=' . urlencode(html_entity_decode($tag, ENT_QUOTES, 'UTF-8'));
		}

		if (!empty($blog_author_id)) {
			$url .= '&author=' . $blog_author_id;
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$data['limits'] = [];

		$limits = array_unique([$this->config->get('config_pagination'), 25, 50, 75, 100]);

		sort($limits);

		foreach ($limits as $value) {
			$data['limits'][] = [
				'text'  => $value,
				'value' => $value,
				'href'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . $url . '&limit=' . $value)
			];
		}

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (oc_strlen($tag) > 0) {
			$url .= '&tag=' . urlencode(html_entity_decode($tag, ENT_QUOTES, 'UTF-8'));
		}

		if (!empty($blog_author_id)) {
			$url .= '&author=' . $blog_author_id;
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $article_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($article_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($article_total - $limit)) ? $article_total : ((($page - 1) * $limit) + $limit), $article_total, ceil($article_total / $limit));

		if (isset($this->request->get['search']) && $this->config->get('config_customer_search')) {
			$this->load->model('account/search');

			if ($this->customer->isLogged()) {
				$customer_id = $this->customer->getId();
			} else {
				$customer_id = 0;
			}

			if (isset($this->request->server['REMOTE_ADDR'])) {
				$ip = $this->request->server['REMOTE_ADDR'];
			} else {
				$ip = '';
			}

			$search_data = [
				'keyword'     => $search,
				'articles'    => $article_total,
				'customer_id' => $customer_id,
				'ip'          => $ip
			];

			$this->model_account_search->addSearch($search_data);
		}

		$this->load->model("localisation/language");

		$language =  $this->model_localisation_language->getLanguageByCode($this->config->get('config_language'));

		$this->load->model("blog/store");

		$data['tag'] = $tag;
		$data['tags'] = [];
		$tag_rows = $this->model_blog_store->getTags((int)$this->config->get('config_store_id'), (int)($language['language_id']), 30);
		foreach ($tag_rows as $tag_row) {
			$tag_row['link'] = $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&tag=' . urlencode(html_entity_decode($tag_row['tag'], ENT_QUOTES, 'UTF-8')));
			$data['tags'][] = $tag_row;
		}

		$data['author'] = $blog_author_id;
		$data['authors'] = [];
		$author_rows = $this->model_blog_store->getAuthors((int)$this->config->get('config_store_id'), 30);
		foreach ($author_rows as $author_row) {
			$author_row['link'] = $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&author=' . $author_row['blog_author_id']);
			$data['authors'][] = $author_row;
		}

		$data['search'] = $search;

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['language'] = $this->config->get('config_language');

		$data['breadcrumbs'] = $this->load->controller('common/breadcrumbs', $data['breadcrumbs']);
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('blog/search', $data));
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	public function thumb(array $data): string {
		$this->load->language('blog/article');

		$data['action_view_article'] = $this->url->link('blog/article', 'language=' . $this->config->get('config_language') . '&blog_article_id=' . $data['blog_article_id']);

		return $this->load->view('blog/article_thumb', $data);
	}
}
