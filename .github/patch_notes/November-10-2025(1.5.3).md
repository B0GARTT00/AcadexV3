## v1.5.3 - B
**FEATURES**
- Added 'notes' column to final_grades table for chairperson comments
- Implemented saveGradeNotes() method in ChairpersonController
- Added POST route for saving grade notes
- Updated chairperson/view-grades.blade.php with Notes column
- Added modal interface for adding/editing student grade notes
- Included AJAX functionality with SweetAlert confirmation
- Character limit of 1000 characters with counter
- Separate from auto-generated Passed/Failed remarks"
- Added global change to department formulas where departments can change their formulas
- Added Create and Delete Functionalities for Department Formulas(Password Protected for actions)
**IMPROVEMENTS**
- Modified GradeController to only increment graded student counter for new or changed scores
- implemented login security where one user can only be logged in on one device at a time