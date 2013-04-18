<?php
/**
 * Youtube Embedder provider
 */
class EmbedderProvider_Youtube
{

	/**
	 * Register provider
	 */
	public static function register() {
		// Render embed from Youtube links, the following formats are recognized:
		// http://[www.]youtube.com/v/dQw4w9WgXcQ
		// http://[www.]youtube.com/watch?v=dQw4w9WgXcQ
		Embedder::getInstance()->registerHandler(
				"/([^\"|'|=])(http:\/\/)(www\.|)(youtube\.com\/)(watch\?|)(v)(=|\/)([a-z0-9\-_]*)([^a-z0-9\-_])/isU",
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
		if($html = Embedder::getInstance()->renderHTML('http://www.youtube.com/oembed?url=http%3A//www.youtube.com/watch%3Fv%3D'.$matches[8])) {
			return $matches[1] . $html . end($matches);
		}
		// Failed to get oembed, return original HTML
		return $matches[0];
	}

}