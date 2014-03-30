<?php
/**
 * Vimeo Embedder provider
 */
class EmbedderProvider_Vimeo
{

	/**
	 * Register provider
	 */
	public static function register() {
		// Render embed from Vimeo links, the following formats are recognized:
		// http://[www.]vimeo.com/20241459
		Embedder::getInstance()->registerHandler(
			"/([^\"|'|=])(https?:\/\/)(www\.|)(vimeo\.com\/)([a-z0-9\-_]*)([^a-z0-9\-_])/isU",
			array(__CLASS__, "render")
		);
	}

	/**
	 * Render
	 *
	 * @param array $matches
	 * @return string HTML
	 */
	public static function render($matches) {
		// Render from oEmbed json
		if($html = Embedder::getInstance()->renderHTML('http://vimeo.com/api/oembed.xml?url=http%3A//vimeo.com/'.$matches[5])) {
			return $matches[1] . $html . end($matches);
		}
		// Failed to get oembed, return original HTML
		return $matches[0];
	}

}