<?php
/*******************************************************************************
* FPDF                                                                         *
*                                                                              *
* Version: 1.9                                                                 *
* Date:    2026-05-31                                                          *
* Author:  Olivier Plathey                                                     *
*******************************************************************************/

class FPDF
{
const VERSION = '1.9';
protected $page;               // current page number
protected $n;                  // current object number
protected $offsets;            // array of object offsets
protected $buffer;             // buffer holding in-memory PDF
protected $pages;              // array containing pages
protected $state;              // current document state
protected $compress;           // compression flag
protected $iconv;              // whether iconv is available
protected $k;                  // scale factor (number of points in user unit)
protected $DefOrientation;     // default orientation
protected $CurOrientation;     // current orientation
protected $StdPageSizes;       // standard page sizes
protected $DefPageSize;        // default page size
protected $CurPageSize;        // current page size
protected $CurRotation;        // current page rotation
protected $PageInfo;           // page-related data
protected $wPt, $hPt;          // dimensions of current page in points
protected $w, $h;              // dimensions of current page in user unit
protected $lMargin;            // left margin
protected $tMargin;            // top margin
protected $rMargin;            // right margin
protected $bMargin;            // page break margin
protected $cMargin;            // cell margin
protected $x, $y;              // current position in user unit
protected $lasth;              // height of last printed cell
protected $LineWidth;          // line width in user unit
protected $fontpath;           // directory containing fonts
protected $CoreFonts;          // array of core font names
protected $fonts;              // array of used fonts
protected $FontFiles;          // array of font files
protected $encodings;          // array of encodings
protected $cmaps;              // array of ToUnicode CMaps
protected $FontFamily;         // current font family
protected $FontStyle;          // current font style
protected $underline;          // underlining flag
protected $CurrentFont;        // current font info
protected $FontSizePt;         // current font size in points
protected $FontSize;           // current font size in user unit
protected $DrawColor;          // commands for drawing color
protected $FillColor;          // commands for filling color
protected $TextColor;          // commands for text color
protected $ColorFlag;          // indicates whether fill and text colors are different
protected $WithAlpha;          // indicates whether alpha channel is used
protected $ws;                 // word spacing
protected $images;             // array of used images
protected $PageLinks;          // array of links in pages
protected $links;              // array of internal links
protected $AutoPageBreak;      // automatic page breaking
protected $PageBreakTrigger;   // threshold used to trigger page breaks
protected $InHeader;           // flag set when processing header
protected $InFooter;           // flag set when processing footer
protected $AliasNbPages;       // alias for total number of pages
protected $ZoomMode;           // zoom display mode
protected $LayoutMode;         // layout display mode
protected $metadata;           // document properties
protected $CreationDate;       // document creation date
protected $PDFVersion;         // PDF version number

/*******************************************************************************
*                               Public methods                                 *
*******************************************************************************/

public function __construct($orientation='P', $unit='mm', $size='A4')
{
	// Initialization of properties
	$this->state = 0;
	$this->page = 0;
	$this->n = 2;
	$this->buffer = '';
	$this->pages = array();
	$this->PageInfo = array();
	$this->fonts = array();
	$this->FontFiles = array();
	$this->encodings = array();
	$this->cmaps = array();
	$this->images = array();
	$this->links = array();
	$this->InHeader = false;
	$this->InFooter = false;
	$this->lasth = 0;
	$this->FontFamily = '';
	$this->FontStyle = '';
	$this->FontSizePt = 12;
	$this->underline = false;
	$this->DrawColor = '0 G';
	$this->FillColor = '0 g';
	$this->TextColor = '0 g';
	$this->ColorFlag = false;
	$this->WithAlpha = false;
	$this->ws = 0;
	$this->iconv = function_exists('iconv');
	// Font path
	if(defined('FPDF_FONTPATH'))
		$this->fontpath = FPDF_FONTPATH;
	else
		$this->fontpath = dirname(__FILE__).'/font/';
	// Core fonts
	$this->CoreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
	// Scale factor
	if($unit=='pt')
		$this->k = 1;
	elseif($unit=='mm')
		$this->k = 72/25.4;
	elseif($unit=='cm')
		$this->k = 72/2.54;
	elseif($unit=='in')
		$this->k = 72;
	else
		$this->Error('Incorrect unit: '.$unit);
	// Page sizes
	$this->StdPageSizes = array('a3'=>array(841.89, 1190.55), 'a4'=>array(595.28, 841.89), 'a5'=>array(420.94, 595.28),
		'letter'=>array(612, 792), 'legal'=>array(612, 1008));
	$size = $this->_getpagesize($size);
	$this->DefPageSize = $size;
	$this->CurPageSize = $size;
	// Page orientation
	$orientation = strtolower($orientation);
	if($orientation=='p' || $orientation=='portrait')
	{
		$this->DefOrientation = 'P';
		$this->w = $size[0];
		$this->h = $size[1];
	}
	elseif($orientation=='l' || $orientation=='landscape')
	{
		$this->DefOrientation = 'L';
		$this->w = $size[1];
		$this->h = $size[0];
	}
	else
		$this->Error('Incorrect orientation: '.$orientation);
	$this->CurOrientation = $this->DefOrientation;
	$this->wPt = $this->w*$this->k;
	$this->hPt = $this->h*$this->k;
	// Page rotation
	$this->CurRotation = 0;
	// Page margins (1 cm)
	$margin = 28.35/$this->k;
	$this->SetMargins($margin, $margin);
	// Interior cell margin (1 mm)
	$this->cMargin = $margin/10;
	// Line width (0.2 mm)
	$this->LineWidth = .567/$this->k;
	// Automatic page break
	$this->SetAutoPageBreak(true, 2*$margin);
	// Default display mode
	$this->SetDisplayMode('default');
	// Enable compression
	$this->SetCompression(true);
	// Metadata
	$this->metadata = array('Producer'=>'FPDF '.self::VERSION);
	// Set default PDF version number
	$this->PDFVersion = '1.3';
}

public function SetMargins($left, $top, $right=null)
{
	// Set left, top and right margins
	$this->lMargin = $left;
	$this->tMargin = $top;
	if($right===null)
		$right = $left;
	$this->rMargin = $right;
}

public function SetLeftMargin($margin)
{
	// Set left margin
	$this->lMargin = $margin;
	if($this->page>0 && $this->x<$margin)
		$this->x = $margin;
}

public function SetTopMargin($margin)
{
	// Set top margin
	$this->tMargin = $margin;
}

public function SetRightMargin($margin)
{
	// Set right margin
	$this->rMargin = $margin;
}

public function SetAutoPageBreak($auto, $margin=0)
{
	// Set auto page break mode and triggering margin
	$this->AutoPageBreak = $auto;
	$this->bMargin = $margin;
	$this->PageBreakTrigger = $this->h-$margin;
}

public function SetDisplayMode($zoom, $layout='default')
{
	// Set display mode in viewer
	if($zoom=='fullpage' || $zoom=='fullwidth' || $zoom=='real' || $zoom=='default' || !is_string($zoom))
		$this->ZoomMode = $zoom;
	else
		$this->Error('Incorrect zoom display mode: '.$zoom);
	if($layout=='single' || $layout=='continuous' || $layout=='two' || $layout=='default')
		$this->LayoutMode = $layout;
	else
		$this->Error('Incorrect layout display mode: '.$layout);
}

public function SetCompression($compress)
{
	// Set page compression
	if(function_exists('gzcompress'))
		$this->compress = $compress;
	else
		$this->compress = false;
}

public function SetTitle($title, $isUTF8=false)
{
	// Title of document
	$this->metadata['Title'] = $isUTF8 ? $title : $this->_UTF8encode($title);
}

public function SetAuthor($author, $isUTF8=false)
{
	// Author of document
	$this->metadata['Author'] = $isUTF8 ? $author : $this->_UTF8encode($author);
}

public function SetSubject($subject, $isUTF8=false)
{
	// Subject of document
	$this->metadata['Subject'] = $isUTF8 ? $subject : $this->_UTF8encode($subject);
}

public function SetKeywords($keywords, $isUTF8=false)
{
	// Keywords of document
	$this->metadata['Keywords'] = $isUTF8 ? $keywords : $this->_UTF8encode($keywords);
}

public function SetCreator($creator, $isUTF8=false)
{
	// Creator of document
	$this->metadata['Creator'] = $isUTF8 ? $creator : $this->_UTF8encode($creator);
}

public function AliasNbPages($alias='{nb}')
{
	// Define an alias for total number of pages
	$this->AliasNbPages = $alias;
}

public function Error($msg)
{
	// Fatal error
	throw new Exception('FPDF error: '.$msg);
}

public function Close()
{
	// Terminate document
	if($this->state==3)
		return;
	if($this->page==0)
		$this->AddPage();
	// Page footer
	$this->InFooter = true;
	$this->Footer();
	$this->InFooter = false;
	// Close page
	$this->_endpage();
	// Close document
	$this->_enddoc();
}

public function AddPage($orientation='', $size='', $rotation=0)
{
	// Start a new page
	if($this->state==3)
		$this->Error('The document is closed');
	$family = $this->FontFamily;
	$style = $this->FontStyle.($this->underline ? 'U' : '');
	$fontsize = $this->FontSizePt;
	$lw = $this->LineWidth;
	$dc = $this->DrawColor;
	$fc = $this->FillColor;
	$tc = $this->TextColor;
	$cf = $this->ColorFlag;
	if($this->page>0)
	{
		// Page footer
		$this->InFooter = true;
		$this->Footer();
		$this->InFooter = false;
		// Close page
		$this->_endpage();
	}
	// Start new page
	$this->_beginpage($orientation, $size, $rotation);
	// Set line cap style to square
	$this->_out('2 J');
	// Set line join style to miter
	$this->_out('0 j');
	// Restore line width
	$this->LineWidth = $lw;
	$this->_out(sprintf('%.2F w', $lw*$this->k));
	// Restore font
	if($family)
		$this->SetFont($family, $style, $fontsize);
	// Restore colors
	$this->DrawColor = $dc;
	if($dc!='0 G')
		$this->_out($dc);
	$this->FillColor = $fc;
	if($fc!='0 g')
		$this->_out($fc);
	$this->TextColor = $tc;
	$this->ColorFlag = $cf;
	// Restore line width
	$this->LineWidth = $lw;
	// Page header
	$this->InHeader = true;
	$this->Header();
	$this->InHeader = false;
	// Restore line width
	$this->LineWidth = $lw;
	$this->_out(sprintf('%.2F w', $lw*$this->k));
	// Restore line cap style
	$this->_out('2 J');
	// Restore line join style
	$this->_out('0 j');
}

public function Header()
{
	// To be implemented in your own inherited class
}

public function Footer()
{
	// To be implemented in your own inherited class
}

public function SetFont($family, $style='', $size=0)
{
	// Select a font; $family can be a standard font name or a font added with AddFont()
	// $style can be '', 'B', 'I', 'U' or a combination: 'BI', 'BU', 'IU', 'BIU'
	// $size is in points
	$family = strtolower($family);
	if($family=='')
		$family = $this->FontFamily;
	if($family=='arial')
		$family = 'helvetica';
	elseif($family=='symbol' || $family=='zapfdingbats')
		$style = '';
	$style = strtoupper($style);
	if(strpos($style, 'U')!==false)
	{
		$this->underline = true;
		$style = str_replace('U', '', $style);
	}
	else
		$this->underline = false;
	if($style=='IB')
		$style = 'BI';
	if($size==0)
		$size = $this->FontSizePt;
	// Test if font is already selected
	if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
		return;
	// Test if used for the first time
	$fontkey = $family.$style;
	if(!isset($this->fonts[$fontkey]))
	{
		// Check if one of the core fonts
		if(in_array($family, $this->CoreFonts))
		{
			if(!isset($this->CoreFonts[$fontkey]))
				$this->Error('Undefined font: '.$family.' '.$style);
			$i = count($this->fonts)+1;
			$this->fonts[$fontkey] = array('i'=>$i, 'type'=>'core', 'name'=>$this->CoreFonts[$fontkey], 'up'=>-100, 'ut'=>50, 'cw'=>$this->fontdata[$family]['cw']);
		}
		else
			$this->Error('Undefined font: '.$family.' '.$style);
	}
	// Select it
	$this->FontFamily = $family;
	$this->FontStyle = $style;
	$this->FontSizePt = $size;
	$this->FontSize = $size/$this->k;
	$this->CurrentFont = &$this->fonts[$fontkey];
	if($this->page>0)
		$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
}

public function SetFontSize($size)
{
	// Set font size in points
	if($this->FontSizePt==$size)
		return;
	$this->FontSizePt = $size;
	$this->FontSize = $size/$this->k;
	if($this->page>0)
		$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
}

public function AddLink()
{
	// Create a new internal link
	$n = count($this->links)+1;
	$this->links[$n] = array(0, 0);
	return $n;
}

public function SetLink($link, $y=0, $page=-1)
{
	// Set destination of internal link
	if($y==-1)
		$y = $this->y;
	if($page==-1)
		$page = $this->page;
	$this->links[$link] = array($page, $y);
}

public function Link($x, $y, $w, $h, $link)
{
	// Put a link on the page
	$this->PageLinks[$this->page][] = array($x*$this->k, $this->hPt-$y*$this->k, $w*$this->k, $h*$this->k, $link);
}

public function Text($x, $y, $txt)
{
	// Output a string
	$txt = $this->_UTF8toUTF16($txt);
	$s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
	if($this->underline && $txt!='')
		$s .= ' '.$this->_dounderline($this->x+$x, $this->y+$y, $txt);
	if($this->ColorFlag)
		$s = 'q '.$this->TextColor.' '.$s.' Q';
	$this->_out($s);
}

public function AcceptPageBreak()
{
	// Accept automatic page break or not
	return $this->AutoPageBreak;
}

public function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='')
{
	// Output a cell
	$k = $this->k;
	if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
	{
		$x = $this->x;
		$y = $this->y;
		$this->Ln($this->lasth);
		$this->x = $x;
		$this->y = $y;
	}
	if($w==0)
		$w = $this->w-$this->rMargin-$this->x;
	$s = '';
	if($fill || $border==1)
	{
		if($fill)
			$op = ($border==1) ? 'B' : 'f';
		else
			$op = 'S';
		$s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x*$k, ($this->h-$this->y)*$k, $w*$k, -$h*$k, $op);
	}
	if(is_string($border))
	{
		$x = $this->x;
		$y = $this->y;
		if(strpos($border, 'L')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, $x*$k, ($this->h-($y+$h))*$k);
		if(strpos($border, 'T')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-$y)*$k);
		if(strpos($border, 'R')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x+$w)*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
		if(strpos($border, 'B')!==false)
			$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-($y+$h))*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
	}
	if($txt!='')
	{
		$txt = $this->_UTF8toUTF16($txt);
		$txt2 = $this->_escape($txt);
		$dx = 0;
		if($align=='R')
			$dx = $w-$this->cMargin-$this->GetStringWidth($txt2);
		elseif($align=='C')
			$dx = ($w-$this->GetStringWidth($txt2))/2;
		elseif($align=='J')
		{
			$dx = $this->cMargin;
			$wmax = $w-2*$this->cMargin;
			if($this->GetStringWidth($txt2)<$wmax)
			{
				$nb = substr_count($txt, ' ');
				if($nb>0)
					$dx = ($wmax-$this->GetStringWidth($txt2))/$nb;
			}
		}
		else
			$dx = $this->cMargin;
		if($this->ColorFlag)
			$s .= 'q '.$this->TextColor.' ';
		$s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x+$dx)*$k, ($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k, $txt2);
		if($this->underline)
			$s .= ' '.$this->_dounderline($this->x+$dx, $this->y+.5*$h+.3*$this->FontSize, $txt);
		if($this->ColorFlag)
			$s .= ' Q';
		if($link)
			$this->Link($this->x+$dx, $this->y+.5*$h-.5*$this->FontSize, $this->GetStringWidth($txt2), $this->FontSize, $link);
	}
	if($s)
		$this->_out($s);
	$this->lasth = $h;
	if($ln)
	{
		if($ln==1)
			$this->y = $this->y+$h;
		elseif($ln==2)
			$this->x = $this->lMargin;
		else
			$this->x = $this->lMargin;
		$this->y = $this->y+$h;
	}
	else
		$this->x = $this->x+$w;
}

