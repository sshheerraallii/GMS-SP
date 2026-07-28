@extends('layouts.default')

@section('content')
<div class="max-w-2xl mx-auto mt-20 text-center"
     x-data="{
        mood: '',
        messages: {
            great: 'Momentum matters. Use today to push things forward.',
            okay: 'Consistency beats intensity. Steady progress is still progress.',
            not_good: 'Bad days don’t break systems. They refine them.',
            stressed: 'Control what you can. One task at a time.',
            ready: 'Execution turns plans into results. Start with the next action.'
        }
     }">

    <h1 class="text-3xl font-semibold text-gray-800 mb-2">
        Welcome to Secure Premises
    </h1>

    <p class="text-gray-600 mb-8">
        How are you doing today?
    </p>

    <!-- Dropdown -->
    <div class="mb-8">
        <select x-model="mood"
                class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <option value="">Select an option</option>
            <option value="great">I’m feeling great</option>
            <option value="okay">Doing okay</option>
            <option value="not_good">Not too good</option>
            <option value="stressed">Feeling stressed</option>
            <option value="ready">Ready to work</option>
        </select>
    </div>

    <!-- Message -->
    <div x-show="mood" x-transition
         class="bg-gray-100 border border-gray-200 rounded-md p-6 text-gray-700 text-sm">
        <p x-text="messages[mood]"></p>
    </div>

</div>
@endsection
