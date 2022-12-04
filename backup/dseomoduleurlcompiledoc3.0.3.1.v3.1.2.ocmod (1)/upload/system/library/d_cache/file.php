<?php
namespace d_cache;

class File {
	public function get($group, $key) {
		if (is_dir(DIR_CACHE . '/' . $group)) {
			$files = glob(DIR_CACHE . '/' . $group . '/' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.*');

			if ($files) {
				$time = substr(strrchr($files[0], '.'), 1);

				if ($time && ($time < time())) {
					unlink($files[0]);
				} else {
					$handle = fopen($files[0], 'r');

					flock($handle, LOCK_SH);

					$data = filesize($files[0]) ? fread($handle, filesize($files[0])) : '';
					
					$data_arr = json_decode($data, true);
					
					if ($data_arr) {
						$data = $data_arr;
					}
					
					flock($handle, LOCK_UN);

					fclose($handle);

					return $data;
				}
			}
		}

		return false;
	}

	public function set($group, $key, $value, $expire = 0) {		
		$this->delete($group, $key);
		
		if (!is_dir(DIR_CACHE . '/' . $group)) {
			mkdir(DIR_CACHE . '/' . $group, 0777, true);
		}
		
		$file = DIR_CACHE . '/' . $group . '/' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.' . ($expire ? (time() + $expire) : $expire);

		$handle = fopen($file, 'w');

		flock($handle, LOCK_EX);

		fwrite($handle, is_array($value) ? json_encode($value) : $value);

		fflush($handle);

		flock($handle, LOCK_UN);

		fclose($handle);
	}

	public function delete($group, $key) {
		if (is_dir(DIR_CACHE . '/' . $group)) {
			$files = glob(DIR_CACHE . '/' . $group . '/' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.*');

			if ($files) {
				foreach ($files as $file) {
					if (file_exists($file)) {
						unlink($file);
					}
				}
			}
		}
	}
	
	public function deleteAll($group) {
		if (is_dir(DIR_CACHE . '/' . $group)) {
			$files = glob(DIR_CACHE . '/' . $group . '/*');

			if ($files) {
				foreach ($files as $file) {
					if (file_exists($file)) {
						unlink($file);
					}
				}
			}
			
			if (is_dir(DIR_CACHE . '/' . $group)) {
				rmdir(DIR_CACHE . '/' . $group);
			}
		}
	}
	
	public function deleteOld($group) {
		if (is_dir(DIR_CACHE . '/' . $group)) {
			$files = glob(DIR_CACHE . '/' . $group . '/*');

			if ($files) {
				foreach ($files as $file) {
					$time = substr(strrchr($file, '.'), 1);

					if ($time && ($time < time())) {
						if (file_exists($file)) {
							unlink($file);
						}
					}
				}
			}
			
			$files = glob(DIR_CACHE . '/' . $group . '/*');
			
			if (is_dir(DIR_CACHE . '/' . $group) && !$files) {
				rmdir(DIR_CACHE . '/' . $group);
			}
		}
	}
}