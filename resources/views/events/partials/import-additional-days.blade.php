{{--
    Import Additional Days — self-contained widget (own Alpine component,
    so it does not depend on the assignment grid's guardAssignment() state).
    Ported from the event edit page onto the assignment page. Posts to the
    existing importAdditionalDays / additionalDaysPreview routes; the
    controller returns back(), which reloads whichever page invoked it.
    Requires $event in scope (provided by the guards() view).
--}}
@can('events.edit')
    <div class="max-w-5xl mx-auto mt-8 border rounded-lg p-5 bg-gray-50"
         x-data="importAdditionalDaysWidget()">
        <h3 class="text-lg font-semibold mb-2">Add New Shift / Days</h3>

        <p class="text-sm text-gray-600 mb-2">
            Upload Excel to add more days to this event, or more guard slots on an
            existing date. Existing dates can be skipped, overwritten, or merged.
            Default action for duplicates is Skip.
        </p>

        <p class="text-xs text-amber-700 mb-4">
            Save any unsaved assignment changes above before importing — importing reloads this page.
        </p>

        <form action="{{ route('events.importAdditionalDays', $event) }}"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-4">
            @csrf

            <div>
                <label class="block font-medium mb-1">Excel File</label>
                <input type="file"
                       name="import_file"
                       @change="preview($event)"
                       accept=".xlsx,.xls"
                       class="w-full border rounded px-3 py-2 bg-white">
            </div>

            <div>
                <label class="block font-medium mb-1">Global Duplicate Action</label>
                <select name="global_action"
                        x-model="globalAction"
                        @change="applyGlobal()"
                        class="w-full border rounded px-3 py-2 bg-white">
                    <option value="skip">Skip duplicate dates</option>
                    <option value="overwrite">Overwrite duplicate dates</option>
                    <option value="merge">Merge duplicate dates</option>
                </select>
            </div>

            <template x-if="message">
                <p class="text-sm"
                   :class="error ? 'text-red-600' : 'text-green-700'"
                   x-text="message"></p>
            </template>

            <template x-if="dates.length > 0">
                <div class="border rounded bg-white overflow-hidden">
                    <div class="grid grid-cols-4 gap-2 bg-gray-100 px-3 py-2 text-sm font-semibold">
                        <div>Date</div>
                        <div>Rows</div>
                        <div>Status</div>
                        <div>Action</div>
                    </div>

                    <template x-for="item in dates" :key="item.date">
                        <div class="grid grid-cols-4 gap-2 px-3 py-2 border-t text-sm items-center">
                            <div x-text="item.date"></div>

                            <div x-text="item.row_count"></div>

                            <div>
                                <span x-show="item.is_duplicate" class="text-red-700 font-medium">Duplicate</span>
                                <span x-show="!item.is_duplicate" class="text-green-700 font-medium">New Date</span>
                            </div>

                            <div>
                                <template x-if="item.is_duplicate">
                                    <select :name="'date_actions[' + item.date + ']'"
                                            x-model="item.action"
                                            class="w-full border rounded px-2 py-1 bg-white">
                                        <option value="skip">Skip</option>
                                        <option value="overwrite">Overwrite</option>
                                        <option value="merge">Merge</option>
                                    </select>
                                </template>

                                <template x-if="!item.is_duplicate">
                                    <span class="text-gray-500">Will import</span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <button type="submit"
                    class="bg-emerald-600 text-white px-6 py-2 rounded hover:bg-emerald-700">
                Import
            </button>
        </form>
    </div>

    <script>
        function importAdditionalDaysWidget() {
            return {
                globalAction: 'skip',
                message: '',
                error: false,
                dates: [],

                async preview(event) {
                    const file = event.target.files?.[0];

                    this.message = '';
                    this.error = false;
                    this.dates = [];
                    this.globalAction = 'skip';

                    if (!file) return;

                    this.message = 'Reading Excel...';

                    const formData = new FormData();
                    formData.append('import_file', file);

                    try {
                        const response = await fetch('{{ route('events.additionalDaysPreview', $event) }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Import preview failed.');
                        }

                        this.dates = data.dates || [];
                        this.message = `Excel checked. ${data.row_count} row(s) found. Review duplicate actions before importing.`;
                        this.error = false;
                    } catch (e) {
                        console.error(e);
                        this.message = e.message || 'Failed to read Excel file.';
                        this.error = true;
                    }
                },

                applyGlobal() {
                    this.dates = this.dates.map((item) => {
                        if (item.is_duplicate) {
                            item.action = this.globalAction || 'skip';
                        }
                        return item;
                    });
                },
            };
        }
    </script>
@endcan