public function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
{
	// Output text with automatic or explicit line breaks
	$cw = &$this->CurrentFont['cw'];
	if($w==0)
		$w = $this->w-$this->rMargin-$this->x;
	$wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
	$s = str_replace("\r", '', $txt);
	$nb = strlen($s);
	if($nb>0 && $s[$nb-1]=="\n")
		$nb--;
	$b = 0;
	if($border)
	{
		if($border==1)
		{
			$border = 'LTRB';
			$b = 'LRTB';
		}
		else
		{
			$b2 = '';
			if(strpos($border, 'L')!==false)
				$b2 .= 'L';
			if(strpos($border, 'T')!==false)
				$b2 .= 'T';
			if(strpos($border, 'R')!==false)
				$b2 .= 'R';
			if(strpos($border, 'B')!==false)
				$b2 .= 'B';
			$b = $b2;
		}
	}
	$sep = -1;
	$i = 0;
	$j = 0;
	$l = 0;
	$ns = 0;
	$nl = 1;
	while($i<$nb)
	{
		$c = $s[$i];
		if($c=="\n")
		{
			if($this->ws>0)
			{
				$this->_out('0 Tw');
				$this->ws = 0;
			}
			$this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
			$i++;
			$sep = -1;
			$j = $i;
			$l = 0;
			$ns = 0;
			$nl++;
			if($border && $nl==2)
				$b = $b2;
			continue;
		}
		if($c==' ')
		{
			$sep = $i;
			$ls = $l;
			$ns++;
		}
		$l += $cw[ord($c)];
		if($l>$wmax)
		{
			if($sep==-1)
			{
				if($i==$j)
					$i++;
				$this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
			}
			else
			{
				if($align=='J')
				{
					$this->_out(sprintf('%.3F Tw', ($wmax-$ls)/1000*$this->FontSize/($ns>1?$ns-1:1)));
					$this->ws = ($wmax-$ls)/1000*$this->FontSize/($ns>1?$ns-1:1);
				}
				$this->Cell($w, $h, substr($s, $j, $sep-$j), $b, 2, $align, $fill);
				$i = $sep+1;
			}
			$sep = -1;
			$j = $i;
			$l = 0;
			$ns = 0;
			$nl++;
			if($border && $nl==2)
				$b = $b2;
		}
		else
			$i++;
	}
	if($this->ws>0)
	{
		$this->_out('0 Tw');
		$this->ws = 0;
	}
	if($border && strpos($border, 'B')!==false)
		$b .= 'B';
	$this->Cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
	$this->x = $this->lMargin;
}

