<?php
namespace Mathielen\ImportEngine\Storage;

use Ddeboer\DataImport\Reader\CsvReader;
use Ddeboer\DataImport\Reader\ExcelReader;
use Ddeboer\DataImport\Reader\ReaderInterface;
use Ddeboer\DataImport\Writer\CsvWriter;
use Ddeboer\DataImport\Writer\ExcelWriter;
use Mathielen\DataImport\Writer\XmlWriter;
use Ddeboer\DataImport\Writer\WriterInterface;
use Mathielen\DataImport\Reader\XmlReader;
use Mathielen\ImportEngine\Storage\Format\CompressedFormat;
use Mathielen\ImportEngine\Storage\Format\Format;
use Mathielen\ImportEngine\Storage\Format\CsvFormat;
use Mathielen\ImportEngine\Storage\Format\ExcelFormat;
use Mathielen\ImportEngine\Storage\Format\XmlFormat;
use Mathielen\ImportEngine\Exception\InvalidConfigurationException;

class LocalFileStorage implements StorageFormatInterface
{

    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * @var Format
     */
    private $format;

    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var WriterInterface
     */
    private $writer;

    public function __construct(\SplFileInfo $file, Format $format)
    {
        $this->file = $file;
        $this->format = $format;
    }

    /**
     * (non-PHPdoc)
     * @see \Mathielen\ImportEngine\Storage\StorageFormatInterface::getFormat()
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * (non-PHPdoc)
     * @see \Mathielen\ImportEngine\Storage\StorageFormatInterface::getAvailableFormats()
     */
    public function getAvailableFormats()
    {
        return array(
            'csv',
            'excel',
            'xml',
            'zlib'
        );
    }

    public function getFields()
    {
        return $this->reader()->getFields();
    }

    /**
     * (non-PHPdoc)
     * @see \Mathielen\ImportEngine\Source\SourceInterface::reader()
     */
    public function reader()
    {
        if (!$this->reader) {
            $this->reader = $this->formatToReader($this->format, $this->file);
        }

        return $this->reader;
    }

    private function formatToReader(Format $format, \SplFileInfo $file)
    {
        if ($format instanceof CsvFormat) {
            $reader = new CsvReader($file->openFile(), $format->getDelimiter(), $format->getEnclosure(), $format->getEscape());
            $reader->setStrict(false);
            if ($format->isHeaderInFirstRow()) {
                $reader->setHeaderRowNumber(0);
                $reader->setColumnHeaders(array_map('trim', $reader->getColumnHeaders())); //TODO some header-collaborator?
            }

        } elseif ($format instanceof ExcelFormat) {
            $headerRowNumber = $format->isHeaderInFirstRow()?0:null;
            $reader = new ExcelReader($file->openFile(), $headerRowNumber, $format->getActivesheet());
            if ($format->isHeaderInFirstRow()) {
                $reader->setColumnHeaders(array_map('trim', $reader->getColumnHeaders())); //TODO some header-collaborator?
            }

        } elseif ($format instanceof XmlFormat) {
            $reader = new XmlReader($file->openFile(), $format->getXpath());

        } elseif ($format instanceof CompressedFormat && $format->getSubFormat()) {
            $reader = $this->formatToReader($format->getSubFormat(), $format->getInsideStream($file));

        } else {
            throw new InvalidConfigurationException("Cannot build reader. Unknown format: ".$format);
        }

        return $reader;
    }

    /**
     * (non-PHPdoc)
     * @see \Mathielen\ImportEngine\Source\StorageInterface::writer()
     */
    public function writer()
    {
        if (!$this->writer) {
            $this->writer = $this->formatToWriter($this->format, $this->file);
        }

        return $this->writer;
    }

    private function formatToWriter(Format $format, \SplFileInfo $file)
    {
        if ($format instanceof CsvFormat) {
            $writer = new CsvWriter($format->getDelimiter(), $format->getEnclosure(), fopen($file, 'w'));
            if ($format->isHeaderInFirstRow()) {
                //TODO how to handle header?
            }

        } elseif ($format instanceof ExcelFormat) {
            $writer = new ExcelWriter($file->openFile('w'), $format->getActivesheet(), $format->getExceltype());
            if ($format->isHeaderInFirstRow()) {
                //TODO how to handle header?
            }

        } elseif ($format instanceof XmlFormat) {
            $writer = new XMLWriter($file->openFile('w'));

        } elseif ($format instanceof CompressedFormat) {
            throw new \LogicException("Not implemented!");

        } else {
            throw new InvalidConfigurationException("Cannot build writer. Unknown format: ".$format);
        }

        return $writer;
    }

    /**
     * (non-PHPdoc)
     * @see \Mathielen\ImportEngine\Source\SourceInterface::info()
     */
    public function info()
    {
        return new StorageInfo(array(
            'name' => $this->file->getFilename(),
            'hash' => md5_file($this->file->getRealPath()),
            'format' => $this->getFormat(),
            'size' => $this->file->getSize(),
            'count' => count($this->reader())
        ));
    }

}
