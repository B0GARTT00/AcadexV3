<div id="grade-section">
    @include('instructor.partials.term-stepper')
    @include('instructor.partials.activity-header', ['subject' => $subject, 'term' => $term, 'activityTypes' => $activityTypes])

    <form method="POST" action="{{ route('instructor.grades.store') }}" id="gradeForm" data-no-page-loader="true">
        @csrf
        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
        <input type="hidden" name="term" value="{{ $term }}">
    @include('instructor.partials.grade-table')
    </form>
</div>
