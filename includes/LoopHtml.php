<?php

class LoopHtml{

    protected static $_instance = null;

    public static function getInstance() {

       if (null === self::$_instance) {
           self::$_instance = new self;
       }

       return self::$_instance;

   }

   private $requestedUrls = array();
   private $exportDirectory;

    public static function structure2html(LoopStructure $loopStructure, RequestContext $context, $exportDirectory) {
        set_time_limit(0);
//dd("todo dauert zu lange!");

        $loopStructureItems = $loopStructure->getStructureItems();

        if(is_array($loopStructureItems)) {

            global $wgOut, $wgDefaultUserOptions, $wgResourceLoaderDebug, $wgUploadDirectory, $wgScriptPath;

            LoopHtml::getInstance()->exportDirectory = $wgUploadDirectory.$exportDirectory.'/'.$loopStructure->getId().'/';

            # prepare global config
            $renderModeBefore = $wgOut->getUser()->getOption( 'LoopRenderMode', $wgDefaultUserOptions['LoopRenderMode'], true );
            $debugModeBefore = $wgResourceLoaderDebug;
            $wgOut->getUser()->setOption( 'LoopRenderMode', 'offline' );
            $wgResourceLoaderDebug = true;
            $wgStyleDirectory=$wgScriptPath."/skins/Loop/";

            # Todo $this->copyResources();

            $exportSkin = clone $context->getSkin();

            foreach($loopStructureItems as $loopStructureItem) {

                $articleId = $loopStructureItem->getArticle();

                if( isset( $articleId ) && is_numeric( $articleId )) {

                    $title = Title::newFromID( $articleId );
                    $wikiPage = WikiPage::factory( $title );
                    $revision = $wikiPage->getRevision();
                    $content = $revision->getContent( Revision::RAW );
                    $text = ContentHandler::getContentText( $content );
                    $htmlFileName = LoopHtml::getInstance()->exportDirectory.$title->mTextform.'.html';


                    # prepare skin
                    $exportSkin->getContext()->setTitle( $title );
                    $exportSkin->getContext()->setWikiPage( $wikiPage );
                    $exportSkin->getContext()->getOutput()->mBodytext = $text;

                    # get html with skin object
                    ob_start();
                    $exportSkin->outputPage();
                    $html = ob_get_contents();
                    ob_end_clean();

                    $html = LoopHtml::getInstance()->replaceLoadPhp($html);
                    file_put_contents($htmlFileName, $html);
dd($html);
                }

            }

        }

    }

