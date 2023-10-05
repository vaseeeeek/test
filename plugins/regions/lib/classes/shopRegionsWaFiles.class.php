<?php

/**
 * Class shopRegionsWaFiles
 *
 * копипаста waFiles::copy
 */
class shopRegionsWaFiles
{
	public function copy($source_path, $target_path, $skip_pattern = null, $follow_symlink_directories = false)
	{
		if (is_dir($source_path))
		{
			$source_path = realpath($source_path);

			//$temp_dir = sys_get_temp_dir();
			//$path = 'plugins/regions/temp/shop_regions_tmp_' . rand(1, 10000) . '/' . ltrim(dirname($target_path), '/\\');
			$path = 'plugins/regions/temp/shop_regions_tmp_' . rand(1, 10000) . '/' . ltrim(preg_replace('/[a-z]:/i', '', dirname($target_path)), '/\\');
			$random_dir = wa()->getDataPath($path, false, 'shop', true);

			waFiles::create($random_dir, true);

			$this->_copy($source_path, $random_dir, $skip_pattern, $follow_symlink_directories);

			waFiles::move($random_dir, $target_path);

			waFiles::delete($random_dir);
		}
		else
		{
			$this->_copy($source_path, $target_path, $skip_pattern, $follow_symlink_directories);
		}
	}

	/**
	 * Copies a file or directory contents.
	 *
	 * @param string $source_path Path to the original file or directory. If path to a directory is specified, then the
	 *     contents of that directory are copied to the specified location. Subdirectories are copied recursively.
	 * @param string $target_path Path for saving a copy.
	 * @param string|array $skip_pattern Regular expression string describing the format of file and subdirectory names
	 *     which must not be copied if a path to a subdirectory is specified in $source_path parameter (otherwise this
	 *     regular expression is ignored).
	 * @param bool $follow_symlink_directories
	 * @throws Exception
	 * @throws waException
	 */
	private function _copy($source_path, $target_path, $skip_pattern = null, $follow_symlink_directories = false)
	{
		if (is_dir($source_path) && ($follow_symlink_directories || !is_link($source_path)))
		{
			try
			{
				if ($dir = opendir($source_path))
				{
					waFiles::create($target_path);

					$dir_files = array();
					while (false !== ($path = readdir($dir)))
					{
						$dir_files[] = $path;
					}
					closedir($dir);

					foreach ($dir_files as $path)
					{
						if (($path != '.') && ($path != '..'))
						{
							$destination = $target_path . '/' . $path;
							$source = $source_path . '/' . $path;
							if ($skip_pattern)
							{
								foreach ((array)$skip_pattern as $pattern)
								{
									if (preg_match($pattern, $source))
									{
										continue 2;
									}
								}
							}
							if (file_exists($source))
							{
								if (!is_dir($source) && file_exists($destination))
								{ //skip file move on resume
									waFiles::delete($destination);
								}
								$this->_copy($source, $destination, $skip_pattern, $follow_symlink_directories);
							}
							else
							{
								throw new Exception("Not found {$source_path}/{$path}");
							}
						}
					}
				}
			}
			catch (Exception $e)
			{
				if (!empty($dir) && is_resource($dir))
				{
					closedir($dir);
				}
				throw $e;
			}
		}
		elseif (is_dir($source_path) && is_link($source_path))
		{
			@symlink($source_path, $target_path);
		}
		elseif (!is_dir($source_path))
		{
			waFiles::create(dirname($target_path) . '/');
			if (@copy($source_path, $target_path))
			{
				/*@todo copy file permissions*/
			}
			else
			{
				if (file_exists($source_path) && file_exists($target_path) && (filesize($source_path) === 0))
				{
					/*It's ok - it's windows*/
				}
				else
				{
					throw new Exception(sprintf(_ws("Error copying file from %s to %s"), $source_path, $target_path));
				}
			}
		}
	}
}