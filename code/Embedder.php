<?php

namespace RichardsJoqvist\silverstripeEmbedder;
use RichardsJoqvist\silverstripeEmbedder\Youtube;
use RichardsJoqvist\silverstripeEmbedder\Vimeo;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Injector\Injector;

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
    private $maxWidth = null;

    /**
     * Max height of embedded media
     *
     * @var int $height
     */
    private $maxHeight = null;

    /**
     * Fields to execute embedder on
     *
     * @var array
     */
    private $fields = [];

    /**
     * List of ClassNames that implement IEmbedderProvider
     *
     * @var array
    */

    private $providers = [];


    /**
     * Element tags to render
     *
     * @var array
     */
    private $tags = [];


    
    private static $dependencies = [
        'cache' => '%$Psr\SimpleCache\CacheInterface.silverstripeEmbedder' // see _config/cache.yml
    ];

    /**
     * @var Psr\SimpleCache\CacheInterface
     */
    public $cache;



    /**
     * Render HTML from oEmbed response
     *
     * @param  string $url to oEmbed service
     * @return string HTML or boolean false on failure
     */
    public function renderHTML($url)
    {
		$width = $this->owner->config()->get('width');
		$maxWidth = $this->owner->config()->get('maxWidth');
		$height = $this->owner->config()->get('height');
		$maxHeight = $this->owner->config()->get('maxHeight'); 

        // Get json
        $url .= '&format=json';
        if ($width || $maxWidth) {
			$urlWidth = ($maxWidth) ? $maxWidth : $width;
            $url .= '&maxwidth=' . $urlWidth;
        }
        if ($height || $maxHeight) {
			$urlHeight = ($maxHeight) ? $maxHeight : $height; 
            $url .= '&maxheight=' . $urlHeight;
        }
        $fileName = md5($url) . '.json';
	
        if (!$json = $this->loadJson($url)) {
            return false;
        }
        // Decode json to an object
        if ($json->type == 'video') {
            $html = $json->html;
            // Calculate size
            if ($size = $this->calculateSize($json->width, $json->height)) {
                $resize = [];
                if (isset($json->width) && $size['width']) {
                    $resize['width="' . $json->width . '"'] = 'width="' . $size['width'] . '"';
                }
                if (isset($json->height) && $size['height']) {
                    $resize['height="' . $json->height . '"'] = 'height="' . $size['height'] . '"';
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
            if ($size = $this->calculateSize($json->width, $json->height)) {
                $json->width = $size['width'];
                $json->height = $size['height'];
            }
            $title = strip_tags($json->title);
            if (empty($title)) {
                $title = $json->provider_name;
            }

            return sprintf($template, $json->url, $json->width, $json->height, strtolower($json->provider_name), $title);
        }
        if($json->type === 'rich' && $json->html) // Instagram type
        {
            return $json->html;
        }

        return false;
    }

    /**
     * Calculate embed size (maintaining aspect ratio)
     *
     * @param  int   $width  original width of media
     * @param  int   $height original height of media
     * @return array
     */
    public function calculateSize($mediaWidth, $mediaHeight)
    {
        try {
            $mediaWidth = (int) $mediaWidth;
            $mediaHeight = (int) $mediaHeight;

			$width = $this->owner->config()->get('width');
			$maxWidth = $this->owner->config()->get('maxWidth');
			$height = $this->owner->config()->get('height');
			$maxHeight = $this->owner->config()->get('maxHeight'); 

            if ($width) {
                if (!$height) {
                    $mediaHeight = round($mediaHeight  * ($width / $mediaWidth));
                }
                $mediaWidth = $width;
            } elseif ($maxWidth) {
                if ($mediaWidth > $maxWidth) {
                    $mediaHeight = round($mediaHeight * ($maxWidth / $mediaWidth));
                    $mediaWidth = $maxWidth;
                }
            }
            if ($height) {
                if (!$width) {
                    $mediaWidth = round($mediaWidth * ($height / $mediaHeight));
                }
                $mediaHeight = $height;
            } elseif ($maxHeight) {
                if ($mediaHeight > $maxHeight) {
                    $mediaWidth = round($mediaWidth * ($maxHeight / $mediaHeight));
                    $mediaHeight = $maxHeight;
                }
            }

            return ['width' => $mediaWidth, 'height' => $mediaHeight];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Load oEmbed json and return it as stdclass object
     *
     * @param  string        $url
     * @param  bool          $refresh
     * @return bool|stdclass
     */
    private function loadJson($url, $refresh = false)
    {
		$cacheKey = md5($url);
        
        // Unless force refreshing, try loading from cache
        if (!$refresh) {
            if ($json = $this->cache->get($cacheKey)) {
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
                $this->cache->set($cacheKey,$json);

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
        $configFields =$this->owner->config()->get('fields');
        if (!is_array($configFields)) {
            user_error('Fields Not Set, Content Fields need to be set in a yml config file. See Readme for details ', E_USER_ERROR);
        }
	
  
		$className = $this->owner->data()->className;
	
        // Get the Wild Fields that apply to all SiteTree Models

        $fieldsToApplyEmbeddersTo = $this->convertToArray('all', $configFields);

        // Get the Fields set to apply to this controllers SiteTree object
        $fieldsToApplyEmbeddersTo = array_merge(
            $fieldsToApplyEmbeddersTo,
            $this->convertToArray($className, $configFields)
        );


        if (empty($fieldsToApplyEmbeddersTo)) {
            return;
        }

		$providers = $this->owner->config()->get('providers');
        $tags = $this->owner->config()->get('tags');

        foreach ($fieldsToApplyEmbeddersTo as $field) {
            $content = $controller->data()->$field;
            if (!$content || empty($content)) {
                continue;
			}
		
			foreach ($providers as $providerClassName) {
				$provider = new $providerClassName($this);
				$content = preg_replace_callback(
					$provider->pattern(), 
					function($matches) use ($provider) {
						return $provider->render($matches);
					},
			 		$content);	

			}


			
            if($tags && is_array($tags)) {
                foreach ($tags  as $tag) {
                    preg_match_all("/(\&lt;{$tag}\s)(.*)(\/{$tag}\&gt;)/isU", $content, $m);
                    $m = array_unique($m[0]);
                    foreach ($m as $match) {
                        $content = str_replace($match, html_entity_decode($match), $content);
                    }
                }
            }

            
            $controller->data()->$field = $content;
			

		}
	
    }

    private function convertToArray($key, $array)
    {
        if (!isset($array[$key])) {
            return [];
        }
        $value = $array[$key];
        if (is_string($value)) {
            return [$value];
        } elseif (is_array($value)) {
            return $value;
        }
        user_error('Config value needs to be a string or Array', E_USER_ERROR);
    }
}
