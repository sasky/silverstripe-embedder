<?php
/**
 * Viddler Embedder provider
 */
class EmbedderProvider_Viddler
{

	/**
	 * Register provider
	 */
	public static function register() {
		// Render embed from Viddler links, the following formats are recognized:
		// http://[www.]viddler.com/v/6d064c53
		Embedder::getInstance()->registerHandler(
			"/([^\"|'|=])(https?:\/\/)(www\.|)(viddler\.com\/v\/)([a-z0-9\-_]*)([^a-z0-9\-_])/isU",
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
		if($html = Embedder::getInstance()->renderHTML('http://www.viddler.com/oembed/?url=http://www.viddler.com/v/'.$matches[5])) {
			return $matches[1] . $html . end($matches);
		}
		// Failed to get oembed, return original HTML
		return $matches[0];
	}

}