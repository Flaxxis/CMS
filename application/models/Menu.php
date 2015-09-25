<?php

class Models_Menu extends ActiveRecord
{
	protected $_table = 'ws_menus';
	protected $_orderby = array('Sequence' => 'ASC', 'Id' => 'ASC');

	public $types = array(
		'1'   => 'Верхнее меню',
		'2'   => 'Левое меню',
		'3'   => 'Правое меню',
		'4'   => 'Подвал',
	);

	protected $folder = '/storage/pages/';
	protected $max_x = 800;
	protected $max_y = 800;

	protected $_parents;

	protected function _defineRelations()
	{
		$this->_relations = array(
			'Parent' => array(
				'type'  => 'hasOne',
				'class' => 'Models_Menu',
				'field' => 'ParentId'
			),
			'Kids'   => array(
				'type'          => 'hasMany',
				'class'         => 'Models_Menu',
				'field_foreign' => 'ParentId',
				'onDelete'      => 'null'
			)
		);
	}

	protected function _beforeDelete()
	{
		if ($this->getImage() != '') {
			@unlink(PUB . $this->getImage());
		}
		return true;
	}

	public function getPath()
	{
		if ($this->getRedirectUrl())
			return $this->getRedirectUrl();

		$items = array();
		$result = '';

		if (!$items = $this->getParents())
			return '/' . $this->getUrl() . '/';


		foreach ($items as $item)
			$result .= '/' . $item->getUrl();

		$result .= '/' . $this->getUrl() . '/';

		return  $result;
	}

	public function getParents()
	{
		if ($this->_parents)
			return $this->_parents;
		$parent = null;
		if (($this->getParentId() == -1) || !$this->getParentId())
			return;
		if (!$parent = $this->getParent())
			return;

		$parents = array();

		do {
			$parents[] = $parent;
		} while ($parent->getParentId() > 0 and $parent = $parent->getParent());

		$this->_parents = array_reverse($parents);

		return $this->_parents;
	}

	public function setUrl($url)
	{
		$this->Url = preg_replace('/[^a-zA-Z0-9_-]+/', '-', trim(strtolower($url)));
	}

	public function getType()
	{
		if (isset($this->types[$this->getTypeId()])) {
			return $this->types[$this->getTypeId()];
		}
		return 'undefined';
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

	public function findByUrlAndParentId($uri, $parent_id)
	{
		return Models_Menu::DB()->findFirst(array('LOWER(ws_menus.Url) = "' . Orm_Statement::escape(strtolower($uri)) . '"', 'ParentId' => $parent_id));
	}

}