<?php

namespace App\Http\Controllers;

use App\Models\SecurityGuard;
use App\Services\DocumentNormalizer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecurityGuardController extends Controller
{
    /* ================= INDEX ================= */

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);

        $q  = trim((string) $request->get('q', ''));
        $by = (string) $request->get('by', 'all'); // all | name | license | email

        $driver = DB::connection()->getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ilike' : 'like';

        $query = SecurityGuard::query()->orderBy('created_at', 'desc');

        if ($q !== '') {
            $query->where(function ($sub) use ($q, $by, $likeOp) {
                $term = '%' . $q . '%';

                if ($by === 'name') {
                    $sub->where('fullname', $likeOp, $term);
                    return;
                }

                if ($by === 'license') {
                    $sub->where('license_number', $likeOp, $term);
                    return;
                }

                if ($by === 'email') {
                    $sub->where('email_address', $likeOp, $term);
                    return;
                }

                $sub->where('fullname', $likeOp, $term)
                    ->orWhere('license_number', $likeOp, $term)
                    ->orWhere('email_address', $likeOp, $term);
            });
        }

        $securityGuards = $query->paginate($perPage)->withQueryString();

        return view('security-guards.index', compact('securityGuards', 'perPage'));
    }

    /* ================= CREATE ================= */

    public function create()
    {
        abort_unless(auth()->user()->can('guards.create'), 403);
        return view('security-guards.create');
    }

    /* ================= STORE ================= */

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('guards.create'), 403);

        $data  = $this->validatedData($request);
        $guard = SecurityGuard::create($data);

        $this->handleUploads($request, $guard);

        activity('security_guard')
            ->performedOn($guard)
            ->causedBy(Auth::user())
            ->log('Security guard created');

        return redirect()
            ->route('security-guards.index')
            ->with('success', 'Security guard created successfully.');
    }

    /* ================= SHOW ================= */

    public function show(SecurityGuard $securityGuard)
    {
        return view('security-guards.show', compact('securityGuard'));
    }

    /* ================= EDIT ================= */

    public function edit(SecurityGuard $securityGuard)
    {
        abort_unless(auth()->user()->can('guards.edit'), 403);
        return view('security-guards.edit', compact('securityGuard'));
    }

    /* ================= UPDATE ================= */

    public function update(Request $request, SecurityGuard $securityGuard)
    {
        abort_unless(auth()->user()->can('guards.edit'), 403);

        $data = $this->validatedData($request);
        $securityGuard->update($data);

        $this->handleUploads($request, $securityGuard, true);

        activity('security_guard')
            ->performedOn($securityGuard)
            ->causedBy(Auth::user())
            ->log('Security guard updated');

        return redirect()
            ->route('security-guards.index')
            ->with('success', 'Security guard updated successfully.');
    }

    /* ================= DESTROY ================= */

    public function destroy(SecurityGuard $securityGuard)
    {
        abort_unless(auth()->user()->can('guards.delete'), 403);

        foreach ($this->fileFields() as $field) {
            if (!$securityGuard->$field) continue;

            $oldPath = ltrim((string) $securityGuard->$field, '/');
            $oldPath = str_starts_with($oldPath, 'public/') ? substr($oldPath, 7) : $oldPath;

            Storage::disk('public')->delete($oldPath);

            // Also delete cached previews for this field
            $this->deletePdfPreviewCache((int) $securityGuard->id, $field);
        }

        // Also delete previews folder if any
        Storage::disk('public')->deleteDirectory("guards/{$securityGuard->id}/previews");

        $securityGuard->delete();

        activity('security_guard')
            ->performedOn($securityGuard)
            ->causedBy(Auth::user())
            ->log('Security guard deleted');

        return redirect()
            ->route('security-guards.index')
            ->with('success', 'Security guard deleted successfully.');
    }

    /* ================= VALIDATION ================= */

    private function validatedData(Request $request): array
    {
        // Phase 3: all guard documents are optional on create and edit.
        $fileRule = 'nullable';

        return $request->validate([
            'fullname'         => 'required|string|max:255',
            'dob' => ['nullable', 'date'],
            'email_address'    => 'nullable|email|max:255',
            'phone_number'     => 'nullable|string|max:30',
            'license_number'   => 'nullable|string|max:100',
            'license_exp_date' => 'nullable|date',
            'category'         => 'nullable|string|max:100',
            'adresse'          => 'nullable|string|max:500',
            'rtw_share_code'   => 'nullable|string|max:100',
            'share_code_expiry'=> 'nullable|date',
            'visa_status'      => 'nullable|string|max:100',
            'ni_number'        => 'nullable|string|max:100',
            'driving_license'  => 'nullable|string|max:100',
            'car'              => 'nullable|string|max:50',
            'city'             => 'nullable|string|max:100',

            // BANK
            'sort_code'        => 'nullable|digits:6',
            'account_number'   => 'nullable|digits:8',
            'beneficiary_name' => 'nullable|string|max:255',

            // FILES
            'profile_picture'     => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'sia_license'         => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'sia_license_back'    => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'driving_license_doc' => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'passport'            => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'evisa_ss'            => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'rtw_ss'              => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'proof_add1'          => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'proof_add2'          => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'ni_letter'           => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            
            
            'act_blue'   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
'act_orange' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
'act_green'  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',

'first_aid'  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',

'cctv_front' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
'cctv_back'  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',

'other_doc1' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
'other_doc2' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
            
            
        ]);
    }

    /* ================= FILE HANDLING ================= */

    private function handleUploads(Request $request, SecurityGuard $guard, bool $replace = false): void
    {
        $basePath = "guards/{$guard->id}";

        foreach ($this->fileFields() as $field) {
            if (!$request->hasFile($field)) continue;

            // Delete old file + cached previews for that field
            if ($replace && $guard->$field) {
                $oldPath = ltrim((string) $guard->$field, '/');
                $oldPath = str_starts_with($oldPath, 'public/') ? substr($oldPath, 7) : $oldPath;

                Storage::disk('public')->delete($oldPath);
                $this->deletePdfPreviewCache((int) $guard->id, $field);
            }

            $storedPath = DocumentNormalizer::process(
                $request->file($field),
                "public/{$basePath}",
                $field
            );

            $guard->$field = str_starts_with($storedPath, 'public/')
                ? substr($storedPath, 7)
                : $storedPath;

            $guard->save();
        }
    }

    private function fileFields(): array
    {
        return [
            'profile_picture',
            'sia_license',
            'sia_license_back',
            'driving_license_doc',
            'passport',
            'evisa_ss',
            'rtw_ss',
            'proof_add1',
            'proof_add2',
            'ni_letter',
            
            // NEW OPTIONAL DOCS
    'act_blue',
    'act_orange',
    'act_green',
    'first_aid',
    'cctv_front',
    'cctv_back',
    'other_doc1',
    'other_doc2',
            
            
        ];
    }
    
    
    private function customPdfFieldOptions(): array
{
    return [
        'fullname'          => 'Full Name',
        'dob'               => 'DOB',
        'email_address'     => 'Email',
        'phone_number'      => 'Phone',
        'license_number'    => 'License #',
        'license_exp_date'  => 'License Expiry',
        'category'          => 'Category',
        'adresse'           => 'Address',
        'rtw_share_code'    => 'RTW Share Code',
        'share_code_expiry' => 'Share-Code Expiry',
        'visa_status'       => 'Visa Status',
        'ni_number'         => 'NI Number',
        'driving_license'   => 'Driving License',
        'car'               => 'Car',
        'city'              => 'City',
        'sort_code'         => 'Sort Code',
        'account_number'    => 'Account Number',
        'beneficiary_name'  => 'Beneficiary Name',
    ];
}

