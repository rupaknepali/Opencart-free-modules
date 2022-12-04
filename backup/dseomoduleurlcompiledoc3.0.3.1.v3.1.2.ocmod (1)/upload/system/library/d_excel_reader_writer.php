<?php
use d_excel_reader_writer\XLSXWriter;
use d_excel_reader_writer\SpreadsheetReader;

class d_excel_reader_writer {
	private $writer;
	private $reader;
	
	public function __construct() {
		$this->writer = new XLSXWriter();
	}
	
	public function setAuthor($author = '') {
		$this->writer->setAuthor($author); 
	}
	
    public function setTempDir($tempdir = '') {
		$this->writer->setTempDir($tempdir);
	}

    public function setDefaultColumnWidth($columnWidth) {
		$this->writer->setDefaultColumnWidth($columnWidth);
	}

    public function setColumnWidths($columnWidths) {
		$this->writer->setColumnWidths($columnWidths);
	}
	
	public function writeSheet($data) {
		$this->writer->writeSheet($data);
	}
		
	public function writeSheetHeader($sheet_name, array $header_types, $suppress_row = false) {
		$this->writer->writeSheetHeader($sheet_name, $header_types, $suppress_row);
	}
	
	public function writeSheetRow($sheet_name, array $row, $style = null) {
		$this->writer->writeSheetRow($sheet_name, $row, $style);
	}
	
	public function writeToStdOut() {
		return $this->writer->writeToStdOut();
	}
	
	public function writeToString() {
		return $this->writer->writeToString();
	}
	
	public function writeToFile($filename) {
		$this->writer->writeToFile($filename);
	}
	
	public function readFromFile($filepath, $original_filename = false, $mimetype = false) {
		$this->reader = new SpreadsheetReader($filepath, $original_filename, $mimetype);
		
		return $this->reader;
	}
}
?>