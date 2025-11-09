## v1.5.0 - B
**GENERAL CHANGES**
- Added Password Protected Update When the Admin Tries to Update the formula with currently existing data

**FIXES**
- Fixed the URL Getting Cached when logging out from the Grades section in the instructor
- When submitting the scores of Grades, it now uses AJAX to update the Grade instead of refresing the page, and added a success popup when there are no errors.
- Fixed the issue when logging out in the Grades Table section triggering a failed grade submission and caching the url of that page.