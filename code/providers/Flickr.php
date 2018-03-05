<?php

namespace RichardsJoqvist\silverstripeEmbedder;

/**
 * Flickr Embedder provider
 */
class Flickr extends EmbedderProvider implements IEmbedderProvider
{
    // Render embed from Flickr links, the following formats are recognized:
    // http://[www.]flickr.com/photos/asimomytis_photography/8594949556

    protected $pattern = "/([^\"|'|=])(https?:\/\/)(www\.|)(flickr\.com\/photos\/)([^\/]*)(\/)([\d]*)([^\d])/isU";

    public function render($matches)
    {
        // Render from oEmbed json
        if ($html = $this->embedder->renderHTML('http://www.flickr.com/services/oembed/?url=http://flickr.com/photos/' . $matches[5] . '/' . $matches[7])) {
            return $matches[1] . $html . end($matches);
        }
        // Failed to get oembed, return original HTML
        return $matches[0];
    }
}
