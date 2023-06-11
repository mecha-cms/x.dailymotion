<?php namespace x\dailymotion;

function from(string $id, string $end, array $m = []) {
    $p = new \HTML('<p' . ($m[1] ?? "") . '>');
    $parts = \array_replace(["", ""], \explode('#', $end, 2));
    return new \HTML(\Hook::fire('y.dailymotion', [[
        'hash' => "" !== $parts[1] ? $parts[1] : null,
        'id' => $id,
        'query' => "" !== $parts[0] ? \From::query($parts[0]) : [],
        0 => $p[0],
        1 => [
            '<' => $m[2] ?? "",
            'embed' => ['iframe', "", [
                'allow' => 'autoplay',
                'allowfullscreen' => true,
                'frameborder' => '0',
                'src' => 'https://www.dailymotion.com/embed/video/' . $id . $end,
                'style' => 'border: 0; display: block; height: 100%; left: 0; margin: 0; overflow: hidden; padding: 0; position: absolute; top: 0; width: 100%;',
                'title' => $m['title'] ?? \i('Dailymotion Video Player')
            ]],
            '>' => $m[4] ?? ""
        ],
        2 => \array_replace([
            'style' => 'display: block; height: 0; margin-left: 0; margin-right: 0; overflow: hidden; padding: 0 0 56.25%; position: relative;'
        ], (array) ($p[2] ?? []))
    ]]), true);
}

function page__content($content) {
    if (!$content || false === \stripos($content, '</p>')) {
        return $content;
    }
    // Skip parsing process if we are in these HTML element(s)
    $parts = (array) \preg_split('/(<!--[\s\S]*?-->|' . \implode('|', (static function ($parts) {
        foreach ($parts as $k => &$v) {
            $v = '<' . \x($k) . '(?:\s[\p{L}\p{N}_:-]+(?:=(?:"[^"]*"|\'[^\']*\'|[^\/>]*))?)*>[\s\S]*?<\/' . \x($k) . '>';
        }
        unset($v);
        return $parts;
    })([
        'pre' => 1,
        'code' => 1, // Must come after `pre`
        'kbd' => 1,
        'math' => 1,
        'script' => 1,
        'style' => 1,
        'textarea' => 1,
        'p' => 1 // Must come last
    ])) . ')/', $content, -1, \PREG_SPLIT_NO_EMPTY | \PREG_SPLIT_DELIM_CAPTURE);
    $content = "";
    foreach ($parts as $part) {
        if ($part && '<' === $part[0] && '>' === \substr($part, -1)) {
            if ('</p>' === \strtolower(\substr($part, -4))) {
                $content .= \preg_replace_callback('/<p(\s(?:"[^"]*"|\'[^\']*\'|[^\/>])*)?>(\s*)(<a\s(?:"[^"]*"|\'[^\']*\'|[^\/>])*>[\s\S]*?<\/a>|<iframe\s(?:"[^"]*"|\'[^\']*\'|[^\/>])*>[\s\S]*?<\/iframe>|https?:\/\/(?:www\.)?(?:dai\.ly|dailymotion\.com)\/\S+)(\s*)<\/p>/i', static function ($m) {
                    if ('</a>' === \strtolower(\substr($v = $m[3], -4)) && false !== \stripos($v, 'href=')) {
                        $a = new \HTML($v);
                        if (!$href = $a['href']) {
                            return $m[0];
                        }
                        $default = \htmlspecialchars_decode(\trim(\strip_tags((string) $a[1])));
                        $m['title'] = $a['title'] ?? ($default !== $href ? $default : null);
                        $m[3] = $v = $href;
                    } else if ('</iframe>' === \strtolower(\substr($v = $m[3], -9)) && false !== \stripos($v, 'src=')) {
                        $iframe = new \HTML($v);
                        if (!$src = $iframe['src']) {
                            return $m[0];
                        }
                        $default = \htmlspecialchars_decode(\trim(\strip_tags((string) $iframe[1])));
                        $m['title'] = $iframe['title'] ?? ($default !== $src ? $default : null);
                        $m[3] = $v = $src;
                    }
                    if (0 === \strpos($v, 'http://') || 0 === \strpos($v, 'https://')) {
                        // `https://www.dailymotion.com/embed/video/:id`
                        if (false !== \strpos($v, '/embed/video/') && \preg_match('/\/embed\/video\/([^\/?&#]+)([?&#].*)?$/', $v, $mm)) {
                            return (string) \x\dailymotion\from($mm[1], $mm[2] ?? "", $m);
                        }
                        // `https://www.dailymotion.com/video/:id`
                        if (false !== \strpos($v, '/video/') && \preg_match('/\/video\/([^\/?&#]+)([?&#].*)?$/', $v, $mm)) {
                            return (string) \x\dailymotion\from($mm[1], $mm[2] ?? "", $m);
                        }
                        // `https://dai.ly/:id`
                        if ((false !== \strpos($v, '/dai.ly/') || false !== \strpos($v, '.dai.ly/')) && \preg_match('/[\/.]dai\.ly\/([^\/?&#]+)([?&#].*)?$/', $v, $mm)) {
                            return (string) \x\dailymotion\from($mm[1], $mm[2] ?? "", $m);
                        }
                    }
                    return $m[0];
                }, $part);
                continue;
            }
            $content .= $part; // Is a HTML tag other than `<p>` or comment, skip!
            continue;
        }
        $content .= $part;
    }
    return "" !== $content ? $content : null;
}

