<?php

namespace RichardsJoqvist\silverstripeEmbedder;

/**
 * Instagram Embedder provider
 */
class Instagram extends EmbedderProvider implements IEmbedderProvider
{
    // Render embed from Instagram links, the following formats are recognized:
    // http://[www.]instagram.com/p/YGRvxQJiOn

    protected $pattern = "/([^\"|'|=])(https?:\/\/)(www\.|)(instagram\.com\/p\/)([a-z0-9\-_]*)(\/|)([^a-z0-9\-_])/isU";

    public function render($matches)
    {
        // Render from oEmbed json
        if ($html = $this->embedder->renderHTML('http://api.instagram.com/oembed?url=http%3A//instagram.com/p/' . $matches[5])) {
            return $matches[1] . $html . end($matches);
        }
        // Failed to get oembed, return original HTML
        return $matches[0];
    }
}