public function Write($h, $txt, $link='')
{
	// Output text in flowing mode
	$cw = &$this->CurrentFont['cw'];
	$w = $this->w-$this->rMargin-$this->x;
	$wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
	$s = str_replace("\r", '', $txt);
	$nb = strlen($s);
	$sep = -1;
	$i = 0;
	$j = 0;
	$l = 0;
	$nl = 1;
	while($i<$nb)
	{
		$c = $s[$i];
		if($c=="\n")
		{
			$this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, '', 0, $link);
			$i++;
			$sep = -1;
			$j = $i;
			$l = 0;
			$nl++;
			continue;
		}
		if($c==' ')
		{
			$sep = $i;
			$ls = $l;
		}
		$l += $cw[ord($c)];
		if($l>$wmax)
		{
			if($sep==-1)
			{
				if($this->x>$this->lMargin)
				{
					$this->x = $this->lMargin;
					$this->y += $h;
					$w = $this->w-$this->rMargin-$this->x;
					$wmax = ($w-2*$this->cMargin)*1000/$this->FontSize;
					$i--;
					$nl++;
					continue;
				}
				if($i==$j)
					$i++;
				$this->Cell($w, $h, substr($s, $j, $i-$j), 0, 2, '', 0, $link);
			}
			else
			{
				$this->Cell($w, $h, substr($s, $j, $sep-$j), 0, 2, '', 0, $link);
				$i = $sep+1;
			}
			$sep = -1;
			$j = $i;
			$l = 0;
			$nl++;
		}
		else
			$i++;
	}
	if($i!=$j)
		$this->Cell($l/1000*$this->FontSize, $h, substr($s, $j), 0, 0, '', 0, $link);
}

