<?php

class Models_News extends ActiveRecord
{
	protected $_table = 'ws_news';
	protected $_orderby = array('Start' => 'ASC', 'Id' => 'ASC');

	protected $folder = '/storage/news/';
	protected $max_x = 400;
	protected $max_y = 400;

	protected $_parents;

	protected function _beforeDelete()
	{
		if ($this->getImage() != '') {
			@unlink(PUB . $this->getImage());
		}
		return true;
	}

	public function getStatusText()
	{
		switch ($this->getStatus()) {
			case '1':
				return 'Активно';
			case '0':
				return 'Не активно';
		}
	}

	public function getPath()
	{
		return '/news/id/' . $this->getId() . '/' . $this->_generateUrl($this->getTitle()) . '/';
	}

	public static function getActiveString()
	{
		return "(Start = '0000-00-00 00:00:00' OR NOW() >= Start) AND (End = '0000-00-00 00:00:00' OR NOW() <= End ) AND Status = 1";
	}

	public function uploadImage($file)
	{
		require_once('upload/class.upload.php');
		$folder = PUB . $this->folder;


		$fname = pathinfo($file['name']);
		$file['name'] = Validation::translit($fname['filename']) . '.' . $fname['extension'];
		$handle = new upload($file, 'ru_RU');

		if ($handle->uploaded) {
			$handle->image_resize = true;
			$handle->image_x = $this->max_x;
			$handle->image_y = $this->max_y;
			$handle->image_ratio_no_zoom_in = true;
			$handle->process($folder);
			if ($handle->processed) {
				$uploaded_image_name = $this->folder . $handle->file_dst_name;
				@unlink(PUB . $this->getImage());
				$this->setImage($uploaded_image_name);
				$this->save();
				return true;
			}
		}
	}

}