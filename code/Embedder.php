<?php
/**
 * Extend {@link DataExtension} with methods to render embedded media from links in content
 */
class Embedder extends DataExtension
{

	/**
	 * Exact width of embedded media
	 * If set then media embedded from links will have this exact width. Media embedded from tags is not affected.
	 *
	 * @var int $width
	 */
	private $width = null;

	/**
	 * Exact height of embedded media
	 * If set then media embedded from links will have this exact width. Media embedded from tags is not affected.
	 *
	 * @var int $height
	 */
	private $height = null;

	/**
	 * Max width of embedded media
	 *
	 * @var int $width
	 */
	private $maxwidth = null;

	/**
	 * Max height of embedded media
	 *
	 * @var int $height
	 */
	private $maxheight = null;

	/**
	 * Fields to execute embedder on
	 *
	 * @var array
	 */
	private $fields = [];
	
	/**
	 * Handlers to match
	 *
	 * @var array
	 */
	private $handlers = [];

	/**
	 * Element tags to render
	 *
	 * @var array
	 */
	private $tags = [];

	/**
	 * Factory
	 *
	 * @return Embedder
	 */
	public static function getInstance() {
		static $instance = null;
		if($instance === null) {
			$instance = new Embedder();
		}
		return $instance;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * Set exact width for embedded media
	 *
	 * @param int $width
	 */
	public function setWidth($width) {
		if((int)$width > 0) {
			self::getInstance()->width = (int)$width;
		}
	}

	/**
	 * Set exact height for embedded media
	 *
	 * @param int $height
	 */
	public function setHeight($height) {
		if((int)$height > 0) {
			self::getInstance()->height = (int)$height;
		}
	}

	/**
	 * Set max width for embedded media
	 *
	 * @param int $width
	 */
	public function setMaxWidth($width) {
		if((int)$width > 0) {
			self::getInstance()->maxwidth = (int)$width;
		}
	}

	/**
	 * Set max height for embedded media
	 *
	 * @param int $height
	 */
	public function setMaxHeight($height) {
		if((int)$height > 0) {
			self::getInstance()->maxheight = (int)$height;
		}
	}

	/**
	 * Register content field to enable embedder on
	 *
	 * @param mixed $className; Like "Page", or "BlogEntry", or array("Page","BlogEntry"), or "*" (for all types)
	 * @param mixed $fieldName; Like "Title", or "LeadIn", or array("Title","LeadIn"), or "*" (for all fields)
	 */
	public function registerField($className, $fieldName) {
		if(!is_string($className)) {
			$className = '*';
		}
		if(!is_array($className)) {
			$className = [$className];
		}
		if(!is_string($fieldName)) {
			$fieldName = '*';
		}
		if(!is_array($fieldName)) {
			$fieldName = [$fieldName];
		}
		foreach($className as $cName) {
			if(!isset(self::getInstance()->fields[$cName])) {
				self::getInstance()->fields[$cName] = [];
			}
			if($cName == '*') {
				foreach(self::getInstance()->fields as $cName => &$fields) {
					if($cName == '*') {
						continue;
					}
					foreach($fieldName as $fName) {
						$index = array_search($fName, $fields);
						if($index !== false) {
							unset($fields[$index]);
						}
					}
				}
			}
			foreach($fieldName as $fName) {
				self::getInstance()->fields[$cName][] = $fName;
			}
			self::getInstance()->fields[$cName] = array_unique(self::getInstance()->fields[$cName]);
		}
		foreach(self::getInstance()->fields as $cName => $fields) {
			if(empty($fields)) {
				unset(self::getInstance()->fields[$cName]);
			}
		}
	}

	/**
	 * Register handler
	 *
	 * @param string $pattern regular expression
	 * @param mixed $callback callable (http://www.php.net/manual/en/language.types.callable.php)
	 */
	public function registerHandler($pattern, $callback) {
		$o = new stdClass();
		$o->pattern = $pattern;
		$o->callback = $callback;
		self::getInstance()->handlers[] = $o;
	}

	/**
	 * Register HTML element tags to be rendered
	 *
	 * @param array $tagNames
	 */
	public function registerTags($tagNames) {
		if(!is_array($tagNames)) {
			$tagNames = [$tagNames];
		}
		self::getInstance()->tags = array_merge(self::getInstance()->tags, $tagNames);
		self::getInstance()->tags = array_unique(self::getInstance()->tags);
	}

	/**
	 * Register provider
	 *
	 * @param string $name (ex: Youtube corresponds to class EmbedderProvider_Youtube)
	 * @return boolean
	 */
	public function registerProvider($name) {
		$className = 'EmbedderProvider_'.$name;
		if(class_exists($className)) {
			$className::register();
			return true;
		}
		return false;
	}

	/**
	 * Render HTML from oEmbed response
	 *
	 * @param string $url to oEmbed service
	 * @return string HTML or boolean false on failure
	 */
	public function renderHTML($url) {
		// Get json
		$url .= '&format=json';
		if(self::getInstance()->width || self::getInstance()->maxwidth) {
			$url .= '&maxwidth='.self::getInstance()->width;
		}
		if(self::getInstance()->height || self::getInstance()->maxheight) {
			$url .= '&maxheight='.self::getInstance()->height;
		}
		$fileName = md5($url).'.json';
		if(!$json = self::getInstance()->getCacheFile($fileName, $url)) {
			return false;
		}
		// Decode json to an object
		$obj = json_decode($json);
		if($obj->type == 'video') {
			$html = $obj->html;
			// Calculate size
			if($size = self::getInstance()->calculateSize($obj->width, $obj->height)) {
				$resize = [];
				if(isset($obj->width) && $size['width']) {
					$resize['width="'.$obj->width.'"'] = 'width="'.$size['width'].'"';
				}
				if(isset($obj->height) && $size['height']) {
					$resize['height="'.$obj->height.'"'] = 'height="'.$size['height'].'"';
				}
				if($resize) {
					$html = str_replace(array_keys($resize), array_values($resize), $html);
				}
			}
			return $html;
		}
		if($obj->type == 'photo') {
			// Image template
			$template = '<img src="%s" width="%d" height="%d" class="embed-%s" title="%s"/>';
			// Calculate size
			if($size = self::getInstance()->calculateSize($obj->width, $obj->height)) {
				$obj->width = $size['width'];
				$obj->height = $size['height'];
			}
			$title = strip_tags($obj->title);
			if(empty($title)) {
				$title = $obj->provider_name;
			}
			return sprintf($template, $obj->url, $obj->width, $obj->height, strtolower($obj->provider_name), $title);
		}
		return false;
	}

	/**
	 * Calculate embed size (maintaining aspect ratio)
	 *
	 * @param int $width original width of media
	 * @param int $height original height of media
	 * @return array
	 */
	public function calculateSize($width, $height) {
		try {
			$width = (int) $width;
			$height = (int) $height;
			if(self::getInstance()->width) {
				if(!self::getInstance()->height) {
					$height = round($height * (self::getInstance()->width / $width));
				}
				$width = self::getInstance()->width;
			}
			else if(self::getInstance()->maxwidth) {
				if($width > self::getInstance()->maxwidth) {
					$height = round($height * (self::getInstance()->maxwidth / $width));
					$width = self::getInstance()->maxwidth;
				}
			}
			if(self::getInstance()->height) {
				if(!self::getInstance()->width) {
					$width = round($width * (self::getInstance()->height / $height));
				}
				$height = self::getInstance()->height;
			}
			else if(self::getInstance()->maxheight) {
				if($height > self::getInstance()->maxheight) {
					$width = round($width * (self::getInstance()->maxheight / $height));
					$height = self::getInstance()->maxheight;
				}
			}
			return array('width'=>$width, 'height'=>$height);
		}
		catch(Exception $e) {
			return false;
		}
	}

	/**
	 * Get file from cache or download file to cache if it does not exist
	 *
	 * @param string $fileName
	 * @param string $sourceUrl
	 * @return mixed or boolean false on failure
	 */
	private function getCacheFile($fileName, $sourceUrl) {
		$cacheFolderPath = dirname(dirname(__FILE__)).'/cache';
		if(!is_dir($cacheFolderPath)) {
			Filesystem::makeFolder($cacheFolderPath);
		}
		if(!is_file($cacheFolderPath.'/.htaccess')) {
			file_put_contents($cacheFolderPath.'/.htaccess', "order deny, allow\ndeny from all", LOCK_EX);
		}
		$cachePath = $cacheFolderPath.'/'.$fileName;
		if(is_file($cachePath)) {
			return file_get_contents($cachePath);
		}
		if($data = file_get_contents($sourceUrl)) {
			file_put_contents($cachePath, $data, LOCK_EX);
			return $data;
		}
		return false;
	}

	/**
	 * Render embeds
	 *
	 * @param Controller $controller
	 */
	function contentcontrollerInit($controller) {
		$fields = [];
		if(isset(self::getInstance()->fields['*'])) {
			$fields = array_merge($fields, self::getInstance()->fields['*']);
		}
		if(isset(self::getInstance()->fields[$this->owner->class])) {
			$fields = array_merge($fields, self::getInstance()->fields[$this->owner->class]);
		}
		if(!$fields) {
			return;
		}
		foreach($fields as $field) {
			$content = $controller->$field;
			if(empty($content)) {
				continue;
			}
			foreach(self::getInstance()->tags as $tag) {
				preg_match_all("/(\&lt;{$tag}\s)(.*)(\/{$tag}\&gt;)/isU", $content, $m);
				$m = array_unique($m[0]);
				foreach($m as $match) {
					$content = str_replace($match, html_entity_decode($match), $content);
				}
			}
			foreach(self::getInstance()->handlers as $handler) {
				$content = preg_replace_callback($handler->pattern, $handler->callback, $content);
			}
			$controller->$field = $content;
		}
	}
	
	/**
	 * Model as controller
	 *
	 * @param Controller $controller
	 */
	function modelascontrollerInit($controller) {
	}

}