public function Ln($h=null)
{
	// Line feed; default value is the last cell height
	$this->x = $this->lMargin;
	if($h===null)
		$this->y += $this->lasth;
	else
		$this->y += $h;
}

public function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
{
	// Put an image on the page
	if(!isset($this->images[$file]))
	{
		// First use of this image, get info
		if($type=='')
		{
			$pos = strrpos($file, '.');
			if(!$pos)
				$this->Error('Image file has no extension and no specified type');
			$type = substr($file, $pos+1);
		}
		$type = strtolower($type);
		if($type=='jpeg')
			$type = 'jpg';
		$mtd = '_parse'.$type;
		if(!method_exists($this, $mtd))
			$this->Error('Unsupported image type: '.$type);
		$info = $this->$mtd($file);
		$info['i'] = count($this->images)+1;
		$this->images[$file] = $info;
	}
	else
		$info = $this->images[$file];
	// Automatic width and height calculation
	if($w==0 && $h==0)
	{
		// Put image at 96 dpi
		$w = -96;
		$h = -96;
	}
	if($w<0)
		$w = -$info['w']*72/$w/$this->k;
	if($h<0)
		$h = -$info['h']*72/$h/$this->k;
	if($w==0)
		$w = $h*$info['w']/$info['h'];
	if($h==0)
		$h = $w*$info['h']/$info['w'];
	// Flowing mode
	if($y===null)
	{
		if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
		{
			$this->AddPage();
			$this->y = $this->tMargin;
		}
		$y = $this->y;
	}
	if($x===null)
		$x = $this->x;
	$this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $w*$this->k, $h*$this->k, $x*$this->k, ($this->h-($y+$h))*$this->k, $info['i']));
	if($link)
		$this->Link($x, $y, $w, $h, $link);
	// Set pointer to the top-right corner
	$this->y = $y+$h;
}

public function GetX()
{
	// Get x position
	return $this->x;
}

public function SetX($x)
{
	// Set x position
	if($x>=0)
		$this->x = $x;
	else
		$this->x = $this->w+$x;
}

public function GetY()
{
	// Get y position
	return $this->y;
}

public function SetY($y)
{
	// Set y position
	if($y>=0)
		$this->y = $y;
	else
		$this->y = $this->h+$y;
}

public function SetXY($x, $y)
{
	// Set x and y positions
	$this->SetY($y);
	$this->SetX($x);
}

public function Output($dest='', $name='', $isUTF8=false)
{
	// Output PDF to some destination
	$this->Close();
	if(strlen($name)==1 && strlen($dest)!=1)
	{
		// Old parameter order
		$dest = $name;
		$name = '';
	}
	if($dest=='')
		$dest = 'I';
	if($name=='')
		$name = 'doc.pdf';
	switch(strtoupper($dest))
	{
		case 'I':
			// Send to standard output
			$this->_checkoutput();
			if(PHP_SAPI!='cli')
			{
				header('Content-Type: application/pdf');
				header('Content-Disposition: inline; filename="'.$name.'"');
				header('Cache-Control: private, max-age=0, must-revalidate');
				header('Pragma: public');
			}
			echo $this->buffer;
			break;
		case 'D':
			// Download file
			$this->_checkoutput();
			header('Content-Type: application/pdf');
			header('Content-Disposition: attachment; filename="'.$name.'"');
			header('Cache-Control: private, max-age=0, must-revalidate');
			header('Pragma: public');
			echo $this->buffer;
			break;
		case 'F':
			// Save to local file
			$f = fopen($name, 'wb');
			if(!$f)
				$this->Error('Unable to create output file: '.$name);
			fwrite($f, $this->buffer, strlen($this->buffer));
			fclose($f);
			break;
		case 'S':
			// Return as a string
			return $this->buffer;
		default:
			$this->Error('Incorrect output destination: '.$dest);
	}
	return '';
}

/*******************************************************************************
*                             Protected methods                                *
*******************************************************************************/

protected function _beginpage($orientation, $size, $rotation)
{
	$this->page++;
	$this->pages[$this->page] = '';
	$this->state = 2;
	$this->x = $this->lMargin;
	$this->y = $this->tMargin;
	$this->FontFamily = '';
	$this->FontStyle = '';
	$this->FontSizePt = 12;
	$this->LineWidth = .567/$this->k;
	// Page orientation
	if(!empty($orientation))
	{
		$orientation = strtolower($orientation);
		if($orientation=='p' || $orientation=='portrait')
			$orientation = 'P';
		elseif($orientation=='l' || $orientation=='landscape')
			$orientation = 'L';
		else
			$this->Error('Incorrect orientation: '.$orientation);
		if($orientation!=$this->DefOrientation)
		{
			// Swap width and height
			$this->w = $this->DefPageSize[1];
			$this->h = $this->DefPageSize[0];
		}
		else
		{
			$this->w = $this->DefPageSize[0];
			$this->h = $this->DefPageSize[1];
		}
		$this->wPt = $this->w*$this->k;
		$this->hPt = $this->h*$this->k;
		$this->CurOrientation = $orientation;
	}
	else
	{
		$this->w = $this->DefPageSize[0];
		$this->h = $this->DefPageSize[1];
		$this->wPt = $this->w*$this->k;
		$this->hPt = $this->h*$this->k;
		$this->CurOrientation = $this->DefOrientation;
	}
	// Page size
	if(!empty($size))
	{
		$size = $this->_getpagesize($size);
		$this->w = $size[0];
		$this->h = $size[1];
		$this->wPt = $this->w*$this->k;
		$this->hPt = $this->h*$this->k;
		$this->CurPageSize = $size;
	}
	else
	{
		$this->CurPageSize = $this->DefPageSize;
	}
	// Page rotation
	if($rotation!=0)
	{
		if($rotation%90!=0)
			$this->Error('Invalid rotation angle: '.$rotation);
		$this->CurRotation = $rotation;
	}
	else
		$this->CurRotation = 0;
	$this->PageInfo[$this->page] = array('w'=>$this->w, 'h'=>$this->h, 'orientation'=>$this->CurOrientation, 'rotation'=>$this->CurRotation);
}

