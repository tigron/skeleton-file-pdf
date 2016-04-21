<?php
/**
 * Pdf class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\File\Pdf;

use Skeleton\File\File;

class Pdf extends File {

	/**
	 * Get the number of pages
	 *
	 * @access public
	 */
	public function count_pages() {
		class_exists('TCPDF', true);
		$pdf = new \FPDI();
		$page_count = $pdf->setSourceFile($this->get_path());
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
			$pdf = new \FPDI();
			$pdf->setSourceFile($this->get_path());
			$templateId = $pdf->importPage($i);
			$size = $pdf->getTemplateSize($templateId);
			// create a page (landscape or portrait depending on the imported page size)
			if ($size['w'] > $size['h']) {
				$pdf->AddPage('L', [ $size['w'], $size['h'] ]);
			} else {
				$pdf->AddPage('P', [ $size['w'], $size['h'] ]);
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
	 * Append a PDF to this PDF
	 *
	 * @access public
	 * @param \Skeleton\File\Pdf\Pdf $pdf
	 */
	public function append(\Skeleton\File\Pdf\Pdf $pdf) {
		$result_pdf = new \FPDI();
		if (!file_exists($this->get_path())) {
			print_r($this);
			echo $this->get_path();
			die();
		}
		$page_count = $result_pdf->setSourceFile($this->get_path());

		/**
		 * Add the pages from this PDF
		 */
		for ($i=1; $i<= $page_count; $i++) {
			$templateId = $result_pdf->importPage($i);
			$size = $result_pdf->getTemplateSize($templateId);
			// create a page (landscape or portrait depending on the imported page size)
			if ($size['w'] > $size['h']) {
				$result_pdf->AddPage('L', [ $size['w'], $size['h'] ]);
			} else {
				$result_pdf->AddPage('P', [ $size['w'], $size['h'] ]);
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
			if ($size['w'] > $size['h']) {
				$result_pdf->AddPage('L', [ $size['w'], $size['h'] ]);
			} else {
				$result_pdf->AddPage('P', [ $size['w'], $size['h'] ]);
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

		foreach ($pdfs as $pdf) {
			if (get_class($pdf) != 'Skeleton\File\Pdf\Pdf') {
				throw new \Exception('Only PDF documents can be merged' . get_class($pdf));
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