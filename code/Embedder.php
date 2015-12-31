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
    private $fields = array();
    
    /**
     * Handlers to match
     *
     * @var array
     */
    private $handlers = array();

    /**
     * Element tags to render
     *
     * @var array
     */
    private $tags = array();

    /**
     * Factory
     *
     * @return Embedder
     */
    public static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new Embedder();
        }
        return $instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Set exact width for embedded media
     *
     * @param int $width
     */
    public function setWidth($width)
    {
        if ((int)$width > 0) {
            self::getInstance()->width = (int)$width;
        }
    }

    /**
     * Set exact height for embedded media
     *
     * @param int $height
     */
    public function setHeight($height)
    {
        if ((int)$height > 0) {
            self::getInstance()->height = (int)$height;
        }
    }

    /**
     * Set max width for embedded media
     *
     * @param int $width
     */
    public function setMaxWidth($width)
    {
        if ((int)$width > 0) {
            self::getInstance()->maxwidth = (int)$width;
        }
    }

    /**
     * Set max height for embedded media
     *
     * @param int $height
     */
    public function setMaxHeight($height)
    {
        if ((int)$height > 0) {
            self::getInstance()->maxheight = (int)$height;
        }
    }

    /**
     * Register content field to enable embedder on
     *
     * @param mixed $className; Like "Page", or "BlogEntry", or array("Page","BlogEntry"), or "*" (for all types)
     * @param mixed $fieldName; Like "Title", or "LeadIn", or array("Title","LeadIn"), or "*" (for all fields)
     */
    public function registerField($className, $fieldName)
    {
        if (!is_string($className)) {
            $className = '*';
        }
        if (!is_array($className)) {
            $className = array($className);
        }
        if (!is_string($fieldName)) {
            $fieldName = '*';
        }
        if (!is_array($fieldName)) {
            $fieldName = array($fieldName);
        }
        foreach ($className as $cName) {
            if (!isset(self::getInstance()->fields[$cName])) {
                self::getInstance()->fields[$cName] = array();
            }
            if ($cName == '*') {
                foreach (self::getInstance()->fields as $cName => &$fields) {
                    if ($cName == '*') {
                        continue;
                    }
                    foreach ($fieldName as $fName) {
                        $index = array_search($fName, $fields);
                        if ($index !== false) {
                            unset($fields[$index]);
                        }
                    }
                }
            }
            foreach ($fieldName as $fName) {
                self::getInstance()->fields[$cName][] = $fName;
            }
            self::getInstance()->fields[$cName] = array_unique(self::getInstance()->fields[$cName]);
        }
        foreach (self::getInstance()->fields as $cName => $fields) {
            if (empty($fields)) {
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
    public function registerHandler($pattern, $callback)
    {
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
    public function registerTags($tagNames)
    {
        if (!is_array($tagNames)) {
            $tagNames = array($tagNames);
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
    public function registerProvider($name)
    {
        $className = 'EmbedderProvider_'.$name;
        if (class_exists($className)) {
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
    public function renderHTML($url)
    {
        // Get json
        $url .= '&format=json';
        if (self::getInstance()->width || self::getInstance()->maxwidth) {
            $url .= '&maxwidth='.self::getInstance()->width;
        }
        if (self::getInstance()->height || self::getInstance()->maxheight) {
            $url .= '&maxheight='.self::getInstance()->height;
        }
        $fileName = md5($url).'.json';
        if (!$json = self::getInstance()->loadJson($url)) {
            return false;
        }
        // Decode json to an object
        if ($json->type == 'video') {
            $html = $json->html;
            // Calculate size
            if ($size = self::getInstance()->calculateSize($json->width, $json->height)) {
                $resize = array();
                if (isset($json->width) && $size['width']) {
                    $resize['width="'.$json->width.'"'] = 'width="'.$size['width'].'"';
                }
                if (isset($json->height) && $size['height']) {
                    $resize['height="'.$json->height.'"'] = 'height="'.$size['height'].'"';
                }
                if ($resize) {
                    $html = str_replace(array_keys($resize), array_values($resize), $html);
                }
            }
            return $html;
        }
        if ($json->type == 'photo') {
            // Image template
            $template = '<img src="%s" width="%d" height="%d" class="embed-%s" title="%s"/>';
            // Calculate size
            if ($size = self::getInstance()->calculateSize($json->width, $json->height)) {
                $json->width = $size['width'];
                $json->height = $size['height'];
            }
            $title = strip_tags($json->title);
            if (empty($title)) {
                $title = $json->provider_name;
            }
            return sprintf($template, $json->url, $json->width, $json->height, strtolower($json->provider_name), $title);
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
    public function calculateSize($width, $height)
    {
        try {
            $width = (int) $width;
            $height = (int) $height;
            if (self::getInstance()->width) {
                if (!self::getInstance()->height) {
                    $height = round($height * (self::getInstance()->width / $width));
                }
                $width = self::getInstance()->width;
            } elseif (self::getInstance()->maxwidth) {
                if ($width > self::getInstance()->maxwidth) {
                    $height = round($height * (self::getInstance()->maxwidth / $width));
                    $width = self::getInstance()->maxwidth;
                }
            }
            if (self::getInstance()->height) {
                if (!self::getInstance()->width) {
                    $width = round($width * (self::getInstance()->height / $height));
                }
                $height = self::getInstance()->height;
            } elseif (self::getInstance()->maxheight) {
                if ($height > self::getInstance()->maxheight) {
                    $width = round($width * (self::getInstance()->maxheight / $height));
                    $height = self::getInstance()->maxheight;
                }
            }
            return array('width'=>$width, 'height'=>$height);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Load oEmbed json and return it as stdclass object
     *
     * @param string $url
     * @param bool $refresh
     * @return bool|stdclass
     */
    private function loadJson($url, $refresh=false)
    {
        $cacheKey = md5($url);
        // Get the Zend Cache to load/store cache into
        $cache = SS_Cache::factory('Embedder_json_', 'Output', array(
            'automatic_serialization' => false,
            'lifetime' => null
        ));
        // Unless force refreshing, try loading from cache
        if (!$refresh) {
            if ($json = $cache->load($cacheKey)) {
                return json_decode($json);
            }
        }
        // Load json and cache it
        $json = file_get_contents($url);
        if (!empty($json)) {
            try {
                $jsonObj = json_decode($json);
            } catch (Exception $e) {
                return false;
            }
            if ($jsonObj) {
                $cache->save($json, $cacheKey);
                return $jsonObj;
            }
        }
        return false;
    }

    /**
     * Render embeds
     *
     * @param Controller $controller
     */
    public function contentcontrollerInit($controller)
    {
        $fields = array();
        if (isset(self::getInstance()->fields['*'])) {
            $fields = array_merge($fields, self::getInstance()->fields['*']);
        }
        if (isset(self::getInstance()->fields[$this->owner->class])) {
            $fields = array_merge($fields, self::getInstance()->fields[$this->owner->class]);
        }
        if (!$fields) {
            return;
        }
        foreach ($fields as $field) {
            $content = $controller->$field;
            if (empty($content)) {
                continue;
            }
            foreach (self::getInstance()->tags as $tag) {
                preg_match_all("/(\&lt;{$tag}\s)(.*)(\/{$tag}\&gt;)/isU", $content, $m);
                $m = array_unique($m[0]);
                foreach ($m as $match) {
                    $content = str_replace($match, html_entity_decode($match), $content);
                }
            }
            foreach (self::getInstance()->handlers as $handler) {
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
    public function modelascontrollerInit($controller)
    {
    }
}