protected function _endpage()
{
	$this->state = 1;
}

protected function _enddoc()
{
	$this->_putpages();
	$this->_putresources();
	// Info
	$this->_newobj();
	$this->_out('<<');
	$this->_putinfo();
	$this->_out('>>');
	$this->_out('endobj');
	// Catalog
	$this->_newobj();
	$this->_out('<<');
	$this->_putcatalog();
	$this->_out('>>');
	$this->_out('endobj');
	// Cross-reference
	$o = $this->buffer;
	$this->buffer = '';
	$this->_out('xref');
	$this->_out('0 '.($this->n+1));
	$this->_out('0000000000 65535 f ');
	for($i=1; $i<=$this->n; $i++)
		$this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
	// Trailer
	$this->_out('trailer');
	$this->_out('<<');
	$this->_puttrailer();
	$this->_out('>>');
	$this->_out('startxref');
	$this->_out($this->n);
	$this->_out('%%EOF');
	$this->buffer = $o;
}

protected function _beginfont()
{
	$this->fontdata = array(
		'courier'=>array('i'=>4, 'cw'=>array(' '=>600, '!'=>600, '"'=>600, '#'=>600, '$'=>600, '%'=>600, '&'=>600, '\''=>600, '('=>600, ')'=>600, '*'=>600, '+'=>600, ','=>600, '-'=>600, '.'=>600, '/'=>600, '0'=>600, '1'=>600, '2'=>600, '3'=>600, '4'=>600, '5'=>600, '6'=>600, '7'=>600, '8'=>600, '9'=>600, ':'=>600, ';'=>600, '<'=>600, '='=>600, '>'=>600, '?'=>600, '@'=>600, 'A'=>600, 'B'=>600, 'C'=>600, 'D'=>600, 'E'=>600, 'F'=>600, 'G'=>600, 'H'=>600, 'I'=>600, 'J'=>600, 'K'=>600, 'L'=>600, 'M'=>600, 'N'=>600, 'O'=>600, 'P'=>600, 'Q'=>600, 'R'=>600, 'S'=>600, 'T'=>600, 'U'=>600, 'V'=>600, 'W'=>600, 'X'=>600, 'Y'=>600, 'Z'=>600, '['=>600, '\\'=>600, ']'=>600, '^'=>600, '_'=>600, '`'=>600, 'a'=>600, 'b'=>600, 'c'=>600, 'd'=>600, 'e'=>600, 'f'=>600, 'g'=>600, 'h'=>600, 'i'=>600, 'j'=>600, 'k'=>600, 'l'=>600, 'm'=>600, 'n'=>600, 'o'=>600, 'p'=>600, 'q'=>600, 'r'=>600, 's'=>600, 't'=>600, 'u'=>600, 'v'=>600, 'w'=>600, 'x'=>600, 'y'=>600, 'z'=>600, '{'=>600, '|'=>600, '}'=>600, '~'=>600),
		'helvetica'=>array('i'=>1, 'cw'=>array(' '=>278, '!'=>278, '"'=>355, '#'=>556, '$'=>556, '%'=>889, '&'=>667, '\''=>191, '('=>333, ')'=>333, '*'=>389, '+'=>584, ','=>278, '-'=>333, '.'=>278, '/'=>278, '0'=>556, '1'=>556, '2'=>556, '3'=>556, '4'=>556, '5'=>556, '6'=>556, '7'=>556, '8'=>556, '9'=>556, ':'=>278, ';'=>278, '<'=>584, '='=>584, '>'=>584, '?'=>556, '@'=>1015, 'A'=>667, 'B'=>667, 'C'=>722, 'D'=>722, 'E'=>667, 'F'=>611, 'G'=>778, 'H'=>722, 'I'=>278, 'J'=>500, 'K'=>667, 'L'=>556, 'M'=>833, 'N'=>722, 'O'=>778, 'P'=>667, 'Q'=>778, 'R'=>722, 'S'=>667, 'T'=>611, 'U'=>722, 'V'=>667, 'W'=>944, 'X'=>667, 'Y'=>667, 'Z'=>611, '['=>278, '\\'=>278, ']'=>278, '^'=>469, '_'=>556, '`'=>333, 'a'=>556, 'b'=>556, 'c'=>500, 'd'=>556, 'e'=>556, 'f'=>278, 'g'=>556, 'h'=>556, 'i'=>222, 'j'=>222, 'k'=>500, 'l'=>222, 'm'=>833, 'n'=>556, 'o'=>556, 'p'=>556, 'q'=>556, 'r'=>333, 's'=>500, 't'=>278, 'u'=>556, 'v'=>500, 'w'=>722, 'x'=>500, 'y'=>500, 'z'=>500, '{'=>334, '|'=>278, '}'=>334, '~'=>584),
		'times'=>array('i'=>3, 'cw'=>array(' '=>250, '!'=>333, '"'=>408, '#'=>500, '$'=>500, '%'=>833, '&'=>778, '\''=>180, '('=>333, ')'=>333, '*'=>500, '+'=>564, ','=>250, '-'=>333, '.'=>250, '/'=>278, '0'=>500, '1'=>500, '2'=>500, '3'=>500, '4'=>500, '5'=>500, '6'=>500, '7'=>500, '8'=>500, '9'=>500, ':'=>278, ';'=>278, '<'=>564, '='=>564, '>'=>564, '?'=>444, '@'=>921, 'A'=>722, 'B'=>667, 'C'=>667, 'D'=>722, 'E'=>611, 'F'=>556, 'G'=>722, 'H'=>722, 'I'=>333, 'J'=>389, 'K'=>722, 'L'=>611, 'M'=>889, 'N'=>722, 'O'=>722, 'P'=>611, 'Q'=>722, 'R'=>667, 'S'=>556, 'T'=>611, 'U'=>722, 'V'=>722, 'W'=>944, 'X'=>722, 'Y'=>722, 'Z'=>611, '['=>333, '\\'=>278, ']'=>333, '^'=>469, '_'=>500, '`'=>333, 'a'=>444, 'b'=>500, 'c'=>444, 'd'=>500, 'e'=>444, 'f'=>333, 'g'=>500, 'h'=>500, 'i'=>278, 'j'=>278, 'k'=>500, 'l'=>278, 'm'=>778, 'n'=>500, 'o'=>500, 'p'=>500, 'q'=>500, 'r'=>333, 's'=>389, 't'=>278, 'u'=>500, 'v'=>444, 'w'=>722, 'x'=>500, 'y'=>500, 'z'=>444, '{'=>400, '|'=>278, '}'=>400, '~'=>564)),
		'symbol'=>array('i'=>5, 'cw'=>array(' '=>250, '!'=>333, '"'=>333, '#'=>333, '$'=>333, '%'=>333, '&'=>333, '\''=>333, '('=>333, ')'=>333, '*'=>333, '+'=>333, ','=>333, '-'=>333, '.'=>333, '/'=>333, '0'=>333, '1'=>333, '2'=>333, '3'=>333, '4'=>333, '5'=>333, '6'=>333, '7'=>333, '8'=>333, '9'=>333, ':'=>333, ';'=>333, '<'=>333, '='=>333, '>'=>333, '?'=>333, '@'=>333, 'A'=>333, 'B'=>333, 'C'=>333, 'D'=>333, 'E'=>333, 'F'=>333, 'G'=>333, 'H'=>333, 'I'=>333, 'J'=>333, 'K'=>333, 'L'=>333, 'M'=>333, 'N'=>333, 'O'=>333, 'P'=>333, 'Q'=>333, 'R'=>333, 'S'=>333, 'T'=>333, 'U'=>333, 'V'=>333, 'W'=>333, 'X'=>333, 'Y'=>333, 'Z'=>333, '['=>333, '\\'=>333, ']'=>333, '^'=>333, '_'=>333, '`'=>333, 'a'=>333, 'b'=>333, 'c'=>333, 'd'=>333, 'e'=>333, 'f'=>333, 'g'=>333, 'h'=>333, 'i'=>333, 'j'=>333, 'k'=>333, 'l'=>333, 'm'=>333, 'n'=>333, 'o'=>333, 'p'=>333, 'q'=>333, 'r'=>333, 's'=>333, 't'=>333, 'u'=>333, 'v'=>333, 'w'=>333, 'x'=>333, 'y'=>333, 'z'=>333, '{'=>333, '|'=>333, '}'=>333, '~'=>333)),
		'zapfdingbats'=>array('i'=>2, 'cw'=>array(' '=>250, '!'=>250, '"'=>250, '#'=>250, '$'=>250, '%'=>250, '&'=>250, '\''=>250, '('=>250, ')'=>250, '*'=>250, '+'=>250, ','=>250, '-'=>250, '.'=>250, '/'=>250, '0'=>250, '1'=>250, '2'=>250, '3'=>250, '4'=>250, '5'=>250, '6'=>250, '7'=>250, '8'=>250, '9'=>250, ':'=>250, ';'=>250, '<'=>250, '='=>250, '>'=>250, '?'=>250, '@'=>250, 'A'=>250, 'B'=>250, 'C'=>250, 'D'=>250, 'E'=>250, 'F'=>250, 'G'=>250, 'H'=>250, 'I'=>250, 'J'=>250, 'K'=>250, 'L'=>250, 'M'=>250, 'N'=>250, 'O'=>250, 'P'=>250, 'Q'=>250, 'R'=>250, 'S'=>250, 'T'=>250, 'U'=>250, 'V'=>250, 'W'=>250, 'X'=>250, 'Y'=>250, 'Z'=>250, '['=>250, '\\'=>250, ']'=>250, '^'=>250, '_'=>250, '`'=>250, 'a'=>250, 'b'=>250, 'c'=>250, 'd'=>250, 'e'=>250, 'f'=>250, 'g'=>250, 'h'=>250, 'i'=>250, 'j'=>250, 'k'=>250, 'l'=>250, 'm'=>250, 'n'=>250, 'o'=>250, 'p'=>250, 'q'=>250, 'r'=>250, 's'=>250, 't'=>250, 'u'=>250, 'v'=>250, 'w'=>250, 'x'=>250, 'y'=>250, 'z'=>250, '{'=>250, '|'=>250, '}'=>250, '~'=>250))
	);
	$this->CoreFonts = array('courier'=>'Courier', 'helvetica'=>'Helvetica', 'times'=>'Times-Roman', 'symbol'=>'Symbol', 'zapfdingbats'=>'ZapfDingbats');
}

protected function _checkoutput()
{
	if(PHP_SAPI!='cli')
	{
		if(headers_sent($file, $line))
			$this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
	}
}

protected function _getpagesize($size)
{
	if(is_string($size))
	{
		$size = strtolower($size);
		if(isset($this->StdPageSizes[$size]))
			$size = $this->StdPageSizes[$size];
		else
			$this->Error('Unknown page size: '.$size);
	}
	return $size;
}

protected function _beginstream()
{
	$this->_out('stream');
}

protected function _endstream()
{
	$this->_out('endstream');
}

protected function _putstream($s)
{
	$this->_out('stream');
	$this->_out($s);
	$this->_out('endstream');
}

protected function _out($s)
{
	if($this->state==2)
		$this->pages[$this->page] .= $s."\n";
	else
		$this->buffer .= $s."\n";
}

protected function _putpages()
{
	$nb = $this->page;
	for($n=1; $n<=$nb; $n++)
	{
		$this->_newobj();
		$this->_out('<</Type /Page');
		$this->_out('/Parent 1 0 R');
		$this->_out('/Resources <<');
		$this->_out('/Font <<');
		foreach($this->fonts as $k=>$font)
			$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
		$this->_out('>>');
		if(count($this->images)>0)
		{
			$this->_out('/XObject <<');
			foreach($this->images as $k=>$image)
				$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
			$this->_out('>>');
		}
		$this->_out('>>');
		if(isset($this->PageLinks[$n]))
		{
			$annots = '/Annots [';
			foreach($this->PageLinks[$n] as $pl)
			{
				$_rect = sprintf('%.2F %.2F %.2F %.2F', $pl[0], $pl[1], $pl[2], $pl[3]);
				$_rect = str_replace(',', ' ', $_rect);
				$annots .= '<</Type /Annot /Subtype /Link /Rect ['.$_rect.'] /Border [0 0 0] ';
				if(is_string($pl[4]))
					$annots .= '/A <</S /URI /URI '.$this->_textstring($pl[4]).'>>';
				else
				{
					$l = $this->links[$pl[4]];
					if(isset($this->PageInfo[$l[0]]))
						$h = $this->PageInfo[$l[0]]['h'];
					else
						$h = $this->h;
					$annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]', 1+2*$l[0], $h-$l[1]*$this->k);
				}
				$annots .= '>>';
			}
			$annots .= ']';
			$this->_out($annots);
		}
		$this->_out('/Contents '.($this->n+1).' 0 R>>');
		$this->_out('endobj');
		$this->_newobj();
		$this->_putstream($this->pages[$n]);
		$this->_out('endobj');
	}
	$this->offsets[1] = strlen($this->buffer);
	$this->_out('1 0 obj');
	$this->_out('<</Type /Pages');
	$kids = '/Kids [';
	for($i=0; $i<$nb; $i++)
		$kids .= (3+2*$i).' 0 R ';
	$this->_out($kids.']');
	$this->_out('/Count '.$nb);
	$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->DefPageSize[0]*$this->k, $this->DefPageSize[1]*$this->k));
	$this->_out('>>');
	$this->_out('endobj');
}

