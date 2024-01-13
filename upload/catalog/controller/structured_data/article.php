<?php
namespace Opencart\Catalog\Controller\StructuredData;

/**
 * Class Article
 *
 * @package Opencart\Catalog\Controller\StructuredData
 */
class Article extends \Opencart\System\Engine\Controller {
	/**
	 * @param string     $title
	 * @param array      $authors
	 * @param array      $images
	 * @param int|string $date_published
	 * @param int|string $date_modified
	 *
	 * @return string
	 */
	public function index(string $title, array $authors, array $images, int|string $date_published, int|string $date_modified): string {
		$data = [];

		if (is_string($date_published)) {
			$date_published = date('c', strtotime($date_published));
		}

		if (is_string($date_modified)) {
			$date_modified = date('c', strtotime($date_modified));
		}

		$data['structured_data'] = [
			"@context"      => "https://schema.org",
			"@type"         => "BlogPosting",
			"headline"      => $title,
			"datePublished" => $date_published,
			"dateModified"  => $date_modified,
			"image"         => [],
			"author"        => []
		];

		foreach ($images as $image) {
			$data['structured_data']['image'][] = html_entity_decode($image, ENT_QUOTES, 'UTF-8');
		}

		foreach ($authors as $author) {
			$data['structured_data']['author'][] = [
				"@type" => "Person",
				"name"  => $author['name'],
				"url"   => html_entity_decode($author['link'], ENT_QUOTES, 'UTF-8')
			];
		}

		return $this->load->view('structured_data/default', $data);
	}
}