function page__image($image) {
    // Skip if `image` data has been set!
    if ($image) {
        return $image;
    }
    // Get Dailymotion link from `content` data
    if ($content = $this->content) {
        if (false !== \strpos($content, '<iframe ') && \preg_match('/<iframe(\s[^>]+)>/', $content, $m)) {
            if (false !== \strpos($m[1], ' src=')) {
                $link = \htmlspecialchars_decode(\trim(\strstr(\substr(\strstr($m[1], ' src='), 5) . ' ', ' ', true), '\'"'));
                // Get Dailymotion video image from link
                if (false !== \strpos($link, 'dailymotion.com/embed/video/') && \preg_match('/\/embed\/video\/([^\/?&#]+)$/', $link, $mm)) {
                    return 'https://www.dailymotion.com/thumbnail/video/' . $mm[1];
                }
            }
        }
    }
    return $image;
}

function page__images($images) {
    $images = (array) ($images ?? []);
    // Get Dailymotion link(s) from `content` data
    if ($content = $this->content) {
        if (false !== \strpos($content, '<iframe ') && \preg_match_all('/<iframe(\s[^>]+)>/', $content, $m)) {
            foreach ($m[1] as $v) {
                if (false !== \strpos($v, ' src=')) {
                    $link = \htmlspecialchars_decode(\trim(\strstr(\substr(\strstr($v, ' src='), 5) . ' ', ' ', true), '\'"'));
                    // Get Dailymotion video image from link
                    if (false !== \strpos($link, 'dailymotion.com/embed/video/') && \preg_match('/\/embed\/video\/([^\/?&#]+)$/', $link, $mm)) {
                        // Merge with the current `images` data
                        $images[] = 'https://www.dailymotion.com/thumbnail/video/' . $mm[1];
                    }
                }
            }
        }
    }
    return \array_unique($images);
}

\Hook::set('page.content', __NAMESPACE__ . "\\page__content", 2.1);
if (isset($state->x->image)) {
    \Hook::set('page.image', __NAMESPACE__ . "\\page__image", 2.2);
    \Hook::set('page.images', __NAMESPACE__ . "\\page__images", 2.2);
}

if (\defined("\\TEST") && 'x.dailymotion' === \TEST && \is_file($test = __DIR__ . \D . 'test.php')) {
    require $test;
}