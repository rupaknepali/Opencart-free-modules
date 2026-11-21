<?php
namespace Opencart\Admin\Model\Extension\Sociallogin\Module;
class Sociallogin extends \Opencart\System\Engine\Model {
	public function install(): void {
		// One row per linked Google account. `subject` is Google's stable user ID (the "sub" claim):
		// it never changes, unlike the email address.
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "customer_social` (
			`customer_social_id` INT(11) NOT NULL AUTO_INCREMENT,
			`customer_id` INT(11) NOT NULL,
			`provider` VARCHAR(32) NOT NULL,
			`subject` VARCHAR(255) NOT NULL,
			`email` VARCHAR(96) NOT NULL,
			`date_added` DATETIME NOT NULL,
			`date_login` DATETIME NOT NULL,
			PRIMARY KEY (`customer_social_id`),
			UNIQUE KEY `provider_subject` (`provider`, `subject`),
			KEY `customer_id` (`customer_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}
}
