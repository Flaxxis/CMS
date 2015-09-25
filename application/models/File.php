<?php

class Models_File extends ActiveRecord
{
	protected $_table = 'ws_files';
	protected $_orderby = array('Sequence' => 'ASC', 'Id' => 'ASC');

	public $types = array(
		0 => '',
		1 => 'Изображение',
		2 => 'PDF',
	);

	protected $folder = array(
		1 => '/storage/images/',
		2 => '/storage/pdf/',
		0 => '/storage/other/',
	);

	protected $images = array('jpg', 'jpeg', 'gif', 'png', 'gif');
	protected $pdf = array('pdf');

	protected $max_x = 800;
	protected $max_y = 800;

	protected $small_x = 300;
	protected $small_y = 300;


	protected function _beforeDelete()
	{
		if ($this->getLocation() != '') {
			@unlink(PUB . $this->getLocation());
		}
		if ($this->getSmallImage() != '') {
			@unlink(PUB . $this->getSmallImage());
		}
		return true;
	}


	public function uploadImage($file)
	{
		require_once('upload/class.upload.php');

		$this->setTypeId(0);

		$exts = explode('.', @$_FILES['file']['name']);
		$ext = strtolower($exts[count($exts) - 1]);

		if (in_array($ext, $this->images)) {
			$this->setTypeId(1);
		}
		if (in_array($ext, $this->pdf)) {
			$this->setTypeId(2);
		}

		$folder = PUB . $this->folder[$this->getTypeId()];

		$fname = pathinfo($file['name']);
		$file['name'] = Validation::translit($fname['filename']) . '.' . $fname['extension'];
		$handle = new upload($file, 'ru_RU');

		if ($handle->uploaded) {
			if ($this->getTypeId() == 1) {
				$handle->image_resize = true;
				$handle->image_x = $this->max_x;
				$handle->image_y = $this->max_y;
				$handle->image_ratio_no_zoom_in = true;
			}
			$handle->process($folder);
			if ($handle->processed) {

				$uploaded_image_name = $this->folder[$this->getTypeId()] . $handle->file_dst_name;
				$this->setLocation($uploaded_image_name);
				$this->setName($handle->file_src_name);
				$this->setFilename($handle->file_dst_name);
				$this->setHeaderType($handle->file_src_mime);
				$this->setSize($handle->file_src_size);
				$this->save();
				$handle->clean();

				if ($this->getTypeId() == 1) {
					$this->resize(300, 300, 0, 'small');
				}
				return true;
			}
		}
		return false;
	}


	public function resize($width, $height, $watermark = 0, $sizename = '', $no_zoom = true, $crop = false)
	{
		require_once('upload/class.upload.php');
		$handle = new upload(PUB . $this->getLocation());

		$handle->image_resize = true;
		$handle->image_x = $width; //1024
		$handle->image_y = $height; //1024
		$handle->image_ratio_crop = $crop;
		$handle->image_ratio_no_zoom_in = $no_zoom;
		if ($watermark) {
			$handle->image_watermark = PUB . Models_Config::findByCode('watermark_image')->getValue();
			$handle->image_watermark_position = 'BR';
		}

		if ($handle->uploaded) {

			$handle->process(PUB . $this->folder[1] . $sizename . '/');
			if ($handle->processed) {
				$this->setSmallImage($this->folder[1] . $sizename . '/' . $handle->file_dst_name);
				$this->save();
			} else {
				return $handle->error;
			}
		} else
			return $handle->error;

		return true;
	}

	public function isOK()
	{
		return file_exists(PUB . $this->getFilepath());
	}

	public function download($show = 0)
	{
		$range = 0;

		$name = $this->getLocation() ? $this->getLocation() : $this->getFilename();
		$filename = $this->getFilepath();

		$finfo = finfo_open(FILEINFO_MIME);
		$ftype = finfo_file($finfo, '.' . $filename);
		finfo_close($finfo);

		if (!$this->isOk()) {
			header("HTTP/1.0 404 Not Found");
			exit;
		} else {
			$fsize = filesize('.' . $filename);
			$ftime = date("D, d M Y H:i:s T", filemtime('.' . $filename));
			$fd = @fopen('.' . $filename, "rb");
			if (!$fd) {
				header("HTTP/1.0 403 Forbidden");
				exit;
			}
			if (isset($HTTP_SERVER_VARS["HTTP_RANGE"])) {
				$range = $HTTP_SERVER_VARS["HTTP_RANGE"];
				$range = str_replace("bytes=", "", $range);
				$range = str_replace("-", "", $range);
				if ($range)
					fseek($fd, $range);
			}

			if ($range) {
				header("HTTP/1.1 206 Partial Content");
			} else {
				header("HTTP/1.1 200 OK");
			}
			if ($show)
				header("Content-Disposition: inline; filename=\"" . rawurlencode($this->getName()) . "\"");
			else
				header("Content-Disposition: attachment; filename=\"" . rawurlencode($this->getName()) . "\"");

			header("Last-Modified: $ftime");
			header("Accept-Ranges: bytes");
			header("Content-Length: " . ($fsize - $range));
			header("Content-Range: bytes $range-" . ($fsize - 1) . "/" . $fsize);
			header("Content-type: " . $ftype);

			fpassthru($fd);
			fclose($fd);
			die();
		}
	}


	public function SizeText() {
		$size = $this->getSize();
		$units = array('KB', 'MB', 'GB', 'TB');
		$currUnit = 'B';
		while (count($units) > 0  &&  $size > 1024) {
			$currUnit = array_shift($units);
			$size /= 1024;
		}
		return ($size | 0) . $currUnit;
	}
}