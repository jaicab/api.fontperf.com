<?php

use \Curl\Curl;
use Sabberworm\CSS\Parser as CSSParser;

/**
 * GFont - Google Fonts service handler
 *
 * This class handles requests to Google Fonts and transforms
 * the response into useful setups or font downloads
 * @author Jaime Caballero <me@jaicab.com>
 * @version 1.0
 */
class GFont {

  const BASEURL = 'https://fonts.googleapis.com/css';
  const SUPPORTED_FORMATS = ['woff2', 'woff', 'ttf', 'eot'];
  const USER_AGENTS = [
    "woff2" => "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1",
    "woff" => "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko",
    "ttf" => "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.54.16 (KHTML, like Gecko) Version/5.1.4 Safari/534.54.16",
    "eot" => "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)",
    "svg" => "Mozilla/4.0 (iPad; CPU OS 4_0_1 like Mac OS X) AppleWebKit/534.46 (KHTML, like Gecko) Version/4.1 Mobile/9A405 Safari/7534.48.3"
  ];

  private $family; // Family query string
  private $css; // Combined fetched CSS
  private $font_list; // Font object array
  private $family_breakdown; // Font variations

  public $error = false;
  public $errorMessage = null;
  public $errorStatus = null;

  public $count_requested = 0;
  public $count = 0;



  /**
   * Constructor sets the initial data and fetches the CSS
   *
   * @access public
   * @param  string $family Value of the query string with the same name provided by Google Fonts
   */
  function __construct($family, $critical_subset = null) {
    $this->family = urlencode($family);
    $this->font_list = [];
    $this->critical_subset = $critical_subset;

    $this->fetchCSS();
  }


  /**
   * Sets an error to be detected on instance level
   *
   * @access private
   * @param $message Message to be shown on error
   * @param $status Status code to return if any
   */
  private function setError($message, $status = null) {
    $this->error = true;
    $this->errorMessage = $message;

    if(!is_null($status)) $this->errorStatus = $status;
  }


  /**
   * Lists the font files, styles and families
   *
   * @access public
   * @return object Font list breakdown
   */
  public function getList() {
    return $this->font_list;
  }


  /**
   * Combined CSS for all the different formats
   *
   * @access public
   * @return string Contents of all the requested CSS in a string
   */
  public function getCss() {
    return $this->css;
  }


  /**
   * Parses class contents as string (CSS form)
   *
   * @access public
   * @return string Contents of all the requested CSS in a string
   */
  public function __toString() {
    return $this->buildCSS();
  }


  /**
   * Gives MIME type for a format given
   *
   * @access public
   * @param string $format File extension
   * @return string MIME type
   */
  public function getMime($format) {
    switch($format) {
      case 'woff':
        $mime = 'application/font-woff';
        break;
      case 'eot':
        $mime = 'application/vnd.ms-fontobject';
        break;
      case 'ttf':
        $mime = 'application/font-sfnt';
        break;
      case 'svg':
        $mime = 'font/svg+xml';
        break;
      default:
        $mime = 'font/'.$format;
    }
    return $mime;
  }



  /**
   * Builds a filename for a Google-Fonts-like font string
   *
   * @access public
   * @param string $id Google-Fonts-like string (e.g. Open+Sans:400italic)
   */
  private function nameFont($id) {
    $ret = $id;
    // Change : into -
    $ret = str_replace(':', '-', $ret);
    // Remove +
    $ret = str_replace('+', '', $ret);

    return $ret;
  }



  /**
   * Gives value for format() on @font-face syntax
   *
   * @access public
   * @param string $format File extension
   * @return string Appropiate value for format()
   */
  public function fontfaceFormat($format) {
    $ret = $format;

    switch($format) {
      case 'ttf':
        $ret = "truetype";
        break;
      case 'eot':
        $ret = "embedded-opentype";
        break;
      default:
        $ret = $format;
    }

    return '"'. $ret . '"';
  }



  /**
   * Encodes file on base64 and creates dataURI
   *
   * @access public
   * @param string $file File path
   * @param string $format File extension
   * @return string DataURI for the file given
   */
  public function dataURI($file, $format) {
    $contents=file_get_contents($file);
    $base64=base64_encode($contents);
    return "data:".$this->getMime($format).";base64,$base64";
  }



