<?php

/**
 * Class for Lingo extension implementation https://www.mediawiki.org/wiki/Extension:Lingo
 * @author Dennis Krohn @krohnden <dennis.krohn@th-luebeck.de>
 */
if ( !defined( 'MEDIAWIKI' ) ) {
    die( "This file cannot be run standalone.\n" );
}

use MediaWiki\MediaWikiServices;

class LoopTerminology {

    public static function getShowTerminology() {

        global $wgOut;

        $contentText = LoopTerminology::getTerminologyWikiText();
		$user = $wgOut->getUser();
		$editMode = $user->getOption( 'LoopEditMode', false, true );

		if ( !empty( $contentText ) ) {
			return true;
		} elseif ( $editMode ) {
			return "empty";
		} else {
			return false;
		}

    }

    public static function getSortedTerminology( $input ) {
        $items = array();
        $dom = new DomDocument();
        $dom->loadXml($input);
        $tags = $dom->getElementsByTagName( "dl" );
        foreach ( $tags as $tag ) {
            $childNodes = $tag->childNodes;
            if ( !empty( $childNodes ) ) {
                $currentELementTitle = trim($childNodes[0]->nodeValue);
                foreach ( $childNodes as $child ) {
					if ($child->hasChildNodes()) { # math node
						$tmpVal = str_replace( "\n", "", $child->nodeValue );
						$tmpVal = preg_replace('/\s*(\S*)\s*{.*}/', '$1', $tmpVal);
						$items[ $currentELementTitle ][ $child->nodeName ][] = $tmpVal;

					} else {
						$items[ $currentELementTitle ][ $child->nodeName ][] = $child->nodeValue;
					}
                }
            }
		}
        return $items;
    }

    public static function getTerminologyPageContentText() {

        global $wgParserConf;

        $parser = new Parser( $wgParserConf );
        $tmpTitle = Title::newFromText( 'NO TITLE' );
        $parserOutput = $parser->parse("{{Mediawiki:LoopTerminologyPage}}", $tmpTitle, new ParserOptions() );
        $output = $parserOutput->getText();

        return $output;
    }

    public static function getTerminologyOutput() {

        $contentText = self::getTerminologyPageContentText();
        $items = self::getSortedTerminology( $contentText );

        $html = '';
        if ( !empty( $items ) ) {
            ksort( $items, SORT_FLAG_CASE | SORT_STRING ); # ignore case
            foreach ( $items as $item => $content ) {
                if ( array_key_exists( "dt", $content ) &&  array_key_exists( "dd", $content ) ) {
                    $html .= "<div class='loopterminology-term font-weight-bold'><span>";
                    $i = 0;
                    foreach ( $content["dt"] as $term ) {
                        $html .= ( $i == 0 ? "" : ", " );
                        $html .= $term;
                        $i++;
                    }
                    $html .= "</span></div>\n";
                    $html .= "<div class='loopterminology-definition'>";
                    foreach ( $content["dd"] as $def ) {
                        $html .= "<span>" . $def . "</span><br>\n";
                    }
                    $html .= "</div>\n";
                }
            }
        }
		#dd($html);
        return $html;

    }

    public static function getTerminologyOutputForXML() {

        global $wgParserConf;

        $parser = new Parser( $wgParserConf );
        $tmpTitle = Title::newFromText( 'NO TITLE' );
        $parserOutput = $parser->parse("{{Mediawiki:LoopTerminologyPage}}", $tmpTitle, new ParserOptions() );
        $output = $parserOutput->getText();

        $items = self::getSortedTerminology( $output );

        $html = '';
        if ( !empty( $items ) ) {
            ksort( $items );
            foreach ( $items as $item => $content ) {
                if ( array_key_exists( "dt", $content ) &&  array_key_exists( "dd", $content ) ) {
                    $html .= "<div class='loopterminology-term font-weight-bold'><span>";
                    $i = 0;
                    foreach ( $content["dt"] as $term ) {
                        $html .= ( $i == 0 ? "" : ", " );
                        $html .= $term;
                        $i++;
                    }
                    $html .= "</span></div>\n";
                    $html .= "<div class='loopterminology-definition'>";
                    foreach ( $content["dd"] as $def ) {
                        $html .= "<span>" . $def . "</span><br>\n";
                    }
                    $html .= "</div>\n";
                }
            }
        }

        return $html;

    }

