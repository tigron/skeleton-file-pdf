<?php
/**
 * Pdf class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\File\Pdf;

use Skeleton\File\File;
use setasign\Fpdi\TcpdfFpdi;
use setasign\Fpdi\PdfReader;

class Pdf extends File {

	/**
	 * Get the number of pages
	 *
	 * @access public
	 */
	public function count_pages() {
		$pdf = new TcpdfFpdi();

		try {
			$page_count = $pdf->setSourceFile($this->get_path());
		} catch (\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException $e) {
			return 0;
		}

		return $page_count;
	}

	/**
	 * Extract pages
	 *
	 * @access public
	 * @return array $pdfs
	 */
	public function extract_pages() {
		if ($this->count_pages() == 0) {
			throw new \Exception('Cannot extract pages, this PDF contains 0 pages');
		}

		$pages = [];
		for ($i=1; $i<= $this->count_pages(); $i++) {
			$pdf = new TcpdfFpdi();
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
			$pdf->setSourceFile($this->get_path());
			$templateId = $pdf->importPage($i);
			$size = $pdf->getTemplateSize($templateId);
			// create a page (landscape or portrait depending on the imported page size)
			if ($size['width'] > $size['height']) {
				$pdf->AddPage('L', [ $size['width'], $size['height'] ]);
			} else {
				$pdf->AddPage('P', [ $size['width'], $size['height'] ]);
			}
			// use the imported page
			$pdf->useTemplate($templateId);
			$content = $pdf->Output('ignored', 'S');

			$page = \Skeleton\File\File::store('page_' . $i . '.pdf', $content);
			$pages[] = $page;
		}
		return $pages;
	}

	/**
	 * Rotate
	 *
	 * @access public
	 * @param int $degrees
	 */
	public function rotate($degrees) {
		$pdf = new TcpdfFpdi();
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pagecount = $pdf->setSourceFile($this->get_path());
		for ($i=1; $i <= $pagecount; $i++) {
			$templateId = $pdf->importPage($i);
			$size = $pdf->getTemplateSize($templateId);
			if ($size['width'] > $size['height']) {
				$pdf->AddPage('L', [ $size['width'], $size['height'], 'Rotate' => $degrees ]);
			} else {
				$pdf->AddPage('P', [ $size['width'], $size['height'], 'Rotate' => $degrees ]);
			}
			$pdf->useTemplate($templateId);
		}
		$pdf->Rotate($degrees);
		$content = $pdf->Output('ignored', 'S');
		$file = \Skeleton\File\File::store('page_' . $i . '.pdf', $content);
		return $file;
	}

	/**
	 * Append a PDF to this PDF
	 *
	 * @access public
	 * @param \Skeleton\File\Pdf\Pdf $pdf
	 */
	public function append(\Skeleton\File\Pdf\Pdf $pdf) {
		$result_pdf = new TcpdfFpdi();
		$result_pdf->setPrintHeader(false);
		$result_pdf->setPrintFooter(false);
		if (!file_exists($this->get_path())) {
			throw new \Exception('Cannot append file. Filename "' . $this->get_path() . '" not found');
		}
		$page_count = $result_pdf->setSourceFile($this->get_path());

		/**
		 * Add the pages from this PDF
		 */
		for ($i=1; $i<= $page_count; $i++) {
			$templateId = $result_pdf->importPage($i);
			$size = $result_pdf->getTemplateSize($templateId);
			// create a page (landscape or portrait depending on the imported page size)
			if ($size['width'] > $size['height']) {
				$result_pdf->AddPage('L', [ $size['width'], $size['height'] ]);
			} else {
				$result_pdf->AddPage('P', [ $size['width'], $size['height'] ]);
			}
			// use the imported page
			$result_pdf->useTemplate($templateId);
		}

		/**
		 * Add the pages from the incoming PDF
		 */
		$page_count = $result_pdf->setSourceFile($pdf->get_path());

		for ($i=1; $i<= $page_count; $i++) {
			$templateId = $result_pdf->importPage($i);
			$size = $result_pdf->getTemplateSize($templateId);
			// create a page (landscape or portrait depending on the imported page size)
			if ($size['width'] > $size['height']) {
				$result_pdf->AddPage('L', [ $size['width'], $size['height'] ]);
			} else {
				$result_pdf->AddPage('P', [ $size['width'], $size['height'] ]);
			}
			// use the imported page
			$result_pdf->useTemplate($templateId);
		}

		$content = $result_pdf->Output('ignored', 'S');
		file_put_contents($this->get_path(), $content);
	}

	/**
	 * Get a pdf by ID
	 *
	 * @access public
	 * @param int $id
	 * @return Pdf $pdf
	 */
	public static function get_by_id($id) {
		return new Pdf($id);
	}

	/**
	 * Merge pdfs
	 *
	 * @access public
	 * @param string $filename
	 * @param array $pdfs
	 * @return \Skeleton\File\Pdf\Pdf $pdf
	 */
	public static function merge($filename, $pdfs = []) {
		if (count($pdfs) == 0) {
			throw new \Exception('At least 1 pdf has to be given to merge');
		}

		if (!class_exists(Config::$pdf_interface)) {
			throw new \Exception('Unknown classname given in Config::$pdf_interface');
		}

		$config_class = new Config::$pdf_interface;

		foreach ($pdfs as $pdf) {
			if (get_class($pdf) != get_class($config_class)) {
				throw new \Exception('Only PDF documents can be merged ' . get_class($pdf));
			}
		}

		$first = array_shift($pdfs);
		$result = $first->copy($filename);
		$result->name = $filename;
		$result->save();
		foreach ($pdfs as $pdf) {
			$result->append($pdf);
		}
		return $result;
	}
}