  /**
   * Breaks down family into styles for separate requests
   *
   * @access private
   * @param $message Message to be shown on error
   * @param $status Status code to return if any
   */
  private function breakDownFamily() {
    $ret = [];

    $fonts = explode('|', urldecode($this->family));

    foreach($fonts as $font) {
      $name_style = explode(':', $font);

      $name = $name_style[0];
      $styles = explode(',', $name_style[1]);

      if(sizeof($name_style)>1) {
        foreach($styles as $style) {
          $ret[] = encodeSpacing($name.':'.$style);
        }
      } else {
        $ret[] = encodeSpacing($name);
      }
    }

    $this->family_breakdown = $ret;
    $this->count_requested = count($ret);
  }



  /**
   * Fetches the CSS for a given family string
   *
   * @access private
   * @param $family Family string from GF
   * @param $userAgent User agent string for different output
   * @return string CSS contents as a string
   */
  private function singleFetchCSS($family, $userAgent = '', $critical_subset = null) {
    $ret = '';

    $query = [
      "family" => urldecode($family)
    ];

    // Critical font
    // TODO move this to a better place
    if($critical_subset) {

      $critical = '';

      if(strpos($critical_subset, 'a') !== FALSE) $critical .= 'abcdefghijklmnopqrstuvwxyz';
      if(strpos($critical_subset, 'A') !== FALSE) $critical .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      if(strpos($critical_subset, '1') !== FALSE) $critical .= '0123456789';

      if(!empty($critical)) $query['text'] = $critical;
      // TODO else error
    }

    $curl = new Curl();
    $curl->setUserAgent($userAgent);
    $curl->get(self::BASEURL, $query);


    if ($curl->error) {
      $this->setError($curl->errorMessage, $curl->errorCode);
    } else {
      $ret = $curl->response;
    }

    $curl->close();

    return $ret;
  }



  /**
   * Fetches all the necessary CSS to get all the formats we want
   *
   * @access private
   */
  private function fetchCSS() {
    $ret = '';
    $this->breakDownFamily();

    // If this is critical
    $critical = null;
    if($this->critical_subset) {
      $critical = $this->critical_subset;
    }

    $formats = array_diff(self::SUPPORTED_FORMATS, ['eot', 'svg']);

    // Modern browsers - Every format but EOT and SVG
    foreach($formats as $format) {
      $fetchedCSS = $this->singleFetchCSS($this->family, self::USER_AGENTS[$format], $critical);
      $ret .= $fetchedCSS;
    }

    // EOT and SVG need one request per style and weight
    $formats = ['eot', 'svg'];
    foreach($formats as $format) {
      foreach($this->family_breakdown as $font) {
        $fetchedCSS = $this->singleFetchCSS($font, self::USER_AGENTS[$format], $critical);
        $ret .= $fetchedCSS;
      }
    }

    $this->css = $ret;
  }



  /**
   * Gives the necessary information for a font download
   *
   * @access public
   * @param string $format File extension requested
   */
  public function getFont($format) {
    $ret = [];

    // Make sure we are only requesting one font
    if($this->count > 1) {
      $this->setError("This endpoint only downloads one file, but more than one has been passed");
      return;
    }

    // Get the files for the font
    $font = $this->font_list[0]->types[0];

    // Make sure we got the format
    if(!is_string($format) || !isset($font->files->$format)) {
      $this->setError("The requested format is not available or it hasn't been specified");
      return;
    }

    $ret['file'] = $font->files->$format;
    $ret['mime'] = $this->getMime($format);
    $ret['filename'] = $this->nameFont($font->id) . "." . $format;

    return $ret;
  }



