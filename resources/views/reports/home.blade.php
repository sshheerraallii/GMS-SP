@extends('layouts.default')

@section('title', 'Reports')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
            <p class="text-sm text-gray-500">Choose a report type to continue.</p>
        </div>
        <div class="text-xs text-gray-500">
            Access: <span class="font-medium text-gray-900">Admin / Super Admin</span>
        </div>
    </div>

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <!-- Event Reports -->
        <a href="{{ route('reports.events.index') }}"
           class="group bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden">
            <div class="p-6 flex items-start gap-4">
                <div class="h-12 w-12 rounded-xl bg-gray-900 text-white flex items-center justify-center">
                    <!-- Calendar Icon (inline SVG) -->
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 2v4M16 2v4M3 10h18"/>
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                    </svg>
                </div>

                <div class="flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">Event Reports</h2>
                        <span class="text-gray-400 group-hover:text-gray-700 transition">→</span>
                    </div>

                    <p class="text-sm text-gray-500 mt-1">
                        Event summary + detailed day-wise billing view.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">
                            Summary
                        </span>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">
                            Day-wise breakdown
                        </span>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">
                            Event totals
                        </span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-sm text-gray-700">
                Open Event Reports
            </div>
        </a>

        <!-- Guard Reports -->
        <a href="{{ route('reports.guards.index') }}"
           class="group bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden">
            <div class="p-6 flex items-start gap-4">
                <div class="h-12 w-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center">
                    <!-- User Icon (inline SVG) -->
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21a8 8 0 0 0-16 0"/>
                        <circle cx="12" cy="8" r="4"/>
                    </svg>
                </div>

                <div class="flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">Guard Reports</h2>
                        <span class="text-gray-400 group-hover:text-gray-700 transition">→</span>
                    </div>

                    <p class="text-sm text-gray-500 mt-1">
                        Payroll-style report per guard per event (hours × pay rate).
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                            Per guard
                        </span>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                            Per event
                        </span>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">
                            Totals
                        </span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-sm text-gray-700">
                Open Guard Reports
            </div>
        </a>

        @can('view-guard-statement')
        <!-- Guard Statement -->
        <a href="{{ route('reports.guardStatement.form') }}"
           class="group bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden">
            <div class="p-6 flex items-start gap-4">
                <div class="h-12 w-12 rounded-xl bg-gray-800 text-white flex items-center justify-center">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 2v6h6"/>
                        <path d="M8 13h8M8 17h8"/>
                    </svg>
                </div>

                <div class="flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">Guard Statement</h2>
                        <span class="text-gray-400 group-hover:text-gray-700 transition">&rarr;</span>
                    </div>

                    <p class="text-sm text-gray-500 mt-1">
                        On-demand PDF for one guard over a date range (hours &times; each event's pay rate).
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">Date range</span>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">Per guard</span>
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">PDF</span>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-sm text-gray-700">
                Open Guard Statement
            </div>
        </a>
        @endcan

    </div>

    <!-- Footer hint -->
    <div class="text-xs text-gray-500">
        Tip: Event reports are client-facing (billing). Guard reports are internal (payroll). Rates are isolated.
    </div>

</div>
@endsection