    private function replaceLoadPhp($html) {

        global $wgServer, $wgDefaultUserOptions;

        $requestUrls = array();

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHtml($html);
        
        if ( !file_exists( $this->exportDirectory ) ) {
            mkdir( $this->exportDirectory, 0775, true );
        }

        $linkElements = $doc->getElementsByTagName('link');
        if($linkElements) {
            foreach($linkElements as $link) {
                $tmpHref = $link->getAttribute( 'href' );
                if(strpos($tmpHref, 'load.php') !== false) {
                    $requestUrls[] = $wgServer.$tmpHref;
                    $link->setAttribute('href', "resources/styles/".md5($wgServer.$tmpHref).'.css');
                }
            }
        }

        # request contents for all matched <link> urls
        $requestUrls = $this->requestContent( $requestUrls );
        foreach ( $requestUrls as $url => $content ) {
            //$contentR = preg_replace('/(\/mediawiki\/skins\/Loop\/resources\/styles\/)/', '', $content);
            //$contentR = preg_replace('/(\/mediawiki\/skins\/Loop\/resources\/js\/)/', '', $contentR);
            $contentR = preg_replace('/(\/mediawiki\/skins\/Loop\/resources\/)/', '../', $content);
            $fileName = $this->resolveUrl( $url, '.css' );
                if ( !file_exists( $this->exportDirectory."resources/styles/".$fileName ) ) {
                file_put_contents( $this->exportDirectory."resources/styles/".$fileName, $contentR );
            }
        }

        # reset container for <script> hrefs
        $requestUrls = array();

        $scriptElements = $doc->getElementsByTagName('script');
        if($scriptElements) {
            foreach($scriptElements as $script) {
                $tmpScript = $script->getAttribute( 'src' );
                if(strpos($tmpScript, 'load.php') !== false) {
                    $requestUrls[] = $wgServer.$tmpScript;
                    //echo ($wgServer.$tmpScript);
                    $script->setAttribute('src', "resources/js/".md5($wgServer.$tmpScript).'.js');
                }
            }
      
        }
        # request contents for all matched <link> urls
        $requestUrls = $this->requestContent($requestUrls);
        foreach($requestUrls as $url => $content) {
            $fileName = $this->resolveUrl($url, '.js');
            //echo ($url);

            if(!file_exists($this->exportDirectory.$fileName)) {
                file_put_contents($this->exportDirectory."resources/js/".$fileName, $content);
            }
        }

       
        $skinPath = $wgServer . "/mediawiki/skins/Loop/";
        $resourcePath = $skinPath . "resources/";
        $resources = array(
                "jquery.js" => array(
                    "srcpath" => $wgServer . "/mediawiki/resources/lib/jquery/jquery.js",
                    "targetpath" => "resources/js/",
                    "link" => "script"
                ),
                "loopfont.eot" => array(
                    "srcpath" => $resourcePath."loopicons/fonts/loopfont.eot",
                    "targetpath" => "resources/loopicons/fonts/",
                ),
                "loopfont.svg" => array(
                    "srcpath" => $resourcePath."loopicons/fonts/loopfont.svg",
                    "targetpath" => "resources/loopicons/fonts/",
                ),
                "loopfont.ttf" => array(
                    "srcpath" => $resourcePath."loopicons/fonts/loopfont.ttf",
                    "targetpath" => "resources/loopicons/fonts/",
                ),
                "loopfont.woff" => array(
                    "srcpath" => $resourcePath."loopicons/fonts/loopfont.woff",
                    "targetpath" => "resources/loopicons/fonts/",
                ),
                "32px.png" => array(
                    "srcpath" => $resourcePath."js/jstree/dist/themes/default/32px.png",
                    "targetpath" => "resources/js/jstree/dist/themes/default/",
                ),
                "throbber.gif" => array(
                    "srcpath" => $resourcePath."js/jstree/dist/themes/default/throbber.gif",
                    "targetpath" => "resources/js/jstree/dist/themes/default/",
                )
        );

        $skinStyle = $wgDefaultUserOptions["LoopSkinStyle"];
        $skinFolder = "resources/styles/$skinStyle/img/";
        $skinFiles = scandir("skins/Loop/$skinFolder");
        $skinFiles = array_slice($skinFiles, 2);



        foreach( $skinFiles as $file => $data ) {
                $resources[$data] = array(
                    "srcpath" => "skins/Loop/$skinFolder$data",
                    "targetpath" => $skinFolder
                );
        
        }
        # load resourcemodules from skin and extension json
        $skinJson = json_decode(file_get_contents("$wgServer/mediawiki/skins/Loop/skin.json"), 1);
        $extJson = json_decode(file_get_contents("$wgServer/mediawiki/extensions/Loop/extension.json"), 1);
        $resourceModules = array_merge($skinJson["ResourceModules"], $extJson["ResourceModules"]);
        
        $tmpHtml = $doc->saveHtml();
        $requiredModules = array();
        # lines encaptured by ", start with skin.loop or ext.loop and end with .js 
        # js modules are missing, so we fetch those.
        preg_match_all('/"(([skinext]{3,5}\.loop.*\S*\.js))"/', $tmpHtml, $requiredModules);

        
        foreach ( $requiredModules[1] as $module => $modulename ) {
            
            if ( isset($resourceModules[$modulename]["scripts"]) ) {

                foreach( $resourceModules[$modulename]["scripts"] as $pos => $scriptpath) {
                    $targetPath = "";
                    $resources[$modulename] = array(
                        "srcpath" => $skinPath . $scriptpath,
                        "targetpath" => "resources/js/",
                        "link" => "script"
                    );
                }

            }
        }
        //dd($resources);
       // $this->requestResourceContent( $resources );

        $headElements = $doc->getElementsByTagName('head');

        foreach( $resources as $file => $data ) {
            //if ( file_exists( $data["srcpath"] ) ) {
                $tmpContent[$file]["content"] =  file_get_contents( $data["srcpath"] );
            //}
            if ( ! file_exists( $this->exportDirectory.$data["targetpath"] ) ) {
                mkdir( $this->exportDirectory.$data["targetpath"], 0775, true );
            }
            if ( ! file_exists( $this->exportDirectory.$data["targetpath"] . $file ) ) {
                file_put_contents( $this->exportDirectory.$data["targetpath"] . $file, $tmpContent[$file]["content"] );
            }
            if ( isset ( $data["link"] ) )  {
                if ($data["link"] == "style") {
                    $tmpNode = $doc->createElement("link");
                    $tmpNode->setAttribute('href', $data["targetpath"] . $file );
                    $tmpNode->setAttribute('rel', "stylesheet" );
                } else if ( $data["link"] == "script" ) {
                    $tmpNode = $doc->createElement("script");
                    $tmpNode->setAttribute('src', $data["targetpath"] . $file );
                }
                foreach( $headElements as $head) {
                    $head->appendChild( $tmpNode );
                }
            }

            
        }

        /*
       // $fontElements = $resources["loopicons"];
        foreach( $resources as $file => $path ) {
            $array[] = array( $resourcePath . $path . key($path) );
            $fontElements = $this->requestContent( $array );
        }
        //dd($requestUrls);
            dd($fontElements);
        foreach( $fontElements as $url => $content ) {
            //$fileName = $fontElements;
            
            if(!file_exists($this->exportDirectory."resources/".$fileName)) {
                file_put_contents($this->exportDirectory."resources/".$fileName, $content);
            }
            dd($fontElements);
        }
*/
        

        $html = $doc->saveHtml();
        libxml_clear_errors();

        return $html;
    }

    /**
     * Requests urls and returns an array.
     * @Return Array ($url => $content)
     */
    private function requestContent (Array $urls) : Array {
        $tmpContent = array();

        foreach($urls as $url) {

            if( ! in_array( $url, $this->requestedUrls ) ) {
                $content = file_get_contents( $url );
                $this->requestedUrls[ $url ] = $content;
            }

            $tmpContent[ $url ] = $this->requestedUrls[ $url ];

        }

        return $tmpContent;

    }

    private function resolveUrl($url, $suffix) {
        global $wgServer;
        return md5($url).$suffix;
    }


}
