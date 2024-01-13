<?php
namespace Opencart\Catalog\Controller\Blog;
/**
 * Class Article
 *
 * @package Opencart\Catalog\Controller\Blog
 */
class Article extends \Opencart\System\Engine\Controller {
	/**
	 * @return void
	 */
	public function index(): void {
		if ((int)($this->config->get('config_blog_enabled')) !== 1) {
			$this->response->redirect($this->url->link('common/home', 'language=' . $this->config->get('config_language')));

			return;
		}

		$this->load->language('blog/article');

		$this->load->model('tool/image');
		$this->load->model('blog/article');

		$blog_article_id = isset($this->request->get['blog_article_id']) ? (int)($this->request->get['blog_article_id']) : 0;

		if ($blog_article_id < 1) {
			$this->response->redirect($this->url->link('blog/search', 'language=' . $this->config->get('config_language')));

			return;
		}

		$this->load->model("localisation/language");

		$language =  $this->model_localisation_language->getLanguageByCode($this->config->get('config_language'));
		$language_id = (int)($language['language_id']);
		$store_id = (int)$this->config->get('config_store_id');

		$blog_article = $this->model_blog_article->getArticle($store_id, $language_id, $blog_article_id);

		if ($blog_article === null) {
			$this->response->redirect($this->url->link('blog/search', 'language=' . $this->config->get('config_language')));

			return;
		}

		// Breadcrumbs
		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_blog'),
			'href' => $this->url->link('blog/search', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $blog_article['title'],
			'href' => $this->url->link('blog/article', 'language=' . $this->config->get('config_language') . '&blog_article_id=' . $blog_article_id)
		];

		// Page Title
		$this->document->setTitle($this->language->get('text_blog') . ' - ' . $blog_article['title']);

		// Image

		if (is_file(DIR_IMAGE . html_entity_decode($blog_article['image'], ENT_QUOTES, 'UTF-8'))) {
			$image = HTTP_SERVER . oc_substr(DIR_IMAGE, oc_strlen(DIR_OPENCART)) . html_entity_decode($blog_article['image'], ENT_QUOTES, 'UTF-8');
		} else {
			$image = null;
		}

		$data['title'] = $blog_article['title'];
		$data['description'] = $blog_article['description'];
		$data['content'] = html_entity_decode($blog_article['content'], ENT_QUOTES, 'UTF-8');
		$data['image'] = $image;
		$data['date_added'] = date($this->language->get('date_format_long'), strtotime($blog_article['date_added']));
		$data['date_modified'] = date($this->language->get('date_format_long'), strtotime($blog_article['date_modified']));

		if (!empty($blog_article['author_name'])) {

			$author_photo = null;

			if (is_file(DIR_IMAGE . html_entity_decode($blog_article['author_photo'], ENT_QUOTES, 'UTF-8'))) {
				$author_photo = $this->model_tool_image->resize(html_entity_decode($blog_article['author_photo'], ENT_QUOTES, 'UTF-8'), 256, 256);
			}

			$data['author'] = [
				'name'  => $blog_article['author_name'],
				'photo' => $author_photo,
				'email' => html_entity_decode($blog_article['author_email'], ENT_QUOTES, 'UTF-8'),
				'link'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&author=' . $blog_article['blog_author_id']),
			];
		}

		$data['text_added_at'] = sprintf($this->language->get('text_added_at'), $data['date_added']);

		$data['text_modified_at'] = sprintf($this->language->get('text_modified_at'), $data['date_modified']);
		$data['text_view_times'] = sprintf($this->language->get('text_view_times'), $blog_article['view_count'] + 1);
		$data['link_blog'] = $this->url->link('blog/search', 'language=' . $this->config->get('config_language'));

		$data['tags'] = [];
		$tags = $this->model_blog_article->getArticleTags($blog_article_id, $language_id);
		foreach ($tags as $tag) {
			$data['tags'][] = [
				'tag'  => $tag['tag'],
				'link' => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&tag=' . urlencode(html_entity_decode($tag['tag'], ENT_QUOTES, 'UTF-8'))),
			];
		}

		$data['breadcrumbs'] = $this->load->controller('common/breadcrumbs', $data['breadcrumbs']);
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		// region Structured Data

		$structured_data = [];
		$structured_data['title'] = $blog_article['title'];
		$structured_data['image'] = [
			$image
		];
		$structured_data['author'] = [];
		if (!empty($blog_article['author_name'])) {
			$author_photo = null;

			if (is_file(DIR_IMAGE . html_entity_decode($blog_article['author_photo'], ENT_QUOTES, 'UTF-8'))) {
				$author_photo = $this->model_tool_image->resize(html_entity_decode($blog_article['author_photo'], ENT_QUOTES, 'UTF-8'), 256, 256);
			}

			$structured_data['author'][] = [
				'name'  => $blog_article['author_name'],
				'photo' => $author_photo,
				'link'  => $this->url->link('blog/search', 'language=' . $this->config->get('config_language') . '&author=' . $blog_article['blog_author_id'], true),
			];
		}
		$data['structured_data'] = $this->load->controller('structured_data/article', $structured_data['title'], $structured_data['author'], $structured_data['image'], $blog_article['date_added'], $blog_article['date_modified']);

		// endregion Structured Data

		$this->model_blog_article->increaseViewCountOfArticle($store_id, $blog_article_id);

		$this->response->setOutput($this->load->view('blog/article', $data));
	}
}
