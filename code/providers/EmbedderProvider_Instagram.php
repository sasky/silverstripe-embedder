<?php
/**
 * Instagram Embedder provider
 */
class EmbedderProvider_Instagram
{

    /**
     * Register provider
     */
    public static function register()
    {
        // Render embed from Instagram links, the following formats are recognized:
        // http://[www.]instagram.com/p/YGRvxQJiOn
        Embedder::getInstance()->registerHandler(
            "/([^\"|'|=])(https?:\/\/)(www\.|)(instagram\.com\/p\/)([a-z0-9\-_]*)(\/|)([^a-z0-9\-_])/isU",
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
        if ($html = Embedder::getInstance()->renderHTML('http://api.instagram.com/oembed?url=http%3A//instagram.com/p/'.$matches[5])) {
            return $matches[1] . $html . end($matches);
        }
        // Failed to get oembed, return original HTML
        return $matches[0];
    }
}
