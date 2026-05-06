<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Services\CertificatePDF;
use Illuminate\Http\Request;
use Illuminate\Support\Str;



class CertificateController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/certificates
    // Terbitkan sertifikat untuk enrollment yang sudah selesai (Admin only).
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'signer_left' => 'nullable|string|max:100',
            'signer_right' => 'nullable|string|max:100',
        ]);

        // Cek status enrollment
        $enrollment = Enrollment::with(['student.user', 'class'])->findOrFail($request->enrollment_id);

        if ($enrollment->status !== 'completed') {
            return response()->json([
                'error' => 'Certificate can only be issued for completed enrollments.',
            ], 400);
        }

        // Cek duplikat
        if (Certificate::where('enrollment_id', $enrollment->id)->exists()) {
            return response()->json([
                'error' => 'A certificate has already been issued for this enrollment.',
            ], 400);
        }

        $student = $enrollment->student;
        $course = $enrollment->class;
        $courseCode = Str::upper(Str::substr(preg_replace('/[^a-zA-Z]/', '', $course->course_name), 0, 4));
        $year = now()->year;

        $certNumber = Certificate::generateNumber($courseCode, $year);
        $fileName = 'cert_' . $enrollment->id . '_' . time() . '.pdf';
        $filePath = 'certificates/' . $fileName;

        // Generate PDF
        $pdf = $this->buildPDF(
            studentName: $student->user->name,
            courseName: $course->course_name,
            certNumber: $certNumber,
            issuedDate: now()->format('d F Y'),
            signerRight: $request->signer_right ?? 'Ketua Panitia',
            description: $course->description ?? '',
        );

        // Simpan ke storage/app/public/certificates/
        $storagePath = storage_path('app/public/' . $filePath);
        if (!is_dir(dirname($storagePath))) {
            mkdir(dirname($storagePath), 0755, true);
        }
        $pdf->Output('F', $storagePath);

        // Simpan record
        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'certificate_number' => $certNumber,
            'certificate_url' => asset('storage/' . $filePath),
            'issued_at' => now(),
            'issued_by' => auth()->user()->id ?? 'system',
            'certification_status' => 'issued',
        ]);

        return response()->json([
            'message' => 'Certificate issued successfully.',
            'data' => $certificate,
            'pdf_url' => $certificate->certificate_url,
        ], 201);
    }


    public function test(Request $request)
    {
        $description = $request->query(
            'description',
            "You've demonstrated dedication, learned new skills,\nand taken an important step in your learning journey."
        );
        $description = str_replace(["\r\n", "\r"], "\n", $description);

        $pdf = $this->buildPDF(
            studentName: $request->query('name', 'Guruh Putra Mahendra'),
            courseName: $request->query('course', 'Robotics Beginner Course'),
            certNumber: $request->query('cert_number', 'CERT-TEST-001'),
            issuedDate: $request->query('issued_date', now()->format('d F Y')),
            signerRight: $request->query('signer_right', 'Mr. Ilham'),
            description: $description,
        );

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="test_certificate.pdf"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/certificates/{id}/download
    // Tampilkan / download PDF sertifikat langsung di browser.
    // ─────────────────────────────────────────────────────────────────────────
    public function download(int $id)
    {
        $certificate = Certificate::with(['student.user', 'course', 'enrollment'])->findOrFail($id);

        $student = $certificate->student;
        $course = $certificate->course;

        $pdf = $this->buildPDF(
            studentName: $student->user->name,
            courseName: $course->course_name,
            certNumber: $certificate->certificate_number,
            issuedDate: $certificate->issued_at?->format('d F Y') ?? now()->format('d F Y'),
            signerRight: 'Ketua Panitia',
            description: $course->description ?? '',
        );

        // Stream PDF ke browser
        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="sertifikat_' . $certificate->certificate_number . '.pdf"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/certificates
    // Daftar semua sertifikat.
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $certificates = Certificate::with(['student.user', 'course', 'enrollment'])
            ->latest()
            ->get();

        return response()->json(['data' => $certificates]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/certificates/{id}
    // Detail satu sertifikat.
    // ─────────────────────────────────────────────────────────────────────────
    public function show(int $id)
    {
        $certificate = Certificate::with(['student.user', 'course', 'enrollment'])
            ->findOrFail($id);

        return response()->json(['data' => $certificate]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private: bangun objek CertificatePDF
    // ─────────────────────────────────────────────────────────────────────────
    private function buildPDF(
        string $studentName,
        string $courseName,
        string $certNumber,
        string $issuedDate,
        string $signerRight = '',
        string $description = '',
    ): CertificatePDF {
        $pdf = new CertificatePDF('L', 'mm', 'A4');   // Landscape A4
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pdf->drawBackground();
        $pdf->drawContent(
            studentName: $studentName,
            courseName: $courseName,
            certNumber: $certNumber,
            issuedDate: $issuedDate,
            signerRight: $signerRight,
            description: $description,
        );

        return $pdf;
    }
}
