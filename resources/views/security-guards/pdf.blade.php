<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Guard PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .muted { color: #666; }
        .h1 { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
        .h2 { font-size: 14px; font-weight: 700; margin: 18px 0 8px; }
        .card { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 6px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 4px 6px; vertical-align: top; }
        .label { width: 170px; color: #555; }

        /* Doc blocks */
        .doc { border: 1px solid #eee; padding: 10px; margin: 10px 0; }
        .doc-title { font-weight: 700; margin: 0 0 6px; }
        .doc-meta { font-size: 11px; color: #666; margin: 0 0 8px; }

        /* Images */
        img { max-width: 100%; height: auto; }

        /* Page behavior (DomPDF-safe) */
        .doc { page-break-inside: avoid; }
        .pagebreak { page-break-before: always; }

        a { color: #0b63ce; text-decoration: underline; }
        .small { font-size: 11px; }
    </style>
</head>
<body>

    <div class="card">
        <div class="h1">{{ $guard->fullname }}</div>
        <div class="muted small">Security Guard Full PDF</div>
    </div>

    <div class="card">
        <div class="h2">Basic Details</div>
        <table class="grid">
            <tr><td class="label">Email</td><td>{{ $guard->email_address ?? '-' }}</td></tr>
            <tr><td class="label">Phone</td><td>{{ $guard->phone_number ?? '-' }}</td></tr>
            <tr><td class="label">License #</td><td>{{ $guard->license_number ?? '-' }}</td></tr>
            <tr><td class="label">License Expiry</td><td>{{ $guard->license_exp_date ?? '-' }}</td></tr>
            <tr><td class="label">Category</td><td>{{ $guard->category ?? '-' }}</td></tr>
            <tr><td class="label">Address</td><td>{{ $guard->adresse ?? '-' }}</td></tr>
            <tr><td class="label">RTW Share Code</td><td>{{ $guard->rtw_share_code ?? '-' }}</td></tr>
            <tr><td class="label">Visa Status</td><td>{{ $guard->visa_status ?? '-' }}</td></tr>
            <tr><td class="label">NI Number</td><td>{{ $guard->ni_number ?? '-' }}</td></tr>
            <tr><td class="label">Driving License</td><td>{{ $guard->driving_license ?? '-' }}</td></tr>
            <tr><td class="label">Car</td><td>{{ $guard->car ?? '-' }}</td></tr>
            <tr><td class="label">City</td><td>{{ $guard->city ?? '-' }}</td></tr>
        </table>
    </div>

    <div class="card">
        <div class="h2">Bank Details</div>
        <table class="grid">
            <tr><td class="label">Sort Code</td><td>{{ $guard->sort_code ?? '-' }}</td></tr>
            <tr>
                <td class="label">Account Number</td>
                <td>
                    {{ $guard->account_number ?? '-' }}
                    @if(!empty($guard->beneficiary_name))
                        <span class="muted"> — </span><strong>{{ $guard->beneficiary_name }}</strong>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <div class="h2">Documents</div>

        @if(empty($docs))
            <div class="muted">No documents uploaded.</div>
        @else
            @foreach($docs as $doc)
                @php
                    $label  = $doc['label'] ?? 'Document';
                    $ext    = strtoupper((string)($doc['ext'] ?? ''));
                    $url    = (string)($doc['url'] ?? '');
                    $embeds = $doc['embeds'] ?? [];
                    $count  = is_array($embeds) ? count($embeds) : 0;
                @endphp

                @if(!is_array($embeds))
                    @php $embeds = []; $count = 0; @endphp
                @endif

                {{-- If multiple embeds (PDF preview pages), make EACH page its own .doc and force a page break between --}}
                @if($count > 1)
                    @foreach($embeds as $i => $src)
                        @if($i > 0)
                            <div class="pagebreak"></div>
                        @endif

                        <div class="doc">
                            <div class="doc-title">
                                {{ $label }} (Page {{ $i + 1 }})
                            </div>
                            <div class="doc-meta">
                                Embedded preview
                                @if($ext) — {{ $ext }} @endif
                            </div>

                            <img src="{{ $src }}" alt="{{ $label }}">
                        </div>
                    @endforeach

                {{-- Single embed (image or single-page preview) --}}
                @elseif($count === 1)
                    <div class="doc">
                        <div class="doc-title">{{ $label }}</div>
                        <div class="doc-meta">
                            Embedded
                            @if($ext) — {{ $ext }} @endif
                        </div>

                        <img src="{{ $embeds[0] }}" alt="{{ $label }}">
                    </div>

                {{-- Not embeddable --}}
                @else
                    <div class="doc">
                        <div class="doc-title">{{ $label }}</div>

                        <div class="muted small">
                            Not embeddable
                            @if($ext) ({{ $ext }}) @endif
                            . Open from:
                            <br>
                            @if($url !== '')
                                <a href="{{ $url }}">{{ $url }}</a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </div>
                    </div>
                @endif

            @endforeach
        @endif
    </div>

</body>
</html>
