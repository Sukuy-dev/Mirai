<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Mirai_Rss
{
    private $options;
    private $baseUrl;

    public function __construct()
    {
        $this->options = Typecho_Widget::widget('Widget_Options');
        $this->baseUrl = $this->options->siteUrl;
    }

    private function getPosts($limit = 10)
    {
        $posts = [];
        $archive = Typecho_Widget::widget('Widget_Archive@mirai_rss', 'pageSize=' . $limit . '&type=index', 'page=1');
        
        while ($archive->next()) {
            if (!empty($archive->password) || $archive->status !== 'publish' || !$archive->allowFeed) {
                continue;
            }
            
            $categories = [];
            if ($archive->categories) {
                foreach ($archive->categories as $category) {
                    $categories[] = ['name' => $category['name'], 'slug' => $category['slug']];
                }
            }
            
            $content = $archive->content;
            $payFiltered = $this->filterPayContent($content, $archive->cid, $archive->permalink);
            
            $posts[] = [
                'title' => $archive->title,
                'created' => $archive->created,
                'modified' => $archive->modified,
                'text' => $payFiltered,
                'permalink' => $archive->permalink,
                'screenName' => $archive->author->screenName,
                'categories' => $categories
            ];
        }
        return $posts;
    }

    private function filterPayContent($content, $cid, $permalink)
    {
        if (!function_exists('Mirai_payEnabled') || !function_exists('Mirai_payPostSettings') || !function_exists('Mirai_payAvailableForPost')) {
            return $content;
        }
        
        if (!Mirai_payEnabled()) {
            return $content;
        }
        
        $settings = Mirai_payPostSettings($cid);
        if (!Mirai_payAvailableForPost($settings)) {
            return $content;
        }
        
        if ($settings['mode'] === 'read') {
            return '<p>本文为付费阅读内容，请<a href="' . htmlspecialchars($permalink, ENT_QUOTES, 'UTF-8') . '">访问原文</a>购买后阅读。</p>';
        }
        
        if ($settings['mode'] === 'partial') {
            $content = preg_replace('/\[pay\][\s\S]*?\[\/pay\]/i', '<p>[付费内容已隐藏，请<a href="' . htmlspecialchars($permalink, ENT_QUOTES, 'UTF-8') . '">访问原文</a>购买后阅读]</p>', $content);
        }
        
        return $content;
    }

    /**
     * Cleans content for RSS feed
     * Removes dangerous tags, handles relative URLs, and manages excerpts
     */
    private function cleanContent($content, $permalink = '')
    {
        // Remove dangerous tags
        foreach (['script', 'iframe', 'object', 'embed', 'form'] as $tag) {
            $content = preg_replace('/<' . $tag . '[^>]*>.*?<\/' . $tag . '>/is', '', $content);
            $content = preg_replace('/<' . $tag . '[^>]*\/?>/i', '', $content);
        }
        
        // Fix relative URLs for images
        $content = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/i', function($m) {
            $src = $m[1];
            if (!preg_match('/^(https?:)?\/\//i', $src)) {
                $src = rtrim($this->baseUrl, '/') . '/' . ltrim($src, '/');
            }
            return str_replace($m[1], $src, $m[0]);
        }, $content);
        
        // Handle "Read More" and full text option
        $more = strpos($content, '<!--more-->');
        if ($more !== false && !$this->options->feedFullText) {
            $content = substr($content, 0, $more) . '<p><a href="' . htmlspecialchars($permalink) . '">继续阅读全文...</a></p>';
        } elseif (!$this->options->feedFullText && $permalink) {
            $text = strip_tags(preg_replace('/\s+/', ' ', strip_tags($content)));
            if (mb_strlen($text, 'UTF-8') > 500) {
                $content = mb_substr($content, 0, 800, 'UTF-8');
                $content = preg_replace('/<[^>]*$/i', '', $content) . '...<p><a href="' . htmlspecialchars($permalink) . '">继续阅读全文...</a></p>';
            }
        }
        
        return $this->cleanXmlContent($content);
    }

    /**
     * Cleans XML content by removing invalid control characters
     * Adapted from Emlog's best practice
     */
    private function cleanXmlContent($string)
    {
        $string = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $string);
        // Only use iconv if available and valid
        if (function_exists('iconv')) {
            $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        }
        $string = str_replace(']]>', ']]&gt;', $string);
        return $string;
    }

    private function send304($etag)
    {
        header('HTTP/1.1 304 Not Modified');
        header('ETag: ' . $etag);
        exit;
    }

    private function sendHeaders($type, $lastModified)
    {
        $etag = '"' . md5($lastModified . $this->baseUrl) . '"';
        header('Content-Type: ' . $type . '; charset=' . $this->options->charset);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=3600');
    }

    public function renderRss2()
    {
        $this->render('rss2');
    }

    public function renderAtom()
    {
        $this->render('atom');
    }

    private function render($type)
    {
        $posts = $this->getPosts();
        $lastModified = !empty($posts) ? $posts[0]['created'] : time();
        
        $contentType = $type === 'atom' ? 'application/atom+xml' : 'application/rss+xml';

        $xml = ($type === 'atom') ? $this->buildAtomXml($posts, $lastModified) : $this->buildRss2Xml($posts, $lastModified);
        
        $this->sendHeaders($contentType, $lastModified);
        echo $xml;
    }

    private function buildRss2Xml($posts, $lastModified)
    {
        $title = htmlspecialchars($this->options->siteTitle ?: $this->options->title);
        $link = $this->baseUrl;
        $description = htmlspecialchars($this->options->siteDescription ?: $this->options->description);
        $pubDate = (new \Typecho\Date($lastModified))->format(DATE_RSS);
        $lastBuildDate = (new \Typecho\Date())->format(DATE_RSS);
        $selfLink = rtrim($this->options->index, '/') . '/rss';

        $items = '';
        foreach ($posts as $post) {
            $items .= $this->buildRss2Item($post);
        }

        return <<<XML
<?xml version="1.0" encoding="{$this->options->charset}"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title>{$title}</title>
<link>{$link}</link>
<description>{$description}</description>
<language>zh-CN</language>
<pubDate>{$pubDate}</pubDate>
<lastBuildDate>{$lastBuildDate}</lastBuildDate>
<ttl>60</ttl>
<generator>Mirai RSS</generator>
<atom:link href="{$selfLink}" rel="self" type="application/rss+xml" />
{$items}
</channel>
</rss>
XML;
    }

    private function buildRss2Item($post)
    {
        $link = $post['permalink'];
        $content = $this->cleanContent($post['text'], $link);
        $title = htmlspecialchars($post['title']);
        $pubDate = (new \Typecho\Date($post['created']))->format(DATE_RSS);
        $creator = htmlspecialchars($post['screenName']);
        
        $categories = '';
        foreach ($post['categories'] as $cat) {
            $categories .= '<category>' . htmlspecialchars($cat['name']) . '</category>';
        }

        return <<<ITEM
<item>
<title>{$title}</title>
<link>{$link}</link>
<guid isPermaLink="true">{$link}</guid>
<pubDate>{$pubDate}</pubDate>
<dc:creator>{$creator}</dc:creator>
{$categories}
<description><![CDATA[{$content}]]></description>
<content:encoded><![CDATA[{$content}]]></content:encoded>
</item>
ITEM;
    }

    private function buildAtomXml($posts, $lastModified)
    {
        $title = htmlspecialchars($this->options->siteTitle ?: $this->options->title);
        $subtitle = htmlspecialchars($this->options->siteDescription ?: $this->options->description);
        $selfLink = rtrim($this->options->index, '/') . '/atom';
        $link = $this->baseUrl;
        $updated = (new \Typecho\Date())->format('Y-m-d\TH:i:sP');
        
        $entries = '';
        foreach ($posts as $post) {
            $entries .= $this->buildAtomEntry($post);
        }

        return <<<XML
<?xml version="1.0" encoding="{$this->options->charset}"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="zh-CN">
<title>{$title}</title>
<subtitle>{$subtitle}</subtitle>
<link href="{$selfLink}" rel="self" type="application/atom+xml" />
<link href="{$link}" rel="alternate" type="text/html" />
<id>{$link}</id>
<updated>{$updated}</updated>
<generator uri="https://github.com/dreamer-paul/Mirai">Mirai</generator>
{$entries}
</feed>
XML;
    }

    private function buildAtomEntry($post)
    {
        $link = $post['permalink'];
        $content = $this->cleanContent($post['text'], $link);
        $title = htmlspecialchars($post['title']);
        $updated = (new \Typecho\Date(!empty($post['modified']) ? $post['modified'] : $post['created']))->format('Y-m-d\TH:i:sP');
        $published = (new \Typecho\Date($post['created']))->format('Y-m-d\TH:i:sP');
        $author = htmlspecialchars($post['screenName']);

        $categories = '';
        foreach ($post['categories'] as $cat) {
            $categories .= '<category term="' . htmlspecialchars($cat['slug']) . '" label="' . htmlspecialchars($cat['name']) . '" />';
        }

        return <<<ENTRY
<entry>
<title type="text">{$title}</title>
<link href="{$link}" rel="alternate" type="text/html" />
<id>{$link}</id>
<updated>{$updated}</updated>
<published>{$published}</published>
<author><name>{$author}</name></author>
{$categories}
<summary type="html"><![CDATA[{$content}]]></summary>
<content type="html"><![CDATA[{$content}]]></content>
</entry>
ENTRY;
    }
}
