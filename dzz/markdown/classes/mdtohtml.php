<?php
namespace dzz\markdown\classes;
require_once DZZ_ROOT.'./dzz/markdown/phpmarkdown/vendor/autoload.php';
use \core as C;
use Michelf\Markdown as Markdown;
use Michelf\MarkdownExtra as MarkdownExtra;
class mdtohtml{
    public function run(&$content,$arr=array()){
        $content=Markdown::defaultTransform($content);
        $content=MarkdownExtra::defaultTransform($content);
    }
}