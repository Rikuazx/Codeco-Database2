<?php

namespace App\Services;

require_once base_path('fpdf/fpdf.php');


class CertificatePDF extends \FPDF
{

    protected string $templatePath;

    public function __construct(string $orientation = 'L', string $unit = 'mm', string $size = 'A4')
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '120');

        parent::__construct($orientation, $unit, $size);
        $this->templatePath = base_path('templateSertifikat.png');
        $this->SetMargins(0, 0, 0);
        $this->AddFont(
            'FredokaOne-Regular',
            '',
            'FredokaOne-Regular.php',
            storage_path('fonts/')
        );
        $this->AddFont(
            'Montserrat-Bold',
            '',
            'Montserrat-Bold.php',
            storage_path('fonts/')
        );
        $this->AddFont(
            'Montserrat-Medium',
            '',
            'Montserrat-Medium.php',
            storage_path('fonts/')
        );
        $this->AddFont(
            'Montserrat-Regular',
            '',
            'Montserrat-Regular.php',
            storage_path('fonts/')
        );
        $this->AddFont(
            'GreatVibes',
            '',
            'GreatVibes-Regular.php',
            storage_path('fonts/')
        );
        $this->SetFont('GreatVibes', '', 36);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Gambar background template (full A4 landscape: 297×210 mm)
    // ─────────────────────────────────────────────────────────────────────────
    public function drawBackground(): void
    {
        if (file_exists($this->templatePath)) {
            $this->Image($this->templatePath, 0, 0, 297, 210);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tulis semua konten teks di atas template
    // ─────────────────────────────────────────────────────────────────────────
    public function drawContent(
        string $studentName,
        string $courseName,
        string $certNumber,
        string $issuedDate,
        string $signerRight,
        string $description = '',
    ): void {
        // ── Nama Peserta ─────────────────────────────────────────────────────
        $this->SetFont('FredokaOne-Regular', '', 40);
        $this->SetTextColor(79, 170, 188); // dark navy
        $this->SetXY(30, 85);
        $this->Cell(237, 18, $this->enc($studentName), 0, 0, 'C');

        // ── Nama Kursus ──────────────────────────────────────────────────────
        $this->SetFont('Montserrat-Bold', '', 20);
        $this->SetTextColor(79, 170, 188);
        $this->SetXY(30, 116);
        $this->Cell(237, 8, $this->enc($courseName), 0, 0, 'C');

        // ── Deskripsi (opsional) ─────────────────────────────────────────────
        if ($description !== '') {
            $this->SetFont('Montserrat-Medium', '', 12);
            $this->SetTextColor(100, 100, 100);
            $this->SetXY(40, 128);
            $this->MultiCell(217, 6, $this->enc($description), 0, 'C');
        }

        // ── Tanggal ───────────────────────────────────────────────────────────
        $this->SetFont('Montserrat-Medium', '', 10);
        $this->SetTextColor(80, 80, 80);
        $this->SetXY(48, 158);
        $this->Cell(80, 6, $this->enc($issuedDate), 0, 0, 'C');


        // ── Penandatangan Kanan ───────────────────────────────────────────────
        $this->SetFont('Montserrat-Bold', '', 10);
        $this->SetXY(171, 167);
        $this->Cell(80, 6, $this->enc($signerRight), 0, 0, 'C');

        // ── Nomor Sertifikat (pojok kiri bawah) ──────────────────────────────
        //$this->SetFont('Arial', '', 14);
        //$this->SetTextColor(120, 120, 120);
        //$this->SetXY(120, 60);
        //$this->Cell(237, 18, 'No: ' . $this->enc($certNumber), 0, 0, 'L');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Encode UTF-8 ke ISO-8859-1 (FPDF built-in font limitation)
    // ─────────────────────────────────────────────────────────────────────────
    protected function enc(string $text): string
    {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text) ?: $text;
    }
}
