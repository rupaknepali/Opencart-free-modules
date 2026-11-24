<?php
namespace Opencart\System\Library\Extension\Pdfinvoice;
/**
 * A minimal PDF 1.4 writer: Helvetica / Helvetica-Bold text (Windows-1252), lines, filled
 * rectangles and one RGB image type. Enough for an invoice, with no third-party library.
 * Coordinates are in points from the TOP-left corner of the page.
 */
class Pdf {
	private float $width;
	private float $height;
	private array $pages = [];
	private int $page = -1;
	private array $images = [];
	private string $font = 'F1';
	private float $size = 10;

	// Helvetica and Helvetica-Bold advance widths (1/1000 em) for characters 32-126.
	private const REGULAR = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
	private const BOLD = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];

	public function __construct(string $paper = 'a4') {
		[$this->width, $this->height] = $paper == 'letter' ? [612.0, 792.0] : [595.28, 841.89];
	}

	public function getWidth(): float {
		return $this->width;
	}

	public function getHeight(): float {
		return $this->height;
	}

	public function addPage(): void {
		$this->pages[] = '';
		$this->page = count($this->pages) - 1;
	}

	public function pageCount(): int {
		return count($this->pages);
	}

	public function setPage(int $page): void {
		$this->page = $page;
	}

	public function setFont(bool $bold, float $size): void {
		$this->font = $bold ? 'F2' : 'F1';
		$this->size = $size;
	}

	public function setColor(float $r, float $g, float $b): void {
		$this->pages[$this->page] .= sprintf("%.3F %.3F %.3F rg %.3F %.3F %.3F RG\n", $r, $g, $b, $r, $g, $b);
	}

	/**
	 * UTF-8 to Windows-1252. Characters outside it (Cyrillic, CJK, ...) become "?".
	 */
	private function encode(string $text): string {
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return (string)mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
	}

	public function textWidth(string $text): float {
		$widths = $this->font == 'F2' ? self::BOLD : self::REGULAR;
		$total = 0;

		foreach (str_split($this->encode($text)) as $char) {
			$code = ord($char);
			$total += ($code >= 32 && $code <= 126) ? $widths[$code - 32] : 556;
		}

		return $total * $this->size / 1000;
	}

	/**
	 * $align: L, R (x is the right edge) or C (x is the center).
	 */
	public function text(float $x, float $y, string $text, string $align = 'L'): void {
		if ($text === '') {
			return;
		}

		if ($align == 'R') {
			$x -= $this->textWidth($text);
		} elseif ($align == 'C') {
			$x -= $this->textWidth($text) / 2;
		}

		$escaped = strtr($this->encode($text), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);

		$this->pages[$this->page] .= sprintf("BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $this->font, $this->size, $x, $this->height - $y, $escaped);
	}

	/**
	 * Split text into lines that fit $width with the current font.
	 */
	public function wrap(string $text, float $width): array {
		$lines = [];

		foreach (preg_split('/\R/u', $text) as $paragraph) {
			$line = '';

			foreach (preg_split('/\s+/u', trim($paragraph)) as $word) {
				$try = $line === '' ? $word : $line . ' ' . $word;

				if ($line !== '' && $this->textWidth($try) > $width) {
					$lines[] = $line;
					$line = $word;
				} else {
					$line = $try;
				}
			}

			$lines[] = $line;
		}

		return $lines;
	}

	public function line(float $x1, float $y1, float $x2, float $y2, float $thickness = 0.5): void {
		$this->pages[$this->page] .= sprintf("%.2F w %.2F %.2F m %.2F %.2F l S\n", $thickness, $x1, $this->height - $y1, $x2, $this->height - $y2);
	}

	public function rect(float $x, float $y, float $w, float $h): void {
		$this->pages[$this->page] .= sprintf("%.2F %.2F %.2F %.2F re f\n", $x, $this->height - $y - $h, $w, $h);
	}

	/**
	 * Draw a JPEG/PNG/GIF/WebP file (transparency is flattened onto white) inside a $max_w x $max_h box.
	 * Returns the drawn [width, height] in points, or null if GD can't read the file.
	 */
	public function image(string $file, float $x, float $y, float $max_w, float $max_h): ?array {
		$source = @imagecreatefromstring((string)@file_get_contents($file));

		if (!$source) {
			return null;
		}

		$w = imagesx($source);
		$h = imagesy($source);

		// Three pixels per point is sharp enough for print and keeps the file small.
		$scale = min(1, ($max_w * 3) / $w, ($max_h * 3) / $h);
		$pw = max(1, (int)round($w * $scale));
		$ph = max(1, (int)round($h * $scale));

		$canvas = imagecreatetruecolor($pw, $ph);
		imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
		imagecopyresampled($canvas, $source, 0, 0, 0, 0, $pw, $ph, $w, $h);

		$rgb = '';

		for ($py = 0; $py < $ph; $py++) {
			for ($px = 0; $px < $pw; $px++) {
				$c = imagecolorat($canvas, $px, $py);
				$rgb .= chr(($c >> 16) & 255) . chr(($c >> 8) & 255) . chr($c & 255);
			}
		}

		$name = 'I' . (count($this->images) + 1);
		$this->images[$name] = ['w' => $pw, 'h' => $ph, 'data' => gzcompress($rgb)];

		$ratio = min($max_w / $w, $max_h / $h, 1);
		$dw = $w * $ratio;
		$dh = $h * $ratio;

		$this->pages[$this->page] .= sprintf("q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n", $dw, $dh, $x, $this->height - $y - $dh, $name);

		return [$dw, $dh];
	}

	public function output(string $title = ''): string {
		$objects = [];
		$add = function (string $body) use (&$objects): int {
			$objects[] = $body;

			return count($objects);
		};

		$catalog = $add('');
		$pages = $add('');
		$f1 = $add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
		$f2 = $add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');

		$xobjects = '';

		foreach ($this->images as $name => $image) {
			$id = $add('<< /Type /XObject /Subtype /Image /Width ' . $image['w'] . ' /Height ' . $image['h'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /FlateDecode /Length ' . strlen($image['data']) . " >>\nstream\n" . $image['data'] . "\nendstream");
			$xobjects .= '/' . $name . ' ' . $id . ' 0 R ';
		}

		$resources = '<< /Font << /F1 ' . $f1 . ' 0 R /F2 ' . $f2 . ' 0 R >>' . ($xobjects ? ' /XObject << ' . $xobjects . '>>' : '') . ' >>';

		$kids = [];

		foreach ($this->pages as $content) {
			$stream = gzcompress($content);
			$content_id = $add('<< /Length ' . strlen($stream) . " /Filter /FlateDecode >>\nstream\n" . $stream . "\nendstream");
			$kids[] = $add(sprintf('<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.2F %.2F] /Resources %s /Contents %d 0 R >>', $pages, $this->width, $this->height, $resources, $content_id)) . ' 0 R';
		}

		$objects[$catalog - 1] = '<< /Type /Catalog /Pages ' . $pages . ' 0 R >>';
		$objects[$pages - 1] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';

		$info = $add('<< /Title (' . strtr($this->encode($title), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']) . ') /Producer (OpenCart PDF Invoice) /CreationDate (D:' . date('YmdHis') . ') >>');

		$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = [];

		foreach ($objects as $i => $body) {
			$offsets[] = strlen($pdf);
			$pdf .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
		}

		$xref = strlen($pdf);
		$pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

		foreach ($offsets as $offset) {
			$pdf .= sprintf("%010d 00000 n \n", $offset);
		}

		$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . ' /Root ' . $catalog . ' 0 R /Info ' . $info . " 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";

		return $pdf;
	}
}
