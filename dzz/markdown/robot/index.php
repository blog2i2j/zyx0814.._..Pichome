<?php
    require MOD_PATH.'/phpmarkdown/vendor/autoload.php';
    use Michelf\Markdown;
    use Michelf\MarkdownExtra;

    if(!$patharr=Pdecode($_GET['path'])){
        exit('Access Denied');
    }

    $rid = $patharr['path'];

    $perm = $patharr['perm'];

    $data=C::t('pichome_resources')->fetch($rid);
    $content=IO::getFileContent($rid);
    $content = Markdown::defaultTransform($content);
    $content = MarkdownExtra::defaultTransform($content);
   include template('robot/index');
   exit();
//include template('page/main');