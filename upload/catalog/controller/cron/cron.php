<?php
namespace Opencart\Catalog\Controller\Cron;
class Cron extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$time = time();

		echo 'cron' . "\n";

		$this->load->model('setting/cron');

		$results = $this->model_setting_cron->getCrons();

		foreach ($results as $result) {
			echo $result['code'] . "\n";

			//if ($result['status'] && (strtotime('+1 ' . $result['cycle'], strtotime($result['date_modified'])) < ($time + 10))) {
				$this->load->controller($result['action'], $result['cron_id'], $result['code'], $result['cycle'], $result['date_added'], $result['date_modified']);

				$this->model_setting_cron->editCron($result['cron_id']);
			//}
		}
	}
}