private function customPdfDocumentOptions(): array
{
    return [
        'profile_picture'     => 'Profile Picture',
        'sia_license'         => 'SIA License',
        'sia_license_back'    => 'SIA License (Back)',
        'driving_license_doc' => 'Driving License',
        'passport'            => 'Passport',
        'evisa_ss'            => 'E-Visa Screenshot',
        'rtw_ss'              => 'RTW Screenshot',
        'proof_add1'          => 'Proof of Address 1',
        'proof_add2'          => 'Proof of Address 2',
        'ni_letter'           => 'NI Letter',

        'act_blue'            => 'ACT Blue',
        'act_orange'          => 'ACT Orange',
        'act_green'           => 'ACT Green',
        'first_aid'           => 'First Aid',
        'cctv_front'          => 'CCTV License Front',
        'cctv_back'           => 'CCTV License Back',
        'other_doc1'          => 'Other Document 1',
        'other_doc2'          => 'Other Document 2',
    ];
}

private function buildGuardPdfDocs(SecurityGuard $securityGuard, array $docFields): array
{
    $docs = [];

    foreach ($docFields as $field => $label) {
        $path = (string) ($securityGuard->$field ?? '');
        if ($path === '') {
            continue;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $url = Storage::disk('public')->url($path);
        $embeds = [];

        if (!Storage::disk('public')->exists($path)) {
            $docs[] = compact('field', 'label', 'path', 'ext', 'url') + ['embeds' => []];
            continue;
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $bytes = Storage::disk('public')->get($path);

            $mime = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png'         => 'image/png',
                'webp'        => 'image/webp',
                default       => 'application/octet-stream',
            };

            $embeds[] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        }

        if ($ext === 'pdf') {
            $absolute = Storage::disk('public')->path($path);

            $embeds = $this->pdfToCachedPreviewDataUris(
                guardId: (int) $securityGuard->id,
                field: $field,
                absolutePdfPath: $absolute,
                maxPages: 2,
                resolution: 120
            );
        }

        $docs[] = [
            'field'  => $field,
            'label'  => $label,
            'path'   => $path,
            'ext'    => $ext,
            'url'    => $url,
            'embeds' => $embeds,
        ];
    }

    return $docs;
}
    
    
    

    /* ================= PDF (Profile + Embedded Docs) ================= */

    public function pdf(SecurityGuard $securityGuard)
    {
        abort_unless(auth()->user()->can('guards.view'), 403);

        // Keep the request bounded
        @set_time_limit(120);

       $docFields = [
    'profile_picture'     => 'Profile Picture',
    'sia_license'         => 'SIA License (Front)',
    'sia_license_back'    => 'SIA License (Back)',
    'driving_license_doc' => 'Driving License',
    'passport'            => 'Passport',
    'evisa_ss'            => 'E-Visa Screenshot',
    'rtw_ss'              => 'RTW Screenshot',
    'proof_add1'          => 'Proof of Address 1',
    'proof_add2'          => 'Proof of Address 2',
    'ni_letter'           => 'NI Letter',

    'act_blue'            => 'ACT Blue',
    'act_orange'          => 'ACT Orange',
    'act_green'           => 'ACT Green',
    'first_aid'           => 'First Aid',
    'cctv_front'          => 'CCTV License Front',
    'cctv_back'           => 'CCTV License Back',
    'other_doc1'          => 'Other Document 1',
    'other_doc2'          => 'Other Document 2',
];

$docs = $this->buildGuardPdfDocs($securityGuard, $docFields);

        foreach ($docFields as $field => $label) {
            $path = (string) ($securityGuard->$field ?? '');
            if ($path === '') continue;

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $url = Storage::disk('public')->url($path);

            $embeds = [];

            if (!Storage::disk('public')->exists($path)) {
                $docs[] = compact('field', 'label', 'path', 'ext', 'url') + ['embeds' => []];
                continue;
            }

            // Normal images
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $bytes = Storage::disk('public')->get($path);

                $mime = match ($ext) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png'         => 'image/png',
                    'webp'        => 'image/webp',
                    default       => 'application/octet-stream',
                };

                $embeds[] = 'data:' . $mime . ';base64,' . base64_encode($bytes);
            }

            // PDFs -> embed cached previews (first N pages)
            if ($ext === 'pdf') {
                $absolute = Storage::disk('public')->path($path);

                $embeds = $this->pdfToCachedPreviewDataUris(
                    guardId: (int) $securityGuard->id,
                    field: $field,
                    absolutePdfPath: $absolute,
                    maxPages: 2,      // keep small to prevent timeouts
                    resolution: 120   // keep small to prevent huge output
                );
            }

            $docs[] = [
                'field'  => $field,
                'label'  => $label,
                'path'   => $path,
                'ext'    => $ext,
                'url'    => $url,
                'embeds' => $embeds,
            ];
        }

        $filename = 'guard-' . $securityGuard->id . '-' . Str::slug((string) $securityGuard->fullname) . '.pdf';

        $pdfBytes = Pdf::loadView('security-guards.pdf', [
            'guard' => $securityGuard,
            'docs'  => $docs,
        ])->setPaper('a4', 'portrait')->output();

        return response($pdfBytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
    
    
    
    public function customPdf(Request $request, SecurityGuard $securityGuard)
{
    abort_unless(auth()->user()->can('guards.view'), 403);

    @set_time_limit(120);

    $fieldOptions = $this->customPdfFieldOptions();
    $docOptions   = $this->customPdfDocumentOptions();

    $selectedFields = array_values(array_intersect(
        (array) $request->input('fields', []),
        array_keys($fieldOptions)
    ));

    $selectedDocs = array_values(array_intersect(
        (array) $request->input('docs', []),
        array_keys($docOptions)
    ));

    if (empty($selectedFields) && empty($selectedDocs)) {
        return redirect()
            ->route('security-guards.show', $securityGuard->id)
            ->with('error', 'Please select at least one field or document for Custom PDF.');
    }

    $docs = $this->buildGuardPdfDocs(
        $securityGuard,
        array_intersect_key($docOptions, array_flip($selectedDocs))
    );

    $filename = 'guard-custom-' . $securityGuard->id . '-' . Str::slug((string) $securityGuard->fullname) . '.pdf';

    $pdfBytes = Pdf::loadView('security-guards.custom-pdf', [
        'guard'          => $securityGuard,
        'fieldOptions'   => $fieldOptions,
        'selectedFields' => $selectedFields,
        'selectedDocs'   => $selectedDocs,
        'docs'           => $docs,
    ])->setPaper('a4', 'portrait')->output();

    return response($pdfBytes, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
}
    
    
    

    /**
     * PDF -> cached JPG previews -> data URIs
     * Stores: public/guards/{id}/previews/{field}-p{n}.jpg
     */
    private function pdfToCachedPreviewDataUris(
        int $guardId,
        string $field,
        string $absolutePdfPath,
        int $maxPages = 2,
        int $resolution = 120
    ): array {
        if (!extension_loaded('imagick')) {
            return [];
        }

        $maxPages = max(1, min($maxPages, 4));
        $resolution = max(72, min($resolution, 150));

        $disk = Storage::disk('public');
        $previewDir = "guards/{$guardId}/previews";
        $disk->makeDirectory($previewDir);

        // Use cache if exists
        $cached = [];
        for ($i = 1; $i <= $maxPages; $i++) {
            $previewPath = "{$previewDir}/{$field}-p{$i}.jpg";
            if ($disk->exists($previewPath)) {
                $bytes = $disk->get($previewPath);
                $cached[] = 'data:image/jpeg;base64,' . base64_encode($bytes);
            }
        }
        if (!empty($cached)) {
            return $cached;
        }

        // Generate (first N pages only)
        try {
            // prevent server death
            $im = new \Imagick();
            $im->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256);
            $im->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 256);

            $uris = [];

            for ($p = 0; $p < $maxPages; $p++) {
                $page = new \Imagick();
                $page->setResolution($resolution, $resolution);

                // Read only this page
                $page->readImage($absolutePdfPath . '[' . $p . ']');

                $page->setImageFormat('jpeg');
                $page->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $page->setImageCompressionQuality(80);

                if (defined('\Imagick::ALPHACHANNEL_REMOVE')) {
                    $page->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
                }
                $page->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $page->stripImage();

                $bytes = $page->getImageBlob();

                $previewPath = "{$previewDir}/{$field}-p" . ($p + 1) . ".jpg";
                $disk->put($previewPath, $bytes);

                $uris[] = 'data:image/jpeg;base64,' . base64_encode($bytes);

                $page->clear();
                $page->destroy();
            }

            $im->clear();
            $im->destroy();

            return $uris;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function deletePdfPreviewCache(int $guardId, string $field): void
    {
        $disk = Storage::disk('public');
        $previewDir = "guards/{$guardId}/previews";

        // delete all cached pages for this field (1..10 safe)
        for ($i = 1; $i <= 10; $i++) {
            $disk->delete("{$previewDir}/{$field}-p{$i}.jpg");
        }
    }
}