protected function _putfonts()
{
	foreach($this->fonts as $k=>$font)
	{
		$this->_newobj();
		$this->fonts[$k]['n'] = $this->n;
		$this->_out('<</Type /Font');
		$this->_out('/BaseFont /'.$font['name']);
		$this->_out('/Subtype /Type1');
		if($font['name']!='Symbol' && $font['name']!='ZapfDingbats')
			$this->_out('/Encoding /WinAnsiEncoding');
		$this->_out('>>');
		$this->_out('endobj');
	}
}

protected function _putimages()
{
	foreach($this->images as $k=>$image)
	{
		$this->_newobj();
		$this->images[$k]['n'] = $this->n;
		$this->_out('<</Type /XObject');
		$this->_out('/Subtype /Image');
		$this->_out('/Width '.$image['w']);
		$this->_out('/Height '.$image['h']);
		$this->_out('/ColorSpace /DeviceRGB');
		$this->_out('/BitsPerComponent 8');
		$this->_out('/Filter /DCTDecode');
		$this->_out('/Length '.$image['size']);
		$this->_out('>>');
		$this->_putstream($image['data']);
		$this->_out('endobj');
	}
}

protected function _putresources()
{
	$this->_putfonts();
	$this->_putimages();
	// Resource dictionary
	$this->offsets[2] = strlen($this->buffer);
	$this->_out('2 0 obj');
	$this->_out('<<');
	$this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
	$this->_out('/Font <<');
	foreach($this->fonts as $font)
		$this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
	$this->_out('>>');
	if(count($this->images)>0)
	{
		$this->_out('/XObject <<');
		foreach($this->images as $image)
			$this->_out('/I'.$image['i'].' '.$image['n'].' 0 R');
		$this->_out('>>');
	}
	$this->_out('>>');
	$this->_out('endobj');
}

