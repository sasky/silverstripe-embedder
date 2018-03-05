<?php

namespace RichardsJoqvist\silverstripeEmbedder;

/**
 * Vimeo Embedder provider
 */
class Vimeo extends EmbedderProvider implements IEmbedderProvider
{
    // Render embed from Vimeo links, the following formats are recognized:
    // http://[www.]vimeo.com/20241459
    protected $pattern = "/([^\"|'|=])(https?:\/\/)(www\.|)(vimeo\.com\/)([a-z0-9\-_]*)([^a-z0-9\-_])/isU";

    public function render($matches)
    {
        // Render from oEmbed json
        if ($html = $this->embedder->renderHTML('http://vimeo.com/api/oembed.xml?url=http%3A//vimeo.com/' . $matches[5])) {
            return $matches[1] . $html . end($matches);
        }
        // Failed to get oembed, return original HTML
        return $matches[0];
    }
}
