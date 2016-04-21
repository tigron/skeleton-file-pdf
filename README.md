# skeleton-file-pdf

## Description

This library adds PDF edit functionality for Skeleton\File\File objects

## Installation

Installation via composer:

    composer require tigron/skeleton-file-pdf

## Howto


Get a PDF 

	$file = \Skeleton\File\File::get_by_id(1);

Check if the file is a PDF

	if (!$file->is_pdf()) {
		return;
	}

Count the number of pages in the PDF

	$page_count = $file->count_pages();

Extract all pages from a PDF

	$pages = $file->extract_pages();

Merge different PDF documents into 1 PDF

	$new_pdf = \Skeleton\File\Pdf\Pdf::merge('new_document.pdf', array_reverse($pages));

Append a page to the PDF

	$new_pdf->append(array_shift($pages));
