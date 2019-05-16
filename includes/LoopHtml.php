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

        $loopStructureItems = $loopStructure->getStructureItems();

        if(is_array($loopStructureItems)) {

            global $wgOut, $wgDefaultUserOptions, $wgResourceLoaderDebug, $wgUploadDirectory, $wgArticlePath;
            
            $loopSettings = new LoopSettings();
            $loopSettings->loadSettings();

            $exportHtmlDirectory = $wgUploadDirectory.$exportDirectory;
            LoopHtml::getInstance()->startDirectory = $exportHtmlDirectory.'/'.$loopStructure->getId().'/';
            LoopHtml::getInstance()->exportDirectory = $exportHtmlDirectory.'/'.$loopStructure->getId().'/files/';

            //$articlePath = preg_replace('/(\/)/', '\/', $wgArticlePath);
            //LoopHtml::getInstance()->articlePathRegEx = preg_replace('/(\$1)/', '', $articlePath);

            # prepare global config
            $editModeBefore = $wgOut->getUser()->getOption( 'LoopEditMode', $wgDefaultUserOptions['LoopEditMode'], true );
            $renderModeBefore = $wgOut->getUser()->getOption( 'LoopRenderMode', $wgDefaultUserOptions['LoopRenderMode'], true );
            $debugModeBefore = $wgResourceLoaderDebug;
            $wgOut->getUser()->setOption( 'LoopRenderMode', 'offline' );
            $wgOut->getUser()->setOption( 'LoopEditMode', false );
            $wgResourceLoaderDebug = true;

            $exportSkin = clone $context->getSkin();

            # Create start file
            $mainPage = $context->getTitle()->newMainPage(); # Content of Mediawiki:Mainpage. Might not exist and cause error

            $wikiPage = WikiPage::factory( $mainPage );
            $revision = $wikiPage->getRevision();
            if ( $revision != null ) {
                LoopHtml::writeArticleToFile( $mainPage, "files/", $exportSkin );
            } else {
                $mainPage = $loopStructure->mainPage; 
                LoopHtml::writeArticleToFile( $mainPage, "files/", $exportSkin );
            }

            # Create toc file
            $tocPage = Title::newFromText( 'Special:LoopStructure' );
            LoopHtml::writeSpecialPageToFile( $tocPage, "", $exportSkin );

            foreach($loopStructureItems as $loopStructureItem) {

                $articleId = $loopStructureItem->getArticle();

                if( isset( $articleId ) && is_numeric( $articleId )) {

                    $title = Title::newFromID( $articleId );
                    $html = LoopHtml::writeArticleToFile( $title, "", $exportSkin );
                   
                }

            }

            if ( filter_var( htmlspecialchars_decode( $loopSettings->imprintLink ), FILTER_VALIDATE_URL ) == false ) {
                $imprintTitle = Title::newFromText( $loopSettings->imprintLink );
                if ( ! empty ( $imprintTitle->mTextform ) ) {
                    $wikiPage = WikiPage::factory( $imprintTitle );
                    $revision = $wikiPage->getRevision();
                    if ( $revision != null ) {
                        LoopHtml::writeArticleToFile( $imprintTitle, "", $exportSkin );
                    }
                }
            }
            if ( filter_var( htmlspecialchars_decode( $loopSettings->privacyLink ), FILTER_VALIDATE_URL ) == false ) {
                $privacyTitle = Title::newFromText( $loopSettings->privacyLink );
                if ( ! empty ( $privacyTitle->mTextform ) ) {
                    $wikiPage = WikiPage::factory( $privacyTitle );
                    $revision = $wikiPage->getRevision();
                    if ( $revision != null ) {
                        LoopHtml::writeArticleToFile( $privacyTitle, "", $exportSkin );
                    }
                }
            }

            //dd($html);
            $tmpZipPath = $exportHtmlDirectory.'/tmpfile.zip';
            $tmpDirectoryToZip = $exportHtmlDirectory.'/'.$loopStructure->getId();

            $zip = new ZipArchive();
            $zip->open( $tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $tmpDirectoryToZip ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ( $files as $name => $file ) {
                if ( ! $file->isDir() ) {
                    $tmpFilePath = $file->getRealPath();
                    $tmpRelativePath = substr($tmpFilePath, strlen($tmpDirectoryToZip) + 1);
                    $zip->addFile( $tmpFilePath, $tmpRelativePath );
                    $filesToDelete[] = $tmpFilePath;
                }
            }
            $zip->close();
            $zip = file_get_contents( $tmpZipPath );

            foreach ($filesToDelete as $file) {
                unlink($file);
            }

            unlink( $tmpZipPath );

            
            # reset global config
            $wgOut->getUser()->setOption( 'LoopRenderMode', $renderModeBefore );
            $wgOut->getUser()->setOption( 'LoopEditMode', $editModeBefore );
            $wgResourceLoaderDebug = $debugModeBefore;

            return $zip;

        } else {
            return false;
        }

    }
     /**
     * Write Special Page to file, with all given resources
     * @param Title $specialPage
     * @param string $prependHref for start file 
     * @param $exportSkin
     * 
     * @Return string html
     */   
    private static function writeSpecialPageToFile( $specialPage, $prependHref, $exportSkin ) {

        $loopStructure = new LoopStructure;
        $loopStructure->loadStructureItems();

        $text = $loopStructure->render();
        $tmpFileName = LoopHtml::getInstance()->resolveUrl( $specialPage->mTextform, '.html');
        $htmlFileName = LoopHtml::getInstance()->exportDirectory.$tmpFileName;
    
        $exportSkin->getContext()->setTitle( $specialPage );
        $exportSkin->getContext()->getOutput()->mBodytext = $text;

        # get html with skin object
        ob_start();
        $exportSkin->outputPage();
        $html = ob_get_contents();
        ob_end_clean();

        $html = LoopHtml::getInstance()->replaceResourceLoader($html, $prependHref);
        $html = LoopHtml::getInstance()->replaceManualLinks($html, $prependHref);
        $html = LoopHtml::getInstance()->replaceContentHrefs($html, $prependHref);
        file_put_contents($htmlFileName, $html);

        return $html;

    }

     /**
     * Write article from structure to file, with all given resources
     * @param Title $title
     * @param string $prependHref for start file 
     * @param $exportSkin
     * 
     * @Return string html
     */   
    private static function writeArticleToFile( $title, $prependHref, $exportSkin ) {

        $wikiPage = WikiPage::factory( $title );
        $revision = $wikiPage->getRevision();
        $content = $revision->getContent( Revision::RAW );
    
        $localParser = new Parser();
        $text = $localParser->parse(ContentHandler::getContentText( $content ), $title, new ParserOptions())->mText;
        
        # regular articles are in ZIP/files/ folder, start article in ZIP/
        if ( $prependHref == "" ) {
            $tmpFileName = LoopHtml::getInstance()->resolveUrl($title->mUrlform, '.html');
            $htmlFileName = LoopHtml::getInstance()->exportDirectory.$tmpFileName;
        } else {
            $htmlFileName = LoopHtml::getInstance()->startDirectory.$title->mUrlform.'.html'; # TODO name start file
        } 

        # prepare skin
        $exportSkin->getContext()->setTitle( $title );
        $exportSkin->getContext()->setWikiPage( $wikiPage );
        $exportSkin->getContext()->getOutput()->mBodytext = $text;

        # get html with skin object
        ob_start();
        $exportSkin->outputPage();
        $html = ob_get_contents();
        ob_end_clean();

        $html = LoopHtml::getInstance()->replaceResourceLoader($html, $prependHref);
        $html = LoopHtml::getInstance()->replaceManualLinks($html, $prependHref);
        $html = LoopHtml::getInstance()->replaceContentHrefs($html, $prependHref);
        file_put_contents($htmlFileName, $html);
        
        return $html;
        
    }

     /**
     * Replaces resources provided by resource loader
     * @param string $html
     * @param string $prependHref for start file 
     * 
     * @Return string html
     */   
    private function replaceResourceLoader($html, $prependHref = "") {

        global $wgServer, $wgDefaultUserOptions, $wgResourceModules;

        $requestUrls = array();

        libxml_use_internal_errors(true);
        
        # suppress error message in console for mw.loader not working
        $html = preg_replace('/mw.loader.load\(RLPAGEMODULES\);/', '/*mw.loader.load\(RLPAGEMODULES\);*/', $html);

        $doc = new DOMDocument();
        $doc->loadHtml($html);
        
        if ( !file_exists( $this->exportDirectory ) ) {
            mkdir( $this->exportDirectory, 0775, true );
        }

        $linkElements = $doc->getElementsByTagName('link');
        if( $linkElements ) {
            foreach($linkElements as $link) {
                $tmpHref = $link->getAttribute( 'href' );
                if(strpos($tmpHref, 'load.php') !== false) {
                    $requestUrls[] = $wgServer.$tmpHref;
                    $link->setAttribute('href', $prependHref."resources/styles/".md5($wgServer.$tmpHref).'.css');
                }
            }
        }

        # request contents for all matched <link> urls
        $requestUrls = $this->requestContent( $requestUrls );
        foreach ( $requestUrls as $url => $content ) {
            # Undoing MW's absolute paths in CSS files
            $content = preg_replace('/(\/mediawiki\/skins\/Loop\/resources\/)/', '../', $content);
            $fileName = $this->resolveUrl( $url, '.css' );
            $this->writeFile( "resources/styles/", $fileName, $content );
        }

        # reset container for <script> hrefs
        $requestUrls = array();

        $scriptElements = $doc->getElementsByTagName('script');
        if($scriptElements) {
            foreach($scriptElements as $script) {
                $tmpScript = $script->getAttribute( 'src' );
                if(strpos($tmpScript, 'load.php') !== false) {
                    $requestUrls[] = $wgServer.$tmpScript;
                    $script->setAttribute('src', $prependHref."resources/js/".md5($wgServer.$tmpScript).'.js');
                }
            }
        }

        # request contents for all matched <link> urls
        $requestUrls = $this->requestContent($requestUrls);
        foreach($requestUrls as $url => $content) {
            $fileName = $this->resolveUrl($url, '.js');
            $this->writeFile( "resources/js/", $fileName, $content );
        }

        $skinPath = $wgServer . "/mediawiki/skins/";
        $extPath = $wgServer . "/mediawiki/extensions/";

        # Files that are called from our resources (e.g. in some css or js file) need to be added manually
        # - will be extended by skin files and resource modules
        # Mediawiki:Common.css is already included
        $resources = array(
            "jquery.js" => array(
                "srcpath" => $wgServer . "/mediawiki/resources/lib/jquery/jquery.js",
                "targetpath" => "resources/js/",
                "link" => "script"
            ),
            "shared.css" => array(
                "srcpath" => $wgServer . "/mediawiki/resources/src/mediawiki.legacy/shared.css",
                "targetpath" => "resources/styles/",
                "link" => "style"
            ),
            "loopfont.eot" => array(
                "srcpath" => $skinPath."Loop/resources/loopicons/fonts/loopfont.eot",
                "targetpath" => "resources/loopicons/fonts/",
            ),
            "loopfont.svg" => array(
                "srcpath" => $skinPath."Loop/resources/loopicons/fonts/loopfont.svg",
                "targetpath" => "resources/loopicons/fonts/",
            ),
            "loopfont.ttf" => array(
                "srcpath" => $skinPath."Loop/resources/loopicons/fonts/loopfont.ttf",
                "targetpath" => "resources/loopicons/fonts/",
            ),
            "loopfont.woff" => array(
                "srcpath" => $skinPath."Loop/resources/loopicons/fonts/loopfont.woff",
                "targetpath" => "resources/loopicons/fonts/",
            ),
            "tree.png" => array(
                "srcpath" => $skinPath."Loop/resources/img/tree.png",
                "targetpath" => "resources/img/",
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
        
        $resourceModules = $wgResourceModules;
        
        $requiredModules = array("skin" => array(), "ext" => array() );
        # lines encaptured by ", start with skin.loop or ext.loop and end with .js 
        # js modules are missing, so we fetch those.
        preg_match_all('/"(([skins]{5}\.loop.*\S*\.js))"/', $html, $requiredModules["skin"]);
        preg_match_all('/"(([ext]{3}\.loop.*\S*\.js))"/', $html, $requiredModules["ext"]);

        # adds modules that have been declared for resourceloader on $doc to our $resources array.
        foreach ( $requiredModules as $type => $res ) { // skin or ext?

            foreach ( $res[1] as $module => $modulename ) { 
            
                if ( isset($resourceModules[$modulename]["scripts"]) ) { // does our requested module have scripts?

                    foreach( $resourceModules[$modulename]["scripts"] as $pos => $scriptpath ) { // include all scripts
                        if ( $type == "skin" ){
                            $sourcePath = $skinPath . $resourceModules[$modulename]["remoteSkinPath"]."/";
                        } else {
                            $sourcePath = $extPath . $resourceModules[$modulename]["remoteExtPath"]."/";
                        }

                        $resources[$modulename] = array(
                            "srcpath" => $sourcePath . $scriptpath,
                            "targetpath" => "resources/js/",
                            "link" => "script"
                        );
                    }
                }
            }
        }

        $headElements = $doc->getElementsByTagName('head');

        # request contents for all entries in $resources array,
        # writes file in it's targetpath and links it on output page.
        foreach( $resources as $file => $data ) {
            $tmpContent[$file]["content"] =  file_get_contents( $data["srcpath"] );
            
            $this->writeFile( $data["targetpath"], $file, $tmpContent[$file]["content"] );
            
            if ( isset ( $data["link"] ) )  { # add file to output page if requested
                if ($data["link"] == "style") {
                    $tmpNode = $doc->createElement("link");
                    $tmpNode->setAttribute('href', $prependHref.$data["targetpath"] . $file );
                    $tmpNode->setAttribute('rel', "stylesheet" );
                } else if ( $data["link"] == "script" ) {
                    $tmpNode = $doc->createElement("script");
                    $tmpNode->setAttribute('src', $prependHref.$data["targetpath"] . $file );
                }
                foreach( $headElements as $headElement) {
                    $headElement->appendChild( $tmpNode );
                }
            }

        }

        $html = $doc->saveHtml();
        libxml_clear_errors();

        return $html;
    }

     /**
     * Replaces internal link href by class "internal-link" and template links.
     * @param string $html
     * @param string $prependHref for start file 
     * 
     * @Return string html
     */   
    private function replaceManualLinks( $html, $prependHref = "" ) {
        
        global $wgServer;
        $doc = new DOMDocument();
        $doc->loadHtml($html);
        
        $loopSettings = new LoopSettings();
        $loopSettings->loadSettings();

        $body = $doc->getElementsByTagName('body');

        if ( !empty( $prependHref ) ) { # ONLY for start file - add folder to path
            $internalLinks = $this->getElementsByClass( $body[0], "a", "local-link" );
            
            if ( $internalLinks ) {
                foreach ( $internalLinks as $element ) {
                    $tmpHref = $element->getAttribute( 'href' );
                    if ( isset ( $tmpHref ) && $tmpHref != '#' ) {
                        $element->setAttribute( 'href', $prependHref.$tmpHref );
                    }
                }
            }
        }

        # links to non-existing internal pages lose their href and look like normal text 
        # TODO make hook
        
        $newLinks = $this->getElementsByClass( $body[0], "a", "new" );
        if ( $newLinks ) {
            foreach ( $newLinks as $element ) {
                $element->removeAttribute( 'href' );
                $element->removeAttribute( 'title' );
            }
        }

        # apply custom logo, if given
        if ( !empty ( $loopSettings->customLogo ) ) {
            $loopLogo = $doc->getElementById('logo');
            $logoUrl = $loopSettings->customLogoFilePath;
            $logoFile = $this->requestContent( array($logoUrl) );
            
            preg_match('/(.*)(\.{1})(.*)/', $loopSettings->customLogoFileName, $fileData);
            $fileName = $this->resolveUrl($fileData[1], '.'.$fileData[3]); 

            $this->writeFile( "resources/images/", $fileName, $logoFile[$logoUrl] );
            $loopLogo->setAttribute( 'style', 'background-image: url("'.$prependHref.'resources/images/'. $fileName.'");' );
        }        

        $html = $doc->saveHtml();
        
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

     /**
     * Creates md5 filename for load.php files
     * @param string $url Node which to look inside
     * @param string $suffix file suffix
     * 
     * @Return string
     */   
    public function resolveUrl($url, $suffix) {
        return md5($url).$suffix;
    }

     /**
     * Looks for nodes with specific class name.
     * @param $parentNode Node which to look inside
     * @param string $tagName tag to look for
     * @param string $className class to look for
     * 
     * @Return Array $nodes
     */   
    private function getElementsByClass( &$parentNode, $tagName, $className ) {

        $nodes = array();
    
        $childNodeList = $parentNode->getElementsByTagName( $tagName );
        for ( $i = 0; $i < $childNodeList->length; $i++ ) {
            $temp = $childNodeList->item( $i );
            if ( stripos( $temp->getAttribute( 'class' ), $className ) !== false ) {
                $nodes[] = $temp;
            }
        }
    
        return $nodes;
    }

     /**
     * Writes file with given data
     * @param string $pathAddendum changes destination 
     * @param string $fileName 
     * @param string $content file content
     * 
     * @Return true
     */   
    private function writeFile( $pathAddendum, $fileName, $content ) {
        
        if ( ! file_exists( $this->exportDirectory.$pathAddendum ) ) { # folder creation
            mkdir( $this->exportDirectory.$pathAddendum, 0775, true );
        }
        if ( ! file_exists( $this->exportDirectory.$pathAddendum.$fileName ) ) {
            file_put_contents($this->exportDirectory.$pathAddendum.$fileName, $content);
        }
        return true;
    }

     /**
     * Replaces href and src from files and other content
     * @param string $html
     * @param string $prependHref for start file 
     * 
     * @Return string $html
     */   

    private function replaceContentHrefs( $html, $prependHref = "" ) {
        global $wgServer;

        $doc = new DOMDocument();
        $doc->loadHtml($html);
        
        # todo: download more media (videos, pdf, zip)
        # replace breadcrumb links
        $body = $doc->getElementsByTagName('body');

        $imageElements = $this->getElementsByClass( $body[0], "img", "responsive-image" );
        $imageUrls = array();
        if ( $imageElements ) {
            foreach ( $imageElements as $element ) {

                $tmpSrc = $element->getAttribute( 'src' );
                $imageData["content"][] = $wgServer . $tmpSrc;
                preg_match('/(.*\/)(.*)(\.{1})(.*)/', $tmpSrc, $tmpTitle);
                $imageData["name"][$wgServer . $tmpSrc] = $tmpTitle[2];
                $imageData["suffix"][$wgServer . $tmpSrc] = $tmpTitle[4];
                $newSrc = $prependHref."resources/images/" . $this->resolveUrl($tmpTitle[2], '.'.$tmpTitle[4] );
                $element->setAttribute( 'src', $newSrc );
            }
            if ( $imageData["name"] && $imageData["suffix"] ) {
                $imageData["content"] = $this->requestContent($imageData["content"]);
                foreach ( $imageData["name"] as $image => $data ) {
                    $fileName = $this->resolveUrl($imageData["name"][$image], '.'.$imageData["suffix"][$image] );
                    $content = $imageData["content"][$image];
                    $this->writeFile( 'resources/images/', $fileName, $content );
                }
            }
        }

        $html = $doc->saveHtml();
        return $html;

    }
}