  /**
   * Creates the ZIP with all the fonts and the CSS for them
   *
   * @access public
   */
  public function createZip($critical = null) {
    // Prepare File
    $file = tempnam("tmp", "zip");
    $zip = new ZipArchive();
    $zip->open($file, ZipArchive::OVERWRITE);

    // Add CSS file
    $css = cssComment(false, ['Change this package at https://google.fontperf.com/package?family='.$this->family]);
    $css .= $this->buildCSS();
    $zip->addFromString('fonts.css', $css);

    $format_list = self::SUPPORTED_FORMATS;

    // Add fonts
    foreach($this->font_list as $family) {
      // Directory option
      $dir = str_replace(' ', '', $family->family)."/";

      foreach($family->types as $font) {
        foreach($font->files as $format => $url) {
          if(in_array($format, $format_list)) $zip->addFromString(("font/".$dir. $this->nameFont($font->id) . "." . $format), file_get_contents($url));
        }
      }
    }

    // Add critical fonts
    if($critical && isset($critical['family'])) {
      $set = 'Aa';
      if(isset($critical['set'])) $set = $critical['set'];
      $myCriticalFonts = new GFont($critical['family'], $set);

      $myCriticalFonts->buildList();

      $css = $myCriticalFonts->buildCss('datauri', 'woff2');

      if($myCriticalFonts->error) {
        $myFonts->setError($myCriticalFonts->errorMessage);
      } else {
        $css = cssComment(false, ["Encoded fonts are in 'woff2' format."]) . $css;
        $zip->addFromString('critical_fonts.css', $css);
      }

    }

    // Close and send to users
    $zip->close();

    return $file;
  }



  /**
   * Builds an clean object that contains all the families, styles, and files in different formats.
   *
   * @access public
   */
  public function buildList() {

    $font_list = [];
    $oParser = new CSSParser($this->css);

    $oCss = $oParser->parse();
    $oCssContents = $oCss->getContents();

    // Each @font-face
    foreach($oCssContents as $font) {
      $fontRuleList = $font->getRules();
      $name = null;
      $weight = 400;
      $style = 'normal';
      $src = null;

      // Go through properties
      foreach($fontRuleList as $fontRule) {
        if($fontRule->getRule() == "font-family")
          $name = trim(strval($fontRule->getValue()), '"');

        if($fontRule->getRule() == "font-weight")
          $weight = intval(strval($fontRule->getValue()));

        if($fontRule->getRule() == "font-style")
          $style = strval($fontRule->getValue());

        if($fontRule->getRule() == "src")
          $src = $fontRule->getValue();
      }

      $styleID = encodeSpacing($name. ':' . $weight . ($style!="normal" ? $style : ''));

      // Ignore if no name
      if(is_null($name) || is_null($src))
        break;

      // Add family to font list
      if(!isset($font_list[$name])) {
        $font_list[$name] = [];
        $font_list[$name]['family'] = $name;
        $font_list[$name]['types'] = [];

        if($this->critical_subset)
          $font_list[$name]['family'] .= " Critical";
      }

      // Add font variation
      if(!isset($font_list[$name]['types'][$styleID])) {
        $font_list[$name]['types'][$styleID] = new stdClass();
      }
      $font_list[$name]['types'][$styleID]->id = $styleID;
      $font_list[$name]['types'][$styleID]->weight = $weight;
      $font_list[$name]['types'][$styleID]->style = $style;


      // Files
      if(!isset($font_list[$name]['types'][$styleID]->files)) {
        $font_list[$name]['types'][$styleID]->files = new stdClass();
      }
      // Break down src
      if($src instanceof Sabberworm\CSS\Value\URL) {
        $details = processURL($src);
        $font_list[$name]['types'][$styleID]->files->$details['ext'] = $details['file'];

      } elseif($src instanceof Sabberworm\CSS\Value\RuleValueList) {

        $components = $src->getListComponents();
        if($components[0] instanceof Sabberworm\CSS\Value\RuleValueList) {
          $components = $components[0]->getListComponents();
        }

        foreach($components as $bit) {
          if($bit instanceof Sabberworm\CSS\Value\URL) {
            $details = processURL($bit);
            $font_list[$name]['types'][$styleID]->files->$details['ext'] = $details['file'];
            break;
          }
        }
      } else {
        var_dump($src);
        die();
      }
    }

    // Make list of styles an unordered array
    foreach($font_list as $key => $family) {
      // Real count
      $this->count += count($font_list[$key]['types']);

      $font_list[$key]['types'] = array_values($family['types']);
      $font_list[$key] = (object) $font_list[$key];
    }

    $this->font_list = array_values($font_list);
    return;
  }