protected function _putinfo()
{
	$this->_out('/Producer '.$this->_textstring('FPDF '.self::VERSION));
	if(!empty($this->metadata['Title']))
		$this->_out('/Title '.$this->_textstring($this->metadata['Title']));
	if(!empty($this->metadata['Author']))
		$this->_out('/Author '.$this->_textstring($this->metadata['Author']));
	if(!empty($this->metadata['Subject']))
		$this->_out('/Subject '.$this->_textstring($this->metadata['Subject']));
	if(!empty($this->metadata['Keywords']))
		$this->_out('/Keywords '.$this->_textstring($this->metadata['Keywords']));
	if(!empty($this->metadata['Creator']))
		$this->_out('/Creator '.$this->_textstring($this->metadata['Creator']));
	$this->_out('/CreationDate '.$this->_textstring('D:'.date('YmdHis')));
}

protected function _putcatalog()
{
	$this->_out('/Type /Catalog');
	$this->_out('/Pages 1 0 R');
	if($this->ZoomMode=='fullpage')
		$this->_out('/OpenAction [3 0 R /Fit]');
	elseif($this->ZoomMode=='fullwidth')
		$this->_out('/OpenAction [3 0 R /FitH null]');
	elseif($this->ZoomMode=='real')
		$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
	elseif(!is_string($this->ZoomMode))
		$this->_out('/OpenAction [3 0 R /XYZ null null '.sprintf('%.2F', $this->ZoomMode).']');
	if($this->LayoutMode=='single')
		$this->_out('/PageLayout /SinglePage');
	elseif($this->LayoutMode=='continuous')
		$this->_out('/PageLayout /OneColumn');
	elseif($this->LayoutMode=='two')
		$this->_out('/PageLayout /TwoColumnLeft');
}

protected function _puttrailer()
{
	$this->_out('/Size '.($this->n+1));
	$this->_out('/Root 2 0 R');
	$this->_out('/Info '.($this->n-1).' 0 R');
}

protected function _newobj()
{
	$this->n++;
	$this->offsets[$this->n] = strlen($this->buffer);
	$this->_out($this->n.' 0 obj');
}

protected function _dounderline($x, $y, $txt)
{
	$up = $this->CurrentFont['up'];
	$ut = $this->CurrentFont['ut'];
	$w = $this->GetStringWidth($txt)+$this->ws*substr_count($txt, ' ');
	return sprintf('%.2F %.2F %.2F %.2F re f', $x*$this->k, ($this->h-($y-$up/1000*$this->FontSize))*$this->k, $w*$this->k, -$ut/1000*$this->FontSizePt);
}

protected function _parsejpg($file)
{
	$a = getimagesize($file);
	if(!$a)
		$this->Error('Missing or incorrect image file: '.$file);
	if($a[2]!=2)
		$this->Error('Not a JPEG file: '.$file);
	$f = fopen($file, 'rb');
	$data = fread($f, filesize($file));
	fclose($f);
	return array('w'=>$a[0], 'h'=>$a[1], 'cs'=>$a['channels'], 'data'=>$data, 'size'=>strlen($data));
}

