<?php


namespace light\swagger;

use yii\web\AssetBundle;
use yii\web\View;

class RRKDUIAsset extends AssetBundle
{
    public $sourcePath = '@bower/swagger-ui/dist';

     public $js = [
        'lib/jquery-1.8.0.min.js',
        'lib/jquery.slideto.min.js',
        'lib/jsoneditor.min.js',
        'lib/jquery.ba-bbq.min.js',
        'lib/marked.js',
        'lib/jquery.wiggle.min.js',
        
      
    ];

    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];

    public $css = [
        'css/typography.css',
        'css/reset.css',
        'css/screen.css',
    ];
    
}