  /**
   * Takes the contents of the font list and builds a CSS using the given pattern
   *
   * @access public
   * @param string $type 'normal' for external font requests, 'datauri' for woff2 data-URIs.
   */
  public function buildCSS($type = 'normal', $format = 'woff2') {

    $oCss = new Sabberworm\CSS\CSSList\Document();

    // Create different setup for different styles (Open Sans Bold Italic)

    foreach($this->font_list as $family) {
      $dir = str_replace(' ', '', $family->family)."/";

      foreach($family->types as $version) {

        $oFontFace = new Sabberworm\CSS\RuleSet\AtRuleSet("font-face");

        // font-family: "Open Sans";
        $name = new Sabberworm\CSS\Rule\Rule("font-family");
        $name->setValue('"'. $family->family .'"');
        $oFontFace->addRule($name);

        // font-weight: 400;
        $weight = new Sabberworm\CSS\Rule\Rule("font-weight");
        $weight->setValue($version->weight);
        $oFontFace->addRule($weight);

        // font-style: normal;
        $style = new Sabberworm\CSS\Rule\Rule("font-style");
        $style->setValue($version->style);
        $oFontFace->addRule($style);

        // Font loading logic via CSS! - font-display: swap;
        $style = new Sabberworm\CSS\Rule\Rule("font-display");
        $style->setValue("swap");
        $oFontFace->addRule($style);

        // src:
        $src = new Sabberworm\CSS\Rule\Rule("src");

        if($type == "datauri") {
          if(!isset($version->files->$format)) {
            $this->setError("The requested format '".$format."' is not available.");
            return;
          }

          // src: url(data-bas64)
          $string = new Sabberworm\CSS\Value\CSSString($this->datauri($version->files->$format, $format));
          $url = new Sabberworm\CSS\Value\URL($string);
          $src->setValue($url);
          $oFontFace->addRule($src);

        } else {

          // EOT IE9 compat mode (fallback src)
          $format = 'eot';

          $string = new Sabberworm\CSS\Value\CSSString('./font/' . $dir . $this->nameFont($version->id).".".$format);
          $url = new Sabberworm\CSS\Value\URL($string);
          $src->setValue($url);
          $oFontFace->addRule($src);


          // src multiple (real deal)
          $src_multiple = new Sabberworm\CSS\Rule\Rule("src");
          $src_value = new Sabberworm\CSS\Value\RuleValueList();


          // Add EOT for IE 6-8
          $oString = new Sabberworm\CSS\Value\CSSString('./font/' . $dir . $this->nameFont($version->id).".".$format . "?#iefix");
          $oUrl = new Sabberworm\CSS\Value\URL($oString);

          $oFormat = new Sabberworm\CSS\Value\CSSFunction("format", $this->fontfaceFormat("eot"));

          $oUrlWithFormat = new Sabberworm\CSS\Value\RuleValueList(' ');
          $oUrlWithFormat->setListComponents([
            $oUrl,
            $oFormat
          ]);

          $src_value->addListComponent($oUrlWithFormat);

          // Modern browsers
          $format_list = array_diff(self::SUPPORTED_FORMATS, ['eot']);

          foreach($format_list as $format) {
            $path = './font/' . $dir . $this->nameFont($version->id).".".$format;
            if($format == 'svg') $path .= '#'.str_replace(' ', '', $family->family);
            $oString = new Sabberworm\CSS\Value\CSSString($path);
            $oUrl = new Sabberworm\CSS\Value\URL($oString);

            $oFormat = new Sabberworm\CSS\Value\CSSFunction("format", $this->fontfaceFormat($format));

            $oUrlWithFormat = new Sabberworm\CSS\Value\RuleValueList(' ');
            $oUrlWithFormat->setListComponents([
              $oUrl,
              $oFormat
            ]);

            $src_value->addListComponent($oUrlWithFormat);
          }

          $src_multiple->setValue($src_value);
          $oFontFace->addRule($src_multiple);
        }

        $oCss->append($oFontFace);

      }
    }

    $oFormat = Sabberworm\CSS\OutputFormat::createPretty();
    return $oCss->render($oFormat);
  }



}
