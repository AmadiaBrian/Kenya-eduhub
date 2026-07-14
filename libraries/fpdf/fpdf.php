<?php
// FPDF Library - Simplified version for PDF generation
// This is a minimal implementation for generating basic PDF documents

class FPDF {
    protected $page;
    protected $n;
    protected $offsets;
    protected $buffer;
    protected $pages;
    protected $state;
    protected $compress;
    protected $k;
    protected $DefOrientation;
    protected $CurOrientation;
    protected $OrientationChanges;
    protected $wPt;
    protected $hPt;
    protected $w;
    protected $h;
    protected $lMargin;
    protected $tMargin;
    protected $rMargin;
    protected $bMargin;
    protected $cMargin;
    protected $x;
    protected $y;
    protected $lasth;
    protected $LineWidth;
    protected $fontpath;
    protected $CoreFonts;
    protected $fonts;
    protected $FontFiles;
    protected $diffs;
    protected $images;
    protected $PageLinks;
    protected $links;
    protected $AutoPageBreak;
    protected $PageBreakTrigger;
    protected $InHeader;
    protected $InFooter;
    protected $AliasNbPages;
    protected $ZoomMode;
    protected $LayoutMode;
    protected $metadata;
    
    public function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->state = 0;
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = array();
        $this->offsets = array();
        $this->PageLinks = array();
        $this->links = array();
        $this->InHeader = false;
        $this->InFooter = false;
        $this->AliasNbPages = '{nb}';
        $this->ZoomMode = 'fullwidth';
        $this->LayoutMode = 'continuous';
        $this->metadata = array();
        $this->PDFVersion = '1.3';
        
        $this->k = 1;
        if ($unit == 'pt') {
            $this->k = 1;
        } elseif ($unit == 'mm') {
            $this->k = 72/25.4;
        } elseif ($unit == 'cm') {
            $this->k = 72/2.54;
        } elseif ($unit == 'in') {
            $this->k = 72;
        }
        
        $this->DefOrientation = strtoupper($orientation);
        $this->CurOrientation = $this->DefOrientation;
        $this->OrientationChanges = array();
        
        $size = $this->getPageSize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        
        $this->PageBreakTrigger = $this->hBreak = $size[1] * $this->k;
        $this->AutoPageBreak = true;
        $this->bMargin = 30 / $this->k;
        $this->cMargin = 10 / $this->k;
        
        $this->lMargin = 30 / $this->k;
        $this->tMargin = 30 / $this->k;
        $this->rMargin = 30 / $this->k;
        
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->fontpath = dirname(__FILE__).'/font/';
        
