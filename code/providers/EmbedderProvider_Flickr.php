<?php
/**
 * Flickr Embedder provider
 */
class EmbedderProvider_Flickr
{

    /**
     * Register provider
     */
    public static function register()
    {
        // Render embed from Flickr links, the following formats are recognized:
        // http://[www.]flickr.com/photos/asimomytis_photography/8594949556
        Embedder::getInstance()->registerHandler(
            "/([^\"|'|=])(https?:\/\/)(www\.|)(flickr\.com\/photos\/)([^\/]*)(\/)([\d]*)([^\d])/isU",
            array(__CLASS__, "render")
        );
    }

    /**
     * Render
     *
     * @param array $matches
     * @return string HTML
     */
    public static function render($matches)
    {
        // Render from oEmbed json
        if ($html = Embedder::getInstance()->renderHTML('http://www.flickr.com/services/oembed/?url=http://flickr.com/photos/'.$matches[5].'/'.$matches[7])) {
            return $matches[1] . $html . end($matches);
        }
        // Failed to get oembed, return original HTML
        return $matches[0];
    }
}
