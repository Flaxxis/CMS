<?php

class Models_Gallery extends ActiveRecord
{
	protected $_table = 'ws_gallery';

	public $codeKey = 'site_gallery_';

	static $types = array(
		1 => 'Список',
		2 => 'Слайдер',
	);

	protected function _defineRelations()
	{
		$this->_relations = array(
			'Items'   => array(
				'type'          => 'hasMany',
				'class'         => 'Models_File',
				'field_foreign' => 'GalleryId',
				'onDelete'      => 'null'
			)
		);
	}

	protected function _beforeDelete()
	{
		foreach($this->getItems() as $item){
			$item->setGalleryId(null);
			$item->save();
		}
		return true;
	}

	public function getType()
	{
		if (isset(self::$types[$this->getTypeId()])) {
			return self::$types[$this->getTypeId()];
		}
		return 'undefined';
	}

}