        $this->CoreFonts = array('courier', 'helvetica', 'times');
        $this->fonts = array();
        $this->FontFiles = array();
        $this->diffs = array();
        $this->images = array();
        $this->LineWidth = 0.567 / $this->k;
        $this->compress = true;
    }
    
    protected function getPageSize($size) {
        if (is_string($size)) {
            $size = strtolower($size);
            if ($size == 'a3') {
                return array(841.89, 1190.55);
            } elseif ($size == 'a4') {
                return array(595.28, 841.89);
            } elseif ($size == 'a5') {
                return array(420.94, 595.28);
            } elseif ($size == 'letter') {
                return array(612, 792);
            } elseif ($size == 'legal') {
                return array(612, 1008);
            }
        } else {
            return $size;
        }
        return array(595.28, 841.89);
    }
    
    public function SetMargins($left, $top, $right = null) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if ($right === null) {
            $right = $left;
        }
        $this->rMargin = $right;
    }
    
    public function SetLeftMargin($margin) {
        $this->lMargin = $margin;
        if ($this->page > 0 && $this->x < $margin) {
            $this->x = $margin;
        }
    }
    
    public function SetTopMargin($margin) {
        $this->tMargin = $margin;
    }
    
    public function SetRightMargin($margin) {
        $this->rMargin = $margin;
    }
    
    public function SetAutoPageBreak($auto, $margin = 0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }
    
    public function SetDisplayMode($zoom, $layout = 'continuous') {
        if ($zoom == 'fullpage' || $zoom == 'fullwidth' || $zoom == 'real' || $zoom == 'default' || !is_string($zoom)) {
            $this->ZoomMode = $zoom;
        } else {
            $this->Error('Incorrect zoom display mode: '.$zoom);
        }
        if ($layout == 'single' || $layout == 'continuous' || $layout == 'two' || $layout == 'default') {
            $this->LayoutMode = $layout;
        } else {
            $this->Error('Incorrect layout display mode: '.$layout);
        }
    }
    
    public function SetCompression($compress) {
        $this->compress = $compress;
    }
    
    public function SetTitle($title, $isUTF8 = false) {
        $this->metadata['Title'] = $isUTF8 ? $this->UTF8ToUTF16($title) : $title;
    }
    
    public function SetSubject($subject, $isUTF8 = false) {
        $this->metadata['Subject'] = $isUTF8 ? $this->UTF8ToUTF16($subject) : $subject;
    }
    
    public function SetAuthor($author, $isUTF8 = false) {
        $this->metadata['Author'] = $isUTF8 ? $this->UTF8ToUTF16($author) : $author;
    }
    
    public function SetKeywords($keywords, $isUTF8 = false) {
        $this->metadata['Keywords'] = $isUTF8 ? $this->UTF8ToUTF16($keywords) : $keywords;
    }
    
    public function SetCreator($creator, $isUTF8 = false) {
        $this->metadata['Creator'] = $isUTF8 ? $this->UTF8ToUTF16($creator) : $creator;
    }
    
    protected function UTF8ToUTF16($str) {
        return chr(254).chr(255).mb_convert_encoding($str, 'UTF-16LE', 'UTF-8');
    }
    
    public function AliasNbPages($alias = '{nb}') {
        $this->AliasNbPages = $alias;
    }
    
    public function Error($msg) {
        die('<b>FPDF error:</b> '.$msg);
    }
    
    public function Open() {
        $this->state = 1;
    }
    
    public function Close() {
        if ($this->state == 3) {
            return;
        }
        if ($this->page == 0) {
            $this->AddPage();
        }
        $this->InFooter = true;
        $this->Footer();
        $this->InFooter = false;
        $this->_endpage();
        $this->_enddoc();
    }
    
    public function AddPage($orientation = '', $size = '') {
        if ($this->state == 3) {
            $this->Error('The document is closed');
        }
        $family = $this->FontFamily;
        $style = $this->FontStyle;
        $size = $this->FontSizePt;
        if ($this->page > 0) {
            $this->InFooter = true;
            $this->Footer();
            $this->InFooter = false;
            $this->_endpage();
        }
        $this->_beginpage($orientation, $size);
        $this->_out('2 J');
        $this->SetFont($family, $style, $size);
        $this->SetY($this->tMargin);
    }
    
    protected function _beginpage($orientation, $size) {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        if ($orientation == '') {
            $orientation = $this->DefOrientation;
        } else {
            $orientation = strtoupper($orientation);
            if ($orientation != $this->DefOrientation) {
                $this->OrientationChanges[$this->page] = true;
            }
        }
        if ($size == '') {
            $size = $this->DefPageSize;
        } else {
            $size = $this->getPageSize($size);
        }
        if ($orientation != $this->CurOrientation || $size[0] != $this->CurPageSize[0] || $size[1] != $this->CurPageSize[1]) {
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
            if ($orientation == 'P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w * $this->k;
            $this->hPt = $this->h * $this->k;
            $this->PageBreakTrigger = $this->h - $this->bMargin;
            $this->PageSizes[$this->page] = array($this->wPt, $this->hPt);
        }
    }
    
    protected function _endpage() {
        $this->state = 1;
    }
    
    public function SetFont($family, $style = '', $size = 0) {
        if ($family == '') {
            $family = $this->FontFamily;
        }
        $family = strtolower($family);
        if ($family == 'arial') {
            $family = 'helvetica';
        } elseif ($family == 'symbol' || $family == 'zapfdingbats') {
            $family = '';
        }
        $style = strtoupper($style);
        if (strpos($style, 'U') !== false) {
            $this->underline = true;
            $style = str_replace('U', '', $style);
        } else {
            $this->underline = false;
        }
        if ($style == 'IB') {
            $style = 'BI';
        }
        if ($size == 0) {
            $size = $this->FontSizePt;
        }
        if ($this->FontFamily == $family && $this->FontStyle == $style && $this->FontSizePt == $size) {
            return;
        }
        $fontkey = $family.$style;
        if (!isset($this->fonts[$fontkey])) {
            if ($family == 'times' || $family == 'helvetica' || $family == 'courier') {
                $this->_loadfont($fontkey, $family);
            } else {
                $this->Error('Undefined font: '.$family.' '.$style);
            }
        }
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = $this->fonts[$fontkey];
        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }
    
    protected function _loadfont($fontkey, $family) {
        $name = 'Core';
        if ($family == 'times') {
            $name = 'Times-Roman';
        } elseif ($family == 'courier') {
            $name = 'Courier';
        } else {
            $name = 'Helvetica';
        }
        $style = '';
        if (strpos($fontkey, 'B') !== false) {
            $style .= 'Bold';
        }
        if (strpos($fontkey, 'I') !== false) {
            $style .= 'Italic';
        }
        if ($style == '') {
            $style = 'Regular';
        }
        $name .= '-' . $style;
        
        $i = count($this->fonts) + 1;
        $this->fonts[$fontkey] = array('i' => $i, 'name' => $name, 'type' => 'core');
    }
    
    public function SetFontSize($size) {
        if ($this->FontSizePt == $size) {
            return;
        }
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    }
    
    public function AddLink() {
        $n = count($this->links) + 1;
        $this->links[$n] = array(0, 0);
        return $n;
    }
    
    public function SetLink($link, $y = 0, $page = -1) {
        if ($page == -1) {
            $page = $this->page;
        }
        $this->links[$link] = array($page, $y);
    }
    
    public function Link($x, $y, $w, $h, $link) {
        $this->_out(sprintf('%.2F %.2F %.2F %.2F /Link %d Do', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $link));
    }
    
    public function Text($x, $y, $txt) {
        $txt = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
        $s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $txt);
        if ($this->underline && $txt != '') {
            $s .= ' ' . $this->_dounderline($x, $y, $txt);
        }
        if ($this->ColorFlag) {
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        }
        $this->_out($s);
    }
    
    protected function _dounderline($x, $y, $txt) {
        $up = $this->CurrentFont['up'];
        $ut = $this->CurrentFont['ut'];
        $w = $this->GetStringWidth($txt) + $this->ws * substr_count($txt, ' ');
        return sprintf('%.2F %.2F %.2F %.2F re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
    }
    
    public function AcceptPageBreak() {
        return $this->AutoPageBreak;
    }
    
    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
        $k = $this->k;
        if ($this->y + $h > $this->PageBreakTrigger && $this->AcceptPageBreak()) {
            $this->AddPage($this->CurOrientation);
            $this->y = $this->tMargin;
        }
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $s = '';
        if ($fill || $border == 1) {
            if ($fill) {
                $op = ($border == 1) ? 'B' : 'f';
            } else {
                $op = 'S';
            }
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        }
        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (strpos($border, 'L') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
            }
            if (strpos($border, 'T') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
            }
            if (strpos($border, 'R') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
            if (strpos($border, 'B') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
        }
        if ($txt !== '') {
            if ($align == 'R') {
                $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            } elseif ($align == 'C') {
                $dx = ($w - $this->GetStringWidth($txt)) / 2;
            } else {
                $dx = $this->cMargin;
            }
            if ($this->ColorFlag) {
                $s .= 'q ' . $this->TextColor . ' ';
            }
            $txt2 = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt2);
            if ($this->underline) {
                $s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->FontSize, $txt);
            }
            if ($this->ColorFlag) {
                $s .= ' Q';
            }
            if ($link) {
                $this->Link($this->x + $dx, $this->y + .5 * $h - .3 * $this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
            }
        }
        if ($s) {
            $this->_out($s);
        }
        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            if ($ln == 1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x += $w;
        }
    }
    
    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $b = 0;
        if ($border) {
            if ($border == 1) {
                $border = 'LTRB';
            }
            $b2 = '';
            if (strpos($border, 'L') !== false) {
                $b2 .= 'L';
            }
            if (strpos($border, 'T') !== false) {
                $b2 .= 'T';
            }
            if (strpos($border, 'R') !== false) {
                $b2 .= 'R';
            }
            if (strpos($border, 'B') !== false) {
                $b2 .= 'B';
            }
            $b2 = $b2[strlen($b2) - 1];
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                if ($this->ws > 0) {
                    $this->_out('0 Tw');
                }
                $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                    if ($this->ws > 0) {
                        $this->_out(sprintf('%.3F Tw', ($ns - 1) * $this->ws / 1000));
                    }
                    $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                } else {
                    if ($align == 'J') {
                        $this->_out(sprintf('%.3F Tw', ($ns - 1) * $this->ws / 1000));
                    }
                    $this->Cell($w, $h, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        if ($this->ws > 0) {
            $this->_out('0 Tw');
        }
        if ($border && strpos($border, 'B') !== false) {
            $b .= 'B';
        }
        $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
        $this->x = $this->lMargin;
    }
    
    public function Write($h, $txt, $link = '') {
        $cw = &$this->CurrentFont['cw'];
        $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                }
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($this->x > $this->lMargin) {
                        $this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
                        $this->x = $this->lMargin;
                    } else {
                        $i++;
                    }
                } else {
                    $this->Cell($w, $h, substr($s, $j, $sep - $j), 0, 2, '', 0, $link);
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                }
                $nl++;
            } else {
                $i++;
            }
        }
        if ($i != $j) {
            $this->Cell($l / 1000 * $this->FontSize, $h, substr($s, $j, $i - $j), 0, 0, '', 0, $link);
        }
    }
    
    public function Ln($h = null) {
        $this->x = $this->lMargin;
        if ($h === null) {
            $this->y += $this->lasth;
        } else {
            $this->y += $h;
        }
    }
    
    public function SetX($x) {
        $this->x = $x;
    }
    
    public function GetX() {
        return $this->x;
    }
    
    public function SetY($y) {
        $this->y = $y;
        if ($y < $this->tMargin) {
            $this->y = $this->tMargin;
        }
        if ($y > $this->h - $this->bMargin) {
            $this->y = $this->h - $this->bMargin;
        }
    }
    
    public function GetY() {
        return $this->y;
    }
    
    public function SetXY($x, $y) {
        $this->SetY($y);
        $this->SetX($x);
    }
    
    public function Output($name = '', $dest = '') {
        if ($this->state < 3) {
            $this->Close();
        }
        $dest = strtoupper($dest);
        if ($dest == '') {
            if ($name == '') {
                $name = 'doc.pdf';
                $dest = 'I';
            } else {
                $dest = 'F';
            }
        }
        switch ($dest) {
            case 'I':
                $this->_output($name);
                break;
            case 'D':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                $this->_output($name);
                break;
            case 'F':
                $f = fopen($name, 'wb');
                if (!$f) {
                    $this->Error('Unable to create output file: '.$name);
                }
                fwrite($f, $this->buffer);
                fclose($f);
                break;
            case 'S':
                return $this->buffer;
                break;
            default:
                $this->Error('Incorrect output destination: '.$dest);
        }
        return '';
    }
    
    protected function _output($name) {
        header('Content-Type: application/pdf');
        header('Content-Length: '.strlen($this->buffer));
        header('Content-Disposition: inline; filename="'.$name.'"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $this->buffer;
    }
    
    public function GetStringWidth($s) {
        $cw = &$this->CurrentFont['cw'];
        $w = 0;
        $l = strlen($s);
        for ($i = 0; $i < $l; $i++) {
            $w += $cw[$s[$i]];
        }
        return $w * $this->FontSize / 1000;
    }
    
    public function SetLineWidth($width) {
        $this->LineWidth = $width / $this->k;
        if ($this->page > 0) {
            $this->_out(sprintf('%.2F w', $this->LineWidth * $this->k));
        }
    }
    
    public function Line($x1, $y1, $x2, $y2) {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
    }
    
    public function Rect($x, $y, $w, $h, $style = '') {
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' || $style == 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
    }
    
    protected function _enddoc() {
        $this->_putheader();
        $this->_putpages();
        $this->_putresources();
        $this->_putcatalog();
        $this->_puttrailer();
        $this->_putinfo();
        $this->_putxref();
        $this->_puttrailerend();
    }
    
    protected function _putheader() {
        $this->buffer = '%PDF-'.$this->PDFVersion."\n";
    }
    
    protected function _putpages() {
        $nb = $this->page;
        for ($n = 1; $n <= $nb; $n++) {
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            if (isset($this->PageSizes[$n])) {
                $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->PageSizes[$n][0], $this->PageSizes[$n][1]));
            }
            $this->_out('/Resources 2 0 R');
            if (isset($this->PageLinks[$n])) {
                $annots = '/Annots [';
                foreach ($this->PageLinks[$n] as $pl) {
                    $annots .= $pl.' 0 R ';
                }
                $this->_out($annots.']');
            }
            $this->_out('/Contents '.($this->n + 1).' 0 R>>');
            $this->_out('endobj');
            $this->_newobj();
            $this->_out('<<');
            $this->_putstream($this->pages[$n]);
            $this->_out('endstream');
            $this->_out('endobj');
        }
        $this->offsets[1] = strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages');
        $kids = '/Kids [';
        for ($i = 1; $i <= $nb; $i++) {
            $kids .= (3 * $i).' 0 R ';
        }
        $this->_out($kids.']');
        $this->_out('/Count '.$nb);
        $this->_out('>>');
        $this->_out('endobj');
    }
    
    protected function _putresources() {
        $this->_putfonts();
        $this->_newobj();
        $this->offsets[2] = strlen($this->buffer);
        $this->_out('2 0 obj');
        $this->_out('<</ProcSet [/PDF /Text]');
        $this->_out('/Font <<');
        foreach ($this->fonts as $font) {
            $this->_out('/F'.$font['i'].' '.$font['n'].' 0 R');
        }
        $this->_out('>>');
        $this->_out('>>');
        $this->_out('endobj');
    }
    
    protected function _putfonts() {
        foreach ($this->fonts as $font) {
            $this->_newobj();
            $this->_out('<</Type /Font');
            $this->_out('/BaseFont /'.$font['name']);
            $this->_out('/Subtype /Type1');
            $this->_out('/Encoding /WinAnsiEncoding');
            $this->_out('>>');
            $this->_out('endobj');
            $font['n'] = $this->n;
        }
    }
    
    protected function _putcatalog() {
        $this->_newobj();
        $this->offsets[3] = strlen($this->buffer);
        $this->_out('3 0 obj');
        $this->_out('<</Type /Catalog');
        $this->_out('/Pages 1 0 R');
        $this->_out('/OpenAction [3 0 R /FitH null]');
        $this->_out('>>');
        $this->_out('endobj');
    }
    
    protected function _puttrailer() {
        $this->_newobj();
        $this->offsets[4] = strlen($this->buffer);
        $this->_out('4 0 obj');
        $this->_out('<</Size '.($this->n + 1));
        $this->_out('/Root 3 0 R');
        $this->_out('/Info 5 0 R');
        $this->_out('>>');
        $this->_out('endobj');
    }
    
    protected function _putinfo() {
        $this->_newobj();
        $this->offsets[5] = strlen($this->buffer);
        $this->_out('5 0 obj');
        $this->_out('<</Producer (FPDF '.FPDF_VERSION.')');
        if (!empty($this->title)) {
            $this->_out('/Title ('.$this->_escape($this->title).')');
        }
        if (!empty($this->subject)) {
            $this->_out('/Subject ('.$this->_escape($this->subject).')');
        }
        if (!empty($this->author)) {
            $this->_out('/Author ('.$this->_escape($this->author).')');
        }
        if (!empty($this->keywords)) {
            $this->_out('/Keywords ('.$this->_escape($this->keywords).')');
        }
        if (!empty($this->creator)) {
            $this->_out('/Creator ('.$this->_escape($this->creator).')');
        }
        $this->_out('/CreationDate (D:'.date('YmdHis').')');
        $this->_out('>>');
        $this->_out('endobj');
    }
    
    protected function _escape($s) {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('(', '\\(', $s);
        $s = str_replace(')', '\\)', $s);
        return $s;
    }
    
    protected function _putxref() {
        $this->_out('xref');
        $this->_out('0 '.($this->n + 1));
        $this->_out('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++) {
            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }
    }
    
    protected function _puttrailerend() {
        $this->_out('trailer');
        $this->_out('<</Size '.($this->n + 1));
        $this->_out('/Root 3 0 R');
        $this->_out('/Info 5 0 R');
        $this->_out('>>');
        $this->_out('startxref');
        $this->_out($this->offsets[1]);
        $this->_out('%%EOF');
    }
    
    protected function _newobj() {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_out($this->n.' 0 obj');
    }
    
    protected function _out($s) {
        if ($this->state == 2) {
            $this->pages[$this->page] .= $s."\n";
        } else {
            $this->buffer .= $s."\n";
        }
    }
    
    protected function _putstream($s) {
        $this->_out('/Length '.strlen($s));
        if ($this->compress) {
            $s = gzcompress($s);
            $this->_out('/Filter /FlateDecode');
        }
        $this->_out('>>');
        $this->_out('stream');
        $this->_out($s);
        $this->_out('endstream');
    }
    
    public function SetFillColor($r, $g = null, $b = null) {
        if ($g === null) {
            $g = $r;
            $b = $r;
        }
        $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
        if ($this->page > 0) {
            $this->_out($this->FillColor);
        }
    }
    
    public function SetTextColor($r, $g = null, $b = null) {
        if ($g === null) {
            $g = $r;
            $b = $r;
        }
        $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        $this->ColorFlag = ($this->FillColor != $this->TextColor);
        if ($this->page > 0) {
            $this->_out($this->TextColor);
        }
    }
    
    public function SetDrawColor($r, $g = null, $b = null) {
        if ($g === null) {
            $g = $r;
            $b = $r;
        }
        $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        if ($this->page > 0) {
            $this->_out($this->DrawColor);
        }
    }
    
    public function Header() {
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->PageNo().'/{nb}', 0, 0, 'C');
    }
    
    public function PageNo() {
        return $this->page;
    }
    
    protected function _dochecks() {
        if (strpos($this->buffer, "\x00\x25\x53\x50\x45") !== false) {
            $this->Error('This document already uses a compression method');
        }
    }
}

define('FPDF_VERSION', '1.7');
?>
