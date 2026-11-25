<?php
namespace Opencart\Catalog\Controller\Extension\Reviewreply\Module;
/**
 * Review Replies: shows the store's reply under each product review.
 */
class Reviewreply extends \Opencart\System\Engine\Controller {
	/**
	 * Event: catalog/model/catalog/review.getReviewsByProductId/after
	 * The core query returns no review_id, so run the same query with the ID and the reply added.
	 * The controller merges extra keys into each review (" + $result"), so they reach the template data.
	 */
	public function reviews(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_reviewreply_status') || !$output) {
			return;
		}

		$product_id = (int)($args[0] ?? 0);
		$start = max(0, (int)($args[1] ?? 0));
		$limit = (int)($args[2] ?? 20);

		if ($limit < 1) {
			$limit = 20;
		}

		// Same filters and order as the core query, with review_id as a tie-breaker.
		$query = $this->db->query("SELECT `r`.`review_id`, `r`.`author`, `r`.`rating`, `r`.`text`, `r`.`date_added`, `rr`.`reply`, `rr`.`date_modified` AS `reply_date` FROM `" . DB_PREFIX . "review` `r` LEFT JOIN `" . DB_PREFIX . "reviewreply` `rr` ON (`rr`.`review_id` = `r`.`review_id`) LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`r`.`product_id` = `p`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `r`.`product_id` = '" . $product_id . "' AND `p`.`date_available` <= NOW() AND `p`.`status` = '1' AND `r`.`status` = '1' AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' ORDER BY `r`.`date_added` DESC, `r`.`review_id` DESC LIMIT " . $start . "," . $limit);

		$output = $query->rows;
	}

	/**
	 * Event: catalog/view/product/review_list/after. Each review is one <table>; add the reply after it.
	 */
	public function reviewList(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_reviewreply_status') || empty($args['reviews'])) {
			return;
		}

		$this->load->language('extension/reviewreply/module/reviewreply', 'reviewreply');

		$author = trim(html_entity_decode((string)$this->config->get('module_reviewreply_author'), ENT_QUOTES, 'UTF-8'));

		if ($author === '') {
			$author = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
		}

		$heading = html_entity_decode((string)$this->config->get('module_reviewreply_heading'), ENT_QUOTES, 'UTF-8') ?: $this->language->get('reviewreply_text_heading');
		$heading = str_contains($heading, '%s') ? sprintf($heading, $author) : $heading;

		$offset = 0;

		foreach ($args['reviews'] as $review) {
			$end = strpos($output, '</table>', $offset);

			if ($end === false) {
				break;
			}

			$offset = $end + 8;

			if (empty($review['reply'])) {
				continue;
			}

			$html = '<div class="review-reply border-start border-4 border-primary bg-body-tertiary rounded-end px-3 py-2 mb-3 ms-3" style="margin-top:-.5rem">';
			$html .= '<div class="small fw-semibold mb-1"><i class="fa-solid fa-reply fa-flip-horizontal"></i> ' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');

			if (!empty($review['reply_date'])) {
				$html .= ' <span class="text-body-secondary fw-normal">&middot; ' . date($this->language->get('date_format_short'), strtotime($review['reply_date'])) . '</span>';
			}

			$html .= '</div><div>' . nl2br(htmlspecialchars($review['reply'], ENT_QUOTES, 'UTF-8')) . '</div></div>';

			$output = substr_replace($output, $html, $offset, 0);
			$offset += strlen($html);
		}
	}
}
