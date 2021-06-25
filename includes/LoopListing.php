<?php
# TODO test
/**
 * @description A parser extension that adds the tag <loop_listing> to mark content as listing and provide a table of listings
 * @ingroup Extensions
 * @author Marc Vorreiter @vorreiter <marc.vorreiter@th-luebeck.de>
 * @author Dennis Krohn @krohnden <dennis.krohn@th-luebeck.de>
 */
if ( !defined( 'MEDIAWIKI' ) ) die ( "This file cannot be run standalone.\n" );

class LoopListing extends LoopObject{

	public static $mTag = 'loop_listing';
	public static $mIcon = 'we-list';

	/**
	 * {@inheritDoc}
	 * @see LoopObject::getShowNumber()
	 */
	public function getShowNumber() {
		global $wgLoopObjectNumbering;
		return $wgLoopObjectNumbering;
	}

	/**
	 * {@inheritDoc}
	 * @see LoopObject::getDefaultRenderOption()
	 */
	public function getDefaultRenderOption() {
		global $wgLoopObjectDefaultRenderOption;
		return $wgLoopObjectDefaultRenderOption;
	}

	/**
	 *
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param Frame $frame
	 * @return string
	 */
	public static function renderLoopListing($input, array $args, $parser, $frame) {

		$listing = new LoopListing();
		$listing->init($input, $args, $parser, $frame);
		$listing->parse();
		$html = $listing->render();

		return  $html ;
	}

}

/**
 * Display list of listings for current structure
 *
 * @author vorreitm, krohnden
 *
 */
class SpecialLoopListings extends SpecialPage {

	public function __construct() {
		parent::__construct ( 'LoopListings' );
	}

	public function execute($sub) {

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();
		Loop::handleLoopRequest( $out, $request, $user ); #handle editmode

		$out->setPageTitle ( $this->msg ( 'looplistings-specialpage-title' ) );
		$html = self::renderLoopListingSpecialPage();
		$out->addHtml ( $html );


	}

	public static function renderLoopListingSpecialPage() {
	    global $wgParserConf, $wgLoopNumberingType;
	    $html = '<h1>';
	    $html .= wfMessage( 'looplistings-specialpage-title' );
	    $html .= '</h1>';

	    $loopStructure = new LoopStructure();
	    $loopStructure->loadStructureItems();

	    $parser = new Parser ( $wgParserConf );
	    #$parserOptions = new ParserOptions();
	    $parser->getOptions (); # TODO test

	    $listings = array ();
	    $structureItems = $loopStructure->getStructureItems();
	    $glossaryItems = LoopGlossary::getGlossaryPages();
	    $listing_number = 1;
	    $articleIds = array();
	    $html .= '<table class="table table-hover list_of_objects">';
	    $listing_tags = LoopObjectIndex::getObjectsOfType ( 'loop_listing' );

	    foreach ( $structureItems as $structureItem ) {
	        $articleIds[ $structureItem->article ] = NS_MAIN;
	    }
	    foreach ( $glossaryItems as $glossaryItem ) {
	        $articleIds[ $glossaryItem->mArticleID ] = NS_GLOSSARY;
	    }

	    foreach ( $articleIds as $article => $ns ) {

	        $article_id = $article;

	        if ( isset( $listing_tags[$article_id] ) ) {
	            foreach ( $listing_tags[$article_id] as $listing_tag ) {
	                $listing = new LoopListing();
	                $listing->init($listing_tag["thumb"], $listing_tag["args"]);

	                $listing->parse();
	                if ( $wgLoopNumberingType == "chapter" ) {
	                    $listing->setNumber ( $listing_tag["nthoftype"] );
	                } elseif ( $wgLoopNumberingType == "ongoing" ) {
	                    $listing->setNumber ( $listing_number );
	                    $listing_number ++;
	                }
	                $listing->setArticleId ( $article_id );

	                $html .= $listing->renderForSpecialpage ( $ns );
	            }
	        }
	    }
	    $html .= '</table>';
	    return $html;
	}

	protected function getGroupName() {
		return 'loop';
	}
}