protected function _parsepng($file)
{
	$f = fopen($file, 'rb');
	if(!$f)
		$this->Error('Can\'t open image file: '.$file);
	$info = $this->_parsepngstream($f, $file);
	fclose($f);
	return $info;
}

protected function _parsepngstream($f, $file)
{
	// Check signature
	if($this->_readstream($f, 8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
		$this->Error('Not a PNG file: '.$file);
	// Read header chunk
	$this->_readstream($f, 4);
	if($this->_readstream($f, 4)!='IHDR')
		$this->Error('Incorrect PNG file: '.$file);
	$w = $this->_readint($f);
	$h = $this->_readint($f);
	$bpc = ord($this->_readstream($f, 1));
	if($bpc>8)
		$this->Error('16-bit depth not supported: '.$file);
	$ct = ord($this->_readstream($f, 1));
	if($ct==0 || $ct==4)
		$this->Error('Transparency is not supported: '.$file);
	if($ct==2)
		$cs = 'DeviceGray';
	elseif($ct==3)
		$cs = 'DeviceRGB';
	elseif($ct==4)
		$cs = 'DeviceGray';
	else
		$cs = 'DeviceRGB';
	$this->_readstream($f, 4);
	$parms = '/DecodeParms <<';
	if($ct==3)
		$parms .= '/Predictor 15 /Colors 1 /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
	else
		$parms .= '/Predictor 1 /Colors 1 /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
	$parms .= ' ';
	// Scan chunks looking for image data
	$pal = '';
	$trns = '';
	$data = '';
	do
	{
		$n = $this->_readint($f);
		$type = $this->_readstream($f, 4);
		if($type=='PLTE')
		{
			// Read palette
			$pal = $this->_readstream($f, $n);
			$this->_readstream($f, 4);
		}
		elseif($type=='tRNS')
		{
			// Read transparency info
			$t = $this->_readstream($f, $n);
			if($ct==0)
				$trns = array(ord(substr($t, 1, 1)));
			elseif($ct==2)
				$trns = array(ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1)));
			else
			{
				$pos = strpos($t, chr(0));
				if($pos!==false)
					$trns = array($pos);
			}
			$this->_readstream($f, 4);
		}
		elseif($type=='IDAT')
		{
			// Read image data
			$data .= $this->_readstream($f, $n);
			$this->_readstream($f, 4);
		}
		elseif($type=='IEND')
			break;
		else
			$this->_readstream($f, $n+4);
	}
	while($n);
	if($ct==3 && $pal=='')
		$this->Error('Missing palette in PNG file: '.$file);
	return array('w'=>$w, 'h'=>$h, 'cs'=>$cs, 'parms'=>$parms, 'pal'=>$pal, 'trns'=>$trns, 'data'=>$data, 'size'=>strlen($data));
}

protected function _readstream($f, $n)
{
	$res = '';
	while($n>0 && !feof($f))
	{
		$s = fread($f, $n);
		if($s===false)
			$this->Error('Error while reading stream');
		$res .= $s;
		$n -= strlen($s);
	}
	if($n>0)
		$this->Error('Unexpected end of stream');
	return $res;
}

protected function _readint($f)
{
	$a = unpack('Ni', $this->_readstream($f, 4));
	return $a['i'];
}

protected function _textstring($s)
{
	if($this->iconv)
		$s = iconv('UTF-8', 'UTF-16BE', $s);
	else
		$s = mb_convert_encoding($s, 'UTF-16BE', 'UTF-8');
	return '('.$this->_escape($s).')';
}

protected function _escape($s)
{
	$s = str_replace('\\', '\\\\', $s);
	$s = str_replace('(', '\\(', $s);
	$s = str_replace(')', '\\)', $s);
	$s = str_replace("\r", '\\r', $s);
	return $s;
}

protected function _UTF8toUTF16($s)
{
	if($this->iconv)
		return iconv('UTF-8', 'UTF-16BE', $s);
	else
		return mb_convert_encoding($s, 'UTF-16BE', 'UTF-8');
}

protected function _UTF8encode($s)
{
	if($this->iconv)
		return iconv('UTF-8', 'ISO-8859-1', $s);
	else
		return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
}

protected function GetStringWidth($s)
{
	$s = $this->_UTF8toUTF16($s);
	$cw = &$this->CurrentFont['cw'];
	$w = 0;
	$l = strlen($s);
	for($i=0; $i<$l; $i++)
	{
		$c = ord($s[$i]);
		if(isset($cw[$c]))
			$w += $cw[$c];
		elseif(isset($cw[ord($s[$i+1])]))
		{
			$w += $cw[ord($s[$i+1])];
			$i++;
		}
	}
	return $w*$this->FontSize/1000;
}

public function SetDrawColor($r, $g=null, $b=null)
{
	if(is_array($r))
	{
		list($r, $g, $b) = $r;
	}
	elseif($g===null)
	{
		$g = $r;
		$b = $r;
	}
	$this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
	if($this->page>0)
		$this->_out($this->DrawColor);
}

public function SetFillColor($r, $g=null, $b=null)
{
	if(is_array($r))
	{
		list($r, $g, $b) = $r;
	}
	elseif($g===null)
	{
		$g = $r;
		$b = $r;
	}
	$this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
	$this->ColorFlag = ($this->FillColor!=$this->TextColor);
	if($this->page>0)
		$this->_out($this->FillColor);
}

public function SetTextColor($r, $g=null, $b=null)
{
	if(is_array($r))
	{
		list($r, $g, $b) = $r;
	}
	elseif($g===null)
	{
		$g = $r;
		$b = $r;
	}
	$this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
	$this->ColorFlag = ($this->FillColor!=$this->TextColor);
	if($this->page>0)
		$this->_out($this->TextColor);
}

public function Line($x1, $y1, $x2, $y2)
{
	$this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1*$this->k, ($this->h-$y1)*$this->k, $x2*$this->k, ($this->h-$y2)*$this->k));
}

public function Rect($x, $y, $w, $h, $style='')
{
	$s = sprintf('%.2F %.2F %.2F %.2F re', $x*$this->k, ($this->h-$y)*$this->k, $w*$this->k, -$h*$this->k);
	if(strpos($style, 'F')!==false)
		$s .= ' f';
	if(strpos($style, 'D')!==false || strpos($style, 'S')!==false || $style=='')
		$s .= ' S';
	$this->_out($s);
}
}
?>
