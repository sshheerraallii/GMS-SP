<?php

namespace App\Http\Controllers;

use App\Models\SecurityGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentNormalizer;

class SecurityGuardController extends Controller
{
    /* ================= INDEX ================= */

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);

        $securityGuards = SecurityGuard::orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

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
            if (!$securityGuard->$field) {
                continue;
            }

            $oldPath = ltrim((string) $securityGuard->$field, '/');
            $oldPath = str_starts_with($oldPath, 'public/') ? substr($oldPath, 7) : $oldPath;

            Storage::disk('public')->delete($oldPath);
        }

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
        // store = POST, update = PUT/PATCH
        $isCreate = $request->isMethod('post');
        $fileRule = $isCreate ? 'required' : 'nullable';

        return $request->validate([
            'fullname'         => 'required|string|max:255',
            'email_address'    => 'nullable|email|max:255',
            'phone_number'     => 'nullable|string|max:30',
            'license_number'   => 'nullable|string|max:100',
            'license_exp_date' => 'nullable|date',
            'category'         => 'nullable|string|max:100',
            'adresse'          => 'nullable|string|max:500',
            'rtw_share_code'   => 'nullable|string|max:100',
            'visa_status'      => 'nullable|string|max:100',
            'ni_number'        => 'nullable|string|max:100',
            'driving_license'  => 'nullable|string|max:100',
            'car'              => 'nullable|string|max:50',
            'city'             => 'nullable|string|max:100',
            'sort_code'        => 'nullable|digits:6',
            'account_number'   => 'nullable|digits:8',

            // FILES
            'profile_picture'     => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'sia_license'         => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'driving_license_doc' => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'passport'            => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'evisa_ss'            => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'rtw_ss'              => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'proof_add1'          => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'proof_add2'          => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
            'ni_letter'           => "{$fileRule}|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx",
        ]);
    }

    /* ================= FILE HANDLING ================= */

    private function handleUploads(Request $request, SecurityGuard $guard, bool $replace = false): void
    {
        $basePath = "guards/{$guard->id}";

        foreach ($this->fileFields() as $field) {

            if (!$request->hasFile($field)) {
                continue;
            }

            // Delete old file (safe for both "guards/.." and "public/guards/..")
            if ($replace && $guard->$field) {
                $oldPath = ltrim((string) $guard->$field, '/');
                $oldPath = str_starts_with($oldPath, 'public/') ? substr($oldPath, 7) : $oldPath;

                Storage::disk('public')->delete($oldPath);
            }

            // Normalize + store (DocumentNormalizer returns "public/guards/{id}/..." or similar)
            $storedPath = DocumentNormalizer::process(
                $request->file($field),
                "public/{$basePath}",
                $field
            );

            // Store in DB without "public/" prefix (disk('public') expects relative path)
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
            'driving_license_doc',
            'passport',
            'evisa_ss',
            'rtw_ss',
            'proof_add1',
            'proof_add2',
            'ni_letter',
        ];
    }
}
