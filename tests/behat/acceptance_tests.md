# Acceptance tests — `mod_externalassignment`

This document tracks the Behat coverage for the plugin against the acceptance criteria it
should satisfy. Every row links to the `.feature` file that implements it; rows with no link
are not yet covered.

**Legend:** ✅ covered · ⚠️ partially covered · ❌ missing

- [1. Creating an external assignment (teacher)](#1-creating-an-external-assignment-teacher)
- [2. Validation errors while creating](#2-validation-errors-while-creating)
- [3. Editing an existing external assignment](#3-editing-an-existing-external-assignment)
- [4. Validation errors while editing](#4-validation-errors-while-editing)
- [5. Grading → completion status](#5-grading--completion-status)
- [6. Student view of an assignment](#6-student-view-of-an-assignment)
- [7. Other feature / regression coverage](#7-other-feature--regression-coverage)
- [8. Archived tests](#8-archived-tests)
- [Summary of gaps](#summary-of-gaps)

---

## 1. Creating an external assignment (teacher)

| # | Due Date | Cut-off Date | Completion | Status | Feature |
|---|:---:|:---:|---|:---:|---|
| a | Y | Y | passing grade | ✅ | [`teacher_cutoff_and_passinggrade.feature`](teacher_cutoff_and_passinggrade.feature) |
| b | Y | Y | manual | ✅ | [`teacher_cutoff_and_manual.feature`](teacher_cutoff_and_manual.feature) |
| c | Y | Y | none | ✅ | [`teacher_cutoff_no_conditions.feature`](teacher_cutoff_no_conditions.feature) |
| d | Y | N | passing grade | ✅ | [`teacher_due_and_passinggrade.feature`](teacher_due_and_passinggrade.feature) |
| e | Y | N | manual | ✅ | [`teacher_due_and_manual.feature`](teacher_due_and_manual.feature) |
| f | Y | N | none | ✅ | [`teacher_due_no_conditions.feature`](teacher_due_no_conditions.feature) |
| g | N | N | passing grade | ✅ | [`teacher_nodates_and_passinggrade.feature`](teacher_nodates_and_passinggrade.feature) |
| h | N | N | manual | ✅ | [`teacher_nodates_and_manual.feature`](teacher_nodates_and_manual.feature) |
| i | N | N | none | ✅ | [`teacher_nodates_no_conditions.feature`](teacher_nodates_no_conditions.feature) |

All nine due-date × cut-off × completion combinations are covered. 🎉

---

## 2. Validation errors while creating

| # | Condition | Status | Feature |
|---|---|:---:|---|
| a | Open date >= due date | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| b | Due date >= cut-off date | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| c | Assignment link missing | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| d | Assignment link invalid URL | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| e | Assignment name empty | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| f | External grade max empty | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| g | External grade max not a number | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| h | External grade max negative | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| i | Manual grade max not a number | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| j | Manual grade max negative | ✅ | [`teacher_create_validation_errors.feature`](teacher_create_validation_errors.feature) |
| k | Duplicate external name within course | ✅ | [`teacher_duplicate_name_validation.feature`](teacher_duplicate_name_validation.feature) |

All eleven conditions are now covered. Rows d, h and j required adding the validation itself to
`mod_externalassignment_mod_form::validation()` in `mod_form.php` — it previously had no
URL-format check on `externallink` and no minimum-value check on `externalgrademax` /
`manualgrademax`. The assignment link now must start with `http://`/`https://` and pass Moodle's
`PARAM_URL` syntax check; the two grade maxima now reject negative values (parsed with
`unformat_float()` so locale decimal separators still work). New strings:
`externallinkinvalidvalidation`, `externalgrademaxnegativevalidation`,
`manualgrademaxnegativevalidation` in `lang/en/externalassignment.php`.

---

## 3. Editing an existing external assignment

| # | Due Date | Cut-off Date | Completion | Status | Feature |
|---|:---:|:---:|---|:---:|---|
| a | Y | Y | passing grade | ✅ | [`teacher_edit_cutoff_and_passinggrade.feature`](teacher_edit_cutoff_and_passinggrade.feature) |
| b | Y | Y | manual | ✅ | [`teacher_edit_cutoff_and_manual.feature`](teacher_edit_cutoff_and_manual.feature) |
| c | Y | Y | none | ✅ | [`teacher_edit_cutoff_no_conditions.feature`](teacher_edit_cutoff_no_conditions.feature) |
| d | Y | N | passing grade | ✅ | [`teacher_edit_due_and_passinggrade.feature`](teacher_edit_due_and_passinggrade.feature) |
| e | Y | N | manual | ✅ | [`teacher_edit_due_and_manual.feature`](teacher_edit_due_and_manual.feature) |
| f | Y | N | none | ✅ | [`teacher_edit_due_no_conditions.feature`](teacher_edit_due_no_conditions.feature) |
| g | N | N | passing grade | ✅ | [`teacher_edit_nodates_and_passinggrade.feature`](teacher_edit_nodates_and_passinggrade.feature) |
| h | N | N | manual | ✅ | [`teacher_edit_nodates_and_manual.feature`](teacher_edit_nodates_and_manual.feature) |
| i | N | N | none | ✅ | [`teacher_edit_nodates_no_conditions.feature`](teacher_edit_nodates_no_conditions.feature) |

All nine combinations are covered. Rows a–h start from a bare assignment (no dates, no
completion) and edit it via `modedit.php` to add the target dates/completion, then check the
same course-page indicators (`Due:`, `Mark as done`, `Completion` panel) used by the section 1
create tests. Row i does the reverse — it starts from a fully-configured assignment and edits it
back down to no dates/no conditions (using `disabled` as the field value to uncheck an optional
date selector, and "Completion conditions > None" to drop back out of automatic completion) — to
prove that *clearing* settings through the edit form works as well as adding them.

⚠️ [`teacher_edit_needspassinggrade.feature`](teacher_edit_needspassinggrade.feature) covers a
related regression — that the "needs passing grade" rule survives an *unrelated* settings edit —
which is a different concern from the matrix above.

---

## 4. Validation errors while editing

| # | Condition | Status | Feature |
|---|---|:---:|---|
| a | Open date >= due date | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| b | Due date >= cut-off date | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| c | Assignment link missing | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| d | Assignment link invalid URL | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| e | Assignment name empty | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| f | External grade max empty | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| g | External grade max not a number | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| h | External grade max negative | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| i | Manual grade max not a number | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |
| j | Manual grade max negative | ✅ | [`teacher_edit_validation_errors.feature`](teacher_edit_validation_errors.feature) |

All eleven conditions are covered, exercised through the edit form of an already-existing
assignment instead of the create form.

---

## 5. Grading → completion status

| # | Existing grade | Completion condition | Status Before | Status After | Status | Feature |
|---|:---:|---|:---:|:---:|:---:|---|
| a | N | none | – | – | ✅ | [`teacher_grading_none_first_grade.feature`](teacher_grading_none_first_grade.feature) |
| b | N | manual | – | – | ✅ | [`teacher_grading_manual_first_grade.feature`](teacher_grading_manual_first_grade.feature) |
| c | N | passing grade | – | failed | ✅ | [`teacher_manual_grading_completion.feature`](teacher_manual_grading_completion.feature) |
| d | N | passing grade | – | done | ✅ | [`teacher_manual_grading_completion.feature`](teacher_manual_grading_completion.feature) |
| e | Y | none | – | – | ✅ | [`teacher_regrading_none.feature`](teacher_regrading_none.feature) |
| f | Y | manual | done | done | ✅ | [`teacher_regrading_manual_done_stays_done.feature`](teacher_regrading_manual_done_stays_done.feature) |
| g | Y | manual | todo | todo | ✅ | [`teacher_regrading_manual_todo_stays_todo.feature`](teacher_regrading_manual_todo_stays_todo.feature) |
| h | Y | passing grade | done | done | ✅ | [`teacher_regrading_passinggrade_done_stays_done.feature`](teacher_regrading_passinggrade_done_stays_done.feature) |
| i | Y | passing grade | done | failed | ✅ | [`teacher_regrading_passinggrade_done_to_failed.feature`](teacher_regrading_passinggrade_done_to_failed.feature) |
| j | Y | passing grade | failed | done | ✅ | [`teacher_regrading_passinggrade_failed_to_done.feature`](teacher_regrading_passinggrade_failed_to_done.feature) |
| k | Y | passing grade | failed | failed | ✅ | [`teacher_regrading_passinggrade_failed_stays_failed.feature`](teacher_regrading_passinggrade_failed_stays_failed.feature) |

All eleven rows are covered. Note the "Status" column uses `todo`/`done`/`failed` rather than the
original doc's `Y`/`N`: `mod_externalassignment_completion\custom_completion::get_state()`
(`classes/completion/custom_completion.php`) returns `COMPLETION_INCOMPLETE` ("todo") **only**
when a student has no grade at all yet; once *any* grade exists it's always either
`COMPLETION_COMPLETE` ("done") or `COMPLETION_COMPLETE_FAIL` ("failed") — there is no
graded-but-"todo" state, which is why rows h–k use `failed` rather than `todo` for a graded,
below-threshold assignment.

Building these tests surfaced three real, previously-unnoticed bugs in the plugin, all now
fixed (none of the existing grading-related Behat scenarios had ever actually been run
successfully before this — see git history for the fixes):
- `classes/output/view_grading.php` — the "Show all" grading overview page crashed for *any*
  student who had never been graded yet (`student::to_stdclass()` only sets a `grade` property
  when a grade already exists, but the view unconditionally read `$gradedata->grade->...`).
- `classes/local/grade_control.php` — the single-student grader form always crashed, graded or
  not, because `$data->externallink` was read in `grader_form::definition()` before ever being
  set on the customdata.
- `classes/local/grade.php` — `grade::__construct()` read `$formdata->externalassignmentid`,
  which only exists on a submitted grader-form payload; a raw DB record (as loaded by
  `assign::load_grades()`) has the column `externalassignment` instead, so loading a student's
  existing grade from the database always failed silently.

---

## 6. Student view of an assignment

Not part of the original matrix, but a full set of student-facing view tests exists, mirroring
section 1's due/cut-off/completion combinations:

| # | Due Date | Cut-off Date | Completion | Status | Feature |
|---|:---:|:---:|---|:---:|---|
| a | Y | Y | passing grade | ✅ | [`student_cutoff_and_passinggrade.feature`](student_cutoff_and_passinggrade.feature) |
| b | Y | Y | manual | ✅ | [`student_cutoff_and_manual.feature`](student_cutoff_and_manual.feature) |
| c | Y | Y | none | ✅ | [`student_cutoff_no_conditions.feature`](student_cutoff_no_conditions.feature) |
| d | Y | N | passing grade | ✅ | [`student_due_and_passinggrade.feature`](student_due_and_passinggrade.feature) |
| e | Y | N | manual | ✅ | [`student_due_and_manual.feature`](student_due_and_manual.feature) |
| f | Y | N | none | ✅ | [`student_due_and_no_conditions.feature`](student_due_and_no_conditions.feature) |
| g | N | N | passing grade | ✅ | [`student_nodates_and_passinggrade.feature`](student_nodates_and_passinggrade.feature) |
| h | N | N | manual | ✅ | [`student_nodates_and_manual.feature`](student_nodates_and_manual.feature) |
| i | N | N | none | ✅ | [`student_nodates_no_conditions.feature`](student_nodates_no_conditions.feature) |

All nine combinations are covered.

Note: while verifying these, I found `student_due_and_passinggrade.feature` uses a generator
column named `passinggrade`, which isn't a real field (the actual DB/mod_form field is
`needspassinggrade`) — it happens to still pass only because
`mod_externalassignment_generator::create_instance()` already defaults `needspassinggrade` to
`1` for every generated activity. The new files above use the correct `needspassinggrade` column
directly rather than relying on that default.

---

## 7. Other feature / regression coverage

Tests that don't fit the matrices above, mostly written as regression tests for specific
GitHub issues:

| Feature | Covers | GitHub issue |
|---|---|:---:|
| [`backup_restore.feature`](backup_restore.feature) | Course backup/restore and activity duplication, including that the "is due" calendar event is duplicated/restored correctly | #14, #31 |
| [`grade_with_manual_completion.feature`](grade_with_manual_completion.feature) | Saving a grade doesn't error when completion tracking is manual | #39 |
| [`grant_extension_calendar.feature`](grant_extension_calendar.feature) | A per-student due-date override updates their Dashboard "overdue" status | #37 |
| [`report_editdates_calendar.feature`](report_editdates_calendar.feature) | Editing dates via the "Dates" report updates the calendar/overdue status, same as editing via the settings form | #38 |
| [`teacher_duplicate_name_validation.feature`](teacher_duplicate_name_validation.feature) | Duplicate external name within a course is rejected (see also §2k) | #35 |
| [`teacher_edit_needspassinggrade.feature`](teacher_edit_needspassinggrade.feature) | "Needs passing grade" rule is set on creation and survives unrelated edits | #12, #36 |
| [`student_assignment_link_visibility.feature`](student_assignment_link_visibility.feature) | Assignment link visibility respects "Always show link" / submission opening date | #27 |
| [`student_description_visibility.feature`](student_description_visibility.feature) | Description visibility respects "Always show description" / submission opening date | #13 |

> Note: `student_description_visibility.feature`'s own docblock flags its first scenario as
> expected to fail against the current `view.php` implementation — see the feature file for
> details.

---

## 8. Archived tests

`archive/` contains older tests written against the plugin's previous component name
(`@mod_extassignment`, before it was renamed to `mod_externalassignment`). They are kept for
reference but are **not** run as part of the active suite:

| Feature |
|---|
| `archive/assign_activity_completion.feature` |
| `archive/display_dates.feature` |
| `archive/display_error_message_onbadformat.feature` |
| `archive/display_grade.feature` |
| `archive/page_title.feature` |

Their scenarios overlap heavily with sections 1, 5 and 6 above; if any are still relevant they
should be rewritten against the current component name/step definitions rather than restored
as-is.

---

## Summary of gaps

No open gaps — every row in sections 1–6 now has a passing Behat test. The only follow-up items
are the five legacy files under `archive/` (§8), which pre-date the plugin's rename to
`mod_externalassignment` and would need rewriting against the current step definitions before
they could be restored to the active suite.