    public static function getTerminologyWikiText() {

        global $wgParserConf;

        $title = Title::newFromText( 'LoopTerminologyPage', NS_MEDIAWIKI );
        $wikiPage = WikiPage::factory( $title );
        $revision = $wikiPage->getRevision();
        $contentWikitext = '';
        if ( $revision ) {
            $contentWikitext = $revision->getContent()->getText();
        }

        return $contentWikitext;

    }

}

class SpecialLoopTerminology extends SpecialPage {

	public function __construct() {
		parent::__construct ( 'LoopTerminology' );
	}

	public function execute( $sub ) {

		$out = $this->getOutput();
		$request = $this->getRequest();
        $user = $this->getUser();
		Loop::handleLoopRequest( $out, $request, $user ); #handle editmode
		$loopEditMode = $this->getSkin()->getUser()->getOption( 'LoopEditMode', false, true );
		$loopRenderMode = $this->getSkin()->getUser()->getOption( 'LoopRenderMode' );
		$this->setHeaders();
		$out->setPageTitle( $this->msg( 'loopterminology' ) );

		$html = self::renderLoopTerminologySpecialPage( $loopEditMode, $loopRenderMode, $user );
        $out->addHtml ( $html );
    }

    public static function renderLoopTerminologySpecialPage( $loopEditMode = false, $loopRenderMode = 'default', $user = null ) {

        $html = '<h1>';
	    $html .= wfMessage( 'loopterminology' )->text();
        if ( $user ) {
    	    if( ! $user->isAnon() && $user->isAllowed( 'loop-toc-edit' ) && $loopRenderMode == 'default' && $loopEditMode ) {

                $linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
                $linkRenderer->setForceArticlePath(true);
    	        # show link to the edit page if user is permitted
                $html .= $linkRenderer->makeLink(
                    Title::newFromText( "LoopTerminologyEdit", NS_SPECIAL ),
                    new HtmlArmor( '<i class="ic ic-edit"></i>' ),
                    array( "class" => "ml-2", "id" => "editpagelink" )
                );
    	    }
        }
        $html .= '</h1>';
        $html .= LoopTerminology::getTerminologyOutput();

        return $html;
    }

    protected function getGroupName() {
		return 'loop';
	}
}

class SpecialLoopTerminologyEdit extends SpecialPage {

	public function __construct() {
		parent::__construct ( 'LoopTerminologyEdit' );
	}

