<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Custom Guard PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .muted { color: #666; }
        .h1 { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
        .h2 { font-size: 14px; font-weight: 700; margin: 18px 0 8px; }
        .card { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 6px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { padding: 4px 6px; vertical-align: top; }
        .label { width: 190px; color: #555; }

        .doc { border: 1px solid #eee; padding: 10px; margin: 10px 0; }
        .doc-title { font-weight: 700; margin: 0 0 6px; }
        .doc-meta { font-size: 11px; color: #666; margin: 0 0 8px; }

        img { max-width: 100%; height: auto; }

        .doc { page-break-inside: avoid; }
        .pagebreak { page-break-before: always; }

        a { color: #0b63ce; text-decoration: underline; }
        .small { font-size: 11px; }
    </style>
</head>
<body>

    <div class="card">
        <div class="h1">{{ $guard->fullname }}</div>
        <div class="muted small">Security Guard Custom PDF</div>
    </div>

    @if(!empty($selectedFields))
        <div class="card">
            <div class="h2">Selected Details</div>
            <table class="grid">
                @foreach($selectedFields as $field)
                    @php
                        $label = $fieldOptions[$field] ?? $field;
                        $value = $guard->$field;

                        if ($field === 'dob' || $field === 'license_exp_date' || $field === 'share_code_expiry') {
                            $value = $value ? \Carbon\Carbon::parse($value)->format('d M Y') : '-';
                        } elseif ($field === 'account_number') {
                            $value = $guard->account_number ?? '-';
                            if (!empty($guard->beneficiary_name) && !in_array('beneficiary_name', $selectedFields, true)) {
                                $value .= ' — ' . $guard->beneficiary_name;
                            }
                        } else {
                            $value = filled($value) ? $value : '-';
                        }
                    @endphp

                    <tr>
                        <td class="label">{{ $label }}</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    @if(!empty($docs))
        <div class="card">
            <div class="h2">Selected Documents</div>

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

                @if($count > 1)
                    @foreach($embeds as $i => $src)
                        @if($i > 0)
                            <div class="pagebreak"></div>
                        @endif

                        <div class="doc">
                            <div class="doc-title">{{ $label }} (Page {{ $i + 1 }})</div>
                            <div class="doc-meta">
                                Embedded preview
                                @if($ext) — {{ $ext }} @endif
                            </div>
                            <img src="{{ $src }}" alt="{{ $label }}">
                        </div>
                    @endforeach
                @elseif($count === 1)
                    <div class="doc">
                        <div class="doc-title">{{ $label }}</div>
                        <div class="doc-meta">
                            Embedded
                            @if($ext) — {{ $ext }} @endif
                        </div>
                        <img src="{{ $embeds[0] }}" alt="{{ $label }}">
                    </div>
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
        </div>
    @endif

</body>
</html>