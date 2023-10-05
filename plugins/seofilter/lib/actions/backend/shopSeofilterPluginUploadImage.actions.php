<?php

class shopSeofilterPluginUploadImageActions extends waActions
{
	const IMG_PATH = 'plugins/seofilter/img';

	protected function preExecute()
	{
		$user_rights = new shopSeofilterUserRights();
		if (!$user_rights->hasRights())
		{
			throw new waException('Доступ запрещен', 403);
		}
	}

	protected function execute($action)
	{
		$path = wa()->getDataPath(self::IMG_PATH, true);

		$response = '';

		if (!is_writable($path))
		{
			$p = substr($path, strlen(wa()->getDataPath('', true)));
			$errors = sprintf(
				_w("File could not be saved due to insufficient write permissions for the %s folder."),
				$p
			);
		}
		else
		{
			$errors = array();
			$f = waRequest::file('file');
			$name = $f->name;
			if ($this->processFile($f, $path, $name, $errors))
			{
				$response = wa()->getDataUrl(
					self::IMG_PATH . '/' . $name,
					true,
					null,
					!!waRequest::get('absolute')
				);
			}
			$errors = implode(" \r\n", $errors);
		}
		if (waRequest::get('filelink'))
		{
			$this->getResponse()->sendHeaders();
			if ($errors)
			{
				echo json_encode(array('error' => $errors));
			}
			else
			{
				echo json_encode(array('filelink' => $response));
			}
		}
		else
		{
			$this->displayJson($response, $errors);
		}
	}

	/**
	 * @param waRequestFile $f
	 * @param string $path
	 * @param string $name
	 * @param array $errors
	 * @return bool
	 */
	protected function processFile(waRequestFile $f, $path, &$name, &$errors = array())
	{
		if ($f->uploaded())
		{
			if (!$this->isFileValid($f, $errors))
			{
				return false;
			}
			if (!$this->saveFile($f, $path, $name))
			{
				$errors[] = sprintf(_w('Failed to upload file %s.'), $f->name);

				return false;
			}

			return true;
		}
		else
		{
			$errors[] = sprintf(_w('Failed to upload file %s.'), $f->name) . ' (' . $f->error . ')';

			return false;
		}
	}

	protected function isFileValid($f, &$errors = array())
	{
		$allowed = array('jpg', 'jpeg', 'png', 'gif');
		if (!in_array(strtolower($f->extension), $allowed))
		{
			$errors[] = sprintf(_ws("Files with extensions %s are allowed only."), '*.' . implode(', *.', $allowed));

			return false;
		}

		return true;
	}

	protected function saveFile(waRequestFile $f, $path, &$name)
	{
		$name = $f->name;
		if (!preg_match('//u', $name))
		{
			$tmp_name = @iconv('windows-1251', 'utf-8//ignore', $name);
			if ($tmp_name)
			{
				$name = $tmp_name;
			}
		}
		if (file_exists($path . DIRECTORY_SEPARATOR . $name))
		{
			$i = strrpos($name, '.');
			$ext = substr($name, $i + 1);
			$name = substr($name, 0, $i);
			$i = 1;
			while (file_exists($path . DIRECTORY_SEPARATOR . $name . '-' . $i . '.' . $ext))
			{
				$i++;
			}
			$name = $name . '-' . $i . '.' . $ext;
		}

		return $f->moveTo($path, $name);
	}
}