	public function execute( $sub ) {

		global $wgSecretKey;

		$out = $this->getOutput();
		$request = $this->getRequest();
        $user = $this->getUser();
		Loop::handleLoopRequest( $out, $request, $user ); #handle editmode
		$tabindex = 0;

		$this->setHeaders();
		$out->setPageTitle( $this->msg( 'loopterminologyedit' ) );

           # headline output
           $out->addHtml(
            Html::rawElement(
                'h1',
                array(
                    'id' => 'loopterminology-h1'
                ),
                $this->msg( 'loopterminologyedit' )->parse()
            )
        );

        $saltedToken = $user->getEditToken( $wgSecretKey, $request );
        $newterminologyWikitext = $request->getText( 'loopterminology-content' );
		$requestToken = $request->getText( 't' );

		$userIsPermitted = (! $user->isAnon() && $user->isAllowed( 'loop-toc-edit' ));
        $terminologyWikitext = LoopTerminology::getTerminologyWikiText();

		$success = null;
		$error = false;
		$feedbackMessageClass = 'success';

        if( ! empty( $requestToken ) ) {
            if ( empty( $newterminologyWikitext ) ) {
				$error = $this->msg( 'loopterminology-warning-deleted' )->parse();
                $feedbackMessageClass = 'warning';
            }
			if ( $userIsPermitted ) {
				if ( $user->matchEditToken( $requestToken, $wgSecretKey, $request )) {

                    $systemUser = User::newFromName( 'LOOP_SYSTEM' );
                    $systemUser->addGroup("sysop");

                    $title = Title::newFromText( 'LoopTerminologyPage', NS_MEDIAWIKI );
                    $wikiPage = WikiPage::factory( $title );
					$contentHandler = $wikiPage->getContentHandler();

                    $wikiPageContent = $contentHandler->unserializeContent( $newterminologyWikitext );
                    $wikiPageUpdater = $wikiPage->newPageUpdater( $systemUser ); # use system user to ensure editing of mediawiki namespace page is successful
                    $summary = CommentStoreComment::newUnsavedComment( $user->getName() ); #add user name to summary to ensure being able to trace back edits
					$wikiPageUpdater->setContent( "main", $wikiPageContent );
					if ( ! $wikiPage->getRevision() ) {
						$wikiPageUpdater->saveRevision ( $summary, EDIT_NEW );
					} else {
						$wikiPageUpdater->saveRevision ( $summary, EDIT_UPDATE );
					}

                    # save success output
                    $out->addHtml(
                        Html::rawElement(
                            'div',
                            array(
                                'name' => 'loopstructure-content',
                                'class' => 'alert alert-success'
                            ),
                            $this->msg( 'loopterminology-save-success' )->parse()
                        )
                    );
                    $success = true;
                } else {
					$error = $this->msg( 'loop-token-error' )->parse();
                    $feedbackMessageClass = 'danger';
				}
            } else {
				$error = $this->msg( 'loop-permission-error' )->parse();
                $feedbackMessageClass = 'danger';
			}
        }

        # error message output (if exists)
        if( $error !== false ) {
            $out->addHTML(
                Html::rawElement(
                    'div',
                    array(
                        'class' => 'alert alert-'.$feedbackMessageClass,
                        'role' => 'alert'
                    ),
                    $error
                )
            );
        }

        if( $userIsPermitted ) {

        	# user is permitted to edit the toc, print edit form here
			if ( $success ) {
				$displayedWikitext = $newterminologyWikitext;
			} else {
				$displayedWikitext = $terminologyWikitext;
			}
	        $out->addHTML(
	            Html::openElement(
	                'form',
	                array(
	                    'class' => 'mw-editform mt-3 mb-3',
	                    'id' => 'loopterminology-form',
	                    'method' => 'post',
	                    'enctype' => 'multipart/form-data'
	                )
                )
                . Html::rawElement(
	                'p',
                    array(),
                    $this->msg( 'loopterminology-hint' )->parse()
	            ) . Html::rawElement(
	                'textarea',
	                array(
	                    'name' => 'loopterminology-content',
	                    'id' => 'loopterminology-textarea',
	                    'tabindex' => ++$tabindex,
	                    'class' => 'd-block mt-3',
	                ),
	                $displayedWikitext
	            )
	            . Html::rawElement(
	                'input',
	                array(
	                    'type' => 'hidden',
	                    'name' => 't',
	                    'id' => 'loopterminology-token',
	                    'value' => $saltedToken
	                )
	            )
	            . Html::rawElement(
	                'input',
	                array(
	                    'type' => 'submit',
	                    'tabindex' => ++$tabindex,
	                    'class' => 'mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive mt-2',
	                    'id' => 'loopterminology-submit',
	                    'value' => $this->msg( 'submit' )->parse()
	                )
	            ) . Html::closeElement(
	                'form'
	            ) . Html::rawElement(
	                'p',
                    array(),
                    $this->msg( 'loopterminology-example' )->plain()
	            )
	        );

        } else {

        	# user has no permission, just show content without textarea

        	$out->addHtml(
        		Html::rawElement(
        			'div',
        			array(
        				'class' => 'alert alert-dark',
        				'role' => 'alert',
        				'style' => 'white-space: pre;'
        			),
        			$terminologyWikitext
        		)
        	);

        }



        #$out->addHtml ( $html );
    }

    protected function getGroupName() {
		return 'loop';
	}
}
