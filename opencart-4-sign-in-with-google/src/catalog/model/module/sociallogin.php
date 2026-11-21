<?php
namespace Opencart\Catalog\Model\Extension\Sociallogin\Module;
class Sociallogin extends \Opencart\System\Engine\Model {
	public function getLink(string $provider, string $subject): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_social` WHERE `provider` = '" . $this->db->escape($provider) . "' AND `subject` = '" . $this->db->escape($subject) . "'")->row;
	}

	public function addLink(int $customer_id, string $provider, string $subject, string $email): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_social` SET `customer_id` = '" . (int)$customer_id . "', `provider` = '" . $this->db->escape($provider) . "', `subject` = '" . $this->db->escape($subject) . "', `email` = '" . $this->db->escape($email) . "', `date_added` = NOW(), `date_login` = NOW() ON DUPLICATE KEY UPDATE `customer_id` = '" . (int)$customer_id . "', `email` = '" . $this->db->escape($email) . "'");
	}

	public function updateLogin(string $provider, string $subject): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "customer_social` SET `date_login` = NOW() WHERE `provider` = '" . $this->db->escape($provider) . "' AND `subject` = '" . $this->db->escape($subject) . "'");
	}
}
