<!-- resources/views/components/add-activity-modal.blade.php -->

@props(['subject', 'term', 'activityTypes' => []])

<div x-data="{ open: false }" class="relative">
    <!-- Trigger Button -->
    <button @click="open = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Activity
    </button>

    <!-- Modal Overlay -->
    <div
        x-show="open"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-40"
        x-cloak
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
            <!-- Close Button -->
            <button @click="open = false" class="absolute top-3 right-3 text-gray-600 hover:text-black">
                ✕
            </button>

            <h2 class="text-xl font-semibold mb-4">Add New Activity</h2>

            @php
                $typeOptions = collect($activityTypes)
                    ->map(fn ($type) => mb_strtolower($type))
                    ->unique()
                    ->values()
                    ->all();

                if (empty($typeOptions)) {
                    $typeOptions = ['quiz', 'ocr', 'exam'];
                }

                $formatActivityType = fn ($type) => ucwords(str_replace('_', ' ', $type));
            @endphp

            <form method="POST" action="{{ route('activities.store') }}">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <input type="hidden" name="term" value="{{ $term }}">

                <!-- Activity Type -->
                <div class="mb-4">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Activity Type</label>
                    <select name="type" id="type" class="w-full border rounded px-3 py-2" required>
                        <option value="">-- Select Type --</option>
                        @foreach($typeOptions as $type)
                            <option value="{{ $type }}">{{ $formatActivityType($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Title -->
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="title" class="w-full border rounded px-3 py-2" required>
                </div>

                <!-- Points -->
                <div class="mb-4">
                    <label for="points" class="block text-sm font-medium text-gray-700 mb-1">Number of Items</label>
                    <input type="number" name="points" id="points" min="1" class="w-full border rounded px-3 py-2" required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Save Activity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
