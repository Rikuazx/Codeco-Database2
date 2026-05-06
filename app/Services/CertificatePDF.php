<?php

namespace App\Services;

// Load FPDF dari folder lokal di root project
require_once base_path('fpdf/fpdf.php');

/**
 * CertificatePDF
 *
 * Extends FPDF untuk generate sertifikat dengan template gambar.
 * Digunakan langsung oleh CertificateController via buildPDF().
 */
class CertificatePDF extends \FPDF
{
    /** Path ke file template sertifikat (PNG/JPG A4 landscape) */
    protected string $templatePath;

    public function __construct(string $orientation = 'L', string $unit = 'mm', string $size = 'A4')
    {
        parent::__construct($orientation, $unit, $size);
        $this->templatePath = base_path('templateSertifikat.png');
        $this->SetMargins(0, 0, 0);
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
        $this->SetFont('GreatVibes', '', 40);
        $this->SetTextColor(79, 170, 188); // dark navy
        $this->SetXY(30, 85);
        $this->Cell(237, 18, $this->enc($studentName), 0, 0, 'C');

        // ── Nama Kursus ──────────────────────────────────────────────────────
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(79, 170, 188);
        $this->SetXY(30, 118);
        $this->Cell(237, 8, $this->enc($courseName), 0, 0, 'C');

        // ── Deskripsi (opsional) ─────────────────────────────────────────────
        if ($description !== '') {
            $this->SetFont('Arial', '', 12);
            $this->SetTextColor(100, 100, 100);
            $this->SetXY(40, 128);
            $this->MultiCell(217, 6, $this->enc($description), 0, 'C');
        }

        // ── Tanggal ───────────────────────────────────────────────────────────
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(80, 80, 80);
        $this->SetXY(47, 158);
        $this->Cell(80, 6, $this->enc($issuedDate), 0, 0, 'C');


        // ── Penandatangan Kanan ───────────────────────────────────────────────
        $this->SetFont('Arial', 'B', 10);
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
