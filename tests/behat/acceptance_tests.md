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
| a | Y | Y | passing grade | ❌ | — |
| b | Y | Y | manual | ❌ | — |
| c | Y | Y | none | ❌ | — |
| d | Y | N | passing grade | ❌ | — |
| e | Y | N | manual | ❌ | — |
| f | Y | N | none | ❌ | — |
| g | N | N | passing grade | ❌ | — |
| h | N | N | manual | ❌ | — |
| i | N | N | none | ❌ | — |

⚠️ [`teacher_edit_needspassinggrade.feature`](teacher_edit_needspassinggrade.feature) covers a
related edit scenario — that the "needs passing grade" completion rule survives an unrelated
settings edit — but it doesn't exercise the due/cut-off/completion matrix above, so the matrix
itself is still untested.

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
| a | N | none | – | – | ❌ | — |
| b | N | manual | – | – | ❌ | — |
| c | N | passing grade | – | todo | ✅ | [`teacher_manual_grading_completion.feature`](teacher_manual_grading_completion.feature) |
| d | N | passing grade | – | done | ✅ | [`teacher_manual_grading_completion.feature`](teacher_manual_grading_completion.feature) |
| e | Y | none | – | – | ❌ | — |
| f | Y | manual | done | done | ❌ | — |
| g | Y | manual | todo | todo | ❌ | — |
| h | Y | passing grade | done | done | ❌ | — |
| i | Y | passing grade | done | todo | ❌ | — |
| j | Y | passing grade | todo | done | ❌ | — |
| k | Y | passing grade | todo | todo | ❌ | — |

Rows c and d (grading a fresh — i.e. "no existing grade" — assignment above/below the passing
threshold) are covered. All the "re-grading an already-graded assignment" rows (e–k) are still
missing.

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
| g | N | N | passing grade | ❌ | — |
| h | N | N | manual | ❌ | — |
| i | N | N | none | ❌ | — |

The three "no dates at all" student-view scenarios (g–i) are missing.

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

- **Editing an existing assignment** (section 3) — the due/cut-off/completion matrix has zero
  direct coverage; only a narrow "needs passing grade survives an edit" regression test exists.
- **Re-grading** (section 5, rows e–k) — only "first grade ever" scenarios are covered; changing
  a grade that already exists, for every completion condition, is untested.
- **Student view without any dates** (section 6, rows g–i) — the "no due/cut-off date" case is
  untested from the student's perspective, even though it's covered for teacher creation (§1i).
